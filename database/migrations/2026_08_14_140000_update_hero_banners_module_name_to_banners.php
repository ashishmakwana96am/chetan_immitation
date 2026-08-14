<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Module::where('name', 'Hero Banners')->update(['name' => 'Banners']);
    }

    public function down(): void
    {
        Module::where('name', 'Banners')->where('route', 'admin.banners.index')->update(['name' => 'Hero Banners']);
    }
};
