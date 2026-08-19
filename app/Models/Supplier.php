<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, LogsActivity;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'state',
        'gst_no',
        'status',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function balanceRecord()
    {
        return $this->hasOne(SupplierBalance::class);
    }

    public function advancePayments()
    {
        return $this->hasMany(SupplierAdvancePayment::class, 'supplier_id');
    }

    public function getAdvanceBalanceAttribute()
    {
        return (float) ($this->balanceRecord->balance ?? 0.00);
    }
}
