<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestructureProductImagesToBarcodeFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:restructure-images {--dry-run : Test run without moving files or updating DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move existing product images into barcode-wise subfolders and update database paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('--- DRY RUN MODE: No files will be moved, no DB records updated ---');
        }

        $this->info('Fetching product images with associated product barcodes...');

        $images = ProductImage::with('product')->get();

        if ($images->isEmpty()) {
            $this->info('No product images found in the database.');
            return Command::SUCCESS;
        }

        $baseUploadDir = public_path('uploads');

        $movedCount = 0;
        $alreadyInStructureCount = 0;
        $skippedNoBarcode = 0;
        $missingFilesCount = 0;

        $bar = $this->output->createProgressBar($images->count());
        $bar->start();

        foreach ($images as $image) {
            $bar->advance();

            $product = $image->product;
            if (!$product || empty($product->barcode)) {
                $skippedNoBarcode++;
                continue;
            }

            $barcode = trim($product->barcode);
            $currentRelativePath = ltrim(str_replace('\\', '/', $image->image_path), '/');

            // Expected new relative path: products/{barcode}/{filename}
            $filename = basename($currentRelativePath);
            $expectedRelativePath = "products/{$barcode}/{$filename}";

            // If already in correct path structure
            if ($currentRelativePath === $expectedRelativePath) {
                $alreadyInStructureCount++;
                continue;
            }

            $currentFullPath = $baseUploadDir . '/' . $currentRelativePath;
            $targetDir = $baseUploadDir . '/products/' . $barcode;
            $targetFullPath = $targetDir . '/' . $filename;

            // Check if current file exists
            if (!File::exists($currentFullPath)) {
                // Check if target file already exists (maybe file was moved previously)
                if (File::exists($targetFullPath)) {
                    if (!$dryRun) {
                        $image->image_path = $expectedRelativePath;
                        $image->save();
                    }
                    $movedCount++;
                } else {
                    $missingFilesCount++;
                }
                continue;
            }

            if (!$dryRun) {
                // Ensure target directory exists
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true, true);
                }

                // Move file
                File::move($currentFullPath, $targetFullPath);

                // Update database
                $image->image_path = $expectedRelativePath;
                $image->save();
            }

            $movedCount++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Restructuring Summary:");
        $this->line(" - Images Moved / Path Updated : {$movedCount}");
        $this->line(" - Already in Barcode Structure : {$alreadyInStructureCount}");
        $this->line(" - Skipped (No Barcode/Product): {$skippedNoBarcode}");
        $this->line(" - Missing Physical Files     : {$missingFilesCount}");

        if ($dryRun) {
            $this->warn('DRY RUN Completed. Run without --dry-run to apply changes.');
        } else {
            $this->info('Product images restructured successfully!');
        }

        return Command::SUCCESS;
    }
}
