<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Product-only bulk import: reuses ProductCreationService for the exact same
 * Category/SubCategory/Product/Pair/Variant logic as Purchase Import, but
 * never creates a Purchase — this module only ever produces Products.
 */
class ProductImportService
{
    public function __construct(private ProductCreationService $productCreation)
    {
    }

    public function process(UploadedFile $file, ?int $userId): array
    {
        $summary = [
            'total_groups'           => 0,
            'products_created'       => 0,
            'existing_products_used' => 0,
            'categories_created'     => 0,
            'sub_categories_created' => 0,
            'failed_rows'            => 0,
            'skipped_rows'           => 0,
        ];
        $failures = [];

        $rows = $this->parseWorkbook($file);

        if (empty($rows)) {
            throw new \RuntimeException('The uploaded Excel file is empty or contains no data rows.');
        }

        [$groups, $skippedRows] = $this->groupRows($rows);
        $summary['skipped_rows'] += $skippedRows;
        $summary['total_groups'] = count($groups);

        if ($summary['total_groups'] === 0) {
            throw new \RuntimeException('The uploaded Excel file does not contain any usable rows.');
        }

        $barcodes = array_values(array_unique(array_filter(array_map(fn ($g) => $g['barcode'], $groups))));
        $productsByBarcode = $this->productCreation->lookupProducts($barcodes);

        $history = [];
        foreach ($groups as $group) {
            $this->processGroup($group, $productsByBarcode, $summary, $failures, $userId, $history);
        }

        ActivityLogger::log(
            'Product',
            'import',
            null,
            null,
            $summary,
            "Product import: {$summary['products_created']} created, {$summary['existing_products_used']} reused, {$summary['failed_rows']} failed"
        );

        return ['summary' => $summary, 'failures' => $failures, 'history' => $history];
    }

    private function parseWorkbook(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $header = null;
        $rows = [];

        foreach ($sheetData as $row) {
            if ($header === null) {
                $header = $this->normalizeHeader($row);
                continue;
            }

            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            } elseif (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $rows[] = array_combine($header, array_map(fn ($v) => trim((string) $v), $row));
        }

        return $rows;
    }

