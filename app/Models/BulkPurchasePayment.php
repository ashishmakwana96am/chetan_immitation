<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkPurchasePayment extends Model
{
    protected $fillable = [
        'total_amount',
        'location_id',
        'payment_method',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
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
}
