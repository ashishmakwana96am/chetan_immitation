<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('payment_status');
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `purchases` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Paid, 3 = Partially Paid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        DB::statement("ALTER TABLE `purchases` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Paid'");
    }
};
