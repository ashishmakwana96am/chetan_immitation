<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\FetchesPexelsJewelryPhotos;
use App\Models\Category;
use Illuminate\Console\Command;

class GenerateCategoryImages extends Command
{
    use FetchesPexelsJewelryPhotos;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:generate-images
        {--force : Regenerate images even for categories that already have one}
        {--suffix= : Extra search words appended to each category name (default: "imitation jewelry")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download real imitation-jewelry stock photos (via Pexels) for categories missing an image';

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

        $query = Category::query();

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('image')->orWhere('image', '');
            });
        }

        $categories = $query->get();

        if ($categories->isEmpty()) {
            $this->info('All categories already have an image. Nothing to do.');

            return Command::SUCCESS;
        }

        $this->info("Found {$categories->count()} category(ies) needing an image.");

        $destinationDir = public_path('uploads/categories');
        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $suffix = $this->option('suffix') ?: 'imitation jewelry';

        $bar = $this->output->createProgressBar($categories->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($categories as $category) {
            $bar->clear();

            $searchTerms = array_unique(array_filter([
                trim($category->name.' '.$suffix),
                trim($suffix),
                'imitation jewelry product photography',
                'fashion jewelry flatlay',
            ]));

            $photoUrls = $this->searchJewelryPhotosBroadening($apiKey, $searchTerms, 1);

            if (empty($photoUrls)) {
                $this->warn("No real jewelry photo found for \"{$category->name}\". Skipping.");
                $failCount++;
                $bar->advance();
                $bar->display();

                continue;
            }

            $relativePath = $this->downloadPhotoTo($photoUrls[0], $destinationDir, 'categories');

            if (! $relativePath) {
                $this->warn("Failed to download image for \"{$category->name}\".");
                $failCount++;
                $bar->advance();
                $bar->display();

                continue;
            }

            $category->image = $relativePath;
            $category->save();

            $this->line("Saved image for \"{$category->name}\" -> {$relativePath}");
            $successCount++;

            // Stay comfortably under Pexels' rate limit (200 requests/hour).
            usleep(300000);

            $bar->advance();
            $bar->display();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Category image generation completed!');
        $this->info("Success: {$successCount} categories");
        if ($failCount > 0) {
            $this->error("Failed/skipped: {$failCount} categories");
        }

        return Command::SUCCESS;
    }
}
