<?php

namespace App\Http\Controllers;

use App\Services\PurchaseImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseImportController extends Controller
{
    private const TEMP_DISK = 'local';
    private const TEMP_DIR  = 'purchase-imports';

    /**
     * Step 1: parse + validate the Excel file and run the exact same import
     * logic as a real import, but rolled back (see PurchaseImportService::process
     * $dryRun) — nothing is persisted. Stashes the uploaded file under a token
     * so confirm() can re-run it for real without asking the user to re-upload.
     */
    public function preview(Request $request, PurchaseImportService $service)
    {
        $this->authorize('create purchases');

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        set_time_limit(0);

        try {
            $result = $service->process($request->file('excel_file'), auth()->id(), dryRun: true);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Purchase import preview failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while processing the Excel file.',
            ], 500);
        }

        $token = (string) Str::uuid();
        $request->file('excel_file')->storeAs(self::TEMP_DIR, $token . '.xlsx', self::TEMP_DISK);

        return response()->json([
            'status'            => 'success',
            'token'             => $token,
            'summary'           => $result['summary'],
            'failures'          => $result['failures'],
            'history'           => $result['history'],
            'new_products'      => $result['new_products'],
            'updated_products'  => $result['updated_products'],
            'purchases_preview' => $result['purchases_preview'],
        ]);
    }

    /**
     * Step 2: user reviewed the preview and confirmed — re-run the same file
     * for real (no dry run this time) and clean up the stashed temp file.
     */
    public function confirm(Request $request, PurchaseImportService $service)
    {
        $this->authorize('create purchases');

        $request->validate([
            'token' => ['required', 'string', 'uuid'],
        ]);

        $path = self::TEMP_DIR . '/' . $request->token . '.xlsx';
        if (!Storage::disk(self::TEMP_DISK)->exists($path)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This preview has expired. Please upload the file again.',
            ], 422);
        }

        set_time_limit(0);

        try {
            $file = new \Illuminate\Http\UploadedFile(
                Storage::disk(self::TEMP_DISK)->path($path),
                'import.xlsx',
                null,
                null,
                true
            );
            $result = $service->process($file, auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Purchase import confirm failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while processing the Excel file.',
            ], 500);
        } finally {
            Storage::disk(self::TEMP_DISK)->delete($path);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => 'Purchase Import Completed Successfully.',
            'summary'  => $result['summary'],
            'failures' => $result['failures'],
            'history'  => $result['history'],
        ]);
    }

    /**
     * User backed out of the preview — discard the stashed file instead of
     * letting it sit until the next confirm attempt (which would fail anyway
     * since it wouldn't find a matching token).
     */
    public function cancel(Request $request)
    {
        $this->authorize('create purchases');

        $request->validate([
            'token' => ['required', 'string', 'uuid'],
        ]);

        Storage::disk(self::TEMP_DISK)->delete(self::TEMP_DIR . '/' . $request->token . '.xlsx');

        return response()->json(['status' => 'success']);
    }

    public function sample()
    {
        $this->authorize('create purchases');

        $columns = [
            'Category', 'Sub Category', 'Collection Name', 'Product Name', 'Barcode', 'Product Code',
            'Purchase Price', 'Sale Price', 'MRP', 'Pair Product', 'Pair Sizes',
            'Product Type', 'Supplier Name', 'Variant', 'Variant Value', 'Quantity',
            'Purchase Status', 'Payment Status', 'Payment Method',
        ];

        $rows = [
            ['Necklace', 'Short Necklace (R)', 'NK-S', 'Short Necklace Regular', 'BAR001', '100', '250', '415', '460', 'F', '', 'N', 'Arihant Tools', '', '', '100', 'Approve', 'Pending', 'Cash'],
            ['', 'Short Necklace (A)', 'NK-A', 'Short Necklace Antique', 'BAR002', '110', '275', '455', '505', 'F', '', 'N', 'Arihant Tools', '', '', '80', 'Approve', 'Pending', 'Cash'],
            ['', 'Long Necklace (R)', 'NK-L', 'Long Necklace Regular', 'BAR003', '150', '375', '620', '685', 'T', '2,4', 'V', 'Balaji Electroplaters', 'Color', 'Gold', '40', 'Approve', 'Pending', 'Online'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Rose Gold', '20', 'Approve', 'Paid', 'Online'],
            ['Bangles & Kada', 'Bangal (R)', 'BG-R', 'Bangal Regular', 'BAR004', '90', '225', '370', '410', 'F', '', 'V', 'Arihant Tools', 'Size', '2.6', '120', 'Approve', 'Paid', 'Cash'],
            ['', '', '', '', '', '', '', '', '', '', '', '', 'Star Platers', 'Size', '3.2', '30', 'Approve', 'Pending', 'Cash'],
            ['Rings', 'Fancy Ring', 'RG-F', 'Fancy Ring Combo', 'BAR005', '120', '300', '495', '550', 'F', '', 'V', 'Arihant Tools', 'Color', 'Gold', '25', 'Approve', 'Pending', 'Cash'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Rose Gold', '15', 'Approve', 'Pending', 'Cash'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '1', 'Gold', '10', 'Approve', 'Pending', 'Cash'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchases');

        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $colCount = count($columns);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
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
