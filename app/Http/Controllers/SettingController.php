<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('view settings');

        $razorpayPaymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayTestKeyId = Setting::getValue('razorpay_test_key_id', '');
        $razorpayTestKeySecret = Setting::getValue('razorpay_test_key_secret', '');
        $razorpayLiveKeyId = Setting::getValue('razorpay_live_key_id', '');
        $razorpayLiveKeySecret = Setting::getValue('razorpay_live_key_secret', '');
        $announcementText = Setting::getValue('announcement_text', '');

        return view('settings.index', compact(
            'razorpayPaymentMode',
            'razorpayTestKeyId',
            'razorpayTestKeySecret',
            'razorpayLiveKeyId',
            'razorpayLiveKeySecret',
            'announcementText'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $validator = Validator::make($request->all(), [
            'razorpay_payment_mode' => ['nullable', 'string', 'in:test,live'],
            'announcement_text' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Setting::setValue('razorpay_payment_mode', $request->razorpay_payment_mode ?? 'test');
        Setting::setValue('announcement_text', $request->announcement_text ?? '');

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully.',
        ]);
    }
}
