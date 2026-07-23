<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->unsignedInteger('index')->default(0)->after('sort_order');
        });

        $attributes = DB::table('attributes')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        foreach ($attributes as $i => $attrId) {
            DB::table('attributes')
                ->where('id', $attrId)
                ->update(['index' => $i + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn('index');
        });
    }
};
