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
        if (!$product) {
            return 0.0;
        }

        if ($product->pair_product) {
            $sizeRow = $this->matchingCustomSize($product);
            if ($sizeRow) {
                return (float) $sizeRow['sale_price'];
            }
        }

        $variant = $this->productVariant;
        if ($variant) {
            return (float) $variant->sale_price;
        }

        return (float) $product->sale_price;
    }

    public function getMrp(): float
    {
        $product = $this->product;

        if (!$product) {
            return 0.0;
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
        // A variant with its own pack-size pricing overrides the product's shared list.
        $variant = $this->productVariant;
        $sizes = ($variant && !empty($variant->custom_sizes)) ? $variant->custom_sizes : ($product->custom_sizes ?? []);

        $value = (float) $this->custom_size_value;

        if ($value > 0) {
            foreach ($sizes as $row) {
                if (abs((float) ($row['size'] ?? 0) - $value) < 0.001) {
                    return $row;
                }
            }
        }

        return collect($sizes)->sortBy(fn($r) => (float)($r['size'] ?? 0))->first();
    }
}
