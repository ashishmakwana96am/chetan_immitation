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
            $user = auth('web')->user();

            UtilityReport::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->name,
                'user_role'     => $user?->role?->name,
                'location_id'   => $user?->location_id,
                'location_name' => $user?->location_id ? Location::find($user->location_id)?->name : null,
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
}
