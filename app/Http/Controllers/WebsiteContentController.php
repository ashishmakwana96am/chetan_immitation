<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;

class WebsiteContentController extends Controller
{
    public function index()
    {
        $this->authorize('view website content');

        $termsConditions = Setting::getValue('terms_conditions', '');
        $deliveryReturns = Setting::getValue('delivery_returns', '');
        $privacyPolicy = Setting::getValue('privacy_policy', '');
        $refundCancellation = Setting::getValue('refund_cancellation', '');

        return view('website-content.index', compact(
            'termsConditions',
            'deliveryReturns',
            'privacyPolicy',
            'refundCancellation'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit website content');

        $validator = Validator::make($request->all(), [
            'terms_conditions' => ['nullable', 'string'],
            'delivery_returns' => ['nullable', 'string'],
            'privacy_policy' => ['nullable', 'string'],
            'refund_cancellation' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Setting::setValue('terms_conditions', $request->terms_conditions ?? '');
        Setting::setValue('delivery_returns', $request->delivery_returns ?? '');
        Setting::setValue('privacy_policy', $request->privacy_policy ?? '');
        Setting::setValue('refund_cancellation', $request->refund_cancellation ?? '');

        return response()->json([
            'status' => 'success',
            'message' => 'Website content updated successfully.',
        ]);
    }
}
