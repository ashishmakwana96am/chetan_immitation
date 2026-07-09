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

    protected $fillable = [
        'supplier_id',
        'invoice_no',
        'total_amount',
        'status',
        'payment_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
