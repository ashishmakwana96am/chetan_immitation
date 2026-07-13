<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubCategory;

/**
 * Shared "find or create" product logic used by every bulk-import flow
 * (Purchase Import, Product Import): resolves Category/SubCategory, applies
 * the same pricing formulas as the manual create-product screen (including
 * Pair Product pricing), and creates variant dimensions/combinations.
 */
class ProductCreationService
{
    private const CHUNK_SIZE = 500;

    /**
     * @return array<string, Product> barcode => Product
     */
    public function lookupProducts(array $barcodes): array
    {
        $byBarcode = [];
        foreach (array_chunk($barcodes, self::CHUNK_SIZE) as $chunk) {
            Product::withTrashed()->whereIn('barcode', $chunk)->get()
                ->each(function (Product $p) use (&$byBarcode) {
                    $byBarcode[$p->barcode] = $p;
                });
        }

        return $byBarcode;
    }

    /**
     * @param array $group Must contain: category_name, sub_category_name, product_name,
     *                      barcode, product_code, purchase_multiplier, sale_multiplier,
     *                      mrp_multiplier, pair_product, product_type, dimensions.
     * @param array $summary Reference; increments 'categories_created'/'sub_categories_created' if present.
     */
    public function create(array $group, array &$summary, ?int $userId): Product
    {
        if ($group['category_name'] === '') {
            throw new \RuntimeException('Missing Category');
        }

        if (!is_numeric($group['product_code']) || (float) $group['product_code'] <= 0) {
            throw new \RuntimeException('Invalid Product Code');
        }

        $category = Category::withTrashed()->firstOrCreate(
            ['name' => $group['category_name']],
            ['slug' => generate_slug(Category::class, $group['category_name']), 'status' => 1, 'created_by' => $userId]
        );
        if ($category->wasRecentlyCreated) {
            $summary['categories_created'] = ($summary['categories_created'] ?? 0) + 1;
        }
        if ($category->trashed()) {
            $category->restore();
            $category->update(['image' => null, 'low_stock_threshold' => null]);
        }

        $subCategoryId = null;
        if ($group['sub_category_name'] !== '') {
            $subCategory = SubCategory::withTrashed()->firstOrCreate(
                ['category_id' => $category->id, 'name' => $group['sub_category_name']],
                ['slug' => generate_slug(SubCategory::class, $group['sub_category_name']), 'status' => 1, 'created_by' => $userId]
            );
            if ($subCategory->wasRecentlyCreated) {
                $summary['sub_categories_created'] = ($summary['sub_categories_created'] ?? 0) + 1;
            }
            if ($subCategory->trashed()) {
                $subCategory->restore();
            }
            $subCategoryId = $subCategory->id;
        }

        $purchaseMultiplier = is_numeric($group['purchase_multiplier']) ? (float) $group['purchase_multiplier'] : 2.5;
        $saleMultiplier = is_numeric($group['sale_multiplier']) ? (float) $group['sale_multiplier'] : 4.125;
        $mrpMultiplier = is_numeric($group['mrp_multiplier']) ? (float) $group['mrp_multiplier'] : 4.575;
        $code = (float) $group['product_code'];
        $isPair = $group['pair_product'];

        $purchasePrice = $code * $purchaseMultiplier;

        if ($isPair) {
            $salePrice = $this->roundToNearest5(($code / 2) * $saleMultiplier);
            $mrp = $this->roundToNearest5(($code / 2) * $mrpMultiplier);
            $pairSalePrice = $this->roundToNearest5($code * $saleMultiplier);
            $pairMrp = $this->roundToNearest5($code * $mrpMultiplier);
        } else {
            $salePrice = $this->roundToNearest5($code * $saleMultiplier);
            $mrp = $this->roundToNearest5($code * $mrpMultiplier);
            $pairSalePrice = null;
            $pairMrp = null;
        }

        $defaultDescription = '<p>Classic Silver Tone Adjustable Tennis Bracelet crafted with premium brass and sparkling American Diamonds. Lightweight, skin-friendly and perfect for everyday wear as well as weddings, parties and festive occasions. Elegant finish with an adjustable chain ensures a comfortable fit for every wrist.</p>';
        $defaultInfo = '<ul><li>Premium Quality Brass</li><li>Silver Tone Finish</li><li>Studded with American Diamonds</li><li>Adjustable Chain</li><li>Lightweight Design</li><li>Comfortable for Daily Wear</li><li>Tarnish Resistant Finish</li><li>Elegant Party Wear Bracelet</li></ul>';
        $defaultHighlights = '<p>✓ Premium Finish</p><p>✓ Adjustable Size</p><p>✓ Lightweight</p><p>✓ Skin Friendly</p><p>✓ Anti Tarnish</p><p>✓ Sparkling American Diamonds</p><p>✓ Luxury Look</p><p>✓ Perfect Gift</p>';

        $product = Product::create([
            'name'                   => $group['product_name'],
            'slug'                   => generate_slug(Product::class, $group['product_name']),
            'category_id'            => $category->id,
            'sub_category_id'        => $subCategoryId,
            'barcode'                => $group['barcode'],
            'product_code'           => $code,
            'purchase_multiplier'    => $purchaseMultiplier,
            'sale_multiplier'        => $saleMultiplier,
            'mrp_multiplier'         => $mrpMultiplier,
            'description'            => $defaultDescription,
            'additional_information' => $defaultInfo,
            'product_highlights'     => $defaultHighlights,
            'purchase_price'         => $purchasePrice,
            'sale_price'             => $salePrice,
            'mrp'                    => $mrp,
            'pair_product'           => $isPair,
            'pair_sale_price'        => $pairSalePrice,
            'pair_mrp'               => $pairMrp,
            'type'                   => $group['product_type'],
            'status'                 => Product::STATUS_ACTIVE,
            'created_by'             => $userId,
            'sort_order'             => ((int) Product::max('sort_order')) + 1,
        ]);

        if ($group['product_type'] === 'variable') {
            $this->createVariants($group, $product, $purchasePrice, $salePrice);
        }

        return $product;
    }

