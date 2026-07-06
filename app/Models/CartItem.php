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
     * Get the correct price
     */
    public function getPrice(): float
    {
        $product = $this->product;
        $variant = $this->productVariant;
        
        if (!$product) {
            return 0.0;
        }
        
        if ($variant) {
            return (float) $variant->sale_price;
        }

        return (float) $product->sale_price;
    }

    /**
     * Get the correct MRP
     */
    public function getMrp(): float
    {
        $product = $this->product;
        $variant = $this->productVariant;
        
        if (!$product) {
            return 0.0;
        }
        
        if ($variant) {
            return (float) $product->mrp;
        }

        return (float) $product->mrp;
    }
}
