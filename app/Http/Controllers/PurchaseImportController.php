<?php

namespace App\Http\Controllers;

use App\Services\PurchaseImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseImportController extends Controller
{
    public function store(Request $request, PurchaseImportService $service)
    {
        $this->authorize('create purchases');

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
            Log::error('Purchase import failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while processing the Excel file.',
            ], 500);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => 'Purchase Import Completed Successfully.',
            'summary'  => $result['summary'],
            'failures' => $result['failures'],
            'history'  => $result['history'],
        ]);
    }

    public function sample()
    {
        $this->authorize('create purchases');

        $columns = [
            'Category', 'Sub Category', 'Product Name', 'Barcode', 'Product Code',
            'Purchase Multiplier', 'Sale Multiplier', 'MRP Multiplier', 'Pair Product',
            'Product Type', 'Supplier Name', 'Variant', 'Variant Value', 'Quantity',
            'Purchase Status', 'Payment Status',
        ];

        $rows = [
            ['Necklace', 'Short Necklace (R)', 'Short Necklace Regular', 'BAR001', '100', '2.5', '4.125', '4.575', 'F', 'N', 'Arihant Tools', '', '', '100', 'Approve', 'Pending'],
            ['', 'Short Necklace (A)', 'Short Necklace Antique', 'BAR002', '110', '2.5', '4.125', '4.575', 'F', 'N', 'Arihant Tools', '', '', '80', 'Approve', 'Pending'],
            ['', 'Long Necklace (R)', 'Long Necklace Regular', 'BAR003', '150', '2.5', '4.125', '4.575', 'T', 'V', 'Balaji Electroplaters', 'Color', 'Gold', '40', 'Approve', 'Pending'],
            ['', '', '', '', '', '', '', '', '', '', '', '', 'Rose Gold', '20', 'Approve', 'Paid'],
            ['Bangles & Kada', 'Bangal (R)', 'Bangal Regular', 'BAR004', '90', '2.5', '4.125', '4.575', 'F', 'V', 'Arihant Tools', 'Size', '2.6', '120', 'Approve', 'Paid'],
            ['', '', '', '', '', '', '', '', '', '', 'Star Platers', 'Size', '3.2', '30', 'Approve', 'Pending'],
            ['Rings', 'Fancy Ring', 'Fancy Ring Combo', 'BAR005', '120', '2.5', '4.125', '4.575', 'F', 'V', 'Arihant Tools', 'Color', 'Gold', '25', 'Approve', 'Pending'],
            ['', '', '', '', '', '', '', '', '', '', '', '', 'Rose Gold', '15', 'Approve', 'Pending'],
            ['', '', '', '', '', '', '', '', '', '', '', 'Size', '2.2', '10', 'Approve', 'Pending'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '2.4', '10', 'Approve', 'Pending'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchases');

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
            'Content-Disposition' => 'attachment; filename="sample_purchase_import.xlsx"',
        ];

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }
}
