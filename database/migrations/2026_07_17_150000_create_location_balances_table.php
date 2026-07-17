<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->decimal('cash_balance', 10, 2)->default(0.00);
            $table->decimal('bank_balance', 10, 2)->default(0.00);
            $table->timestamps();
        });

        $hasCash = Schema::hasColumn('locations', 'cash_balance');
        $hasBank = Schema::hasColumn('locations', 'bank_balance');

        $locations = DB::table('locations')->get();
        foreach ($locations as $loc) {
            DB::table('location_balances')->insert([
                'location_id'  => $loc->id,
                'cash_balance' => $hasCash ? ($loc->cash_balance ?? 0.00) : 0.00,
                'bank_balance' => $hasBank ? ($loc->bank_balance ?? 0.00) : 0.00,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $colsToDrop = [];
        if ($hasCash) {
            $colsToDrop[] = 'cash_balance';
        }
        if ($hasBank) {
            $colsToDrop[] = 'bank_balance';
        }

        if (!empty($colsToDrop)) {
            Schema::table('locations', function (Blueprint $table) use ($colsToDrop) {
                $table->dropColumn($colsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('cash_balance', 10, 2)->default(0.00);
            $table->decimal('bank_balance', 10, 2)->default(0.00);
        });

        $balances = DB::table('location_balances')->get();
        foreach ($balances as $bal) {
            DB::table('locations')->where('id', $bal->location_id)->update([
                'cash_balance' => $bal->cash_balance,
                'bank_balance' => $bal->bank_balance,
            ]);
        }

        Schema::dropIfExists('location_balances');
    }
};