    private function createVariants(array $group, Product $product, float $purchasePrice, float $salePrice): void
    {
        $dimensions = array_values($group['dimensions']);

        if (empty($dimensions)) {
            throw new \RuntimeException('Missing Variant Data');
        }

        if (count($dimensions) === 1) {
            $attributeName = $dimensions[0]['name'];
            $attrValues = $dimensions[0]['values'];
        } else {
            $attributeName = implode(' / ', array_map(fn ($d) => $d['name'], $dimensions));
            $attrValues = [''];
            foreach ($dimensions as $dim) {
                $next = [];
                foreach ($attrValues as $existing) {
                    foreach ($dim['values'] as $val) {
                        $next[] = $existing === '' ? $val : $existing . ' - ' . $val;
                    }
                }
                $attrValues = $next;
            }
        }

        $attribute = Attribute::withTrashed()->firstOrCreate(
            ['name' => $attributeName],
            ['slug' => generate_slug(Attribute::class, $attributeName), 'status' => Attribute::STATUS_ACTIVE, 'created_by' => $product->created_by, 'sort_order' => ((int) Attribute::max('sort_order')) + 1]
        );
        if ($attribute->trashed()) {
            $attribute->restore();
        }

        foreach ($attrValues as $val) {
            $attributeValue = AttributeValue::withTrashed()->firstOrCreate([
                'attribute_id' => $attribute->id,
                'value'        => $val,
            ]);
            if ($attributeValue->trashed()) {
                $attributeValue->restore();
            }

            ProductVariant::create([
                'product_id'         => $product->id,
                'attribute_value_id' => $attributeValue->id,
                'purchase_price'     => $purchasePrice,
                'sale_price'         => $salePrice,
                'status'             => ProductVariant::STATUS_ACTIVE,
            ]);
        }
    }

    private function roundToNearest5($val): float
    {
        return ceil(floatval($val) / 5) * 5;
    }
}
