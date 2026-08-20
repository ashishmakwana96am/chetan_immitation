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
            'purchaseprice'          => 'purchase_price',
            'saleprice'              => 'sale_price',
            'mrp'                    => 'mrp',
            'purchasemultiplier'     => 'purchase_multiplier',
            'salemultiplier'         => 'sale_multiplier',
            'mrpmultiplier'          => 'mrp_multiplier',
            'pairproduct'            => 'pair_product',
            'pairsizes'              => 'pair_sizes',
            'pairsize'               => 'pair_sizes',
            'customsizes'            => 'pair_sizes',
            'sizes'                  => 'pair_sizes',
            'producttype'            => 'product_type',
            'productvariant'         => 'product_variant',
            'productvarient'         => 'product_variant',
            'productvariantvalue'    => 'product_variant_value',
            'productvarientvalue'    => 'product_variant_value',
            'barcode'                => 'barcode',
            'category'               => 'category',
            'collection'             => 'collection',
            'collectionname'         => 'collection',
            'collectionshortname'    => 'collection',
            'collectionshort'        => 'collection',
            'collectioncode'         => 'collection',
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
                    'collection'          => trim($row['collection'] ?? ''),
                    'product_name'        => $productName,
                    'barcode'             => trim($row['barcode'] ?? ''),
                    'product_code'        => trim($row['product_code'] ?? ''),
                    'purchase_price'      => trim($row['purchase_price'] ?? ''),
                    'sale_price'          => trim($row['sale_price'] ?? ''),
                    'mrp'                 => trim($row['mrp'] ?? ''),
                    'purchase_multiplier' => trim($row['purchase_multiplier'] ?? ''),
                    'sale_multiplier'     => trim($row['sale_multiplier'] ?? ''),
                    'mrp_multiplier'      => trim($row['mrp_multiplier'] ?? ''),
                    'pair_product'        => $this->toBool($row['pair_product'] ?? ''),
                    'pair_sizes'          => trim($row['pair_sizes'] ?? ''),
                    'product_type'        => in_array(strtolower(trim($row['product_type'] ?? 'n')), ['variable', 'v']) ? 'variable' : 'normal',
                    'dimensions'          => [],
                    'dimension_error'     => null,
                ];
            }

            if ($current === null) {
                $skipped++;
                continue;
            }

            $error = $this->applyVariantDimensions($current, $row);
            if ($error !== null && $current['dimension_error'] === null) {
                $current['dimension_error'] = $error;
            }
        }

        if ($current !== null) {
            $groups[] = $current;
        }

        return [$groups, $skipped];
    }

    private function toBool($value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 't'], true);
    }

    private function applyVariantDimensions(array &$group, array $row): ?string
    {
        $variantNamesRaw = trim($row['product_variant'] ?? '');
        $variantValuesRaw = trim((string) ($row['product_variant_value'] ?? ''));

        if ($variantNamesRaw === '' && $variantValuesRaw === '') {
            return null;
        }

        $variantNames = array_values(array_filter(array_map('trim', explode(',', $variantNamesRaw)), fn ($v) => $v !== ''));
        $valueGroups = array_map('trim', explode('|', $variantValuesRaw));

        if (count($variantNames) !== count($valueGroups)) {
            return 'Product Variant / Product Variant Value count mismatch';
        }

        foreach ($variantNames as $i => $name) {
            $values = array_values(array_filter(array_map('trim', explode(',', $valueGroups[$i])), fn ($v) => $v !== ''));
            if (empty($values)) {
                return 'Product Variant / Product Variant Value count mismatch';
            }

            $key = strtolower($name);
            if (!isset($group['dimensions'][$key])) {
                $group['dimensions'][$key] = ['name' => $name, 'values' => []];
            }
            $group['dimensions'][$key]['values'] = array_values(array_unique(
                array_merge($group['dimensions'][$key]['values'], $values)
            ));
        }

        return null;
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
            try {
                DB::transaction(function () use ($product, $group, &$summary, $userId) {
                    $this->productCreation->restoreTrashedProduct($product);
                    $this->productCreation->updateExistingProduct($product, $group, $summary, $userId);
                });
                $summary['existing_products_used']++;
                $history[] = [
                    'barcode' => $barcode,
                    'product' => $group['product_name'],
                    'status'  => 'Success',
                    'reason'  => 'Updated',
                    'details' => "Product with barcode {$barcode} already exists. Updated details."
                ];
            } catch (\Throwable $e) {
                Log::error('Product import: product update failed for barcode ' . $barcode . ': ' . $e->getMessage());
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($group['first_row_num'], $group['product_name'], $barcode, $e->getMessage());
                $history[] = [
                    'barcode' => $barcode,
                    'product' => $group['product_name'],
                    'status'  => 'Failed',
                    'reason'  => 'Update Failed',
                    'details' => $e->getMessage()
                ];
            }

            return;
        }

        if ($group['dimension_error'] !== null) {
            $summary['failed_rows']++;
            $failures[] = $this->failureRow($group['first_row_num'], $group['product_name'], $barcode, $group['dimension_error']);
            $history[] = [
                'barcode' => $barcode,
                'product' => $group['product_name'],
                'status'  => 'Failed',
                'reason'  => $group['dimension_error'],
                'details' => "Row {$group['first_row_num']}: {$group['dimension_error']}."
            ];

            return;
        }

        try {
            DB::transaction(function () use ($group, &$productsByBarcode, &$summary, $userId, $barcode) {
                $product = $this->productCreation->create($group, $summary, $userId);
                $productsByBarcode[$barcode] = $product;
            });
            $summary['products_created']++;

            $history[] = [
                'barcode' => $barcode,
                'product' => $group['product_name'],
                'status'  => 'Success',
                'reason'  => 'Created',
                'details' => "Successfully created new product."
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
