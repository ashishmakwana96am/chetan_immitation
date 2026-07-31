<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        Module::where('route', 'admin.reports.customer-report')->update(['name' => 'Customer Credit Report']);

        Cache::forget('admin_sidebar_modules');
    }

    public function down(): void
    {
        Module::where('route', 'admin.reports.customer-report')->update(['name' => 'Customer Report']);

        Cache::forget('admin_sidebar_modules');
    }
};
