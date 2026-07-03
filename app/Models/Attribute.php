<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use SoftDeletes;
    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'created_by',
        'sort_order',
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