    private function normalizeHeader(array $rawHeader): array
    {
        $map = [
            'subcategory'            => 'sub_category',
            'productname'            => 'product_name',
            'productcode'            => 'product_code',
            'purchasemultiplier'     => 'purchase_multiplier',
            'salemultiplier'         => 'sale_multiplier',
            'mrpmultiplier'          => 'mrp_multiplier',
            'pairproduct'            => 'pair_product',
            'producttype'            => 'product_type',
            'productvariant'         => 'product_variant',
            'productvarient'         => 'product_variant',
            'productvariantvalue'    => 'product_variant_value',
            'productvarientvalue'    => 'product_variant_value',
            'barcode'                => 'barcode',
            'category'               => 'category',
        ];

        $normalized = [];
        foreach ($rawHeader as $col) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $col)));
            $normalized[] = $map[$key] ?? $key;
        }

        return $normalized;
    }

    /**
     * Group rows by product: a row with a Product Name starts a new group;
     * a blank-Product Name row only ever contributes an extra variant dimension.
     */
    private function groupRows(array $rows): array
    {
        $groups = [];
        $current = null;
        $skipped = 0;
        $lastCategoryName = '';

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;

            $isBlank = true;
            foreach ($row as $v) {
                if (trim((string) $v) !== '') {
                    $isBlank = false;
                    break;
                }
            }

            if ($isBlank) {
                $skipped++;
                continue;
            }

            // Merged Category cells only carry a value on their first row —
            // every row underneath the merge reads blank, so inherit forward.
            $categoryName = trim($row['category'] ?? '');
            if ($categoryName !== '') {
                $lastCategoryName = $categoryName;
            } else {
                $categoryName = $lastCategoryName;
            }

            $productName = trim($row['product_name'] ?? '');

            if ($productName !== '') {
                if ($current !== null) {
                    $groups[] = $current;
                }

                $current = [
                    'first_row_num'       => $rowNum,
                    'category_name'       => $categoryName,
                    'sub_category_name'   => trim($row['sub_category'] ?? ''),
                    'product_name'        => $productName,
                    'barcode'             => trim($row['barcode'] ?? ''),
                    'product_code'        => trim($row['product_code'] ?? ''),
                    'purchase_multiplier' => trim($row['purchase_multiplier'] ?? ''),
                    'sale_multiplier'     => trim($row['sale_multiplier'] ?? ''),
                    'mrp_multiplier'      => trim($row['mrp_multiplier'] ?? ''),
                    'pair_product'        => $this->toBool($row['pair_product'] ?? ''),
                    'product_type'        => strtolower(trim($row['product_type'] ?? 'normal')) === 'variable' ? 'variable' : 'normal',
                    'dimensions'          => [],
                ];
            }

            if ($current === null) {
                // A continuation row with no product context yet — nothing to attach it to.
                $skipped++;
                continue;
            }

            $variantName = trim($row['product_variant'] ?? '');
            $variantValues = array_values(array_filter(array_map('trim', explode(',', (string) ($row['product_variant_value'] ?? '')))));
            if ($variantName !== '' && !empty($variantValues)) {
                $key = strtolower($variantName);
                if (!isset($current['dimensions'][$key])) {
                    $current['dimensions'][$key] = ['name' => $variantName, 'values' => []];
                }
                $current['dimensions'][$key]['values'] = array_values(array_unique(
                    array_merge($current['dimensions'][$key]['values'], $variantValues)
                ));
            }
        }

        if ($current !== null) {
            $groups[] = $current;
        }

        return [$groups, $skipped];
    }

    private function toBool($value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['true', '1', 'yes'], true);
    }

    private function processGroup(array $group, array &$productsByBarcode, array &$summary, array &$failures, ?int $userId, array &$history): void
    {
        $barcode = $group['barcode'];

        if ($barcode === '') {
            $summary['failed_rows']++;
            $failures[] = $this->failureRow($group['first_row_num'], $group['product_name'], $barcode, 'Missing Barcode');
            $history[] = [
                'barcode' => 'N/A',
                'product' => $group['product_name'],
                'status'  => 'Failed',
                'reason'  => 'Missing Barcode',
                'details' => "Row {$group['first_row_num']}: Missing barcode value."
            ];

            return;
        }

        if (isset($productsByBarcode[$barcode])) {
            $product = $productsByBarcode[$barcode];
            if ($product->trashed()) {
                $product->restore();
                $product->variants()->onlyTrashed()->restore();
                $product->images()->onlyTrashed()->restore();
                $product->inventories()->onlyTrashed()->restore();
            }
            // Product already exists — reuse it, never create a duplicate.
            $summary['existing_products_used']++;
            $history[] = [
                'barcode' => $barcode,
                'product' => $group['product_name'],
                'status'  => 'Success',
                'reason'  => 'Reused',
                'details' => "Product with barcode {$barcode} already exists. Reused existing."
            ];

            return;
        }

        $skippedDimensions = [];
        $firstDimensionName = '';
        if (count($group['dimensions']) > 1) {
            $dimensionKeys = array_keys($group['dimensions']);
            $firstKey = $dimensionKeys[0];
            $firstDimensionName = $group['dimensions'][$firstKey]['name'];

            for ($i = 1; $i < count($dimensionKeys); $i++) {
                $skippedDimensions[] = $group['dimensions'][$dimensionKeys[$i]]['name'];
            }

            $group['dimensions'] = [
                $firstKey => $group['dimensions'][$firstKey]
            ];
        }

        try {
            DB::transaction(function () use ($group, &$productsByBarcode, &$summary, $userId, $barcode) {
                $product = $this->productCreation->create($group, $summary, $userId);
                $productsByBarcode[$barcode] = $product;
            });
            $summary['products_created']++;

            $details = "Successfully created new product.";
            $status = 'Success';
            $reason = 'Created';
            if (!empty($skippedDimensions)) {
                $status = 'Warning';
                $reason = 'Created (Variant Skipped)';
                $details .= " Skipped variant dimensions: " . implode(', ', $skippedDimensions) . " (only first dimension \"{$firstDimensionName}\" was added).";
            }

            $history[] = [
                'barcode' => $barcode,
                'product' => $group['product_name'],
                'status'  => $status,
                'reason'  => $reason,
                'details' => $details
            ];
        } catch (\Throwable $e) {
            Log::error('Product import: product creation failed for barcode ' . $barcode . ': ' . $e->getMessage());
            $summary['failed_rows']++;
            $failures[] = $this->failureRow($group['first_row_num'], $group['product_name'], $barcode, $e->getMessage());
            $history[] = [
                'barcode' => $barcode,
                'product' => $group['product_name'],
                'status'  => 'Failed',
                'reason'  => 'Failed',
                'details' => $e->getMessage()
            ];
        }
    }

    private function failureRow(int $rowNum, string $productName, string $barcode, string $reason): array
    {
        return [
            'row'     => $rowNum,
            'product' => $productName,
            'barcode' => $barcode,
            'status'  => 'Failed',
            'reason'  => $reason,
        ];
    }
}
