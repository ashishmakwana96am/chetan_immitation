<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes, LogsActivity;

    public function activityModule(): string
    {
        return 'Sales';
    }

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
        'is_default',
        'user_id',
        'order_no',
        'order_type',
        'status',
        'payment_status',
        'payment_method',
        'final_amount',
        'is_gst',
        'tax_amount',
        'shipping_charge',
        'source',
        'discount_type',
        'order_discount_type',
        'order_discount_value',
        'coupon_id',
        'confirmed_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancellation_reason',
        'shipped_client_url',
        'tracking_id',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_gst' => 'boolean',
            'final_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'order_discount_value' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id')->withTrashed();
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

    public function payment()
    {
        return $this->hasOne(OrderPayment::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function cancellationRequest()
    {
        return $this->hasOne(OrderCancellationRequest::class)->latestOfMany();
    }

    public function cancellationRequests()
    {
        return $this->hasMany(OrderCancellationRequest::class);
    }
}
