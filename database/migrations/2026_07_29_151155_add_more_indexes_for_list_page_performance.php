<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_status');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('status');
            $table->index(['category_id', 'status']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('category');
            $table->index('payment_method');
            $table->index('expense_date');
            $table->index(['location_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['category_id', 'status']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['expense_date']);
            $table->dropIndex(['location_id', 'expense_date']);
        });
    }
};
