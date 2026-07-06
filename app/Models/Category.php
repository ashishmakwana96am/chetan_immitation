<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    const DEFAULT_LOW_STOCK_THRESHOLD = 10;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'status',
        'is_featured',
        'created_by',
        'sort_order',
        'low_stock_threshold',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'low_stock_threshold' => 'integer',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('uploads/'.$this->image) : null;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class)->withTrashed()->where('status', SubCategory::STATUS_ACTIVE);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->withTrashed()->where('status', Product::STATUS_ACTIVE);
    }
}
