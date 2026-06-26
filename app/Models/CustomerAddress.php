<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
