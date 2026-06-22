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
        Setting::setValue('razorpay_key_id', 'rzp_test_T4chvpFsbQQfN1');
        Setting::setValue('razorpay_key_secret', 'tnjNeBopDtsifql61WXubBUB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret'])->delete();
    }
};
