<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use SoftDeletes, LogsActivity;

    public function activityModule(): string
    {
        return 'Collection';
    }

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'short_name',
        'status',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product');
    }

    public function getDisplayNameAttribute(): string
    {
        return !empty(trim((string) $this->name)) ? $this->name : ($this->short_name ?? '');
    }
}
