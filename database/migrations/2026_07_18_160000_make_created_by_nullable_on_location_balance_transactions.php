<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('location_balance_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('location_balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        DB::table('location_balance_transactions')->whereNull('created_by')->delete();

        Schema::table('location_balance_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
