<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sub_category_id',
        'sku',
        'description',
        'additional_information',
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
            'sale_price'     => 'decimal:2',
            'mrp'            => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function inventories()
    {
        return $this->hasMany(\App\Models\Inventory::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getVariantStock($locationId = null)
    {
        $variants = $this->variants()->with('attributeValue.attribute')->get();
        
        // Find all locations
        $locations = \App\Models\Location::when($locationId, function($q) use ($locationId) {
            $q->where('id', $locationId);
        })->get();

        // 1. Get all approved purchases containing this product
        $purchases = \App\Models\PurchaseInvoice::where('status', 2)
            ->whereHas('items', function($q) {
                $q->where('product_id', $this->id);
            })
            ->with(['items' => function($q) {
                $q->where('product_id', $this->id)->with('allocations');
            }])
            ->get();

        // 2. Get all approved sales containing this product
        $sales = \App\Models\Order::where('status', 2)
            ->whereHas('items', function($q) {
                $q->where('product_id', $this->id);
            })
            ->with(['items' => function($q) {
                $q->where('product_id', $this->id);
            }])
            ->get();

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

        foreach ($purchases as $pur) {
            $siblings = $pur->items->sortBy('id')->values();
            if ($siblings->isEmpty()) continue;
            
            $firstItem = $siblings->first();
            
            foreach ($firstItem->allocations as $alloc) {
                $locId = $alloc->location_id;
                if (isset($purchasedQty[$locId])) {
                    $purchasedQty[$locId]['parent'] += $alloc->quantity;
                }
            }
            
            $variantItems = $siblings->slice(1)->values();
            $matchedMap = [];
            $unmatchedSiblings = $variantItems->all();
            
            foreach ($variants as $v) {
                $matchedIdx = -1;
                foreach ($unmatchedSiblings as $idx => $sibling) {
                    if (isset($sibling) && (float)$sibling->purchase_price === (float)$v->purchase_price) {
                        $matchedIdx = $idx;
                        break;
                    }
                }
                if ($matchedIdx !== -1) {
                    $matchedSibling = $unmatchedSiblings[$matchedIdx];
                    $matchedMap[$matchedSibling->id] = $v->id;
                    unset($unmatchedSiblings[$matchedIdx]);
                }
            }
            
            $unmatchedSiblings = array_values($unmatchedSiblings);
            $unmatchedVariants = [];
            foreach ($variants as $v) {
                $alreadyMatched = false;
                foreach ($matchedMap as $vid) {
                    if ($vid === $v->id) {
                        $alreadyMatched = true;
                        break;
                    }
                }
                if (!$alreadyMatched) {
                    $unmatchedVariants[] = $v;
                }
            }
            
            foreach ($unmatchedSiblings as $idx => $sibling) {
                if (isset($unmatchedVariants[$idx])) {
                    $v = $unmatchedVariants[$idx];
                    $matchedMap[$sibling->id] = $v->id;
                }
            }
            
            foreach ($variantItems as $vItem) {
                $vId = $matchedMap[$vItem->id] ?? null;
                if ($vId) {
                    foreach ($vItem->allocations as $alloc) {
                        $locId = $alloc->location_id;
                        if (isset($purchasedQty[$locId])) {
                            $purchasedQty[$locId][$vId] += $alloc->quantity;
                        }
                    }
                }
            }
        }

        foreach ($sales as $sale) {
            $siblings = $sale->items->sortBy('id')->values();
            if ($siblings->isEmpty()) continue;
            
            $firstItem = $siblings->first();
            $locId = $sale->location_id;
            
            if (isset($soldQty[$locId])) {
                $soldQty[$locId]['parent'] += $firstItem->quantity;
            }
            
            $variantItems = $siblings->slice(1)->values();
            $matchedMap = [];
            $unmatchedSiblings = $variantItems->all();
            
            foreach ($variants as $v) {
                $matchedIdx = -1;
                foreach ($unmatchedSiblings as $idx => $sibling) {
                    if (isset($sibling) && (float)$sibling->price === (float)$v->sale_price) {
                        $matchedIdx = $idx;
                        break;
                    }
                }
                if ($matchedIdx !== -1) {
                    $matchedSibling = $unmatchedSiblings[$matchedIdx];
                    $matchedMap[$matchedSibling->id] = $v->id;
                    unset($unmatchedSiblings[$matchedIdx]);
                }
            }
            
            $unmatchedSiblings = array_values($unmatchedSiblings);
            $unmatchedVariants = [];
            foreach ($variants as $v) {
                $alreadyMatched = false;
                foreach ($matchedMap as $vid) {
                    if ($vid === $v->id) {
                        $alreadyMatched = true;
                        break;
                    }
                }
                if (!$alreadyMatched) {
                    $unmatchedVariants[] = $v;
                }
            }
            
            foreach ($unmatchedSiblings as $idx => $sibling) {
                if (isset($unmatchedVariants[$idx])) {
                    $v = $unmatchedVariants[$idx];
                    $matchedMap[$sibling->id] = $v->id;
                }
            }
            
            foreach ($variantItems as $vItem) {
                $vId = $matchedMap[$vItem->id] ?? null;
                if ($vId && isset($soldQty[$locId])) {
                    $soldQty[$locId][$vId] += $vItem->quantity;
                }
            }
        }

        $result = [];
        foreach ($locations as $loc) {
            $parentStock = ($purchasedQty[$loc->id]['parent'] ?? 0) - ($soldQty[$loc->id]['parent'] ?? 0);
            
            if (($purchasedQty[$loc->id]['parent'] ?? 0) === 0 && ($soldQty[$loc->id]['parent'] ?? 0) === 0) {
                $parentStock = (int)\App\Models\Inventory::where('product_id', $this->id)
                    ->where('location_id', $loc->id)
                    ->value('quantity');
            }

            $locData = [
                'location_id'   => $loc->id,
                'location_name' => $loc->name,
                'parent'        => $parentStock,
                'variants'      => [],
            ];
            foreach ($variants as $v) {
                $vStock = ($purchasedQty[$loc->id][$v->id] ?? 0) - ($soldQty[$loc->id][$v->id] ?? 0);
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
}
