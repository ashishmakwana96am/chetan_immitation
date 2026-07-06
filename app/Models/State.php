<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'shipping_charge',
        'delivery_days',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'shipping_charge' => 'decimal:2',
            'delivery_days' => 'integer',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
