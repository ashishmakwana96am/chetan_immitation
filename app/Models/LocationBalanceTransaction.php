<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBalanceTransaction extends Model
{
    const TYPE_CREDIT = 'credit';

    const TYPE_DEBIT = 'debit';

    const BALANCE_TYPE_CASH = 'cash';

    const BALANCE_TYPE_BANK = 'bank';

    protected $fillable = [
        'location_id',
        'balance_type',
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

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getFallbackUserId(?int $preferredUserId = null): int
    {
        if ($preferredUserId) {
            return $preferredUserId;
        }
        if (auth()->check() && auth()->id()) {
            return auth()->id();
        }
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'super-admin');
        })->value('id') ?? 1;
    }
}
