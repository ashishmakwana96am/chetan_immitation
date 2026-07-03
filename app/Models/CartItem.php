<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'product_id',
        'product_variant_id',
        'pair_type',
        'qty',
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

    /**
     * Get the correct price based on pair_type
     */
    public function getPrice(): float
    {
        $product = $this->product;
        $variant = $this->productVariant;
        
        // Safety check: if product is null, return 0
        if (!$product) {
            return 0.0;
        }
        
        $pairType = $this->pair_type ?? 'single';

        if ($variant) {
            // Variant products don't have separate pair pricing
            return (float) $variant->sale_price;
        }

        // Regular product: check if pair_type is 'pair' and pair pricing exists
        if ($pairType === 'pair' && $product->pair_product && $product->pair_sale_price) {
            return (float) $product->pair_sale_price;
        }

        return (float) $product->sale_price;
    }

    /**
     * Get the correct MRP based on pair_type
     */
    public function getMrp(): float
    {
        $product = $this->product;
        $variant = $this->productVariant;
        
        // Safety check: if product is null, return 0
        if (!$product) {
            return 0.0;
        }
        
        $pairType = $this->pair_type ?? 'single';

        if ($variant) {
            // Variants use product's MRP
            return (float) $product->mrp;
        }

        // Regular product: check if pair_type is 'pair' and pair MRP exists
        if ($pairType === 'pair' && $product->pair_product && $product->pair_mrp) {
            return (float) $product->pair_mrp;
        }

        return (float) $product->mrp;
    }
}
