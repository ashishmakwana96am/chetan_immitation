<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPhone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'phone',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
