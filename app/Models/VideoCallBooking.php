<?php

namespace App\Models;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

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

    public static function autoCompletePastBookings(): int
    {
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        $expiredBookings = self::where('status', 'scheduled')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('booking_date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $currentTime) {
                      $q2->where('booking_date', $today)
                         ->where('time_slot_end', '<=', $currentTime);
                  });
            })
            ->get();

        if ($expiredBookings->isEmpty()) {
            return 0;
        }

        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'completed']);
            ActivityLogger::log(
                'Video Call Bookings',
                'complete',
                $booking,
                ['status' => 'scheduled'],
                ['status' => 'completed'],
                'Video call booking #' . $booking->booking_no . ' marked as completed'
            );
        }

        Cache::forget('admin_sidebar_scheduled_video_calls_count');

        return $expiredBookings->count();
    }

    public static function getActiveBookingForCustomer(int $customerId): ?self
    {
        self::autoCompletePastBookings();

        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        return self::where('customer_id', $customerId)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('booking_date', '>', $today)
                  ->orWhere(function ($q2) use ($today, $currentTime) {
                      $q2->where('booking_date', $today)
                         ->where('time_slot_end', '>', $currentTime);
                  });
            })
            ->latest('id')
            ->first();
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
