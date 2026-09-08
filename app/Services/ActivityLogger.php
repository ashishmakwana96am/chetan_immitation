<?php

namespace App\Services;

use App\Models\UtilityReport;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Record a single activity log entry. Never throws — a logging failure
     * must never break the real business operation it's observing.
     */
    public static function log(
        string $module,
        string $action,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null
    ): void {
        try {
            $user = auth()->user() ?? auth('web')->user();

            $locationId = self::resolveLocationId($user, $subject, $old, $new);
            $locationName = null;
            if ($locationId) {
                if ($subject instanceof Location && $subject->id == $locationId) {
                    $locationName = $subject->name;
                } else {
                    $locationName = Location::find($locationId)?->name;
                }
            }

            UtilityReport::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->name,
                'user_role'     => $user ? ($user->roles->first()?->name ?? 'User') : null,
                'location_id'   => $locationId,
                'location_name' => $locationName,
                'module'        => $module,
                'action'        => $action,
                'subject_type'  => $subject ? get_class($subject) : null,
                'subject_id'    => $subject?->getKey(),
                'description'   => $description,
                'old_values'    => $old,
                'new_values'    => $new,
                'ip_address'    => request()?->ip(),
                'user_agent'    => request()?->userAgent(),
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the effective location ID from user, subject model, diffs, request, or session.
     */
    private static function resolveLocationId($user, ?Model $subject, ?array $old, ?array $new)
    {
        // 1. From User profile (if user has an assigned branch)
        if ($user && !empty($user->location_id)) {
            return $user->location_id;
        }

        // 2. From subject Model
        if ($subject) {
            if ($subject instanceof Location) {
                return $subject->id;
            }
            if (!empty($subject->location_id)) {
                return $subject->location_id;
            }
            if (!empty($subject->from_location_id)) {
                return $subject->from_location_id;
            }
            if (!empty($subject->to_location_id)) {
                return $subject->to_location_id;
            }
            if (!empty($subject->branch_id)) {
                return $subject->branch_id;
            }

            // Check nested relations if available
            if (isset($subject->order) && !empty($subject->order->location_id)) {
                return $subject->order->location_id;
            }
            if (isset($subject->purchase) && !empty($subject->purchase->location_id)) {
                return $subject->purchase->location_id;
            }
            if (isset($subject->inventory) && !empty($subject->inventory->location_id)) {
                return $subject->inventory->location_id;
            }
            if (isset($subject->transfer) && (!empty($subject->transfer->from_location_id) || !empty($subject->transfer->to_location_id))) {
                return $subject->transfer->from_location_id ?? $subject->transfer->to_location_id;
            }
        }

        // 3. From new values diff
        if (!empty($new['location_id'])) {
            return $new['location_id'];
        }
        if (!empty($new['from_location_id'])) {
            return $new['from_location_id'];
        }
        if (!empty($new['to_location_id'])) {
            return $new['to_location_id'];
        }
        if (!empty($new['branch_id'])) {
            return $new['branch_id'];
        }

        // 4. From old values diff
        if (!empty($old['location_id'])) {
            return $old['location_id'];
        }
        if (!empty($old['from_location_id'])) {
            return $old['from_location_id'];
        }
        if (!empty($old['to_location_id'])) {
            return $old['to_location_id'];
        }
        if (!empty($old['branch_id'])) {
            return $old['branch_id'];
        }

        // 5. From HTTP Request inputs / query / route params
        $req = request();
        if ($req) {
            if ($req->input('location_id')) {
                return $req->input('location_id');
            }
            if ($req->input('from_location_id')) {
                return $req->input('from_location_id');
            }
            if ($req->input('to_location_id')) {
                return $req->input('to_location_id');
            }
            if ($req->input('branch_id')) {
                return $req->input('branch_id');
            }
            if ($req->query('location_id')) {
                return $req->query('location_id');
            }
            $routeLoc = $req->route('location');
            if ($routeLoc instanceof Location) {
                return $routeLoc->id;
            } elseif (is_numeric($routeLoc)) {
                return (int) $routeLoc;
            }
            if ($req->route('location_id')) {
                return $req->route('location_id');
            }
        }

        // 6. From Session (if user/superadmin selected a branch context)
        if (session()->has('location_id')) {
            return session('location_id');
        }
        if (session()->has('active_location_id')) {
            return session('active_location_id');
        }
        if (session()->has('selected_location_id')) {
            return session('selected_location_id');
        }
        if (session()->has('branch_id')) {
            return session('branch_id');
        }

        return null;
    }
}
