<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;
use App\Services\ActivityLogger;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('view settings');

        $razorpayPaymentMode   = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayTestKeyId     = Setting::getValue('razorpay_test_key_id', '');
        $razorpayTestKeySecret = Setting::getValue('razorpay_test_key_secret', '');
        $razorpayLiveKeyId     = Setting::getValue('razorpay_live_key_id', '');
        $razorpayLiveKeySecret = Setting::getValue('razorpay_live_key_secret', '');
        $announcementText      = Setting::getValue('announcement_text', '');
        $paymentMethodCod      = (bool) Setting::getValue('payment_method_cod', true);
        $paymentMethodRazorpay = (bool) Setting::getValue('payment_method_razorpay', true);
        $comingSoon            = (bool) Setting::getValue('coming_soon', false);

        return view('settings.index', compact(
            'razorpayPaymentMode',
            'razorpayTestKeyId',
            'razorpayTestKeySecret',
            'razorpayLiveKeyId',
            'razorpayLiveKeySecret',
            'announcementText',
            'paymentMethodCod',
            'paymentMethodRazorpay',
            'comingSoon'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $validator = Validator::make($request->all(), [
            'razorpay_payment_mode'   => ['nullable', 'string', 'in:test,live'],
            'razorpay_test_key_id'    => ['nullable', 'string', 'max:255'],
            'razorpay_test_key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_live_key_id'    => ['nullable', 'string', 'max:255'],
            'razorpay_live_key_secret' => ['nullable', 'string', 'max:255'],
            'announcement_text'       => ['nullable', 'string', 'max:500'],
            'payment_method_cod'      => ['nullable', 'boolean'],
            'payment_method_razorpay' => ['nullable', 'boolean'],
            'coming_soon'             => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $codEnabled      = $request->boolean('payment_method_cod');
        $razorpayEnabled = $request->boolean('payment_method_razorpay');

        if (!$codEnabled && !$razorpayEnabled) {
            return response()->json([
                'status'  => 'error',
                'message' => ['payment_method_cod' => ['At least one payment method must be enabled.']],
            ], 422);
        }

        $sensitiveKeys = ['razorpay_test_key_id', 'razorpay_test_key_secret', 'razorpay_live_key_id', 'razorpay_live_key_secret'];
        $newValues = [
            'razorpay_payment_mode'   => $request->razorpay_payment_mode ?? 'test',
            'razorpay_test_key_id'    => $request->razorpay_test_key_id ?? '',
            'razorpay_test_key_secret' => $request->razorpay_test_key_secret ?? '',
            'razorpay_live_key_id'    => $request->razorpay_live_key_id ?? '',
            'razorpay_live_key_secret' => $request->razorpay_live_key_secret ?? '',
            'announcement_text'       => $request->announcement_text ?? '',
            'payment_method_cod'      => $codEnabled ? '1' : '0',
            'payment_method_razorpay' => $razorpayEnabled ? '1' : '0',
            'coming_soon'             => $request->boolean('coming_soon') ? '1' : '0',
        ];

        $old = [];
        $new = [];
        foreach ($newValues as $key => $value) {
            $previous = (string) Setting::getValue($key, '');
            if ($previous !== (string) $value) {
                $old[$key] = in_array($key, $sensitiveKeys, true) ? '••••••••' : $previous;
                $new[$key] = in_array($key, $sensitiveKeys, true) ? '••••••••' : $value;
            }
            Setting::setValue($key, $value);
        }

        if (!empty($new)) {
            ActivityLogger::log('Settings', 'update', null, $old, $new, 'Payment/site settings updated');
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Settings updated successfully.',
        ]);
    }
}
