<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerBalance extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'balance',
        'cash_balance',
        'bank_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance'      => 'decimal:2',
            'cash_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
