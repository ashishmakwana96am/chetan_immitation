<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'amount',
        'cash_amount',
        'online_amount',
        'payment_method',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'cash_amount'   => 'decimal:2',
            'online_amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
