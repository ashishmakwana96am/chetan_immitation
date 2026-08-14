<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::whereIn('name', ['view banners', 'create banners', 'edit banners', 'delete banners'])
            ->update(['module' => 'Hero Section']);

        Permission::whereIn('name', ['view contact inquiries', 'delete contact inquiries'])
            ->update(['module' => 'Website Content']);
    }

    public function down(): void
    {
        Permission::whereIn('name', ['view banners', 'create banners', 'edit banners', 'delete banners'])
            ->update(['module' => 'Website Content']);

        Permission::whereIn('name', ['view contact inquiries', 'delete contact inquiries'])
            ->update(['module' => 'Contact Inquiries']);
    }
};
