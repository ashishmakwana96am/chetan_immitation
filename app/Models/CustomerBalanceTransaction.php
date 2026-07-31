<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerBalanceTransaction extends Model
{
    use SoftDeletes;

    const TYPE_CREDIT = 'credit';

    const TYPE_DEBIT = 'debit';

    const SOURCE_CASH = 'cash';

    const SOURCE_BANK = 'bank';

    protected $fillable = [
        'customer_id',
        'source',
        'type',
        'amount',
        'balance_after',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
