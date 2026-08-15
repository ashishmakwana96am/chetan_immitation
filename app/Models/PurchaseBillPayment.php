<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBillPayment extends Model
{
    protected $fillable = [
        'purchase_bill_id',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function purchaseBill()
    {
        return $this->belongsTo(PurchaseBill::class, 'purchase_bill_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
