<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseAllocation;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
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
        $validRows = [];

        $history = [];
        foreach ($groups as $group) {
            $this->processGroup($group, $productsByBarcode, $seenSignatures, $summary, $failures, $userId, $history, $validRows);
        }

        if (!empty($validRows)) {
            $groupedBySupplier = [];
            foreach ($validRows as $vr) {
                $key = strtolower(trim($vr['supplier_name']));
                if (!isset($groupedBySupplier[$key])) {
                    $groupedBySupplier[$key] = [
                        'supplier_name' => $vr['supplier_name'],
                        'items'         => [],
                    ];
                }
                $groupedBySupplier[$key]['items'][] = $vr;
            }

            $purchaseDate = now()->startOfDay();

            foreach ($groupedBySupplier as $supplierGroup) {
                DB::transaction(function () use ($supplierGroup, $purchaseDate, $userId, &$summary) {
                    $supplierName = $supplierGroup['supplier_name'];
                    $items = $supplierGroup['items'];

                    $supplier = Supplier::firstOrCreate(
                        ['name' => $supplierName],
                        ['status' => Supplier::STATUS_ACTIVE, 'created_by' => $userId]
                    );

                    $totalAmount = 0.0;
                    $paidAmount = 0.0;

                    foreach ($items as $item) {
                        $totalAmount += $item['total'];
                        $paidAmount += $item['paid_amount'];
                    }

                    $totalAmount = round($totalAmount, 2);
                    $paidAmount = round($paidAmount, 2);

                    $status = Purchase::STATUS_PENDING;
                    foreach ($items as $item) {
                        if ($item['status'] === Purchase::STATUS_APPROVE) {
                            $status = Purchase::STATUS_APPROVE;
                            break;
                        }
                    }

                    $paymentStatus = Purchase::PAYMENT_STATUS_PENDING;
                    if ($paidAmount >= $totalAmount) {
                        $paidAmount = $totalAmount;
                        $paymentStatus = Purchase::PAYMENT_STATUS_PAID;
                    } elseif ($paidAmount > 0) {
                        $paymentStatus = Purchase::PAYMENT_STATUS_PARTIAL;
                    }

                    $purchase = Purchase::create([
                        'supplier_id'    => $supplier->id,
                        'invoice_no'     => generate_invoice_no('PS', Purchase::class),
                        'is_gst'         => false,
                        'tax_amount'     => 0.00,
                        'total_amount'   => $totalAmount,
                        'status'         => $status,
                        'payment_status' => $paymentStatus,
                        'paid_amount'    => $paidAmount,
                        'created_by'     => $userId,
                    ]);
                    $purchase->created_at = $purchaseDate;
                    $purchase->updated_at = $purchaseDate;
                    $purchase->save();

                    if ($paidAmount > 0) {
                        PurchasePayment::create([
                            'purchase_id' => $purchase->id,
                            'amount'      => $paidAmount,
                            'created_by'  => $userId,
                        ]);
                    }

                    foreach ($items as $itemData) {
                        $item = PurchaseItem::create([
                            'purchase_id'        => $purchase->id,
                            'product_id'         => $itemData['product']->id,
                            'product_variant_id' => $itemData['product_variant_id'],
                            'purchase_price'     => $itemData['price'],
                            'quantity'           => $itemData['quantity'],
                            'total'              => $itemData['total'],
                        ]);

                        PurchaseAllocation::create([
                            'purchase_item_id' => $item->id,
                            'location_id'      => $this->defaultPurchaseLocation()->id,
                            'quantity'          => $itemData['quantity'],
                        ]);
                    }

                    if ($status === Purchase::STATUS_APPROVE) {
                        PurchaseStockService::approve($purchase);
                    }

                    $summary['purchases_created']++;
                });
            }
        }

        ActivityLogger::log(
            'Purchase',
            'import',
            null,
            null,
            $summary,
            "Purchase import: {$summary['purchases_created']} purchases created, {$summary['failed_rows']} failed"
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
            'suppliername'           => 'supplier_name',
            'variant'                => 'variant',
            'varient'                => 'variant',
            'variantvalue'           => 'variant_value',
            'varientvalue'           => 'variant_value',
            'purchasedate'           => 'purchase_date',
            'quantity'               => 'quantity',
            'purchasestatus'         => 'purchase_status',
            'paymentstatus'          => 'payment_status',
            'paidamount'             => 'paid_amount',
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
                    'product_type'       => in_array(strtolower(trim($row['product_type'] ?? 'n')), ['variable', 'v']) ? 'variable' : 'normal',
                    'rows'               => [],
                ];
            }

            if ($current === null) {
                $skipped++;
                continue;
            }

            $current['rows'][] = [
                'row_num'         => $rowNum,
                'supplier_name'   => trim($row['supplier_name'] ?? ''),
                'variant'         => trim($row['variant'] ?? ''),
                'variant_value'   => trim($row['variant_value'] ?? ''),
                'quantity'        => trim($row['quantity'] ?? ''),
                'purchase_status' => trim($row['purchase_status'] ?? ''),
                'payment_status'  => trim($row['payment_status'] ?? ''),
                'paid_amount'     => trim($row['paid_amount'] ?? ''),
            ];
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

    private function processGroup(array $group, array &$productsByBarcode, array &$seenSignatures, array &$summary, array &$failures, ?int $userId, array &$history, array &$validRows): void
    {
        $barcode = $group['barcode'];

        if ($barcode === '') {
            foreach ($group['rows'] as $row) {
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, 'Missing Barcode');
            }
            $history[] = [
                'barcode' => 'N/A',
                'product' => $group['product_name'],
                'status'  => 'Failed',
                'reason'  => 'Missing Barcode',
                'details' => "Group has no barcode. " . count($group['rows']) . " rows failed."
            ];

            return;
        }

        $product = $productsByBarcode[$barcode] ?? null;

        if ($product) {
            try {
                DB::transaction(function () use ($product, $group, &$summary, $userId) {
                    $this->productCreation->restoreTrashedProduct($product);
                    $this->productCreation->updateExistingProduct($product, $group, $summary, $userId);
                });
                $summary['existing_products_used']++;
            } catch (\Throwable $e) {
                Log::error('Purchase import: product update failed for barcode ' . $barcode . ': ' . $e->getMessage());
                foreach ($group['rows'] as $row) {
                    $summary['failed_rows']++;
                    $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, 'Product Update Failed: ' . $e->getMessage());
                }
                $history[] = [
                    'barcode' => $barcode,
                    'product' => $group['product_name'],
                    'status'  => 'Failed',
                    'reason'  => 'Product Update Failed',
                    'details' => $e->getMessage()
                ];
                return;
            }
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
                $history[] = [
                    'barcode' => $barcode,
                    'product' => $group['product_name'],
                    'status'  => 'Failed',
                    'reason'  => 'Product Creation Failed',
                    'details' => $e->getMessage()
                ];

                return;
            }
        }

        $lastSupplier = null;
        $lastVariant = null;

        $successRows = 0;
        $skippedRows = 0;
        $failedRows = 0;
        $failReasons = [];

        foreach ($group['rows'] as $row) {
            try {
                $validRow = $this->validateAndParseRow($group, $product, $row, $lastSupplier, $lastVariant, $seenSignatures, $userId);
                $validRows[] = $validRow;
                $successRows++;
            } catch (RowSkipException $e) {
                $summary['skipped_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, $e->getMessage(), 'Skipped');
                $skippedRows++;
            } catch (RowFailureException $e) {
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, $e->getMessage());
                $failedRows++;
                $failReasons[] = $e->getMessage();
            } catch (\Throwable $e) {
                Log::error('Purchase import: row ' . $row['row_num'] . ' failed: ' . $e->getMessage());
                $summary['failed_rows']++;
                $failures[] = $this->failureRow($row['row_num'], $group['product_name'], $barcode, 'Unexpected Error');
                $failedRows++;
                $failReasons[] = 'Unexpected Error';
            }
        }

        $uniqueFailReasons = array_values(array_unique($failReasons));

        if ($successRows === 0 && $skippedRows === 0 && !empty($uniqueFailReasons)) {
            $details = implode('; ', $uniqueFailReasons);
        } else {
            $detailsParts = [];
            if ($successRows > 0) {
                $detailsParts[] = "{$successRows} item(s) validated";
            }
            if ($skippedRows > 0) {
                $detailsParts[] = "{$skippedRows} skipped";
            }
            if ($failedRows > 0) {
                $detailsParts[] = "{$failedRows} failed";
            }

            $details = implode(', ', $detailsParts) . ".";
            if (!empty($uniqueFailReasons)) {
                $details .= " Errors: " . implode("; ", $uniqueFailReasons);
            }
        }

        $status = 'Success';
        $reason = 'Processed';
        if ($failedRows > 0) {
            if ($successRows > 0) {
                $status = 'Warning';
                $reason = 'Partial Success';
            } else {
                $status = 'Failed';
                $reason = 'Failed';
            }
        } elseif ($skippedRows > 0 && $successRows === 0) {
            $status = 'Warning';
            $reason = 'Skipped';
        }

        $history[] = [
            'barcode' => $barcode,
            'product' => $group['product_name'],
            'status'  => $status,
            'reason'  => $reason,
            'details' => $details
        ];
    }

    private function validateAndParseRow(array $group, Product $product, array $row, ?string &$lastSupplier, ?string &$lastVariant, array &$seenSignatures, ?int $userId): array
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

        $price = round((float) $product->product_code * (float) $product->purchase_multiplier, 2);
        if ($price <= 0) {
            throw new RowFailureException('Invalid Price Configuration');
        }

        $productVariantId = null;
        if ($product->type === 'variable') {
            $variantName = trim($row['variant']) !== '' ? trim($row['variant']) : (string) $lastVariant;
            $variantValueName = trim($row['variant_value']);

            if ($variantName === '' || $variantValueName === '') {
                throw new RowFailureException('Missing Variant Data');
            }

            $lastVariant = $variantName;

            $productVariant = $this->productCreation->findOrCreateVariant($product, $variantName, $variantValueName, $userId);
            $productVariantId = $productVariant->id;
        }

        $signature = implode('|', [
            $group['barcode'], $productVariantId, $supplierName,
            $price, $quantity,
            $row['purchase_status'], $row['payment_status'],
        ]);
        if (isset($seenSignatures[$signature])) {
            throw new RowSkipException('Duplicate Row');
        }
        $seenSignatures[$signature] = true;

        $status = $this->mapPurchaseStatus($row['purchase_status']);
        $paymentStatus = $this->mapPaymentStatus($row['payment_status']);

        $total = round($quantity * $price, 2);
        $paidAmount = 0.0;

        if ($paymentStatus === Purchase::PAYMENT_STATUS_PAID) {
            $paidAmount = $total;
        } elseif ($paymentStatus === Purchase::PAYMENT_STATUS_PARTIAL) {
            $paidAmountInput = $row['paid_amount'];
            if ($paidAmountInput === '' || !is_numeric($paidAmountInput) || (float) $paidAmountInput <= 0) {
                throw new RowFailureException('Invalid Paid Amount');
            }

            $paidAmount = round((float) $paidAmountInput, 2);
            if ($paidAmount > $total) {
                throw new RowFailureException('Paid Amount cannot be greater than Total Amount');
            }

            if ($paidAmount >= $total) {
                $paidAmount = $total;
                $paymentStatus = Purchase::PAYMENT_STATUS_PAID;
            }
        }

        return [
            'product'            => $product,
            'product_variant_id' => $productVariantId,
            'supplier_name'      => $supplierName,
            'price'              => $price,
            'quantity'           => $quantity,
            'status'             => $status,
            'payment_status'     => $paymentStatus,
            'total'              => $total,
            'paid_amount'        => $paidAmount,
        ];
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
        return match (strtolower(trim($value))) {
            'paid' => Purchase::PAYMENT_STATUS_PAID,
            'partial', 'partially paid' => Purchase::PAYMENT_STATUS_PARTIAL,
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
