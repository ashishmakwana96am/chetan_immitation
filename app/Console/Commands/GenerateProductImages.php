<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\FetchesPexelsJewelryPhotos;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateProductImages extends Command
{
    use FetchesPexelsJewelryPhotos;

    protected const ADDITIONAL_COUNT = 5;

    protected const MIN_REQUIRED = 1; // at least a primary image — 0 is never acceptable

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-images
        {--force : Regenerate images even for products that already have some (replaces existing ones)}
        {--suffix= : Extra search words appended to each product search (default: "imitation jewelry")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download real imitation-jewelry stock photos (via Pexels) for products missing images: 1 primary + up to 5 additional';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('services.pexels.key');

        if (empty($apiKey)) {
            $this->error('PEXELS_API_KEY is not set. Get a free key at https://www.pexels.com/api/ and add it to your .env file.');

            return Command::FAILURE;
        }

        $query = Product::query()->with(['category', 'subCategory']);

        if (! $this->option('force')) {
            $query->whereDoesntHave('images');
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('All products already have images. Nothing to do.');

            return Command::SUCCESS;
        }

        $this->info("Found {$products->count()} product(s) needing images.");

        $destinationDir = public_path('uploads/products');
        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $suffix = $this->option('suffix') ?: 'imitation jewelry';
        $fallbackUserId = User::query()->orderBy('id')->value('id');

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($products as $product) {
            $bar->clear();

            $createdBy = $product->created_by ?: $fallbackUserId;

            if (! $createdBy) {
                $this->warn("Skipping \"{$product->name}\": no user found to attribute the image to.");
                $failCount++;
                $bar->advance();
                $bar->display();

                continue;
            }

            $categoryName = $product->category->name ?? ($product->subCategory->name ?? '');

            // Broaden progressively: specific product+category search first,
            // then category-only, then product-only, then increasingly
            // generic jewelry searches. The last tier is broad enough that
            // finding zero images should never happen in practice — but if
            // it somehow does, we still skip rather than save a bad image.
            $searchTerms = array_unique(array_filter([
                trim($product->name.' '.$categoryName.' '.$suffix),
                trim($categoryName.' '.$suffix),
                trim($product->name.' '.$suffix),
                trim($suffix),
                'imitation jewelry product photography',
                'fashion jewelry flatlay',
                'jewelry',
            ]));

            $photoUrls = $this->searchJewelryPhotosBroadening($apiKey, $searchTerms, 1 + self::ADDITIONAL_COUNT);

            if (count($photoUrls) < self::MIN_REQUIRED) {
                $this->warn("Could not find any real jewelry photo for \"{$product->name}\". Skipping.");
                $failCount++;
                $bar->advance();
                $bar->display();

                continue;
            }

            $savedCount = 0;

            try {
                DB::transaction(function () use ($product, $photoUrls, $destinationDir, $createdBy, &$savedCount) {
                    if ($this->option('force')) {
                        $product->images()->delete();
                    }

                    foreach ($photoUrls as $index => $url) {
                        $relativePath = $this->downloadPhotoTo($url, $destinationDir, 'products');

                        if (! $relativePath) {
                            continue;
                        }

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $relativePath,
                            'is_primary' => $index === 0,
                            'created_by' => $createdBy,
                        ]);

                        $savedCount++;
                    }
                });
            } catch (\Throwable $e) {
                $this->warn("Failed to save images for \"{$product->name}\": {$e->getMessage()}");
                $failCount++;
                $bar->advance();
                $bar->display();

                continue;
            }

            if ($savedCount === 0) {
                $this->warn("Failed to download any image for \"{$product->name}\".");
                $failCount++;
            } else {
                $this->line("Saved {$savedCount} image(s) for \"{$product->name}\" (1 primary + ".($savedCount - 1).' additional)');
                $successCount++;
            }

            // Only the /search call counts against Pexels' rate limit; stay comfortably under it.
            usleep(300000);

            $bar->advance();
            $bar->display();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Product image generation completed!');
        $this->info("Success: {$successCount} products");
        if ($failCount > 0) {
            $this->error("Failed/skipped: {$failCount} products");
        }

        return Command::SUCCESS;
    }
}
