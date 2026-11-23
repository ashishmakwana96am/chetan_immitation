<?php

namespace App\Http\Controllers;

use App\Models\VideoCallBooking;
use App\Services\GoogleCalendarService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VideoCallBookingController extends Controller
{
    public function storeCustomerBooking(Request $request)
    {
        $customer = auth('customer')->user();
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please log in to book a video call.',
            ], 401);
        }

        $maxBookingDate = now()->addMonths(3)->format('Y-m-d');
        $validator = Validator::make($request->all(), [
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:' . $maxBookingDate],
            'time_slot_start' => ['required', 'date_format:H:i'],
            'time_slot_end' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'booking_date.before_or_equal' => 'Booking date cannot be more than 3 months in advance.',
            'booking_date.after_or_equal' => 'Booking date cannot be in the past.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $bookingDate = Carbon::parse($request->booking_date);
        $slotStart = Carbon::parse($request->booking_date . ' ' . $request->time_slot_start);
        $slotEnd = Carbon::parse($request->booking_date . ' ' . $request->time_slot_end);

        if ($bookingDate->isToday() && $slotStart->lt(now()->addHour())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a slot starting at least 1 hour after current time.',
            ], 422);
        }

        $alreadyBooked = VideoCallBooking::where('booking_date', $request->booking_date)
            ->where('time_slot_start', $request->time_slot_start)
            ->where('status', 'scheduled')
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'status' => 'error',
                'message' => 'This slot is already booked. Please choose another time slot.',
            ], 422);
        }

        $calService = app(GoogleCalendarService::class);
        $summary = 'Video Shopping Call - ' . $customer->name;
        $description = "Video Shopping Call with Chetan Imitation\n"
            . "Customer: {$customer->name}\n"
            . "Phone: " . ($customer->phone ?? '-') . "\n"
            . "Email: " . ($customer->email ?? '-') . "\n"
            . "Notes: " . ($request->notes ?: 'None');

        $calResult = $calService->createMeetingEvent(
            $summary,
            $description,
            $slotStart,
            $slotEnd,
            $customer->email
        );

        $booking = VideoCallBooking::create([
            'booking_no' => VideoCallBooking::generateBookingNo(),
            'customer_id' => $customer->id,
            'booking_date' => $request->booking_date,
            'time_slot_start' => $request->time_slot_start,
            'time_slot_end' => $request->time_slot_end,
            'notes' => $request->notes,
            'status' => 'scheduled',
            'google_event_id' => $calResult['event_id'] ?? null,
            'google_meet_link' => $calResult['meet_link'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Your video shopping call has been booked successfully!',
            'booking' => [
                'booking_no' => $booking->booking_no,
                'booking_date' => $booking->booking_date->format('d M Y'),
                'time_slot' => Carbon::parse($booking->time_slot_start)->format('h:i A') . ' - ' . Carbon::parse($booking->time_slot_end)->format('h:i A'),
                'google_meet_link' => $booking->google_meet_link,
            ],
        ]);
    }

    public function index()
    {
        $this->authorize('view video call bookings');
        return view('video-calls.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view video call bookings');

        $bookings = VideoCallBooking::with('customer')->orderBy('id', 'desc')->get();
        $canCancel = auth()->user()->can('cancel video call bookings');

        $data = $bookings->map(function ($booking, $index) use ($canCancel) {
            $meetBtn = ($booking->google_meet_link && $booking->status === 'scheduled')
                ? '<a href="' . e($booking->google_meet_link) . '" target="_blank" class="btn btn-sm btn-label-success d-inline-flex align-items-center gap-1"><i class="ti ti-video"></i> Join Meet</a>'
                : '<span class="text-muted">-</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';

            if ($booking->google_meet_link && $booking->status !== 'cancelled') {
                $actions .= '<a href="' . e($booking->google_meet_link) . '" target="_blank" class="dropdown-item text-success"><i class="ti ti-video me-2"></i>Join Meet</a>';
            }

            if ($booking->status !== 'cancelled' && $canCancel) {
                if ($booking->google_meet_link) {
                    $actions .= '<div class="dropdown-divider"></div>';
                }
                $actions .= '<button type="button" class="dropdown-item text-danger btn-cancel-booking" data-id="' . $booking->id . '" data-booking-no="' . e($booking->booking_no) . '"><i class="ti ti-x me-2"></i>Cancel</button>';
            }

            if ($booking->status === 'cancelled') {
                $actions .= '<span class="dropdown-item text-muted disabled"><i class="ti ti-ban me-2"></i>Cancelled</span>';
            }

            $actions .= '</div></div>';

            $dateStr = $booking->booking_date ? $booking->booking_date->format('d M Y') : '-';
            $startStr = $booking->time_slot_start ? Carbon::parse($booking->time_slot_start)->format('h:i A') : '';
            $endStr = $booking->time_slot_end ? Carbon::parse($booking->time_slot_end)->format('h:i A') : '';
            $slotHtml = '<div><strong>' . $dateStr . '</strong><br><small class="text-muted">' . $startStr . ' - ' . $endStr . '</small></div>';

            return [
                'index' => $index + 1,
                'booking_no' => '<code style="color: #e83e8c !important;">' . e($booking->booking_no) . '</code>',
                'customer_name' => e($booking->customer?->name ?? 'Walk-in / Unknown'),
                'customer_phone' => e($booking->customer?->phone ?? '-'),
                'customer_email' => e($booking->customer?->email ?? '-'),
                'slot_info' => $slotHtml,
                'google_meet' => $meetBtn,
                'actions' => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function cancel(Request $request, VideoCallBooking $booking)
    {
        $this->authorize('cancel video call bookings');

        if ($booking->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'This booking is already cancelled.',
            ], 422);
        }

        $calService = app(GoogleCalendarService::class);
        if ($booking->google_event_id) {
            $calService->cancelMeetingEvent($booking->google_event_id);
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        ActivityLogger::log('Video Call Bookings', 'cancel', $booking, ['status' => 'scheduled'], ['status' => 'cancelled'], 'Cancelled video call booking #' . $booking->booking_no);

        return response()->json([
            'status' => 'success',
            'message' => 'Video call booking #' . $booking->booking_no . ' has been cancelled.',
        ]);
    }
}
