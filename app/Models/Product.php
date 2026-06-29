<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sub_category_id',
        'sku',
        'barcode',
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
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'mrp' => 'decimal:2',
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

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
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

    public function scopeWithReviewStats($query)
    {
        return $query->withCount('reviews')->withAvg('reviews', 'rating');
    }

    public function getVariantStock($locationId = null)
    {
        $variants = $this->variants()->with('attributeValue.attribute')->get();

        // Find all locations
        $locations = Location::when($locationId, function ($q) use ($locationId) {
            $q->where('id', $locationId);
        })->get();

        $purchasedQty = [];
        $soldQty = [];

        foreach ($locations as $loc) {
            $purchasedQty[$loc->id] = ['parent' => 0];
            $soldQty[$loc->id] = ['parent' => 0];
            foreach ($variants as $v) {
                $purchasedQty[$loc->id][$v->id] = 0;
                $soldQty[$loc->id][$v->id] = 0;
            }
        }

        // 1. Get all approved purchase allocations for this product
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

        // 2. Get approved sales for this product
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

        $result = [];
        foreach ($locations as $loc) {
            $parentStock = $purchasedQty[$loc->id]['parent'] - $soldQty[$loc->id]['parent'];

            // Fallback for parent stock to physical inventory table if no history exists
            if ($purchasedQty[$loc->id]['parent'] === 0 && $soldQty[$loc->id]['parent'] === 0) {
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
                $vStock = $purchasedQty[$loc->id][$v->id] - $soldQty[$loc->id][$v->id];
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->barcode)) {
                $product->barcode = self::generateUniqueBarcode($product->category_id);
            }
        });
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
}
