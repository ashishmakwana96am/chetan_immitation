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

    /**
     * @param bool $dryRun When true, every DB write (products, categories, variants,
     *                      suppliers, purchases) happens inside a transaction that is
     *                      rolled back at the end — nothing is persisted. Used to build
     *                      an accurate preview (including auto-created categories/variants)
     *                      without touching real data, by running the exact same code
     *                      path that a real import would.
     */
    public function process(UploadedFile $file, ?int $userId, bool $dryRun = false): array
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
            'purchase_ids'           => [],
        ];
        $failures = [];
        $productDiffs = ['new' => [], 'updated' => []];
        $purchasesPreview = [];

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
        $lastSupplier = null;

        try {
            DB::transaction(function () use (&$groups, &$productsByBarcode, &$seenSignatures, &$summary, &$failures, $userId, &$history, &$validRows, &$lastSupplier, &$productDiffs, &$purchasesPreview, $dryRun) {
                foreach ($groups as $group) {
                    $this->processGroup($group, $productsByBarcode, $seenSignatures, $summary, $failures, $userId, $history, $validRows, $lastSupplier, $productDiffs);
                }

                $this->createPurchasesFromValidRows($validRows, $userId, $summary, $purchasesPreview);

                if ($dryRun) {
                    throw new PurchaseImportDryRun();
                }
            });
        } catch (PurchaseImportDryRun) {
            // Expected — the transaction above has already been rolled back by Laravel.
        }

        if (!$dryRun) {
            ActivityLogger::log(
                'Purchase',
                'import',
                null,
                null,
                $summary,
                "Purchase import: {$summary['purchases_created']} purchases created, {$summary['failed_rows']} failed"
            );
        }

        if ($dryRun) {
            // Every ID above (product IDs, purchase IDs) belongs to a rolled-back
            // transaction and no longer exists — don't let callers act on them.
            $summary['purchase_ids'] = [];
        }

        return [
            'summary'           => $summary,
            'failures'          => $failures,
            'history'           => $history,
            'new_products'      => $productDiffs['new'],
            'updated_products'  => $productDiffs['updated'],
            'purchases_preview' => $purchasesPreview,
        ];
    }

    private function createPurchasesFromValidRows(array $validRows, ?int $userId, array &$summary, array &$purchasesPreview): void
    {
        if (empty($validRows)) {
            return;
        }

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
            DB::transaction(function () use ($supplierGroup, $purchaseDate, $userId, &$summary, &$purchasesPreview) {
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

                $paymentMethod = $items[0]['payment_method'] ?? 'cash';
                $defaultLocation = $this->defaultPurchaseLocation();

                $purchase = Purchase::create([
                    'supplier_id'    => $supplier->id,
                    'location_id'    => $defaultLocation->id,
                    'invoice_no'     => generate_invoice_no('PS', Purchase::class),
                    'is_gst'         => false,
                    'tax_amount'     => 0.00,
                    'total_amount'   => $totalAmount,
                    'status'         => $status,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
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

                // Auto-adjust supplier advance balance if available
                \App\Models\SupplierAdvancePayment::adjustAdvanceForPurchase($purchase);

                foreach ($items as $itemData) {
                    $item = PurchaseItem::create([
                        'purchase_id'        => $purchase->id,
                        'product_id'         => $itemData['product']->id,
                        'product_variant_id' => $itemData['product_variant_id'],
                        'purchase_price'     => $itemData['price'],
                        'quantity'           => $itemData['quantity'],
                        'custom_size_value'  => $itemData['custom_size_value'] ?? null,
                        'total'              => $itemData['total'],
                    ]);

                    PurchaseAllocation::create([
                        'purchase_item_id' => $item->id,
                        'location_id'      => $defaultLocation->id,
                        'quantity'          => $itemData['quantity'],
                    ]);
                }

                if ($status === Purchase::STATUS_APPROVE) {
                    PurchaseStockService::approve($purchase);
                }

                $summary['purchases_created']++;
                $summary['purchase_ids'][] = $purchase->id;

                // Purchase item quantity is entered as a pair-count for pair
                // products (e.g. 6 pairs) and a plain piece-count otherwise —
                // same convention as everywhere else stock is displayed.
                $pairQty = 0;
                $pcsQty = 0;
                foreach ($items as $itemData) {
                    if ($itemData['product']->pair_product) {
                        $pairQty += $itemData['quantity'];
                    } else {
                        $pcsQty += $itemData['quantity'];
                    }
                }

                $purchasesPreview[] = [
                    'supplier_name'   => $supplierName,
                    'invoice_no'      => $purchase->invoice_no,
                    'item_count'      => count($items),
                    'pair_qty'        => $pairQty,
                    'pcs_qty'         => $pcsQty,
                    'total_amount'    => $totalAmount,
                    'paid_amount'     => $paidAmount,
                    'status'          => $status === Purchase::STATUS_APPROVE ? 'Approve' : 'Pending',
                    'payment_status'  => match ($paymentStatus) {
                        Purchase::PAYMENT_STATUS_PAID    => 'Paid',
                        Purchase::PAYMENT_STATUS_PARTIAL => 'Partial',
                        default                           => 'Pending',
                    },
                ];
            });
        }
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
            'suppliername'           => 'supplier_name',
            'variant'                => 'variant',
            'varient'                => 'variant',
            'variantvalue'           => 'variant_value',
            'varientvalue'           => 'variant_value',
            'purchasedate'           => 'purchase_date',
            'quantity'               => 'quantity',
            'purchasestatus'         => 'purchase_status',
            'paymentstatus'          => 'payment_status',
            'paymentmethod'          => 'payment_method',
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
                    'first_row_num'       => $rowNum,
                    'category_name'       => $categoryName,
                    'sub_category_name'   => trim($row['sub_category'] ?? ''),
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
                    'rows'                => [],
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
                'payment_method'  => trim($row['payment_method'] ?? ''),
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

    private function processGroup(array $group, array &$productsByBarcode, array &$seenSignatures, array &$summary, array &$failures, ?int $userId, array &$history, array &$validRows, ?string &$lastSupplier, array &$productDiffs): void
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
            $diffFields = ['name', 'category_id', 'sub_category_id', 'purchase_price', 'sale_price', 'mrp', 'purchase_multiplier', 'sale_multiplier', 'mrp_multiplier', 'type'];
            $oldSnapshot = $product->only($diffFields);
            $oldCategoryName = $product->category->name ?? '-';
            $oldSubCategoryName = $product->subCategory->name ?? '-';

            try {
                DB::transaction(function () use ($product, $group, &$summary, $userId) {
                    $this->productCreation->restoreTrashedProduct($product);
                    $this->productCreation->updateExistingProduct($product, $group, $summary, $userId);
                });
                $summary['existing_products_used']++;

                $newSnapshot = $product->only($diffFields);
                $changes = [];
                foreach ($diffFields as $field) {
                    if ((string) $oldSnapshot[$field] !== (string) $newSnapshot[$field]) {
                        $changes[$field] = ['old' => $oldSnapshot[$field], 'new' => $newSnapshot[$field]];
                    }
                }
                // Category/SubCategory are matched case-insensitively at the DB level
                // (firstOrCreate against a _ci collation), so "Necklace" vs "NECKLACE"
                // resolves to the same row and creates nothing new — only flag it as a
                // change here when it's a genuinely different name, not just casing.
                if (mb_strtolower($oldCategoryName) !== mb_strtolower($group['category_name'])) {
                    $changes['category'] = ['old' => $oldCategoryName, 'new' => $group['category_name']];
                }
                $newSubCategoryName = $group['sub_category_name'] !== '' ? $group['sub_category_name'] : '-';
                if (mb_strtolower($oldSubCategoryName) !== mb_strtolower($newSubCategoryName)) {
                    $changes['sub_category'] = ['old' => $oldSubCategoryName, 'new' => $newSubCategoryName];
                }

                $productDiffs['updated'][] = [
                    'barcode' => $barcode,
                    'name'    => $product->name,
                    'changed' => !empty($changes),
                    'changes' => $changes,
                ];
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

                $productDiffs['new'][] = [
                    'barcode'       => $barcode,
                    'name'          => $product->name,
                    'category'      => $group['category_name'],
                    'sub_category'  => $group['sub_category_name'] !== '' ? $group['sub_category_name'] : '-',
                    'type'          => $product->type,
                    'purchase_price' => (float) $product->purchase_price,
                    'sale_price'    => (float) $product->sale_price,
                    'mrp'           => (float) $product->mrp,
                ];
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

        if ($product->pair_product) {
            $customSizes = $product->custom_sizes ?? [];
            if (empty($customSizes) && !empty($group['pair_sizes'])) {
                $sizesArray = array_filter(array_map('trim', explode(',', $group['pair_sizes'])));
                $maxSize = !empty($sizesArray) ? (float) max($sizesArray) : 2.0;
            } else {
                $maxSize = !empty($customSizes)
                    ? (float) collect($customSizes)->pluck('size')->filter()->max()
                    : 2.0;
            }
            if ($maxSize <= 0) {
                $maxSize = 2.0;
            }
        } else {
            $maxSize = null;
        }

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

            $productVariant = $this->productCreation->findOrCreateVariantByIndexOrName($product, $variantName, $variantValueName, $userId);
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
        $paymentMethod = $this->mapPaymentMethod($row['payment_method']);

        $pairsCount    = $quantity;
        $total         = round($pairsCount * $price, 2);

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
            'quantity'           => $pairsCount,
            'custom_size_value'  => $maxSize,
            'status'             => $status,
            'payment_status'     => $paymentStatus,
            'payment_method'     => $paymentMethod,
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

    private function mapPaymentMethod(string $value): string
    {
        return match (strtolower(trim($value))) {
            'online', 'upi', 'bank', 'bank transfer', 'bank_transfer', 'razorpay' => 'online',
            default => 'cash',
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

/**
 * Thrown at the end of a dry-run import to force the wrapping DB::transaction
 * to roll back everything it just did (products, categories, variants,
 * suppliers, purchases) while still letting process() return the collected
 * preview payload — see PurchaseImportService::process().
 */
class PurchaseImportDryRun extends \Exception
{
}
