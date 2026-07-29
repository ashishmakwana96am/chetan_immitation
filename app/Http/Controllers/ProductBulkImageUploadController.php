<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ProductBulkImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ProductBulkImageUploadController extends Controller
{
    public function form()
    {
        $this->authorize('bulk upload product images');

        return view('products.bulk_image_upload');
    }

    public function store(Request $request, ProductBulkImageUploadService $service)
    {
        $this->authorize('bulk upload product images');

        $request->validate([
            'zip_file' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ]);

        // Large ZIPs (thousands of images) can take a while to process synchronously.
        set_time_limit(0);

        try {
            $result = $service->process($request->file('zip_file'), auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Bulk product image upload failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An unexpected error occurred while processing the ZIP file.',
            ], 500);
        }

        ActivityLogger::log(
            'Product',
            'bulk_image_upload',
            null,
            null,
            $result['summary'],
            "Bulk image upload: {$result['summary']['matched']} matched / {$result['summary']['not_found']} not found"
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'Bulk Product Image Upload Completed Successfully.',
            'summary'  => $result['summary'],
            'failures' => $result['failures'],
            'history'  => $result['history'],
        ]);
    }

    public function sample()
    {
        $this->authorize('bulk upload product images');

        $tmpDir = storage_path('app/private/tmp');
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $tmpFile = $tmpDir . '/sample_product_image_upload_' . uniqid() . '.zip';

        $sampleFiles = [
            '100001_1.jpg',
            '100001_2.jpg',
            '100001_3.jpg',
            '100002_1.jpg',
            '100003_1.jpg',
            '100003_2.jpg',
        ];

        // Generate a real, valid JPEG image stream
        $imageBytes = null;
        if (function_exists('imagecreatetruecolor')) {
            ob_start();
            $im = imagecreatetruecolor(400, 400);
            $bg = imagecolorallocate($im, 240, 243, 246);
            $textColor = imagecolorallocate($im, 100, 116, 139);
            imagefill($im, 0, 0, $bg);
            imagestring($im, 5, 140, 190, "Sample Image", $textColor);
            imagejpeg($im, null, 90);
            imagedestroy($im);
            $imageBytes = ob_get_clean();
        }

        if (!$imageBytes) {
            // Fallback valid minimal JPEG binary
            $imageBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        }

        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($sampleFiles as $file) {
            $zip->addFromString($file, $imageBytes);
        }

        $zip->close();

        return response()->download($tmpFile, 'Sample_Product_Image_Upload.zip')->deleteFileAfterSend(true);
    }
}
