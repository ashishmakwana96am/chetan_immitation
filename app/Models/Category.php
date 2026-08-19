<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes, LogsActivity;

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

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('active_categories_list'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('active_categories_list'));
    }

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
