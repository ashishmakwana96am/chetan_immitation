<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            'SKU',
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

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValue('C' . $row, $product['sku']);
            $sheet->setCellValue('D' . $row, $product['category']);
            $sheet->setCellValue('E' . $row, (float) $product['purchase_price']);
            $sheet->setCellValue('F' . $row, (float) $product['sale_price']);
            $sheet->setCellValue('G' . $row, (float) $margin);
            $sheet->setCellValue('H' . $row, (float) $marginPct);
            $sheet->setCellValue('I' . $row, (int) $product['total_stock']);
            $sheet->setCellValue('J' . $row, ucfirst($product['status']));

            $row++;
        }

        // Add Totals row
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total / Average');
        $sheet->setCellValue('E' . $totalRow, "=AVERAGE(E2:E" . ($totalRow - 1) . ")");
        $sheet->setCellValue('F' . $totalRow, "=AVERAGE(F2:F" . ($totalRow - 1) . ")");
        $sheet->setCellValue('G' . $totalRow, "=AVERAGE(G2:G" . ($totalRow - 1) . ")");
        $sheet->setCellValue('H' . $totalRow, "=AVERAGE(H2:H" . ($totalRow - 1) . ")");
        $sheet->setCellValue('I' . $totalRow, "=SUM(I2:I" . ($totalRow - 1) . ")");

        // Apply Styles
        $lastCol = 'J';
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($this->getHeaderStyle());
        $sheet->getStyle('A2:' . $lastCol . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray($this->getTotalsStyle());

        // Alignments & Number formats
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
        $headers = ['S.No.', 'Product Name', 'SKU', 'Category'];
        foreach ($locations as $loc) {
            $headers[] = $loc->name;
        }
        $headers[] = 'Total';

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Fill Data
        $row = 2;
        foreach ($products as $index => $product) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValue('C' . $row, $product['sku']);
            $sheet->setCellValue('D' . $row, $product['category']);

            $colIdx = 5; // Col E starts at index 5 (1-based)
            foreach ($locations as $loc) {
                $qty = $product['stock'][$loc->id] ?? 0;
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $row, (int) $qty);
                $colIdx++;
            }

            // Total column
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . $row, (int) $product['total']);
            $row++;
        }

        // Totals Row
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'Total Stock');
        
        $lastColLetter = Coordinate::stringFromColumnIndex(count($locations) + 5);
        
        // Dynamic location sums
        for ($i = 0; $i < count($locations) + 1; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(5 + $i);
            $sheet->setCellValue($colLetter . $totalRow, "=SUM(" . $colLetter . "2:" . $colLetter . ($totalRow - 1) . ")");
        }

        // Apply Styles
        $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray($this->getHeaderStyle());
        $sheet->getStyle('A2:' . $lastColLetter . ($totalRow - 1))->applyFromArray($this->getDataStyle());
        $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow)->applyFromArray($this->getTotalsStyle());

        // Alignments & formats
        $sheet->getStyle('A2:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Stock columns align center & number format
        for ($i = 0; $i < count($locations) + 1; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $i);
            $sheet->getStyle($colLetter . '2:' . $colLetter . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colLetter . '2:' . $colLetter . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        }

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
        $sheet1->setTitle('Invoices List');

        $headers1 = ['S.No.', 'Invoice No', 'Supplier', 'Status', 'Date', 'Total Amount'];
        $sheet1->fromArray($headers1, null, 'A1');
        $sheet1->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($invoices as $index => $invoice) {
            $sheet1->setCellValue('A' . $row, $index + 1);
            $sheet1->setCellValue('B' . $row, $invoice->invoice_no);
            $sheet1->setCellValue('C' . $row, $invoice->supplier->name ?? 'Unknown');
            $sheet1->setCellValue('D' . $row, ucfirst($invoice->status));
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

        $headers2 = ['S.No.', 'Product Name', 'SKU', 'Qty Purchased', 'Total Cost'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productPurchases as $index => $item) {
            $sheet2->setCellValue('A' . $row, $index + 1);
            $sheet2->setCellValue('B' . $row, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $row, $item->product->sku ?? '-');
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
            $sheet1->setCellValue('E' . $row, ucfirst($order->payment_status));
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

        $headers2 = ['S.No.', 'Product Name', 'SKU', 'Qty Sold', 'Total Revenue'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productSales as $index => $item) {
            $sheet2->setCellValue('A' . $row, $index + 1);
            $sheet2->setCellValue('B' . $row, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $row, $item->product->sku ?? '-');
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
    public function exportProfitLoss($totalRevenue, $totalCogs, $netProfit, $profitMargin, $productProfitability): Spreadsheet
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
        $sheet1->getStyle('A2:B5')->applyFromArray($this->getDataStyle());

        // Profit margin is a percentage, rest are currency
        $currencyCode = $this->getCurrencyFormatCode();
        $sheet1->getStyle('B2:B4')->getNumberFormat()->setFormatCode($currencyCode);
        $sheet1->getStyle('B5')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        // Add some highlights/backgrounds to sheet 1 rows for premium feel
        $sheet1->getStyle('A4:B4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => $netProfit >= 0 ? 'FF28C76F' : 'FFEA5455']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']]
        ]);

        $this->autoFitColumns($sheet1);

        // Sheet 2: Product Profitability
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Product Profitability');

        $headers2 = ['S.No.', 'Product Name', 'SKU', 'Qty Sold', 'Total Revenue', 'Total Cost (COGS)', 'Net Profit', 'Profit Margin %'];
        $sheet2->fromArray($headers2, null, 'A1');
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($productProfitability as $productId => $item) {
            $net = $item['total_revenue'] - $item['total_cost'];
            $margin = $item['total_revenue'] > 0 ? ($net / $item['total_revenue']) : 0;

            $sheet2->setCellValue('A' . $row, $row - 1);
            $sheet2->setCellValue('B' . $row, $item['name']);
            $sheet2->setCellValue('C' . $row, $item['sku']);
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
}
