<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wishlist extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'product_id',
        'product_variant_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->relationLoaded('productVariant') && $this->productVariant) {
            $variantImg = $this->productVariant->primary_image_url;
            if ($variantImg && !str_contains($variantImg, 'placeholder.png')) {
                return $variantImg;
            }
        } elseif (!$this->relationLoaded('productVariant') && $this->product_variant_id) {
            $variant = $this->productVariant;
            if ($variant && $variant->primary_image_url && !str_contains($variant->primary_image_url, 'placeholder.png')) {
                return $variant->primary_image_url;
            }
        }

        return $this->product?->primary_image_url ?? asset('website/assets/images/no-image.svg');
    }
}
