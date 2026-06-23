<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATUS_PENDING = 1;
    const STATUS_APPROVE = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_OUT_FOR_DELIVERY = 4;
    const STATUS_DELIVERED = 5;
    const STATUS_DECLINE = 6;

    const PAYMENT_STATUS_PENDING = 1;
    const PAYMENT_STATUS_PAID = 2;

    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'location_id',
        'user_id',
        'order_no',
        'order_type',
        'status',
        'payment_status',
        'payment_method',
        'final_amount',
        'source',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'discount_type',
        'coupon_id',
        'confirmed_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'final_amount'        => 'decimal:2',
            'confirmed_at'        => 'datetime',
            'shipped_at'          => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at'        => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
