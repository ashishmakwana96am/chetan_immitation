<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'status',
        'amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    // Status constants
    const STATUS_CAPTURED = 'captured';
    const STATUS_FAILED   = 'failed';
    const STATUS_REFUNDED = 'refunded';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
