<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'purchase_price',
        'quantity',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'total'          => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations()
    {
        return $this->hasMany(PurchaseAllocation::class);
    }
}
