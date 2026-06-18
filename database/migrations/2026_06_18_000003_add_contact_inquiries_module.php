<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $parent = Module::firstOrCreate(
            ['name' => 'Website Content'],
            [
                'icon'           => null,
                'route'          => null,
                'active_pattern' => 'admin/website-content*,admin/contact-inquiries*',
                'permission'     => null,
                'sort_order'     => 8,
            ]
        );

        $parent->update([
            'active_pattern' => 'admin/website-content*,admin/contact-inquiries*',
        ]);

        Module::updateOrCreate(
            ['route' => 'admin.contact-inquiries.index'],
            [
                'parent_id'      => $parent->id,
                'name'           => 'Contact Inquiries',
                'icon'           => 'ti ti-mail',
                'active_pattern' => 'admin/contact-inquiries*',
                'permission'     => 'view contact inquiries',
                'sort_order'     => 2,
            ]
        );
    }

    public function down(): void
    {
        Module::where('route', 'admin.contact-inquiries.index')->delete();
    }
};
