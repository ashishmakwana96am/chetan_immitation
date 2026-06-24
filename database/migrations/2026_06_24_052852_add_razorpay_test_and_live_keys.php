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
        $existingKeyId = Setting::getValue('razorpay_key_id', 'rzp_test_T4chvpFsbQQfN1');
        $existingSecret = Setting::getValue('razorpay_key_secret', 'tnjNeBopDtsifql61WXubBUB');

        Setting::setValue('razorpay_test_key_id', $existingKeyId);
        Setting::setValue('razorpay_test_key_secret', $existingSecret);

        Setting::setValue('razorpay_live_key_id', 'rzp_live_5h3j4k2l1m5o7p');
        Setting::setValue('razorpay_live_key_secret', 'liveSecretKeyStringPlaceholderXYZ');

        Setting::setValue('razorpay_payment_mode', 'test');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'razorpay_test_key_id',
            'razorpay_test_key_secret',
            'razorpay_live_key_id',
            'razorpay_live_key_secret',
            'razorpay_payment_mode'
        ])->delete();
    }
};
