<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
            'history'  => $result['history'],
        ]);
    }

    public function sample()
    {
        $this->authorize('create products');

        $columns = [
            'Category', 'Sub Category', 'Product Name', 'Barcode', 'Product Code',
            'Purchase Price', 'Sale Price', 'MRP', 'Pair Product', 'Pair Sizes',
            'Product Type', 'Product Variant', 'Product Variant Value',
        ];

        $rows = [
            // Normal products — Pair Sizes is blank
            ['Necklace', 'Short Necklace (R)', 'Short Necklace Regular',        'BAR001', '100', '250', '415', '460', 'F', '',    'N', '',           ''],
            ['',         'Short Necklace (A)', 'Short Necklace Antique',         'BAR002', '110', '275', '455', '505', 'F', '',    'N', '',           ''],
            // Pair product with sizes 2 & 4 — prices auto-calculated (proportional to size)
            ['',         'Long Necklace (R)',  'Long Necklace Regular (Pair)',   'BAR003', '150', '375', '620', '685', 'T', '2,4', 'V', 'Color',      'Gold,Rose Gold'],
            // Pair product with single size
            ['',         'Long Necklace (A)',  'Long Necklace Antique (Pair)',   'BAR006', '180', '450', '745', '825', 'T', '2',   'N', '',           ''],
            // Normal variable products
            ['Bangles & Kada', 'Bangal (R)',   'Bangal Regular',                'BAR004',  '90', '225', '370', '410', 'F', '',    'V', 'Size',        '2.6,3.2,3.5'],
            ['Rings',    'Fancy Ring',          'Fancy Ring Combo',              'BAR005', '120', '300', '495', '550', 'F', '',    'V', 'Color,Size',  'Gold,Rose Gold|2.2,2.4'],
        ];

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Products ──────────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($rows,    null, 'A2');

        $colCount   = count($columns);
        $lastColumn = Coordinate::stringFromColumnIndex($colCount);
        $dataEndRow = 1 + count($rows);

        // Header row styling
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto-size columns
        for ($i = 1; $i <= $colCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }


        // Borders
        $sheet->getStyle('A1:' . $lastColumn . $dataEndRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ── Sheet 2: Instructions ──────────────────────────────────────────
        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Instructions');

        $instructions = [
            ['Column',            'Required?', 'Description'],
            ['Category',          'Yes',       'Category name. Leave blank to inherit from the row above (for merged cells).'],
            ['Sub Category',      'No',        'Sub-category name.'],
            ['Product Name',      'Yes',       'Full product name. A new product group starts on each row that has a name.'],
            ['Barcode',           'Yes',       'Unique barcode/SKU.'],
            ['Product Code',      'Yes',       'Numeric base code used to calculate prices (e.g. 100).'],
            ['Purchase Price',    'No',        'Optional direct purchase price.'],
            ['Sale Price',        'No',        'Optional direct sale price.'],
            ['MRP',               'No',        'Optional direct MRP.'],
            ['Pair Product',      'No',        'T / TRUE / 1 / YES = pair product. Anything else = normal.'],
            ['Pair Sizes',        'No',        'Comma-separated sizes for pair products (e.g. 2,4). Prices auto-calculated — leave blank for non-pair products.'],
            ['Product Type',      'No',        'N = Normal, V = Variable.'],
            ['Product Variant',   'No',        'Comma-separated attribute names (e.g. Color,Size).'],
            ['Product Variant Value', 'No',    'Pipe-separated value groups matching each attribute (e.g. Gold,Rose Gold|2.2,2.4).'],
        ];

        $notes->fromArray($instructions, null, 'A1');

        $notes->getStyle('A1:C1')->getFont()->setBold(true);
        $notes->getStyle('A1:C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        foreach (['A', 'B', 'C'] as $col) {
            $notes->getColumnDimension($col)->setAutoSize(true);
        }
        $notes->getStyle('A1:C' . count($instructions))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Wrap text in description column
        $notes->getStyle('C1:C' . count($instructions))->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        $headers = [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="sample_product_import.xlsx"',
        ];

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }
}
