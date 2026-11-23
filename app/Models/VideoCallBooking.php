<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_no',
        'customer_id',
        'booking_date',
        'time_slot_start',
        'time_slot_end',
        'notes',
        'status',
        'google_event_id',
        'google_meet_link',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public static function generateBookingNo(): string
    {
        $year = date('Y');
        $lastBooking = self::whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $nextNum = 1;
        if ($lastBooking && preg_match('/VC-' . $year . '-(\d+)/', $lastBooking->booking_no, $matches)) {
            $nextNum = ((int) $matches[1]) + 1;
        }

        return sprintf('VC-%s-%04d', $year, $nextNum);
    }
}
