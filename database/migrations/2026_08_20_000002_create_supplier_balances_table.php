<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_balances')) {
            Schema::create('supplier_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->decimal('balance', 15, 2)->default(0.00);
                $table->decimal('cash_balance', 15, 2)->default(0.00);
                $table->decimal('bank_balance', 15, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // Backfill supplier balances for existing suppliers
        $suppliers = DB::table('suppliers')->get();
        foreach ($suppliers as $supplier) {
            $exists = DB::table('supplier_balances')->where('supplier_id', $supplier->id)->exists();
            if (!$exists) {
                DB::table('supplier_balances')->insert([
                    'supplier_id'  => $supplier->id,
                    'balance'      => 0.00,
                    'cash_balance' => 0.00,
                    'bank_balance' => 0.00,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_balances');
    }
};
