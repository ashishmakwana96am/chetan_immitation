<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchasePayment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_id',
        'bulk_purchase_payment_id',
        'is_advance',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'is_advance' => 'boolean',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
