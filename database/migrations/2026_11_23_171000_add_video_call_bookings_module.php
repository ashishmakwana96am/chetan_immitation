<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $parent = Module::where('name', 'Website Content')->first();

        if ($parent) {
            $patterns = array_filter(explode(',', (string) $parent->active_pattern));
            if (!in_array('admin/video-call-bookings*', $patterns, true)) {
                $patterns[] = 'admin/video-call-bookings*';
                $parent->update([
                    'active_pattern' => implode(',', $patterns),
                ]);
            }

            Module::updateOrCreate(
                ['route' => 'admin.video-call-bookings.index'],
                [
                    'parent_id'      => $parent->id,
                    'name'           => 'Video Call Bookings',
                    'icon'           => 'ti ti-video',
                    'active_pattern' => 'admin/video-call-bookings*',
                    'permission'     => 'view video call bookings',
                    'sort_order'     => 4,
                ]
            );
        }
    }

    public function down(): void
    {
        Module::where('route', 'admin.video-call-bookings.index')->delete();
    }
};
