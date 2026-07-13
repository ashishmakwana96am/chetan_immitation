<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes, LogsActivity;

    public function activityModule(): string
    {
        return 'Purchase';
    }

    const STATUS_PENDING = 1;

    const STATUS_APPROVE = 2;

    const STATUS_DECLINE = 3;

    const PAYMENT_STATUS_PENDING = 1;

    const PAYMENT_STATUS_PAID = 2;

    const PAYMENT_STATUS_PARTIAL = 3;

    protected $fillable = [
        'supplier_id',
        'invoice_no',
        'is_gst',
        'total_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'status',
        'payment_status',
        'paid_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'is_gst' => 'boolean',
            'tax_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class)->latest();
    }

    public function getBalanceDueAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
