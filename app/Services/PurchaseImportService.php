<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseAllocation;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchaseImportService
{
    private const CHUNK_SIZE = 500;

    private const DATE_FORMATS = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'm-d-Y'];

    public function __construct(private ProductCreationService $productCreation)
    {
    }

    public function process(UploadedFile $file, ?int $userId): array
    {
        $summary = [
            'total_rows'             => 0,
            'products_created'       => 0,
            'existing_products_used' => 0,
            'categories_created'     => 0,
            'sub_categories_created' => 0,
            'purchases_created'      => 0,
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

        foreach ($groups as $group) {
            $summary['total_rows'] += count($group['rows']);
        }

        if ($summary['total_rows'] === 0) {
            throw new \RuntimeException('The uploaded Excel file does not contain any usable rows.');
        }

        $barcodes = array_values(array_unique(array_filter(array_map(fn ($g) => $g['barcode'], $groups))));
        $productsByBarcode = $this->productCreation->lookupProducts($barcodes);

        $seenSignatures = [];

        foreach ($groups as $group) {
            $this->processGroup($group, $productsByBarcode, $seenSignatures, $summary, $failures, $userId);
        }

        ActivityLogger::log(
            'Purchase',
            'import',
            null,
            null,
            $summary,
            "Purchase import: {$summary['purchases_created']} purchases created, {$summary['failed_rows']} failed"
        );

        return ['summary' => $summary, 'failures' => $failures];
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
            'suppliername'           => 'supplier_name',
            'variant'                => 'variant',
            'varient'                => 'variant',
            'variantvalue'           => 'variant_value',
            'varientvalue'           => 'variant_value',
            'purchasedate'           => 'purchase_date',
            'purchaseprice'          => 'purchase_price',
            'quantity'               => 'quantity',
            'purchasestatus'         => 'purchase_status',
            'paymentstatus'          => 'payment_status',
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
     * a blank-Product Name row is a continuation of the current group.
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
                    'first_row_num'      => $rowNum,
                    'category_name'      => $categoryName,
                    'sub_category_name'  => trim($row['sub_category'] ?? ''),
                    'product_name'       => $productName,
                    'barcode'            => trim($row['barcode'] ?? ''),
                    'product_code'       => trim($row['product_code'] ?? ''),
                    'purchase_multiplier' => trim($row['purchase_multiplier'] ?? ''),
                    'sale_multiplier'    => trim($row['sale_multiplier'] ?? ''),
                    'mrp_multiplier'     => trim($row['mrp_multiplier'] ?? ''),
                    'pair_product'       => $this->toBool($row['pair_product'] ?? ''),
                    'product_type'       => strtolower(trim($row['product_type'] ?? 'normal')) === 'variable' ? 'variable' : 'normal',
                    'dimensions'         => [],
                    'rows'               => [],
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

            $current['rows'][] = [
                'row_num'         => $rowNum,
                'supplier_name'   => trim($row['supplier_name'] ?? ''),
                'variant'         => trim($row['variant'] ?? ''),
                'variant_value'   => trim($row['variant_value'] ?? ''),
                'purchase_date'   => trim($row['purchase_date'] ?? ''),
                'purchase_price'  => trim($row['purchase_price'] ?? ''),
                'quantity'        => trim($row['quantity'] ?? ''),
                'purchase_status' => trim($row['purchase_status'] ?? ''),
                'payment_status'  => trim($row['payment_status'] ?? ''),
            ];
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

    private function processGroup(array $group, array &$productsByBarcode, array &$seenSignatures, array &$summary, array &$failures, ?int $userId): void
    {
        $barcode = $group['barcode'];

        if ($barcode === '') {
            foreach ($group['rows'] as $row) {
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, 'Missing Barcode');
            }

            return;
        }

        $product = $productsByBarcode[$barcode] ?? null;

        if ($product) {
            $summary['existing_products_used']++;
        } else {
            try {
                $product = $this->productCreation->create($group, $summary, $userId);
                $productsByBarcode[$barcode] = $product;
                $summary['products_created']++;
            } catch (\Throwable $e) {
                Log::error('Purchase import: product creation failed for barcode ' . $barcode . ': ' . $e->getMessage());
                foreach ($group['rows'] as $row) {
                    $summary['failed_rows']++;
                    $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, $e->getMessage());
                }

                return;
            }
        }

        $variants = $product->type === 'variable'
            ? ProductVariant::where('product_id', $product->id)->with('attributeValue')->get()
            : collect();

        $lastSupplier = null;

        foreach ($group['rows'] as $row) {
            try {
                $this->processRow($group, $product, $variants, $row, $lastSupplier, $seenSignatures, $summary, $userId);
            } catch (RowSkipException $e) {
                $summary['skipped_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, $e->getMessage(), 'Skipped');
            } catch (RowFailureException $e) {
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, $e->getMessage());
            } catch (\Throwable $e) {
                Log::error('Purchase import: row ' . $row['row_num'] . ' failed: ' . $e->getMessage());
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, 'Unexpected Error');
            }
        }
    }

    private function processRow(array $group, Product $product, $variants, array $row, ?string &$lastSupplier, array &$seenSignatures, array &$summary, ?int $userId): void
    {
        $supplierName = $row['supplier_name'] !== '' ? $row['supplier_name'] : $lastSupplier;

        if (empty($supplierName)) {
            throw new RowFailureException('Missing Supplier');
        }

        $lastSupplier = $supplierName;

        $quantity = $row['quantity'];
        if ($quantity === '' || !is_numeric($quantity) || (int) $quantity <= 0) {
            throw new RowFailureException('Invalid Quantity');
        }
        $quantity = (int) $quantity;

        $price = $row['purchase_price'];
        if ($price === '' || !is_numeric($price) || (float) $price <= 0) {
            throw new RowFailureException('Invalid Price');
        }
        $price = (float) $price;

        $purchaseDate = $this->parseDate($row['purchase_date']);
        if (!$purchaseDate) {
            throw new RowFailureException('Invalid Purchase Date');
        }

        $productVariantId = null;
        if ($product->type === 'variable') {
            if ($variants->isEmpty()) {
                throw new RowFailureException('Invalid Variant Data');
            }

            $needle = strtolower($row['variant_value']);
            if ($needle === '') {
                throw new RowFailureException('Invalid Variant Data');
            }

            // For products with 2+ variant dimensions, the stored value is the full
            // combined string (e.g. "2.2 - Gold") — the sheet must match it exactly.
            $exact = $variants->filter(fn ($v) => strtolower(trim($v->attributeValue->value)) === $needle);

            if ($exact->count() !== 1) {
                throw new RowFailureException('Invalid Variant Data');
            }

            $productVariantId = $exact->first()->id;
        }

        $signature = implode('|', [
            $group['barcode'], $productVariantId, $supplierName,
            $purchaseDate->format('Y-m-d'), $price, $quantity,
            $row['purchase_status'], $row['payment_status'],
        ]);
        if (isset($seenSignatures[$signature])) {
            throw new RowSkipException('Duplicate Row');
        }
        $seenSignatures[$signature] = true;

        $status = $this->mapPurchaseStatus($row['purchase_status']);
        $paymentStatus = $this->mapPaymentStatus($row['payment_status']);

        DB::transaction(function () use ($product, $productVariantId, $supplierName, $purchaseDate, $price, $quantity, $status, $paymentStatus, $userId, &$summary) {
            $supplier = Supplier::firstOrCreate(
                ['name' => $supplierName],
                ['status' => Supplier::STATUS_ACTIVE, 'created_by' => $userId]
            );

            $total = $quantity * $price;

            $purchase = Purchase::create([
                'supplier_id'    => $supplier->id,
                'invoice_no'     => generate_invoice_no('PUR', Purchase::class),
                'total_amount'   => $total,
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'created_by'     => $userId,
            ]);
            $purchase->created_at = $purchaseDate;
            $purchase->updated_at = $purchaseDate;
            $purchase->save();

            $item = PurchaseItem::create([
                'purchase_id'        => $purchase->id,
                'product_id'         => $product->id,
                'product_variant_id' => $productVariantId,
                'purchase_price'     => $price,
                'quantity'           => $quantity,
                'total'              => $total,
            ]);

            PurchaseAllocation::create([
                'purchase_item_id' => $item->id,
                'location_id'      => $this->defaultPurchaseLocation()->id,
                'quantity'          => $quantity,
            ]);

            if ($status === Purchase::STATUS_APPROVE) {
                PurchaseStockService::approve($purchase);
            }

            $summary['purchases_created']++;
        });
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->startOfDay();
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapPurchaseStatus(string $value): int
    {
        return match (strtolower($value)) {
            'pending' => Purchase::STATUS_PENDING,
            'decline' => Purchase::STATUS_DECLINE,
            default   => Purchase::STATUS_APPROVE,
        };
    }

    private function mapPaymentStatus(string $value): int
    {
        return match (strtolower($value)) {
            'paid'  => Purchase::PAYMENT_STATUS_PAID,
            default => Purchase::PAYMENT_STATUS_PENDING,
        };
    }

    private function defaultPurchaseLocation(): Location
    {
        $location = Location::where('is_default', true)->first() ?? Location::first();

        if (!$location) {
            throw new \RuntimeException('Please create a default location before importing purchases.');
        }

        return $location;
    }

    private function failureRow(int $rowNum, string $productName, string $barcode, string $reason, string $status = 'Failed'): array
    {
        return [
            'row'      => $rowNum,
            'product'  => $productName,
            'barcode'  => $barcode,
            'status'   => $status,
            'reason'   => $reason,
        ];
    }
}

class RowFailureException extends \Exception
{
}

class RowSkipException extends \Exception
{
}
