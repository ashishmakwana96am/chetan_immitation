<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;
    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'usage_limit',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
        'usage_limit' => 'integer',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
