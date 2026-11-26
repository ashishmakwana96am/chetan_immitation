<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'purchase_item_id',
        'purchase_price',
        'pair_type',
        'custom_size_value',
        'mrp',
        'quantity',
        'price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'mrp' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'custom_size_value' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->relationLoaded('variant') && $this->variant) {
            $variantImg = $this->variant->primary_image_url;
            if ($variantImg && !str_contains($variantImg, 'placeholder.png')) {
                return $variantImg;
            }
        } elseif (!$this->relationLoaded('variant') && $this->product_variant_id) {
            $variant = $this->variant;
            if ($variant && $variant->primary_image_url && !str_contains($variant->primary_image_url, 'placeholder.png')) {
                return $variant->primary_image_url;
            }
        }

        return $this->product?->primary_image_url ?? asset('website/assets/images/no-image.svg');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }
}
