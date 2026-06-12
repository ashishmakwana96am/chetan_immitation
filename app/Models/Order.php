<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'location_id',
        'user_id',
        'order_no',
        'order_type',
        'status',
        'payment_status',
        'payment_method',
        'final_amount',
        'source',
        'discount_type',
        'coupon_id',
    ];

    protected function casts(): array
    {
        return [
            'final_amount'    => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
