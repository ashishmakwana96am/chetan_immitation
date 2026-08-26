<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OrganizeProductImagesByBarcode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:organize-barcode-images
                            {--barcode= : Filter and organize images for a single product by barcode}
                            {--dry-run : Simulate the migration without moving files or updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Organizes existing product images into product barcode subfolders (uploads/products/{barcode}/) and updates image_path in database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $barcodeFilter = $this->option('barcode');

        if ($isDryRun) {
            $this->info('Running in DRY-RUN mode. No files will be moved or database records updated.');
        }

        if ($barcodeFilter) {
            $this->info("Filtering for product barcode: {$barcodeFilter}");
        }

        $baseUploadDir = public_path('uploads');

        $this->info('Fetching product images...');

        $images = ProductImage::query()
            ->join('products', 'product_images.product_id', '=', 'products.id')
            ->whereNull('product_images.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNotNull('products.barcode')
            ->where('products.barcode', '!=', '')
            ->when($barcodeFilter, function ($query, $barcode) {
                $query->where('products.barcode', trim($barcode));
            })
            ->select('product_images.*', 'products.barcode as product_barcode')
            ->get();

        if ($images->isEmpty()) {
            $this->info('No product images found to organize.');
            return Command::SUCCESS;
        }

        $this->info("Found {$images->count()} product image record(s).");

        $movedCount = 0;
        $updatedDbCount = 0;
        $missingFilesCount = 0;

        $bar = $this->output->createProgressBar($images->count());
        $bar->start();

        foreach ($images as $img) {
            $currentRelativePath = ltrim(str_replace('\\', '/', $img->image_path), '/');
            $barcode = trim($img->product_barcode);

            if (empty($barcode)) {
                $bar->advance();
                continue;
            }

            $currentFullPath = $baseUploadDir . '/' . $currentRelativePath;
            $filename = basename($currentRelativePath);

            $targetRelativePath = "products/{$barcode}/{$filename}";
            $targetFullPath = $baseUploadDir . '/' . $targetRelativePath;

            $alreadyInFolder = str_starts_with($currentRelativePath, "products/{$barcode}/");

            // Case 1: Image is on disk at current location but needs moving to barcode folder
            if (File::exists($currentFullPath) && !$alreadyInFolder) {
                if (!$isDryRun) {
                    File::ensureDirectoryExists(dirname($targetFullPath));
                    
                    // If target file already exists, avoid overwriting blindly or create unique
                    if (File::exists($targetFullPath) && $currentFullPath !== $targetFullPath) {
                        $filename = time() . '_' . uniqid() . '_' . $filename;
                        $targetRelativePath = "products/{$barcode}/{$filename}";
                        $targetFullPath = $baseUploadDir . '/' . $targetRelativePath;
                    }

                    File::move($currentFullPath, $targetFullPath);
                }
                $movedCount++;
            } elseif (!File::exists($currentFullPath) && File::exists($targetFullPath)) {
                // Already moved physically, needs DB path fix
            } elseif (!File::exists($currentFullPath) && !File::exists($targetFullPath)) {
                // Search in legacy root uploads/products/{filename}
                $legacyFullPath = $baseUploadDir . '/products/' . $filename;
                if (File::exists($legacyFullPath)) {
                    if (!$isDryRun) {
                        File::ensureDirectoryExists(dirname($targetFullPath));
                        File::move($legacyFullPath, $targetFullPath);
                    }
                    $movedCount++;
                } else {
                    $missingFilesCount++;
                }
            }

            // Update DB record if image path changed
            if ($img->image_path !== $targetRelativePath) {
                if (!$isDryRun) {
                    DB::table('product_images')
                        ->where('id', $img->id)
                        ->update(['image_path' => $targetRelativePath, 'updated_at' => now()]);
                }
                $updatedDbCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration completed!");
        $this->info("Files moved to barcode subfolders: {$movedCount}");
        $this->info("Database records updated: {$updatedDbCount}");
        if ($missingFilesCount > 0) {
            $this->warn("Missing image files (not found on disk): {$missingFilesCount}");
        }

        return Command::SUCCESS;
    }
}
