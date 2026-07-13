<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ProductBulkImageUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    // Matches the per-image cap used by ProductController::saveBase64Image
    private const MAX_IMAGE_BYTES = 52428800;

    private const CHUNK_SIZE = 500;

    /**
     * Extract the ZIP, match each top-level folder to a Product by barcode, and
     * save images through the same storage convention as the manual upload flow.
     */
    public function process(UploadedFile $zipFile, ?int $userId): array
    {
        $summary = [
            'total_folders'    => 0,
            'matched'          => 0,
            'not_found'        => 0,
            'primary_added'    => 0,
            'additional_added' => 0,
            'failed_images'    => 0,
            'skipped_files'    => 0,
        ];
        $failures = [];

        $zip = new ZipArchive();
        $opened = $zip->open($zipFile->getRealPath());

        if ($opened !== true) {
            throw new \RuntimeException('The uploaded file is not a valid ZIP archive.');
        }

        if ($zip->numFiles === 0) {
            $zip->close();
            throw new \RuntimeException('The uploaded ZIP file is empty.');
        }

        [$folders, $seenFolders, $summary['skipped_files']] = $this->scanEntries($zip);

        $allBarcodes = array_keys($seenFolders);
        $summary['total_folders'] = count($allBarcodes);

        if ($summary['total_folders'] === 0) {
            $zip->close();
            throw new \RuntimeException('The ZIP file does not contain any barcode folders.');
        }

        $productsByBarcode = $this->lookupProducts($allBarcodes);
        $hasPrimary = $this->lookupExistingPrimaryFlags($productsByBarcode);

        $destDir = public_path('uploads/products');
        if (!file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $history = [];
        foreach ($allBarcodes as $barcode) {
            $entries = $folders[$barcode] ?? [];
            $product = $productsByBarcode[$barcode] ?? null;

            if (!$product) {
                $summary['not_found']++;
                $failures[] = ['barcode' => $barcode, 'status' => 'Failed', 'reason' => 'Product Not Found'];
                $history[] = [
                    'barcode' => $barcode,
                    'status'  => 'Failed',
                    'reason'  => 'Product Not Found',
                    'details' => 'No product exists with this barcode.'
                ];
                continue;
            }

            $summary['matched']++;

            if (empty($entries)) {
                $failures[] = ['barcode' => $barcode, 'status' => 'Failed', 'reason' => 'Empty Folder'];
                $history[] = [
                    'barcode' => $barcode,
                    'status'  => 'Failed',
                    'reason'  => 'Empty Folder',
                    'details' => 'The folder contains no supported image files.'
                ];
                continue;
            }

            natsort($entries);

            $result = $this->processFolder($zip, $entries, $product, $destDir, $userId, !isset($hasPrimary[$product->id]));

            if ($result === null) {
                // Whole folder failed with an unexpected error; already logged inside processFolder.
                $failures[] = ['barcode' => $barcode, 'status' => 'Failed', 'reason' => 'Unexpected Error'];
                $history[] = [
                    'barcode' => $barcode,
                    'status'  => 'Failed',
                    'reason'  => 'Unexpected Error',
                    'details' => 'An unexpected error occurred while saving the images.'
                ];
                continue;
            }

            $summary['primary_added']    += $result['primary_added'];
            $summary['additional_added'] += $result['additional_added'];
            $summary['failed_images']    += $result['failed_images'];

            if (!$result['saved_any']) {
                $reason = $result['failed_images'] > 0 ? 'Corrupted Image' : 'Invalid Image';
                $failures[] = ['barcode' => $barcode, 'status' => 'Failed', 'reason' => $reason];
                $history[] = [
                    'barcode' => $barcode,
                    'status'  => 'Failed',
                    'reason'  => $reason,
                    'details' => 'No images could be saved from the folder.'
                ];
            } else {
                $details = [];
                if ($result['primary_added'] > 0) {
                    $details[] = $result['primary_added'] . ' primary';
                }
                if ($result['additional_added'] > 0) {
                    $details[] = $result['additional_added'] . ' additional';
                }
                if ($result['failed_images'] > 0) {
                    $details[] = $result['failed_images'] . ' failed';
                }
                $details_str = implode(', ', $details) . ' image(s) processed.';

                $status = 'Success';
                $reason = 'Processed';
                if ($result['failed_images'] > 0) {
                    $status = 'Warning';
                    $reason = 'Partial success';
                }

                $history[] = [
                    'barcode' => $barcode,
                    'status'  => $status,
                    'reason'  => $reason,
                    'details' => $details_str
                ];
            }
        }

        $zip->close();

        return [
            'summary'  => $summary,
            'failures' => $failures,
            'history'  => $history,
        ];
    }

    /**
     * Cheap name-only scan of every ZIP entry: group image files by their top-level
     * barcode folder, without reading any file contents yet.
     */
    private function scanEntries(ZipArchive $zip): array
    {
        $folders = [];
        $seenFolders = [];
        $skippedFiles = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $name = $stat['name'];

            if (str_starts_with($name, '__MACOSX/')) {
                continue;
            }

            // Explicit directory entry — record the folder even if it turns out empty.
            if (str_ends_with($name, '/')) {
                $trimmed = rtrim($name, '/');
                if ($trimmed !== '' && !str_contains($trimmed, '/')) {
                    $seenFolders[$trimmed] = true;
                }
                continue;
            }

            $parts = explode('/', $name, 2);
            if (count($parts) !== 2) {
                // Loose file at the ZIP root — outside the expected barcode-folder structure.
                continue;
            }

            [$barcode, $relativeFile] = $parts;
            $barcode = trim($barcode);

            if ($barcode === '' || $relativeFile === '') {
                continue;
            }

            $seenFolders[$barcode] = true;

            $baseName = basename($relativeFile);

            // Ignore hidden/system files and anything nested deeper than one level.
            if (str_contains($relativeFile, '/') || str_starts_with($baseName, '.')) {
                continue;
            }

            $extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $skippedFiles++;
                continue;
            }

            $folders[$barcode][] = $name;
        }

        return [$folders, $seenFolders, $skippedFiles];
    }

    /**
     * @return array<string, Product> barcode => Product (id, barcode only)
     */
    private function lookupProducts(array $barcodes): array
    {
        $productsByBarcode = [];

        foreach (array_chunk($barcodes, self::CHUNK_SIZE) as $chunk) {
            Product::whereIn('barcode', $chunk)->get(['id', 'barcode'])
                ->each(function (Product $product) use (&$productsByBarcode) {
                    $productsByBarcode[$product->barcode] = $product;
                });
        }

        return $productsByBarcode;
    }

    /**
     * @param  array<string, Product>  $productsByBarcode
     * @return array<int, bool> product_id => true if it already has a primary image
     */
    private function lookupExistingPrimaryFlags(array $productsByBarcode): array
    {
        $hasPrimary = [];
        $productIds = array_map(fn (Product $p) => $p->id, $productsByBarcode);

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            ProductImage::whereIn('product_id', $chunk)->where('is_primary', true)->pluck('product_id')
                ->each(function ($id) use (&$hasPrimary) {
                    $hasPrimary[$id] = true;
                });
        }

        return $hasPrimary;
    }

    /**
     * Save every valid image in one barcode folder inside a single DB transaction.
     * Returns null if the whole folder blew up unexpectedly (already rolled back),
     * otherwise per-folder counts so the caller only merges them once committed.
     */
    private function processFolder(ZipArchive $zip, array $entries, Product $product, string $destDir, ?int $userId, bool $needsPrimary): ?array
    {
        $primaryAdded = 0;
        $additionalAdded = 0;
        $failedImages = 0;
        $savedAny = false;

        try {
            DB::transaction(function () use ($zip, $entries, $product, $destDir, $userId, &$needsPrimary, &$primaryAdded, &$additionalAdded, &$failedImages, &$savedAny) {
                foreach ($entries as $entryName) {
                    $bytes = $zip->getFromName($entryName);

                    if ($bytes === false || $bytes === '' || strlen($bytes) > self::MAX_IMAGE_BYTES || @getimagesizefromstring($bytes) === false) {
                        $failedImages++;
                        continue;
                    }

                    $extension = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    file_put_contents($destDir . '/' . $filename, $bytes);

                    $isPrimary = $needsPrimary;
                    $needsPrimary = false;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => 'products/' . $filename,
                        'is_primary' => $isPrimary,
                        'created_by' => $userId,
                    ]);

                    $savedAny = true;
                    $isPrimary ? $primaryAdded++ : $additionalAdded++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('Bulk product image upload failed for barcode ' . $product->barcode . ': ' . $e->getMessage());

            return null;
        }

        return [
            'primary_added'    => $primaryAdded,
            'additional_added' => $additionalAdded,
            'failed_images'    => $failedImages,
            'saved_any'        => $savedAny,
        ];
    }
}
