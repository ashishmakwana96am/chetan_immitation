<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Collection;
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

        $collectionIds = [];
        $rawCollectionStr = trim($group['collection'] ?? ($group['collection_short_name'] ?? ''));
        if ($rawCollectionStr !== '') {
            $shortNames = array_values(array_filter(array_map('trim', explode(',', $rawCollectionStr))));
            foreach ($shortNames as $shortName) {
                if ($shortName === '') continue;
                $collection = Collection::withTrashed()->firstOrCreate(
                    ['short_name' => $shortName],
                    ['name' => '', 'status' => 1, 'created_by' => $userId]
                );
                if ($collection->wasRecentlyCreated && isset($summary['collections_created'])) {
                    $summary['collections_created'] = ($summary['collections_created'] ?? 0) + 1;
                }
                if ($collection->trashed()) {
                    $collection->restore();
                }
                $collectionIds[] = $collection->id;
            }
        }
        $collectionId = $collectionIds[0] ?? null;

        $code = (float) $group['product_code'];
        $isPair = $group['pair_product'];

        // Purchase Price & Multiplier
        if (isset($group['purchase_price']) && is_numeric($group['purchase_price']) && (float) $group['purchase_price'] > 0) {
            $purchasePrice = (float) $group['purchase_price'];
            $purchaseMultiplier = $code > 0 ? round($purchasePrice / $code, 4) : 2.5;
        } else {
            $purchaseMultiplier = is_numeric($group['purchase_multiplier'] ?? null) ? (float) $group['purchase_multiplier'] : 2.5;
            $purchasePrice = $code * $purchaseMultiplier;
        }

        // Sale Price & Multiplier
        if (isset($group['sale_price']) && is_numeric($group['sale_price']) && (float) $group['sale_price'] > 0) {
            $salePrice = (float) $group['sale_price'];
            $saleMultiplier = $code > 0 ? round($salePrice / $code, 4) : 4.125;
        } else {
            $saleMultiplier = is_numeric($group['sale_multiplier'] ?? null) ? (float) $group['sale_multiplier'] : 4.125;
            if ($isPair) {
                $salePrice = $this->roundToNearest5(($code / 2) * $saleMultiplier);
            } else {
                $salePrice = $this->roundToNearest5($code * $saleMultiplier);
            }
        }

        // MRP & Multiplier
        if (isset($group['mrp']) && is_numeric($group['mrp']) && (float) $group['mrp'] > 0) {
            $mrp = (float) $group['mrp'];
            $mrpMultiplier = $code > 0 ? round($mrp / $code, 4) : 4.575;
        } else {
            $mrpMultiplier = is_numeric($group['mrp_multiplier'] ?? null) ? (float) $group['mrp_multiplier'] : 4.575;
            if ($isPair) {
                $mrp = $this->roundToNearest5(($code / 2) * $mrpMultiplier);
            } else {
                $mrp = $this->roundToNearest5($code * $mrpMultiplier);
            }
        }

        $customSizes = null;
        if ($isPair && !empty($group['pair_sizes'])) {
            $customSizes = $this->buildCustomSizes($group['pair_sizes'], $code, $saleMultiplier, $mrpMultiplier);

            if (!empty($customSizes) && (!isset($group['sale_price']) || !is_numeric($group['sale_price']))) {
                $salePrice = $customSizes[0]['sale_price'];
                $mrp = $customSizes[0]['mrp'];
            }
        }

        $defaultDescription = '<p>Classic Silver Tone Adjustable Tennis Bracelet crafted with premium brass and sparkling American Diamonds. Lightweight, skin-friendly and perfect for everyday wear as well as weddings, parties and festive occasions. Elegant finish with an adjustable chain ensures a comfortable fit for every wrist.</p>';
        $defaultInfo = '<ul><li>Premium Quality Brass</li><li>Silver Tone Finish</li><li>Studded with American Diamonds</li><li>Adjustable Chain</li><li>Lightweight Design</li><li>Comfortable for Daily Wear</li><li>Tarnish Resistant Finish</li><li>Elegant Party Wear Bracelet</li></ul>';
        $defaultHighlights = '<p>✓ Premium Finish</p><p>✓ Adjustable Size</p><p>✓ Lightweight</p><p>✓ Skin Friendly</p><p>✓ Anti Tarnish</p><p>✓ Sparkling American Diamonds</p><p>✓ Luxury Look</p><p>✓ Perfect Gift</p>';

        $product = Product::create([
            'name'                   => $group['product_name'],
            'slug'                   => generate_slug(Product::class, $group['product_name']),
            'category_id'            => $category->id,
            'sub_category_id'        => $subCategoryId,
            'collection_id'          => $collectionId,
            'barcode'                => $group['barcode'],
            'product_code'           => $code,
            'description'            => $defaultDescription,
            'additional_information' => $defaultInfo,
            'product_highlights'     => $defaultHighlights,
            'purchase_price'         => $purchasePrice,
            'sale_price'             => $salePrice,
            'mrp'                    => $mrp,
            'pair_product'           => $isPair,
            'custom_sizes'           => $customSizes,
            'type'                   => $group['product_type'],
            'status'                 => Product::STATUS_ACTIVE,
            'created_by'             => $userId,
            'sort_order'             => ((int) Product::max('sort_order')) + 1,
        ]);

        if (!empty($collectionIds)) {
            $product->collections()->syncWithoutDetaching($collectionIds);
        }

        if ($group['product_type'] === 'variable' && !empty($group['dimensions'] ?? [])) {
            $this->createVariants($group, $product, $purchasePrice, $salePrice, $customSizes);
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

        $collectionIds = [];
        $rawCollectionStr = trim($group['collection'] ?? ($group['collection_short_name'] ?? ''));
        if ($rawCollectionStr !== '') {
            $shortNames = array_values(array_filter(array_map('trim', explode(',', $rawCollectionStr))));
            foreach ($shortNames as $shortName) {
                if ($shortName === '') continue;
                $collection = Collection::withTrashed()->firstOrCreate(
                    ['short_name' => $shortName],
                    ['name' => '', 'status' => 1, 'created_by' => $userId]
                );
                if ($collection->wasRecentlyCreated && isset($summary['collections_created'])) {
                    $summary['collections_created'] = ($summary['collections_created'] ?? 0) + 1;
                }
                if ($collection->trashed()) {
                    $collection->restore();
                }
                $collectionIds[] = $collection->id;
            }
        }
        $collectionId = $collectionIds[0] ?? null;

        $code = (float) $group['product_code'];
        $isPair = $group['pair_product'];

        // Purchase Price & Multiplier
        if (isset($group['purchase_price']) && is_numeric($group['purchase_price']) && (float) $group['purchase_price'] > 0) {
            $purchasePrice = (float) $group['purchase_price'];
            $purchaseMultiplier = $code > 0 ? round($purchasePrice / $code, 4) : 2.5;
        } else {
            $purchaseMultiplier = is_numeric($group['purchase_multiplier'] ?? null) ? (float) $group['purchase_multiplier'] : 2.5;
            $purchasePrice = $code * $purchaseMultiplier;
        }

        // Sale Price & Multiplier
        if (isset($group['sale_price']) && is_numeric($group['sale_price']) && (float) $group['sale_price'] > 0) {
            $salePrice = (float) $group['sale_price'];
            $saleMultiplier = $code > 0 ? round($salePrice / $code, 4) : 4.125;
        } else {
            $saleMultiplier = is_numeric($group['sale_multiplier'] ?? null) ? (float) $group['sale_multiplier'] : 4.125;
            if ($isPair) {
                $salePrice = $this->roundToNearest5(($code / 2) * $saleMultiplier);
            } else {
                $salePrice = $this->roundToNearest5($code * $saleMultiplier);
            }
        }

        // MRP & Multiplier
        if (isset($group['mrp']) && is_numeric($group['mrp']) && (float) $group['mrp'] > 0) {
            $mrp = (float) $group['mrp'];
            $mrpMultiplier = $code > 0 ? round($mrp / $code, 4) : 4.575;
        } else {
            $mrpMultiplier = is_numeric($group['mrp_multiplier'] ?? null) ? (float) $group['mrp_multiplier'] : 4.575;
            if ($isPair) {
                $mrp = $this->roundToNearest5(($code / 2) * $mrpMultiplier);
            } else {
                $mrp = $this->roundToNearest5($code * $mrpMultiplier);
            }
        }

        $customSizes = null;
        if ($isPair && !empty($group['pair_sizes'] ?? '')) {
            $customSizes = $this->buildCustomSizes($group['pair_sizes'], $code, $saleMultiplier, $mrpMultiplier);
            if (!empty($customSizes) && (!isset($group['sale_price']) || !is_numeric($group['sale_price']))) {
                $salePrice = $customSizes[0]['sale_price'];
                $mrp = $customSizes[0]['mrp'];
            }
        }

        $oldType = $product->type;
        $newType = $group['product_type'];

        $updateData = [
            'name'                   => $group['product_name'],
            'category_id'            => $category->id,
            'sub_category_id'        => $subCategoryId,
            'product_code'           => $code,
            'purchase_price'         => $purchasePrice,
            'sale_price'             => $salePrice,
            'mrp'                    => $mrp,
            'pair_product'           => $isPair,
            'custom_sizes'           => $customSizes,
            'type'                   => $newType,
        ];
        if ($collectionId) {
            $updateData['collection_id'] = $collectionId;
        }

        $product->update($updateData);

        if (!empty($collectionIds)) {
            $product->collections()->syncWithoutDetaching($collectionIds);
        }

        if ($oldType === 'variable' && $newType === 'normal') {
            $product->variants()->delete();
        }

        if ($newType === 'variable' && !empty($group['dimensions'] ?? [])) {
            $this->createVariants($group, $product, $purchasePrice, $salePrice, $customSizes);
        }
    }

    public function findOrCreateAttribute(string $name, ?int $userId): Attribute
    {
        $name = trim($name);
        $attribute = Attribute::withTrashed()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if (!$attribute) {
            $nextIndex = (int) Attribute::max('index') + 1;
            $attribute = Attribute::create([
                'name'       => $name,
                'slug'       => generate_slug(Attribute::class, $name),
                'status'     => Attribute::STATUS_ACTIVE,
                'created_by' => $userId,
                'sort_order' => ((int) Attribute::max('sort_order')) + 1,
                'index'      => $nextIndex,
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

    public function findOrCreateVariantByIndexOrName(
        Product $product,
        string $attributeName,
        string $valueName,
        ?int $userId
    ): ProductVariant {
        $attribute = null;
        if (preg_match('/^\d+$/', $attributeName) && (int) $attributeName > 0) {
            $attribute = Attribute::where('index', (int) $attributeName)->first();
        }

        if (!$attribute) {
            $attribute = $this->findOrCreateAttribute($attributeName, $userId);
        } elseif ($attribute->trashed()) {
            $attribute->restore();
        }

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

    private function createVariants(array $group, Product $product, float $purchasePrice, float $salePrice, ?array $customSizes = null): void
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
                    'custom_sizes'   => $customSizes ?: null,
                ]);
            }
        }
    }

    /**
     * Parse pair_sizes string (e.g. "2,4") and auto-calculate sale_price/mrp
     * for each size. Largest size = full rate (code × multiplier),
     * smaller sizes are proportionally scaled.
     *
     * @return array  [{size, sale_price, mrp}, ...] sorted ascending by size
     */
    private function buildCustomSizes(string $pairSizesRaw, float $code, float $saleMultiplier, float $mrpMultiplier): array
    {
        $sizes = array_values(array_filter(
            array_map(fn ($s) => (int) trim($s), explode(',', $pairSizesRaw)),
            fn ($s) => $s > 0
        ));

        if (empty($sizes)) {
            return [];
        }

        sort($sizes);
        $maxSize = max($sizes);

        $result = [];
        foreach ($sizes as $size) {
            $result[] = [
                'size'       => $size,
                'sale_price' => $this->roundToNearest5($code * $saleMultiplier * ($size / $maxSize)),
                'mrp'        => $this->roundToNearest5($code * $mrpMultiplier * ($size / $maxSize)),
            ];
        }

        return $result;
    }

    private function roundToNearest5($val): float
    {
        return ceil(floatval($val) / 5) * 5;
    }
}
