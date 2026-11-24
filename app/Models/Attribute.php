<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Attribute extends Model
{
    use SoftDeletes, LogsActivity;
    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'created_by',
        'sort_order',
        'index',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('active_attributes_list'));
        static::deleted(fn () => Cache::forget('active_attributes_list'));
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
