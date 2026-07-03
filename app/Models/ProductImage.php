<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('uploads/'.$this->image_path);
    }
}
