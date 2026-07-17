<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_variant_id',
        'custom_size_value',
        'purchase_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'quantity',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'custom_size_value' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function allocations()
    {
        return $this->hasMany(PurchaseAllocation::class);
    }
}
