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
        ];
    }

    public static function boot()
    {
        parent::boot();
        self::created(function ($model) {
            $model->balance()->create(['cash_balance' => 0.00, 'bank_balance' => 0.00]);
        });
    }

    public function balance()
    {
        return $this->hasOne(LocationBalance::class);
    }

    public function getCashBalanceAttribute()
    {
        $balance = $this->balance()->first();
        return $balance ? (float) $balance->cash_balance : 0.00;
    }

    public function getBankBalanceAttribute()
    {
        $balance = $this->balance()->first();
        return $balance ? (float) $balance->bank_balance : 0.00;
    }

    public function setCashBalanceAttribute($value)
    {
        $balance = $this->balance()->firstOrCreate([], ['cash_balance' => 0.00, 'bank_balance' => 0.00]);
        $balance->update(['cash_balance' => $value]);
    }

    public function setBankBalanceAttribute($value)
    {
        $balance = $this->balance()->firstOrCreate([], ['cash_balance' => 0.00, 'bank_balance' => 0.00]);
        $balance->update(['bank_balance' => $value]);
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
