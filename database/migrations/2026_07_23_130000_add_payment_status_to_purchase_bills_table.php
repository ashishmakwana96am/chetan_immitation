<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->tinyInteger('payment_status')->default(1)->after('payment_method')->comment('1 = Pending, 2 = Paid');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
