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

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sub_category_id',
        'barcode',
        'product_code',
        'purchase_multiplier',
        'sale_multiplier',
        'mrp_multiplier',
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
    ];

    protected function casts(): array
    {
        return [
            'product_code'    => 'decimal:2',
            'purchase_multiplier' => 'decimal:3',
            'sale_multiplier' => 'decimal:3',
            'mrp_multiplier'  => 'decimal:3',
            'purchase_price'  => 'decimal:2',
            'sale_price'      => 'decimal:2',
            'mrp'             => 'decimal:2',
            'pair_product'    => 'boolean',
            'custom_sizes'    => 'array',
            'bypass_min_price' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class)->withTrashed();
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

            $totalStock = 0;
            foreach ($stockData as $locData) {
                foreach ($locData['variants'] as $vStock) {
                    $totalStock += (int) $vStock;
                }
            }
            return max(0, $totalStock);
        }

        return (int) $this->inventories()->sum('quantity');
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

    public function getVariantStock($locationId = null)
    {
        $variants = $this->variants()->with('attributeValue.attribute')->get();

        $locations = Location::when($locationId, function ($q) use ($locationId) {
            $q->where('id', $locationId);
        })->get();

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

        $purchaseAllocations = PurchaseAllocation::whereHas('purchaseItem', function ($q) {
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
            if (isset($purchasedQty[$locId])) {
                if ($vId && isset($purchasedQty[$locId][$vId])) {
                    $purchasedQty[$locId][$vId] += $alloc->quantity;
                    $purchasedQty[$locId]['parent'] += $alloc->quantity;
                } else if (!$vId) {
                    $purchasedQty[$locId]['parent'] += $alloc->quantity;
                }
            }
        }

        $orderItems = OrderItem::where('product_id', $this->id)
            ->whereHas('order', function ($q) {
                $q->where('status', Order::STATUS_APPROVE);
            })
            ->with('order')
            ->get();

        foreach ($orderItems as $item) {
            $locId = $item->order->location_id;
            $vId = $item->product_variant_id;
            if (isset($soldQty[$locId])) {
                if ($vId && isset($soldQty[$locId][$vId])) {
                    $soldQty[$locId][$vId] += $item->quantity;
                    $soldQty[$locId]['parent'] += $item->quantity;
                } else if (!$vId) {
                    $soldQty[$locId]['parent'] += $item->quantity;
                }
            }
        }

        $transferItems = PurchaseBillItem::where('product_id', $this->id)
            ->whereHas('transfer', function ($q) {
                $q->where('status', PurchaseBill::STATUS_ACCEPTED);
            })
            ->with('transfer')
            ->get();

        foreach ($transferItems as $item) {
            $vId = $item->product_variant_id;
            $fromLocId = $item->transfer->from_location_id;
            $toLocId = $item->transfer->to_location_id;

            if (isset($transferredOutQty[$fromLocId])) {
                if ($vId && isset($transferredOutQty[$fromLocId][$vId])) {
                    $transferredOutQty[$fromLocId][$vId] += $item->quantity;
                    $transferredOutQty[$fromLocId]['parent'] += $item->quantity;
                } else if (!$vId) {
                    $transferredOutQty[$fromLocId]['parent'] += $item->quantity;
                }
            }

            if (isset($transferredInQty[$toLocId])) {
                if ($vId && isset($transferredInQty[$toLocId][$vId])) {
                    $transferredInQty[$toLocId][$vId] += $item->quantity;
                    $transferredInQty[$toLocId]['parent'] += $item->quantity;
                } else if (!$vId) {
                    $transferredInQty[$toLocId]['parent'] += $item->quantity;
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
                $parentStock = (int) Inventory::where('product_id', $this->id)
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
