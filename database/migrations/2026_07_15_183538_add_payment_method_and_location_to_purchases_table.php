<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('payment_status');
            $table->unsignedBigInteger('location_id')->nullable()->after('supplier_id');
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['payment_method', 'location_id']);
        });
    }
};
