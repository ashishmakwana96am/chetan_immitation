<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportExportService
{
    /**
     * Get the default header style.
     */
    protected function getHeaderStyle(): array
    {
        return [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'name' => 'Segoe UI',
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF7367F0'], // Theme violet color
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD9D9D9'],
                ],
            ],
        ];
    }

    /**
     * Get the default data cell style.
     */
    protected function getDataStyle(): array
    {
        return [
            'font' => [
                'name' => 'Segoe UI',
                'size' => 10,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE0E0E0'],
                ],
            ],
        ];
    }

    /**
     * Get the default totals style.
     */
    protected function getTotalsStyle(): array
    {
        return [
            'font' => [
                'bold' => true,
                'name' => 'Segoe UI',
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2F2F2'],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
    }

    /**
     * Auto-adjust column widths for a sheet.
     */
    protected function autoFitColumns($sheet): void
    {
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    /**
     * Format a currency code for Excel format mask.
     */
    protected function getCurrencyFormatCode(): string
    {
        $symbol = currency_symbol();
        return '"' . $symbol . '" #,##0.00;("-' . $symbol . '" #,##0.00);"-"';
    }

    /**
     * Export Products Report.
     */
    public function exportProducts($products): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Report');

        // Headers
        $headers = [
            'S.No.',
            'Product Name',
            'Barcode',
            'Category',
            'Purchase Price',
            'Sale Price',
            'Margin',
            'Margin %',
            'Total Stock',
            'Status'
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($products as $index => $product) {
            $margin = $product['sale_price'] - $product['purchase_price'];
            $marginPct = $product['purchase_price'] > 0 ? ($margin / $product['purchase_price']) : 0;

            $productName = $product['name'];
            $barcode = $product['barcode'];
            $category = $product['category'];
            if (isset($product['is_parent']) && !$product['is_parent']) {
                $productName = "    ↳ " . $product['variant_name'];
                $barcode = "-";
                $category = "-";
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $productName);
            $sheet->setCellValue('C' . $row, $barcode);
            $sheet->setCellValue('D' . $row, $category);
            $sheet->setCellValue('E' . $row, (float) $product['purchase_price']);
            $sheet->setCellValue('F' . $row, (float) $product['sale_price']);
            $sheet->setCellValue('G' . $row, (float) $margin);
            $sheet->setCellValue('H' . $row, (float) $marginPct);
            $sheet->setCellValue('I' . $row, (int) $product['total_stock']);
            $sheet->setCellValue('J' . $row, $product['status'] == 1 ? 'Active' : 'Inactive');

            $row++;
        }

        // Add Totals row
        $variantParentIds = collect($products)->where('is_parent', false)->pluck('id')->unique();
        $totalStock = 0;
        foreach ($products as $product) {
            $isVariantParent = $product['is_parent'] && $variantParentIds->contains($product['id']);
            if (!$isVariantParent) {
                $totalStock += (int) $product['total_stock'];
            }
        }

        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total / Average');
        $sheet->setCellValue('E' . $totalRow, "=AVERAGE(E2:E" . ($totalRow - 1) . ")");
        $sheet->setCellValue('F' . $totalRow, "=AVERAGE(F2:F" . ($totalRow - 1) . ")");
        $sheet->setCellValue('G' . $totalRow, "=AVERAGE(G2:G" . ($totalRow - 1) . ")");
        $sheet->setCellValue('H' . $totalRow, "=AVERAGE(H2:H" . ($totalRow - 1) . ")");
        $sheet->setCellValue('I' . $totalRow, $totalStock);

        $lastCol = 'J';
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($this->getHeaderStyle());
        $sheet->getStyle('A2:' . $lastCol . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray($this->getTotalsStyle());

        $sheet->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:J' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currencyCode = $this->getCurrencyFormatCode();
        $sheet->getStyle('E2:E' . $totalRow)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet->getStyle('F2:F' . $totalRow)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet->getStyle('G2:G' . $totalRow)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet->getStyle('H2:H' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
        $sheet->getStyle('I2:I' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

        $this->autoFitColumns($sheet);

        return $spreadsheet;
    }

    /**
     * Export Stock Inventory Report.
     */
    public function exportStockInventory($products, $locations): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Inventory');

        // Build Headers
        $headers = ['S.No.', 'Product Name', 'Barcode', 'Category'];
        foreach ($locations as $loc) {
            $headers[] = $loc->name;
        }
        $headers[] = 'Total Qty';
        $headers[] = 'Purchase Value';
        $headers[] = 'MRP Value';

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Fill Data
        $row = 2;
        foreach ($products as $index => $product) {
            $productName = $product['name'];
            $barcode     = $product['barcode'];
            $category    = $product['category'];
            if (isset($product['is_parent']) && !$product['is_parent']) {
                $productName = "    ↳ " . $product['variant_name'];
                $barcode     = "-";
                $category    = "-";
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $productName);
            $sheet->setCellValue('C' . $row, $barcode);
            $sheet->setCellValue('D' . $row, $category);

            $colIdx = 5; // Col E starts at index 5 (1-based)
            foreach ($locations as $loc) {
                $qty = $product['stock'][$loc->id] ?? 0;
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $row, (int) $qty);
                $colIdx++;
            }

            // Total Qty column
            $colLetterQty = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetterQty . $row, (int) $product['total']);

            // Purchase Value column
            $colIdx++;
            $colLetterPurch = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetterPurch . $row, (float) ($product['purchase_value'] ?? 0));

            // MRP Value column
            $colIdx++;
            $colLetterSale = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetterSale . $row, (float) ($product['mrp_value'] ?? 0));

            $row++;
        }

        // Totals Row
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total');

        $totalCols = count($locations) + 7;
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // Dynamic location & qty sums
        for ($i = 0; $i < count($locations) + 1; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(5 + $i);
            $sheet->setCellValue($colLetter . $totalRow, "=SUM(" . $colLetter . "2:" . $colLetter . ($totalRow - 1) . ")");
        }

        // Purchase Value sum & MRP Value sum
        $purchColLetter = Coordinate::stringFromColumnIndex(5 + count($locations) + 1);
        $mrpColLetter   = Coordinate::stringFromColumnIndex(5 + count($locations) + 2);

        $sheet->setCellValue($purchColLetter . $totalRow, "=SUM(" . $purchColLetter . "2:" . $purchColLetter . ($totalRow - 1) . ")");
        $sheet->setCellValue($mrpColLetter . $totalRow, "=SUM(" . $mrpColLetter . "2:" . $mrpColLetter . ($totalRow - 1) . ")");

        // Apply Styles
        $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray($this->getHeaderStyle());
        $sheet->getStyle('A2:' . $lastColLetter . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow)->applyFromArray($this->getTotalsStyle());

        // Alignments & formats
        $sheet->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($i = 0; $i < count($locations) + 1; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(5 + $i);
            $sheet->getStyle($colLetter . '2:' . $colLetter . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colLetter . '2:' . $colLetter . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        }

        $currencyCode = $this->getCurrencyFormatCode();
        $sheet->getStyle($purchColLetter . '2:' . $purchColLetter . $totalRow)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet->getStyle($mrpColLetter . '2:' . $mrpColLetter . $totalRow)->getNumberFormat()->setFormatCode($currencyCode);

        $this->autoFitColumns($sheet);

        return $spreadsheet;
    }

    /**
     * Export Purchases Report (2 Sheets: Invoices List & Top Products).
     */
    public function exportPurchases($invoices, $productPurchases): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        
        // Sheet 1: Invoices List
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Purchase List');

        $headers1 = ['S.No.', 'Purchase No', 'Supplier', 'Status', 'Date', 'Total Amount'];
        $sheet1->fromArray($headers1, null, 'A1');
        $sheet1->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($invoices as $index => $invoice) {
            $sheet1->setCellValue('A' . $row, $index + 1);
            $sheet1->setCellValue('B' . $row, $invoice->invoice_no);
            $sheet1->setCellValue('C' . $row, $invoice->supplier->name ?? 'Unknown');
            $purchaseStatuses = [
                1 => 'Draft',
                2 => 'Confirmed',
                3 => 'Cancelled',
            ];
            $statusLabel = $purchaseStatuses[$invoice->status] ?? 'Unknown';
            $sheet1->setCellValue('D' . $row, $statusLabel);
            $sheet1->setCellValue('E' . $row, $invoice->created_at->format('d M Y'));
            $sheet1->setCellValue('F' . $row, (float) $invoice->total_amount);
            $row++;
        }

        $totalRow = $row;
        $sheet1->setCellValue('A' . $totalRow, 'Total');
        $sheet1->setCellValue('F' . $totalRow, "=SUM(F2:F" . ($totalRow - 1) . ")");

        $sheet1->getStyle('A1:F1')->applyFromArray($this->getHeaderStyle());
        $sheet1->getStyle('A2:F' . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet1->getStyle('A' . $totalRow . ':F' . $totalRow)->applyFromArray($this->getTotalsStyle());

        $sheet1->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('B2:B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('D2:D' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('E2:E' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('F2:F' . $totalRow)->getNumberFormat()->setFormatCode($this->getCurrencyFormatCode());

        $this->autoFitColumns($sheet1);

        // Sheet 2: Top Products
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Purchased Products');

        $headers2 = ['S.No.', 'Product Name', 'Barcode', 'Qty Purchased', 'Total Cost'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productPurchases as $index => $item) {
            $sheet2->setCellValue('A' . $row, $index + 1);
            $sheet2->setCellValue('B' . $row, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $row, $item->product->barcode ?? '-');
            $sheet2->setCellValue('D' . $row, (int) $item->qty_purchased);
            $sheet2->setCellValue('E' . $row, (float) $item->total_cost);
            $row++;
        }

        $totalRow2 = $row;
        $sheet2->setCellValue('A' . $totalRow2, 'Total');
        $sheet2->setCellValue('D' . $totalRow2, "=SUM(D2:D" . ($totalRow2 - 1) . ")");
        $sheet2->setCellValue('E' . $totalRow2, "=SUM(E2:E" . ($totalRow2 - 1) . ")");

        $sheet2->getStyle('A1:E1')->applyFromArray($this->getHeaderStyle());
        $sheet2->getStyle('A2:E' . ($totalRow2 - 1))->applyFromArray($this->getDataStyle());
        $sheet2->getStyle('A' . $totalRow2 . ':E' . $totalRow2)->applyFromArray($this->getTotalsStyle());

        $sheet2->getStyle('A2:A' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C2:C' . ($totalRow2 - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        $sheet2->getStyle('E2:E' . $totalRow2)->getNumberFormat()->setFormatCode($this->getCurrencyFormatCode());

        $this->autoFitColumns($sheet2);

        // Reset active sheet to index 0 (Invoices List)
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Export Purchase Bills List.
     */
    public function exportPurchaseBills($transfers): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Bills');

        $headers = ['S.No.', 'Bill No', 'Source', 'Destination', 'Total Quantity', 'Amount', 'Total MRP', 'Status', 'Payment Status', 'Created By', 'Date'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        $statusLabels = [
            1 => 'Pending',
            2 => 'Accepted',
            3 => 'Rejected',
        ];

        $paymentStatusLabels = [
            1 => 'Pending',
            2 => 'Paid',
            3 => 'Partially Paid',
        ];

        $row = 2;
        foreach ($transfers as $index => $transfer) {
            [$totalAmount, $totalMrp] = $this->purchaseBillTotals($transfer);

            $totalPcs = 0;
            $totalPairs = 0;
            $totalRemPcs = 0;

            foreach ($transfer->items as $item) {
                $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type ?? 'single', $item->custom_size_value);
                $itemPcs = (int) round($item->quantity * $multiplier);
                $totalPcs += $itemPcs;

                if ($item->product && $item->product->pair_product) {
                    $pairSize = $multiplier > 0 ? $multiplier : 1.0;
                    $pairs = (int) floor($itemPcs / $pairSize);
                    $remPcs = (int) ($itemPcs % $pairSize);
                    $totalPairs += $pairs;
                    $totalRemPcs += $remPcs;
                } else {
                    $totalRemPcs += $itemPcs;
                }
            }

            $itemsDisplayParts = [];
            if ($totalPairs > 0) {
                $itemsDisplayParts[] = number_format($totalPairs) . ' Pair' . ($totalPairs > 1 ? 's' : '');
            }
            if ($totalRemPcs > 0) {
                $itemsDisplayParts[] = number_format($totalRemPcs) . ' Pcs';
            }
            $itemsDisplay = count($itemsDisplayParts) > 0 ? implode(', ', $itemsDisplayParts) : number_format($totalPcs);

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $transfer->transfer_no);
            $sheet->setCellValue('C' . $row, $transfer->fromLocation->name ?? '-');
            $sheet->setCellValue('D' . $row, $transfer->toLocation->name ?? '-');
            $sheet->setCellValue('E' . $row, $itemsDisplay);
            $sheet->setCellValueExplicit('F' . $row, round((float) $totalAmount, 2), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $row, round((float) $totalMrp, 2), DataType::TYPE_NUMERIC);
            $sheet->setCellValue('H' . $row, $statusLabels[$transfer->status] ?? 'Unknown');
            $sheet->setCellValue('I' . $row, $paymentStatusLabels[(int) ($transfer->payment_status ?? 1)] ?? 'Pending');
            $sheet->setCellValue('J' . $row, $transfer->createdBy->name ?? '-');
            $sheet->setCellValue('K' . $row, $transfer->created_at->format('d M Y'));
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total');
        $sheet->setCellValue('F' . $totalRow, "=SUM(F2:F" . ($totalRow - 1) . ")");
        $sheet->setCellValue('G' . $totalRow, "=SUM(G2:G" . ($totalRow - 1) . ")");

        $sheet->getStyle('A1:K1')->applyFromArray($this->getHeaderStyle());
        $sheet->getStyle('A2:K' . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet->getStyle('A' . $totalRow . ':K' . $totalRow)->applyFromArray($this->getTotalsStyle());

        $sheet->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:I' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K2:K' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:G' . $totalRow)->getNumberFormat()->setFormatCode($this->getCurrencyFormatCode());

        $this->autoFitColumns($sheet);

        return $spreadsheet;
    }

    /**
     * Export Sales Report (2 Sheets: Orders List & Top Products).
     */
    public function exportSales($orders, $productSales): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Orders List
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Orders List');

        $headers1 = ['S.No.', 'Order No', 'Customer', 'Location', 'Payment Status', 'Payment Method', 'Date', 'Final Amount'];
        $sheet1->fromArray($headers1, null, 'A1');
        $sheet1->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($orders as $index => $order) {
            $sheet1->setCellValue('A' . $row, $index + 1);
            $sheet1->setCellValue('B' . $row, $order->order_no);
            $sheet1->setCellValue('C' . $row, $order->customer->name ?? 'Walk-in');
            $sheet1->setCellValue('D' . $row, $order->location->name ?? '-');
            $paymentStatuses = [
                1 => 'Pending',
                2 => 'Paid',
                3 => 'Partially Paid',
            ];
            $paymentStatusLabel = $paymentStatuses[$order->payment_status] ?? 'Pending';
            $sheet1->setCellValue('E' . $row, $paymentStatusLabel);
            $sheet1->setCellValue('F' . $row, strtoupper(str_replace('_', ' ', $order->payment_method)));
            $sheet1->setCellValue('G' . $row, $order->created_at->format('d M Y'));
            $sheet1->setCellValue('H' . $row, (float) $order->final_amount);
            $row++;
        }

        $totalRow = $row;
        $sheet1->setCellValue('A' . $totalRow, 'Total');
        $sheet1->setCellValue('H' . $totalRow, "=SUM(H2:H" . ($totalRow - 1) . ")");

        $sheet1->getStyle('A1:H1')->applyFromArray($this->getHeaderStyle());
        $sheet1->getStyle('A2:H' . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet1->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray($this->getTotalsStyle());

        $sheet1->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('B2:B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('E2:E' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('F2:F' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('G2:G' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('H2:H' . $totalRow)->getNumberFormat()->setFormatCode($this->getCurrencyFormatCode());

        $this->autoFitColumns($sheet1);

        // Sheet 2: Top Selling Products
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Selling Products');

        $headers2 = ['S.No.', 'Product Name', 'Barcode', 'Qty Sold', 'Total Revenue'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productSales as $index => $item) {
            $sheet2->setCellValue('A' . $row, $index + 1);
            $sheet2->setCellValue('B' . $row, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $row, $item->product->barcode ?? '-');
            $sheet2->setCellValue('D' . $row, (int) $item->qty_sold);
            $sheet2->setCellValue('E' . $row, (float) $item->total_revenue);
            $row++;
        }

        $totalRow2 = $row;
        $sheet2->setCellValue('A' . $totalRow2, 'Total');
        $sheet2->setCellValue('D' . $totalRow2, "=SUM(D2:D" . ($totalRow2 - 1) . ")");
        $sheet2->setCellValue('E' . $totalRow2, "=SUM(E2:E" . ($totalRow2 - 1) . ")");

        $sheet2->getStyle('A1:E1')->applyFromArray($this->getHeaderStyle());
        $sheet2->getStyle('A2:E' . ($totalRow2 - 1))->applyFromArray($this->getDataStyle());
        $sheet2->getStyle('A' . $totalRow2 . ':E' . $totalRow2)->applyFromArray($this->getTotalsStyle());

        $sheet2->getStyle('A2:A' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C2:C' . ($totalRow2 - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        $sheet2->getStyle('E2:E' . $totalRow2)->getNumberFormat()->setFormatCode($this->getCurrencyFormatCode());

        $this->autoFitColumns($sheet2);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Export Profit & Loss Report (2 Sheets: P&L Overview & Product Profitability).
     */
    public function exportProfitLoss($totalRevenue, $totalCogs, $totalExpenses, $netProfit, $profitMargin, $productProfitability): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: P&L Overview
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('P&L Overview');

        $headers1 = ['Metric', 'Amount'];
        $sheet1->fromArray($headers1, null, 'A1');
        $sheet1->getRowDimension(1)->setRowHeight(28);

        $data = [
            ['Total Revenue', (float) $totalRevenue],
            ['Total Cost of Goods Sold (COGS)', (float) $totalCogs],
            ['Total Expenses', (float) $totalExpenses],
            ['Net Profit', (float) $netProfit],
            ['Gross Profit Margin', (float) ($profitMargin / 100)],
        ];

        $row = 2;
        foreach ($data as $item) {
            $sheet1->setCellValue('A' . $row, $item[0]);
            $sheet1->setCellValue('B' . $row, $item[1]);
            $row++;
        }

        $sheet1->getStyle('A1:B1')->applyFromArray($this->getHeaderStyle());
        $sheet1->getStyle('A2:B6')->applyFromArray($this->getDataStyle());

        $currencyCode = $this->getCurrencyFormatCode();
        $sheet1->getStyle('B2:B5')->getNumberFormat()->setFormatCode($currencyCode);
        $sheet1->getStyle('B6')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        $sheet1->getStyle('A5:B5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => $netProfit >= 0 ? 'FF28C76F' : 'FFEA5455']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']]
        ]);

        $this->autoFitColumns($sheet1);

        // Sheet 2: Product Profitability
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Product Profitability');

        $headers2 = ['S.No.', 'Product Name', 'Barcode', 'Qty Sold', 'Total Revenue', 'Total Cost (COGS)', 'Net Profit', 'Profit Margin %'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productProfitability as $productId => $item) {
            $net = $item['total_revenue'] - $item['total_cost'];
            $margin = $item['total_revenue'] > 0 ? ($net / $item['total_revenue']) : 0;

            $sheet2->setCellValue('A' . $row, $row - 1);
            $sheet2->setCellValue('B' . $row, $item['name']);
            $sheet2->setCellValue('C' . $row, $item['barcode']);
            $sheet2->setCellValue('D' . $row, (int) $item['qty_sold']);
            $sheet2->setCellValue('E' . $row, (float) $item['total_revenue']);
            $sheet2->setCellValue('F' . $row, (float) $item['total_cost']);
            $sheet2->setCellValue('G' . $row, (float) $net);
            $sheet2->setCellValue('H' . $row, (float) $margin);
            $row++;
        }

        $totalRow2 = $row;
        $sheet2->setCellValue('A' . $totalRow2, 'Total');
        $sheet2->setCellValue('D' . $totalRow2, "=SUM(D2:D" . ($totalRow2 - 1) . ")");
        $sheet2->setCellValue('E' . $totalRow2, "=SUM(E2:E" . ($totalRow2 - 1) . ")");
        $sheet2->setCellValue('F' . $totalRow2, "=SUM(F2:F" . ($totalRow2 - 1) . ")");
        $sheet2->setCellValue('G' . $totalRow2, "=SUM(G2:G" . ($totalRow2 - 1) . ")");
        // Overall margin
        $sheet2->setCellValue('H' . $totalRow2, "=IF(E" . $totalRow2 . ">0, G" . $totalRow2 . "/E" . $totalRow2 . ", 0)");

        $sheet2->getStyle('A1:H1')->applyFromArray($this->getHeaderStyle());
        $sheet2->getStyle('A2:H' . ($totalRow2 - 1))->applyFromArray($this->getDataStyle());
        $sheet2->getStyle('A' . $totalRow2 . ':H' . $totalRow2)->applyFromArray($this->getTotalsStyle());

        $sheet2->getStyle('A2:A' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C2:C' . ($totalRow2 - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D2:D' . $totalRow2)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

        $sheet2->getStyle('E2:E' . $totalRow2)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet2->getStyle('F2:F' . $totalRow2)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet2->getStyle('G2:G' . $totalRow2)->getNumberFormat()->setFormatCode($currencyCode);
        $sheet2->getStyle('H2:H' . $totalRow2)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        $this->autoFitColumns($sheet2);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function stockMultiplierFor($product, ?string $pairType, $customSizeValue = null): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && is_numeric($customSizeValue)) {
            $val = (float) $customSizeValue;
            if ($val > 0) {
                return $val;
            }
        }
        if ($product && !empty($product->pair_product) && !empty($product->custom_sizes)) {
            $maxSize = collect($product->custom_sizes)->pluck('size')->max();
            if ($maxSize && (float)$maxSize > 0) {
                return (float)$maxSize;
            }
        }

        if ($product && !empty($product->pair_product)) {
            return 2.0;
        }

        return 1.0;
    }

    protected function purchaseBillTotals($transfer): array
    {
        $totalAmount = 0.0;
        $totalMrp = 0.0;

        foreach ($transfer->items as $item) {
            $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type ?? 'single', $item->custom_size_value);
            $quantity = (int) $item->quantity;

            $totalAmount += $this->purchasePriceForPurchaseBillItem($item) * $quantity;

            $mrp = $this->mrpForPurchaseBillItem($item, $multiplier);
            $totalMrp += $mrp * $quantity;
        }

        return [$totalAmount, $totalMrp];
    }

    protected function totalPcsForTransfer($transfer): int
    {
        $totalPcs = 0;

        foreach ($transfer->items as $item) {
            $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type ?? 'single', $item->custom_size_value);
            $totalPcs += (int) round($item->quantity * $multiplier);
        }

        return $totalPcs;
    }

    protected function purchasePriceForPurchaseBillItem($item): float
    {
        $product = $item->product;
        $basePrice = (float) ((isset($item->purchase_price) && $item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

        if (!$product || empty($product->pair_product)) {
            return $basePrice;
        }

        $selectedSize = (float) $item->custom_size_value;
        if ($selectedSize <= 0) {
            return $basePrice;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $maxSize = collect($sizes)
            ->pluck('size')
            ->map(fn ($size) => (float) $size)
            ->filter(fn ($size) => $size > 0)
            ->max();

        if (!$maxSize || $maxSize <= 0) {
            return $basePrice;
        }

        return (float) ($basePrice * ($selectedSize / (float) $maxSize));
    }

    protected function mrpForPurchaseBillItem($item, float $multiplier): float
    {
        $product = $item->product;
        if (!$product) {
            return 0.0;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        if (!empty($sizes)) {
            $value = (float) $item->custom_size_value;
            $matched = null;

            if ($value > 0) {
                $matched = collect($sizes)->first(fn ($row) => abs((float) ($row['size'] ?? 0) - $value) < 0.001);
            }

            if (!$matched) {
                $matched = collect($sizes)->sortBy(fn ($row) => (float) ($row['size'] ?? 0))->last();
            }

            if ($matched && isset($matched['mrp']) && is_numeric($matched['mrp'])) {
                return (float) $matched['mrp'];
            }
        }

        return (float) ($product->mrp ?? 0);
    }

    /**
     * Download spreadsheet response helper.
     */
    public function downloadResponse(Spreadsheet $spreadsheet, string $filename)
    {
        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
