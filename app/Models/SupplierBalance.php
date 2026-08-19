<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierBalance extends Model
{
    protected $fillable = [
        'supplier_id',
        'balance',
        'cash_balance',
        'bank_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance'      => 'decimal:2',
            'cash_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
