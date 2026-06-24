<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-seeding not required as legacy keys are fully deprecated.
    }
};
