<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellationRequest extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'order_id',
        'cancellation_reason',
        'status',
        'refund_amount',
        'refund_gateway_id',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
