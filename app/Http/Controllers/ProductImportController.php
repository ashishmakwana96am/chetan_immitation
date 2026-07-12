<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductImportController extends Controller
{
    public function store(Request $request, ProductImportService $service)
    {
        $this->authorize('create products');

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        set_time_limit(0);

        try {
            $result = $service->process($request->file('excel_file'), auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Product import failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while processing the Excel file.',
            ], 500);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => 'Product Import Completed Successfully.',
            'summary'  => $result['summary'],
            'failures' => $result['failures'],
        ]);
    }

    public function sample()
    {
        $this->authorize('create products');

        $columns = [
            'Category', 'Sub Category', 'Product Name', 'Barcode', 'Product Code',
            'Purchase Multiplier', 'Sale Multiplier', 'MRP Multiplier', 'Pair Product',
            'Product Type', 'Product Variant', 'Product Variant Value',
        ];

        $rows = [
            ['Necklace', 'Short Necklace (R)', 'Short Necklace Regular', 'BAR001', '100', '2.5', '4.125', '4.575', 'FALSE', 'Normal', '', ''],
            ['', 'Short Necklace (A)', 'Short Necklace Antique', 'BAR002', '110', '2.5', '4.125', '4.575', 'FALSE', 'Normal', '', ''],
            ['', 'Long Necklace (R)', 'Long Necklace Regular', 'BAR003', '150', '2.5', '4.125', '4.575', 'TRUE', 'Variable', 'Color', 'Gold,Rose Gold'],
            ['Bangles & Kada', 'Bangal (R)', 'Bangal Regular', 'BAR004', '90', '2.5', '4.125', '4.575', 'FALSE', 'Variable', 'Size', '2.6,3.2,3.5'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $lastColumn = chr(ord('A') + count($columns) - 1);
        $dataEndRow = 1 + count($rows);

        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A1:' . $lastColumn . $dataEndRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="sample_product_import.xlsx"',
        ];

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }
}
