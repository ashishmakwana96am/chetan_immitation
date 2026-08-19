<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;
    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected static function booted(): void
    {
        static::saved(fn () => Product::clearMappedCaches());
        static::deleted(fn () => Product::clearMappedCaches());
    }

    protected $fillable = [
        'product_id',
        'attribute_value_id',
        'purchase_price',
        'sale_price',
        'status',
        'custom_sizes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function getCustomSizesAttribute($value)
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($decoded)) {
            return collect($decoded)->sortBy(fn ($item) => (float) ($item['size'] ?? 0))->values()->toArray();
        }
        return $decoded;
    }

    public function setCustomSizesAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (is_array($value)) {
            $value = collect($value)->sortBy(fn ($item) => (float) ($item['size'] ?? 0))->values()->toArray();
        }
        $this->attributes['custom_sizes'] = $value ? json_encode($value) : null;
    }
}
