<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixBrokenImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:fix-broken {--force : Re-fix all images regardless of existence}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds categories and products with missing or broken image files on disk, and automatically generates/attaches a valid placeholder image.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for broken or missing category and product images...');

        $uploadsDir = public_path('uploads');
        $categoriesDir = public_path('uploads/categories');
        $productsDir = public_path('uploads/products');

        File::ensureDirectoryExists($uploadsDir);
        File::ensureDirectoryExists($categoriesDir);
        File::ensureDirectoryExists($productsDir);

        $sampleImagePath = public_path('website/assets/images/placeholder.png');
        if (!File::exists($sampleImagePath)) {
            $sampleImagePath = public_path('uploads/placeholder.jpg');
            $this->createDefaultPlaceholder($sampleImagePath);
        }

        // 1. Fix Categories
        $categories = Category::all();
        $fixedCategoriesCount = 0;

        foreach ($categories as $category) {
            $hasValidImage = !empty($category->image) && File::exists(public_path('uploads/' . $category->image));

            if (!$hasValidImage || $this->option('force')) {
                $filename = 'categories/cat_' . $category->id . '_' . time() . '.jpg';
                $destPath = public_path('uploads/' . $filename);

                $this->createPlaceholderForName($category->name, $destPath, $sampleImagePath);

                $category->image = $filename;
                $category->save();
                $fixedCategoriesCount++;
            }
        }

        $this->info("Fixed {$fixedCategoriesCount} category image(s).");

        // 2. Fix Products
        $products = Product::with('images')->get();
        $fallbackUserId = User::orderBy('id')->value('id') ?? 1;
        $fixedProductsCount = 0;

        foreach ($products as $product) {
            $images = $product->images;
            $hasValidPrimary = false;

            if ($images->isNotEmpty()) {
                foreach ($images as $img) {
                    $filePath = public_path('uploads/' . $img->image_path);
                    if (!File::exists($filePath) || filesize($filePath) === 0 || $this->option('force')) {
                        File::ensureDirectoryExists(dirname($filePath));
                        $this->createPlaceholderForName($product->name, $filePath, $sampleImagePath);
                        $fixedProductsCount++;
                    }
                    if ($img->is_primary && File::exists($filePath)) {
                        $hasValidPrimary = true;
                    }
                }
            }

            if (!$hasValidPrimary) {
                $filename = 'products/prod_' . $product->id . '_' . time() . '.jpg';
                $destPath = public_path('uploads/' . $filename);

                $this->createPlaceholderForName($product->name, $destPath, $sampleImagePath);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $filename,
                    'is_primary' => true,
                    'created_by' => $product->created_by ?: $fallbackUserId,
                ]);

                $fixedProductsCount++;
            }
        }

        $this->info("Fixed/Generated images for {$fixedProductsCount} product(s).");
        $this->info("All broken category and product images have been fixed successfully!");

        return Command::SUCCESS;
    }

    private function createPlaceholderForName(string $name, string $destPath, string $fallbackPath): void
    {
        File::ensureDirectoryExists(dirname($destPath));

        if (function_exists('imagecreatetruecolor')) {
            $width = 500;
            $height = 500;
            $im = imagecreatetruecolor($width, $height);

            $bg = imagecolorallocate($im, 245, 245, 247);
            imagefill($im, 0, 0, $bg);

            $border = imagecolorallocate($im, 220, 220, 225);
            imagerectangle($im, 0, 0, $width - 1, $height - 1, $border);

            $textColor = imagecolorallocate($im, 80, 80, 90);
            $cleanName = mb_substr(trim($name), 0, 25);
            $font = 5;

            $textWidth = imagefontwidth($font) * strlen($cleanName);
            $textHeight = imagefontheight($font);

            $x = (int) max(10, ($width - $textWidth) / 2);
            $y = (int) max(10, ($height - $textHeight) / 2);

            imagestring($im, $font, $x, $y, $cleanName, $textColor);

            imagejpeg($im, $destPath, 90);
            imagedestroy($im);
            return;
        }

        File::copy($fallbackPath, $destPath);
    }

    private function createDefaultPlaceholder(string $destPath): void
    {
        File::ensureDirectoryExists(dirname($destPath));
        if (function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor(400, 400);
            $bg = imagecolorallocate($im, 240, 240, 240);
            imagefill($im, 0, 0, $bg);
            imagejpeg($im, $destPath);
            imagedestroy($im);
        }
    }
}
