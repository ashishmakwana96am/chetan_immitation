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
     * Restores a soft-deleted product together with everything it depends on
     * (Category, SubCategory, and — for variable products — each variant's
     * AttributeValue/Attribute) so a re-imported product doesn't end up
     * pointing at still-trashed relations.
     */
    public function restoreTrashedProduct(Product $product): void
    {
        if (!$product->trashed()) {
            return;
        }

        $product->restore();
        $product->variants()->onlyTrashed()->restore();
        $product->images()->onlyTrashed()->restore();
        $product->inventories()->onlyTrashed()->restore();

        $category = Category::withTrashed()->find($product->category_id);
        if ($category && $category->trashed()) {
            $category->restore();
        }

        if ($product->sub_category_id) {
            $subCategory = SubCategory::withTrashed()->find($product->sub_category_id);
            if ($subCategory && $subCategory->trashed()) {
                $subCategory->restore();
            }
        }

        if ($product->type === 'variable') {
            $attributeValueIds = ProductVariant::withTrashed()
                ->where('product_id', $product->id)
                ->pluck('attribute_value_id')
                ->unique();

            if ($attributeValueIds->isNotEmpty()) {
                $attributeValues = AttributeValue::withTrashed()->whereIn('id', $attributeValueIds)->get();

                foreach ($attributeValues as $attributeValue) {
                    if ($attributeValue->trashed()) {
                        $attributeValue->restore();
                    }
                }

                $attributeIds = $attributeValues->pluck('attribute_id')->unique();
                Attribute::withTrashed()->whereIn('id', $attributeIds)->get()->each(function (Attribute $attribute) {
                    if ($attribute->trashed()) {
                        $attribute->restore();
                    }
                });
            }
        }
    }

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
     *                      mrp_multiplier, pair_product, product_type. For variable products,
     *                      an optional 'dimensions' key pre-declares attribute/value combos to
     *                      create upfront (used by Product Import); when omitted/empty, no
     *                      variants are created here — callers (e.g. Purchase Import) are
     *                      expected to create them lazily via findOrCreateVariant().
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

        if ($group['product_type'] === 'variable' && !empty($group['dimensions'] ?? [])) {
            $this->createVariants($group, $product, $purchasePrice, $salePrice);
        }

        return $product;
    }

    public function updateExistingProduct(Product $product, array $group, array &$summary, ?int $userId): void
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

        $oldType = $product->type;
        $newType = $group['product_type'];

        $product->update([
            'name'                   => $group['product_name'],
            'category_id'            => $category->id,
            'sub_category_id'        => $subCategoryId,
            'product_code'           => $code,
            'purchase_multiplier'    => $purchaseMultiplier,
            'sale_multiplier'        => $saleMultiplier,
            'mrp_multiplier'         => $mrpMultiplier,
            'purchase_price'         => $purchasePrice,
            'sale_price'             => $salePrice,
            'mrp'                    => $mrp,
            'pair_product'           => $isPair,
            'pair_sale_price'        => $pairSalePrice,
            'pair_mrp'               => $pairMrp,
            'type'                   => $newType,
        ]);

        if ($oldType === 'variable' && $newType === 'normal') {
            $product->variants()->delete();
        }

        if ($newType === 'variable' && !empty($group['dimensions'] ?? [])) {
            $this->createVariants($group, $product, $purchasePrice, $salePrice);
        }
    }

    public function findOrCreateAttribute(string $name, ?int $userId): Attribute
    {
        $name = trim($name);
        $attribute = Attribute::withTrashed()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if (!$attribute) {
            $attribute = Attribute::create([
                'name'       => $name,
                'slug'       => generate_slug(Attribute::class, $name),
                'status'     => Attribute::STATUS_ACTIVE,
                'created_by' => $userId,
                'sort_order' => ((int) Attribute::max('sort_order')) + 1,
            ]);
        } elseif ($attribute->trashed()) {
            $attribute->restore();
        }

        return $attribute;
    }

    public function findOrCreateAttributeValue(int $attributeId, string $value): AttributeValue
    {
        $value = trim($value);
        $attrValue = AttributeValue::withTrashed()
            ->where('attribute_id', $attributeId)
            ->whereRaw('LOWER(TRIM(value)) = ?', [mb_strtolower($value)])
            ->first();

        if (!$attrValue) {
            $attrValue = AttributeValue::create([
                'attribute_id' => $attributeId,
                'value'        => $value,
            ]);
        } elseif ($attrValue->trashed()) {
            $attrValue->restore();
        }

        return $attrValue;
    }

    /**
     * Finds an existing Attribute by name (or creates it), finds an existing
     * AttributeValue under that attribute (or creates it), then finds an
     * existing ProductVariant for the pair (or creates it) — used by Purchase
     * Import, where variants are resolved row-by-row from the "Variant" /
     * "Variant Value" columns instead of being pre-declared up front.
     */
    public function findOrCreateVariant(Product $product, string $attributeName, string $valueName, ?int $userId): ProductVariant
    {
        $attribute = $this->findOrCreateAttribute($attributeName, $userId);
        $attributeValue = $this->findOrCreateAttributeValue($attribute->id, $valueName);

        $variant = ProductVariant::withTrashed()->firstOrCreate(
            ['product_id' => $product->id, 'attribute_value_id' => $attributeValue->id],
            ['purchase_price' => $product->purchase_price, 'sale_price' => $product->sale_price, 'status' => ProductVariant::STATUS_ACTIVE]
        );
        if ($variant->trashed()) {
            $variant->restore();
        }

        return $variant;
    }

    private function createVariants(array $group, Product $product, float $purchasePrice, float $salePrice): void
    {
        $dimensions = array_values($group['dimensions']);

        if (empty($dimensions)) {
            throw new \RuntimeException('Missing Variant Data');
        }

        foreach ($dimensions as $dim) {
            $attributeName = $dim['name'];
            $attrValues = $dim['values'];

            $attribute = $this->findOrCreateAttribute($attributeName, $product->created_by);

            foreach ($attrValues as $val) {
                $attributeValue = $this->findOrCreateAttributeValue($attribute->id, $val);

                $variant = ProductVariant::withTrashed()->firstOrCreate(
                    [
                        'product_id'         => $product->id,
                        'attribute_value_id' => $attributeValue->id,
                    ],
                    [
                        'purchase_price'     => $purchasePrice,
                        'sale_price'         => $salePrice,
                        'status'             => ProductVariant::STATUS_ACTIVE,
                    ]
                );
                if ($variant->trashed()) {
                    $variant->restore();
                }
                $variant->update([
                    'purchase_price' => $purchasePrice,
                    'sale_price'     => $salePrice,
                ]);
            }
        }
    }

    private function roundToNearest5($val): float
    {
        return ceil(floatval($val) / 5) * 5;
    }
}
