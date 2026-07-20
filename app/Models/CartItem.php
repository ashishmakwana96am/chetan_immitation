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
        'custom_size_value',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'custom_size_value' => 'decimal:2',
        ];
    }

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

        if ($product->pair_product && $product->pair_mode === 'custom_size' && $this->custom_size_value) {
            $sizeRow = $this->matchingCustomSize($product);
            if ($sizeRow) {
                return (float) $sizeRow['sale_price'];
            }
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

        if ($product->pair_product && $product->pair_mode === 'custom_size' && $this->custom_size_value) {
            $sizeRow = $this->matchingCustomSize($product);
            if ($sizeRow) {
                return (float) $sizeRow['mrp'];
            }
        }

        // Regular product: check if pair_type is 'pair' and pair MRP exists
        if ($pairType === 'pair' && $product->pair_product && $product->pair_mrp) {
            return (float) $product->pair_mrp;
        }

        return (float) $product->mrp;
    }

    /**
     * Find the configured custom-size row (size/sale_price/mrp) matching this
     * cart item's chosen size, using the same 0.001 tolerance as SaleController.
     */
    private function matchingCustomSize(Product $product): ?array
    {
        $value = (float) $this->custom_size_value;

        foreach ($product->custom_sizes ?? [] as $row) {
            if (abs((float) ($row['size'] ?? 0) - $value) < 0.001) {
                return $row;
            }
        }

        return null;
    }
}
