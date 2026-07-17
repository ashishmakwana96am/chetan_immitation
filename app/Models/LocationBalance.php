<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBalance extends Model
{
    protected $fillable = [
        'location_id',
        'cash_balance',
        'bank_balance',
    ];

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
