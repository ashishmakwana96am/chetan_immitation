<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationBalanceTransaction extends Model
{
    use SoftDeletes;
    const TYPE_CREDIT = 'credit';

    const TYPE_DEBIT = 'debit';

    const BALANCE_TYPE_CASH = 'cash';

    const BALANCE_TYPE_BANK = 'bank';

    protected $fillable = [
        'location_id',
        'expense_id',
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

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getFallbackUserId(?int $preferredUserId = null): ?int
    {
        if ($preferredUserId && User::where('id', $preferredUserId)->exists()) {
            return $preferredUserId;
        }
        if (auth()->check() && auth()->id() && User::where('id', auth()->id())->exists()) {
            return auth()->id();
        }
        $adminId = User::whereHas('roles', function ($q) {
            $q->where('name', 'super-admin');
        })->value('id');

        if ($adminId) {
            return $adminId;
        }

        return User::orderBy('id', 'asc')->value('id');
    }

    public static function syncLocationBalance(?int $locationId, ?string $balanceType): void
    {
        if (!$locationId || !$balanceType) return;

        $transactions = self::where('location_id', $locationId)
            ->where('balance_type', $balanceType)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0.0;
        foreach ($transactions as $tx) {
            $amt = (float) $tx->amount;
            if ($tx->type === self::TYPE_CREDIT) {
                $runningBalance += $amt;
            } else {
                $runningBalance -= $amt;
            }

            if (abs((float)$tx->balance_after - round($runningBalance, 2)) > 0.001) {
                self::withoutEvents(fn() => $tx->update(['balance_after' => round($runningBalance, 2)]));
            }
        }

        $balanceCol = $balanceType === self::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $balanceRecord = LocationBalance::firstOrCreate(['location_id' => $locationId]);
        $balanceRecord->update([$balanceCol => round($runningBalance, 2)]);
    }
}
