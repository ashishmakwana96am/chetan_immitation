<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes, LogsActivity;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'gst_number',
        'is_default',
        'status',
        'cash_balance',
        'bank_balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default'   => 'boolean',
            'cash_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(LocationBalanceTransaction::class);
    }
}
