<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateBarcodesForExistingProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-barcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate barcodes for existing products that do not have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting barcode generation for existing products...');

        $productsWithoutBarcode = Product::whereNull('barcode')->get();

        if ($productsWithoutBarcode->isEmpty()) {
            $this->info('All products already have barcodes. No action needed.');
            return Command::SUCCESS;
        }

        $this->info("Found {$productsWithoutBarcode->count()} products without barcodes.");

        $bar = $this->output->createProgressBar($productsWithoutBarcode->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($productsWithoutBarcode as $product) {
            try {
                $barcode = Product::generateUniqueBarcode();
                $product->barcode = $barcode;
                $product->save();
                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to generate barcode for product ID {$product->id}: {$e->getMessage()}");
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Barcode generation completed!");
        $this->info("Success: {$successCount} products");
        if ($failCount > 0) {
            $this->error("Failed: {$failCount} products");
        }

        return Command::SUCCESS;
    }
}
