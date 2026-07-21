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
        
        if (!$product) {
            return 0.0;
        }

        if ($variant) {
            return (float) $variant->sale_price;
        }

        if ($product->pair_product) {
            $sizeRow = $this->matchingCustomSize($product);
            if ($sizeRow) {
                return (float) $sizeRow['sale_price'];
            }
        }

        return (float) $product->sale_price;
    }

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

        if ($product->pair_product) {
            $sizeRow = $this->matchingCustomSize($product);
            if ($sizeRow) {
                return (float) $sizeRow['mrp'];
            }
        }

        return (float) $product->mrp;
    }

    private function matchingCustomSize(Product $product): ?array
    {
        $value = (float) $this->custom_size_value;

        if ($value > 0) {
            foreach ($product->custom_sizes ?? [] as $row) {
                if (abs((float) ($row['size'] ?? 0) - $value) < 0.001) {
                    return $row;
                }
            }
        }

        return collect($product->custom_sizes ?? [])->sortBy(fn($r) => (float)($r['size'] ?? 0))->first();
    }
}
