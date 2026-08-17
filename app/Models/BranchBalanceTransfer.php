<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchBalanceTransfer extends Model
{
    use SoftDeletes;
    public const STATUS_PENDING  = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'transfer_no',
        'from_location_id',
        'to_location_id',
        'balance_type',
        'amount',
        'status',
        'notes',
        'created_by',
        'actioned_by',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'actioned_at' => 'datetime',
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

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
