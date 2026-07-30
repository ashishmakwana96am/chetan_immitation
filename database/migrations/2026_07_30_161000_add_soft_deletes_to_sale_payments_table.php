<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_payments') && !Schema::hasColumn('sale_payments', 'deleted_at')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_payments') && Schema::hasColumn('sale_payments', 'deleted_at')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
