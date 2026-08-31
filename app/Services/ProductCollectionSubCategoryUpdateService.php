<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductCollectionSubCategoryUpdateService
{
    public function processFile(string|UploadedFile $file, ?int $userId = null, bool $isDryRun = false): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Excel file not found at path: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($sheetData) || count($sheetData) < 2) {
            throw new \RuntimeException("The uploaded Excel file is empty or contains no data rows.");
        }

        // Dynamically find the header row
        $header = null;
        $dataRows = [];

        foreach ($sheetData as $i => $row) {
            $normalizedCandidate = $this->normalizeHeader($row);

            if ($header === null) {
                if (
                    in_array('product_name', $normalizedCandidate, true) ||
                    in_array('barcode', $normalizedCandidate, true) ||
                    in_array('sub_category', $normalizedCandidate, true)
                ) {
                    $header = $normalizedCandidate;
                }
                continue;
            }

            $dataRows[] = [
                'excel_row_num' => $i + 1,
                'row'           => $row,
            ];
        }

        if ($header === null) {
            throw new \RuntimeException("Could not find a valid header row containing 'Product Name', 'Barcode', or 'Sub Category' in the Excel sheet.");
        }

        $summary = [
            'total_rows'             => 0,
            'products_updated'       => 0,
            'categories_created'     => 0,
            'sub_categories_created' => 0,
            'collections_created'    => 0,
            'failed_rows'            => 0,
            'skipped_rows'           => 0,
            'is_dry_run'             => $isDryRun,
        ];
        $details = [];

        if ($isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($dataRows as $item) {
                $rowNum = $item['excel_row_num'];
                $row = $item['row'];

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), '');
                } elseif (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                $rowData = array_combine($header, array_map(fn ($v) => trim((string) $v), $row));

                $productName = $rowData['product_name'] ?? '';
                $barcode = $rowData['barcode'] ?? '';
                $subCategoryName = $rowData['sub_category'] ?? '';
                $collectionStr = $rowData['collection'] ?? '';

                if ($productName === '' && $barcode === '' && $subCategoryName === '' && $collectionStr === '') {
                    $summary['skipped_rows']++;
                    continue;
                }

                $summary['total_rows']++;

                try {
                    $actionCallback = function () use (
                        $productName,
                        $barcode,
                        $subCategoryName,
                        $collectionStr,
                        $userId,
                        &$summary,
                        &$details,
                        $rowNum,
                        $isDryRun
                    ) {
                        $product = null;

                        if ($barcode !== '') {
                            $product = Product::withTrashed()->where('barcode', $barcode)->first();
                            if (!$product) {
                                // Try with leading zeros stripped or padded if barcode is numeric
                                $product = Product::withTrashed()->whereRaw('TRIM(barcode) = ?', [$barcode])->first();
                            }
                        }

                        if (!$product && $productName !== '') {
                            $product = Product::withTrashed()
                                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($productName)])
                                ->first();
                        }

                        if (!$product) {
                            $summary['failed_rows']++;
                            $details[] = [
                                'row'          => $rowNum,
                                'barcode'      => $barcode ?: 'N/A',
                                'product'      => $productName ?: 'N/A',
                                'status'       => 'Failed',
                                'sub_category' => $subCategoryName ?: 'N/A',
                                'collection'   => $collectionStr ?: 'N/A',
                                'reason'       => 'Product not found in database',
                            ];
                            return;
                        }

                        if ($product->trashed()) {
                            $product->restore();
                        }

                        $assignedCategoryName = '';
                        $assignedSubCategoryName = '';

                        // 1. Resolve Sub Category & Category
                        if ($subCategoryName !== '' && $subCategoryName !== '-') {
                            $subCategory = SubCategory::withTrashed()
                                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($subCategoryName)])
                                ->first();

                            if ($subCategory) {
                                if ($subCategory->trashed()) {
                                    $subCategory->restore();
                                }
                                $category = Category::withTrashed()->find($subCategory->category_id);
                                if ($category && $category->trashed()) {
                                    $category->restore();
                                }
                            } else {
                                $category = null;
                                if ($product->category_id) {
                                    $category = Category::withTrashed()->find($product->category_id);
                                }
                                if (!$category) {
                                    $category = Category::withTrashed()->firstOrCreate(
                                        ['name' => $subCategoryName],
                                        ['slug' => generate_slug(Category::class, $subCategoryName), 'status' => 1, 'created_by' => $userId]
                                    );
                                    if ($category->wasRecentlyCreated) {
                                        $summary['categories_created']++;
                                    }
                                }
                                if ($category->trashed()) {
                                    $category->restore();
                                }

                                $subCategory = SubCategory::withTrashed()->firstOrCreate(
                                    ['category_id' => $category->id, 'name' => $subCategoryName],
                                    ['slug' => generate_slug(SubCategory::class, $subCategoryName), 'status' => 1, 'created_by' => $userId]
                                );
                                if ($subCategory->wasRecentlyCreated) {
                                    $summary['sub_categories_created']++;
                                }
                            }

                            $product->sub_category_id = $subCategory->id;
                            $product->category_id = $subCategory->category_id;

                            $assignedSubCategoryName = $subCategory->name;
                            $assignedCategoryName = $category ? $category->name : '';
                        }

                        // 2. Resolve Collection
                        $collectionIds = [];
                        $assignedCollectionNames = [];
                        if ($collectionStr !== '' && $collectionStr !== '-') {
                            $shortNames = array_values(array_filter(array_map('trim', explode(',', $collectionStr))));
                            foreach ($shortNames as $shortName) {
                                if ($shortName === '' || $shortName === '-') continue;

                                $collection = Collection::withTrashed()
                                    ->where(function ($q) use ($shortName) {
                                        $q->whereRaw('LOWER(TRIM(short_name)) = ?', [mb_strtolower($shortName)])
                                          ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($shortName)]);
                                    })
                                    ->first();

                                if (!$collection) {
                                    $collection = Collection::create([
                                        'short_name' => $shortName,
                                        'name'       => $shortName,
                                        'status'     => 1,
                                        'created_by' => $userId,
                                    ]);
                                    $summary['collections_created']++;
                                } elseif ($collection->trashed()) {
                                    $collection->restore();
                                }
                                $collectionIds[] = $collection->id;
                                $assignedCollectionNames[] = $collection->short_name ?: $collection->name;
                            }
                        }

                        if (!empty($collectionIds)) {
                            $product->collection_id = $collectionIds[0];
                            $product->collections()->sync($collectionIds);
                        } else {
                            $product->collection_id = null;
                            $product->collections()->sync([]);
                        }

                        $product->save();

                        $summary['products_updated']++;
                        $details[] = [
                            'row'          => $rowNum,
                            'barcode'      => $product->barcode ?: ($barcode ?: 'N/A'),
                            'product'      => $product->name,
                            'status'       => 'Success',
                            'sub_category' => $assignedSubCategoryName ?: 'Unchanged',
                            'category'     => $assignedCategoryName ?: 'Unchanged',
                            'collection'   => !empty($assignedCollectionNames) ? implode(', ', $assignedCollectionNames) : 'NULL (Cleared)',
                            'reason'       => $isDryRun ? '[DRY-RUN] Valid product, ready to update' : 'Updated SubCategory & Collection',
                        ];
                    };

                    if ($isDryRun) {
                        $actionCallback();
                    } else {
                        DB::transaction($actionCallback);
                    }
                } catch (\Throwable $e) {
                    Log::error("Product Collection/SubCategory Update failed for row {$rowNum}: " . $e->getMessage());
                    $summary['failed_rows']++;
                    $details[] = [
                        'row'          => $rowNum,
                        'barcode'      => $barcode ?: 'N/A',
                        'product'      => $productName ?: 'N/A',
                        'status'       => 'Failed',
                        'sub_category' => $subCategoryName ?: 'N/A',
                        'collection'   => $collectionStr ?: 'N/A',
                        'reason'       => $e->getMessage(),
                    ];
                }
            }
        } finally {
            if ($isDryRun) {
                DB::rollBack();
            }
        }

        return [
            'summary' => $summary,
            'details' => $details,
        ];
    }

    private function normalizeHeader(array $rawHeader): array
    {
        $map = [
            'subcategory'         => 'sub_category',
            'sub_category'        => 'sub_category',
            'productname'         => 'product_name',
            'product_name'        => 'product_name',
            'barcode'             => 'barcode',
            'collection'          => 'collection',
            'collectio'           => 'collection',
            'collectionname'      => 'collection',
            'collectionshortname' => 'collection',
        ];

        $normalized = [];
        foreach ($rawHeader as $col) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $col)));
            $normalized[] = $map[$key] ?? $key;
        }

        return $normalized;
    }
}
