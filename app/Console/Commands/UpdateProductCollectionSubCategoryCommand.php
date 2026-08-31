<?php

namespace App\Console\Commands;

use App\Services\ProductCollectionSubCategoryUpdateService;
use Illuminate\Console\Command;

class UpdateProductCollectionSubCategoryCommand extends Command
{
    protected $signature = 'product:update-collection-subcategory 
                            {file : Path to the Excel file} 
                            {--dry-run : Perform a dry run simulation without saving any changes}';

    protected $description = 'Bulk update Product SubCategory and Collection from Excel file';

    public function handle(ProductCollectionSubCategoryUpdateService $service)
    {
        $filePath = $this->argument('file');
        $isDryRun = (bool) $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn("=======================================================");
            $this->warn("   DRY RUN MODE ACTIVE: NO DATABASE CHANGES WILL BE SAVED  ");
            $this->warn("=======================================================");
            $this->newLine();
        }

        $this->info("Processing file: {$filePath}...");

        try {
            $result = $service->processFile($filePath, null, $isDryRun);
            $summary = $result['summary'];
            $details = $result['details'];

            $this->newLine();
            $this->info("--- Update Summary " . ($isDryRun ? "(SIMULATION)" : "") . " ---");
            $this->line("Total Rows Processed: " . $summary['total_rows']);
            $this->line("Products Matched & " . ($isDryRun ? "Ready to Update" : "Updated") . ": " . $summary['products_updated']);
            $this->line("Categories " . ($isDryRun ? "Would Be Created" : "Created") . ": " . $summary['categories_created']);
            $this->line("SubCategories " . ($isDryRun ? "Would Be Created" : "Created") . ": " . $summary['sub_categories_created']);
            $this->line("Collections " . ($isDryRun ? "Would Be Created" : "Created") . ": " . $summary['collections_created']);
            $this->line("Failed Rows: " . $summary['failed_rows']);
            $this->line("Skipped Rows: " . $summary['skipped_rows']);

            if (!empty($details)) {
                $this->newLine();
                $this->info("--- Detailed Row History / Preview ---");

                $headers = ['Row', 'Barcode', 'Product Name', 'Status', 'Sub Category', 'Collection', 'Note'];
                $tableRows = [];

                foreach ($details as $d) {
                    $tableRows[] = [
                        $d['row'],
                        $d['barcode'],
                        $d['product'],
                        $d['status'],
                        $d['sub_category'] ?? '-',
                        $d['collection'] ?? '-',
                        $d['reason'],
                    ];
                }

                $this->table($headers, $tableRows);
            }

            if ($isDryRun) {
                $this->newLine();
                $this->warn("Dry run complete. 0 records were modified in the database.");
                $this->info("To apply these changes for real, run the command WITHOUT --dry-run.");
            } else {
                $this->newLine();
                $this->info("Update process completed successfully!");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to process file: " . $e->getMessage());
            return 1;
        }
    }
}
