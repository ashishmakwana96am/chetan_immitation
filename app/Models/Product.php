<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, LogsActivity;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    public static function clearMappedCaches(): void
    {
        \Illuminate\Support\Facades\Cache::store('file')->forget('all_mapped_products_sales');
        \Illuminate\Support\Facades\Cache::store('file')->forget('all_mapped_products_purchases');
        \Illuminate\Support\Facades\Cache::store('file')->forget('all_mapped_products_bills');
        \Illuminate\Support\Facades\Cache::forget('all_mapped_products_sales');
        \Illuminate\Support\Facades\Cache::forget('all_mapped_products_purchases');
        \Illuminate\Support\Facades\Cache::forget('all_mapped_products_bills');
        \App\Http\Controllers\DashboardController::clearDashboardCaches();
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearMappedCaches());
        static::deleted(fn () => static::clearMappedCaches());
    }

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sub_category_id',
        'collection_id',
        'barcode',
        'product_code',
        'description',
        'additional_information',
        'product_highlights',
        'purchase_price',
        'sale_price',
        'mrp',
        'status',
        'type',
        'created_by',
        'sort_order',
        'sale',
        'pair_product',
        'custom_sizes',
        'bypass_min_price',
        'hide_from_website',
    ];

    protected function casts(): array
    {
        return [
            'product_code'    => 'decimal:2',
            'purchase_price'  => 'decimal:2',
            'sale_price'      => 'decimal:2',
            'mrp'             => 'decimal:2',
            'pair_product'    => 'boolean',
            'custom_sizes'    => 'array',
            'bypass_min_price' => 'boolean',
            'hide_from_website' => 'boolean',
        ];
    }

    public function scopeForWebsite($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('hide_from_website', false);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class)->withTrashed();
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class)->withTrashed();
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product')->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Falls back to a default placeholder image when the product has no primary image.
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        if ($this->relationLoaded('primaryImage')) {
            $img = $this->getRelation('primaryImage');
            return $img?->image_url ?? asset('website/assets/images/placeholder.png');
        }
        if ($this->relationLoaded('images')) {
            $img = $this->images->first();
            return $img?->image_url ?? asset('website/assets/images/placeholder.png');
        }
        return $this->primaryImage?->image_url ?? asset('website/assets/images/placeholder.png');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the total available stock (Pcs) across all locations.
     */
    public function totalAvailableStock($variantId = null)
    {
        if ($this->type === 'variable') {
            $stockData = $this->getVariantStock();

            if ($variantId) {
                $totalStock = 0;
                foreach ($stockData as $locData) {
                    $totalStock += (int) ($locData['variants'][$variantId] ?? 0);
                }
                return max(0, $totalStock);
            }

            $totalVariantStock = 0;
            $totalParentStock  = 0;
            foreach ($stockData as $locData) {
                foreach ($locData['variants'] as $vStock) {
                    $totalVariantStock += (int) $vStock;
                }
                $totalParentStock += (int) ($locData['parent'] ?? 0);
            }

            
            if ($totalVariantStock > 0) {
                return $totalVariantStock;
            }
            if ($totalParentStock > 0) {
                return $totalParentStock;
            }
        }

        return (int) ($this->relationLoaded('inventories') ? $this->inventories->sum('quantity') : $this->inventories()->sum('quantity'));
    }

    /**
     * Get the Sale Price for the largest size if pair_product is true.
     */
    public function getDisplaySalePriceAttribute(): float
    {
        if ($this->pair_product) {
            $sizes = $this->custom_sizes ?? [];
            if (empty($sizes)) {
                $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
                foreach ($variants as $v) {
                    if (!empty($v->custom_sizes)) {
                        $sizes = array_merge($sizes, $v->custom_sizes);
                    }
                }
            }

            if (!empty($sizes)) {
                $maxRow = collect($sizes)->sortBy(fn($row) => (float) ($row['size'] ?? 0))->last();
                if ($maxRow && isset($maxRow['sale_price']) && is_numeric($maxRow['sale_price'])) {
                    return (float) $maxRow['sale_price'];
                }
            }
        }

        return (float) ($this->sale_price ?? 0);
    }

    /**
     * Get the MRP for the largest size if pair_product is true.
     */
    public function getDisplayMrpAttribute(): float
    {
        if ($this->pair_product) {
            $sizes = $this->custom_sizes ?? [];
            if (empty($sizes)) {
                $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
                foreach ($variants as $v) {
                    if (!empty($v->custom_sizes)) {
                        $sizes = array_merge($sizes, $v->custom_sizes);
                    }
                }
            }

            if (!empty($sizes)) {
                $maxRow = collect($sizes)->sortBy(fn($row) => (float) ($row['size'] ?? 0))->last();
                if ($maxRow && isset($maxRow['mrp']) && is_numeric($maxRow['mrp'])) {
                    return (float) $maxRow['mrp'];
                }
            }
        }

        return (float) ($this->mrp ?? 0);
    }

    /**
     * Format stock into Pairs and Pcs format if pair_product is true.
     */
    public function formatStockDisplay(?int $pcs = null, string $separator = '<br>'): string
    {
        $pieces = $pcs !== null ? $pcs : (int) $this->totalAvailableStock();
        if ($pieces <= 0) {
            return 'SOLD OUT';
        }

        if ($this->pair_product) {
            $sizes = collect($this->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = $sizes->count() > 0 ? (float) $sizes->max() : 1.0;
            $pairsCount = $pairSize > 0 ? (int) floor($pieces / $pairSize) : 0;
            $remPcsCount = $pairSize > 0 ? (int) ($pieces % $pairSize) : 0;

            $parts = [];
            if ($pairsCount > 0) {
                $parts[] = number_format($pairsCount) . ' Pair' . ($pairsCount > 1 ? 's' : '');
            }
            if ($remPcsCount > 0) {
                $parts[] = number_format($remPcsCount) . ' Pcs';
            }
            return count($parts) > 0 ? implode($separator, $parts) : '0';
        }

        return number_format($pieces);
    }

    /**
     * Render standardized HTML Badge for stock across all admin pages.
     */
    public function renderStockBadge(?int $customPcs = null): string
    {
        $pieces = $customPcs !== null ? $customPcs : (int) $this->totalAvailableStock();
        if ($pieces <= 0) {
            return '<span class="badge bg-label-danger fw-bold">SOLD OUT</span>';
        }

        $formatted = $this->formatStockDisplay($pieces);
        return '<span class="badge bg-label-success fw-bold">' . $formatted . '</span>';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function scopeWithReviewStats($query)
    {
        return $query->withCount('reviews')->withAvg('reviews', 'rating');
    }

    protected static array $variantStockCache = [];

    protected static ?array $preloadedVariantsByProduct = null;
    protected static ?array $preloadedPurchaseAllocationsByProduct = null;
    protected static ?array $preloadedOrderItemsByProduct = null;
    protected static ?array $preloadedTransferItemsByProduct = null;
    protected static ?array $preloadedInventoryQtyByProductLocation = null;
    protected static $preloadedLocations = null;

    public static function preloadVariantStock($products): void
    {
        $ids = $products->where('type', 'variable')->pluck('id')->filter()->values()->all();
        if (empty($ids)) {
            return;
        }

        static::$preloadedLocations = Location::get();

        static::$preloadedVariantsByProduct = ProductVariant::whereIn('product_id', $ids)
            ->with('attributeValue.attribute')
            ->get()
            ->groupBy('product_id')
            ->all();

        static::$preloadedPurchaseAllocationsByProduct = PurchaseAllocation::whereHas('purchaseItem', function ($q) use ($ids) {
                $q->whereIn('product_id', $ids)
                  ->whereHas('invoice', function ($sub) {
                      $sub->where('status', 2);
                  });
            })
            ->with('purchaseItem')
            ->get()
            ->groupBy(fn($alloc) => $alloc->purchaseItem->product_id)
            ->all();

        static::$preloadedOrderItemsByProduct = OrderItem::whereIn('product_id', $ids)
            ->whereHas('order', function ($q) {
                $q->where('status', Order::STATUS_APPROVE);
            })
            ->with('order')
            ->get()
            ->groupBy('product_id')
            ->all();

        static::$preloadedTransferItemsByProduct = PurchaseBillItem::whereIn('product_id', $ids)
            ->whereHas('transfer', function ($q) {
                $q->where('status', PurchaseBill::STATUS_ACCEPTED);
            })
            ->with('transfer')
            ->get()
            ->groupBy('product_id')
            ->all();

        static::$preloadedInventoryQtyByProductLocation = Inventory::whereIn('product_id', $ids)
            ->get()
            ->keyBy(fn($inv) => $inv->product_id . ':' . $inv->location_id)
            ->map(fn($inv) => (int) $inv->quantity)
            ->all();
    }

    public static function clearPreloadedVariantStock(): void
    {
        static::$preloadedVariantsByProduct = null;
        static::$preloadedPurchaseAllocationsByProduct = null;
        static::$preloadedOrderItemsByProduct = null;
        static::$preloadedTransferItemsByProduct = null;
        static::$preloadedInventoryQtyByProductLocation = null;
        static::$preloadedLocations = null;
    }

    public function getVariantStock($locationId = null)
    {
        $cacheKey = $this->id . ':' . ($locationId ?? 'all');
        if (array_key_exists($cacheKey, static::$variantStockCache)) {
            return static::$variantStockCache[$cacheKey];
        }

        return static::$variantStockCache[$cacheKey] = $this->computeVariantStock($locationId);
    }

    private function computeVariantStock($locationId = null)
    {
        $variants = static::$preloadedVariantsByProduct !== null
            ? collect(static::$preloadedVariantsByProduct[$this->id] ?? [])
            : $this->variants()->with('attributeValue.attribute')->get();
        $variantsById = $variants->keyBy('id');

        $allLocations = static::$preloadedLocations ?? Location::get();
        $locations = $locationId
            ? $allLocations->where('id', $locationId)->values()
            : $allLocations;

        $purchasedQty = [];
        $soldQty = [];
        $transferredInQty = [];
        $transferredOutQty = [];

        foreach ($locations as $loc) {
            $purchasedQty[$loc->id] = ['parent' => 0];
            $soldQty[$loc->id] = ['parent' => 0];
            $transferredInQty[$loc->id] = ['parent' => 0];
            $transferredOutQty[$loc->id] = ['parent' => 0];
            foreach ($variants as $v) {
                $purchasedQty[$loc->id][$v->id] = 0;
                $soldQty[$loc->id][$v->id] = 0;
                $transferredInQty[$loc->id][$v->id] = 0;
                $transferredOutQty[$loc->id][$v->id] = 0;
            }
        }

        $purchaseAllocations = static::$preloadedPurchaseAllocationsByProduct !== null
            ? collect(static::$preloadedPurchaseAllocationsByProduct[$this->id] ?? [])
            : PurchaseAllocation::whereHas('purchaseItem', function ($q) {
                $q->where('product_id', $this->id)
                  ->whereHas('invoice', function ($sub) {
                      $sub->where('status', 2);
                  });
            })
            ->with('purchaseItem')
            ->get();

        foreach ($purchaseAllocations as $alloc) {
            $locId = $alloc->location_id;
            $vId = $alloc->purchaseItem->product_variant_id;
            $qty = (int) round($alloc->quantity * $this->purchasePairMultiplier($alloc->purchaseItem->custom_size_value, $variantsById->get($vId)));
            if (isset($purchasedQty[$locId])) {
                if ($vId && isset($purchasedQty[$locId][$vId])) {
                    $purchasedQty[$locId][$vId] += $qty;
                    $purchasedQty[$locId]['parent'] += $qty;
                } else if (!$vId) {
                    $purchasedQty[$locId]['parent'] += $qty;
                }
            }
        }

        $orderItems = static::$preloadedOrderItemsByProduct !== null
            ? collect(static::$preloadedOrderItemsByProduct[$this->id] ?? [])
            : OrderItem::where('product_id', $this->id)
                ->whereHas('order', function ($q) {
                    $q->where('status', Order::STATUS_APPROVE);
                })
                ->with('order')
                ->get();

        foreach ($orderItems as $item) {
            $locId = $item->order->location_id;
            $vId = $item->product_variant_id;
            $qty = (int) round($item->quantity * $this->orderPairMultiplier($item->pair_type, $item->custom_size_value));
            if (isset($soldQty[$locId])) {
                if ($vId && isset($soldQty[$locId][$vId])) {
                    $soldQty[$locId][$vId] += $qty;
                    $soldQty[$locId]['parent'] += $qty;
                } else if (!$vId) {
                    $soldQty[$locId]['parent'] += $qty;
                }
            }
        }

        $transferItems = static::$preloadedTransferItemsByProduct !== null
            ? collect(static::$preloadedTransferItemsByProduct[$this->id] ?? [])
            : PurchaseBillItem::where('product_id', $this->id)
                ->whereHas('transfer', function ($q) {
                    $q->where('status', PurchaseBill::STATUS_ACCEPTED);
                })
                ->with('transfer')
                ->get();

        foreach ($transferItems as $item) {
            $vId = $item->product_variant_id;
            $fromLocId = $item->transfer->from_location_id;
            $toLocId = $item->transfer->to_location_id;
            $qty = (int) round($item->quantity * $this->orderPairMultiplier($item->pair_type, $item->custom_size_value));

            if (isset($transferredOutQty[$fromLocId])) {
                if ($vId && isset($transferredOutQty[$fromLocId][$vId])) {
                    $transferredOutQty[$fromLocId][$vId] += $qty;
                    $transferredOutQty[$fromLocId]['parent'] += $qty;
                } else if (!$vId) {
                    $transferredOutQty[$fromLocId]['parent'] += $qty;
                }
            }

            if (isset($transferredInQty[$toLocId])) {
                if ($vId && isset($transferredInQty[$toLocId][$vId])) {
                    $transferredInQty[$toLocId][$vId] += $qty;
                    $transferredInQty[$toLocId]['parent'] += $qty;
                } else if (!$vId) {
                    $transferredInQty[$toLocId]['parent'] += $qty;
                }
            }
        }

        $result = [];
        foreach ($locations as $loc) {
            $parentStock = $purchasedQty[$loc->id]['parent']
                - $soldQty[$loc->id]['parent']
                + $transferredInQty[$loc->id]['parent']
                - $transferredOutQty[$loc->id]['parent'];

            if ($purchasedQty[$loc->id]['parent'] === 0 && $soldQty[$loc->id]['parent'] === 0 && $transferredInQty[$loc->id]['parent'] === 0 && $transferredOutQty[$loc->id]['parent'] === 0) {
                $invKey = $this->id . ':' . $loc->id;
                $parentStock = static::$preloadedInventoryQtyByProductLocation !== null
                    ? (int) (static::$preloadedInventoryQtyByProductLocation[$invKey] ?? 0)
                    : (int) Inventory::where('product_id', $this->id)
                        ->where('location_id', $loc->id)
                        ->value('quantity');
            }

            $locData = [
                'location_id' => $loc->id,
                'location_name' => $loc->name,
                'parent' => $parentStock,
                'variants' => [],
            ];
            foreach ($variants as $v) {
                $vStock = $purchasedQty[$loc->id][$v->id]
                    - $soldQty[$loc->id][$v->id]
                    + $transferredInQty[$loc->id][$v->id]
                    - $transferredOutQty[$loc->id][$v->id];
                $locData['variants'][$v->id] = $vStock;
            }
            $result[$loc->id] = $locData;
        }

        return $locationId ? ($result[$locationId] ?? null) : $result;
    }

    private function purchasePairMultiplier($customSizeValue, ?ProductVariant $variant = null): float
    {
        if (!$this->pair_product) {
            return 1.0;
        }

        if ($customSizeValue !== null && $customSizeValue !== '' && (float) $customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        $sizesSource = ($variant && !empty($variant->custom_sizes)) ? $variant->custom_sizes : ($this->custom_sizes ?? []);
        $sizes = collect($sizesSource)->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
        if ($sizes->count() > 0) {
            return (float) $sizes->max();
        }

        return 2.0;
    }

    private function orderPairMultiplier(?string $pairType, $customSizeValue): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float) $customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$this->pair_product) {
            return 1.0;
        }

        return $pairType === 'pair' ? 2.0 : 1.0;
    }

    public function getIsVariableAttribute()
    {
        return $this->type === 'variable';
    }

    public function scopeHasImages($query)
    {
        return $query->has('images');
    }

    public static function generateUniqueBarcode($categoryId = null)
    {
        $prefix = 'PRD';

        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category && $category->slug) {
                $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->slug), 0, 4));
            }
        }

        do {
            $barcode = $prefix.str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (self::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function getCustomSizesAttribute($value)
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($decoded)) {
            return collect($decoded)->sortBy(fn ($item) => (float) ($item['size'] ?? 0))->values()->toArray();
        }
        return $decoded;
    }

    public function setCustomSizesAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (is_array($value)) {
            $value = collect($value)->sortBy(fn ($item) => (float) ($item['size'] ?? 0))->values()->toArray();
        }
        $this->attributes['custom_sizes'] = $value ? json_encode($value) : null;
    }
}
