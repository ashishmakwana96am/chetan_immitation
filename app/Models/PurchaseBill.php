<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PurchaseBill extends Model
{
    use LogsActivity;

    public function activityModule(): string
    {
        return 'Purchase Bill';
    }

    const STATUS_PENDING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;

    protected $fillable = [
        'transfer_no',
        'from_location_id',
        'to_location_id',
        'status',
        'remarks',
        'created_by',
        'accepted_by',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'accepted_at' => 'datetime',
        ];
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseBillItem::class);
    }
}
