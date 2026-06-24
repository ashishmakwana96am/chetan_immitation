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

        $fields = [
            'terms_conditions',
            'delivery_returns',
            'privacy_policy',
            'refund_cancellation',
        ];

        $clearedFields = [];

        foreach ($fields as $field) {
            $newValue = $request->$field ?? '';
            $existingValue = Setting::getValue($field, '');

            if ($newValue === '' && $existingValue !== '') {
                $clearedFields[] = $field;
            }

            Setting::setValue($field, $newValue);
        }

        $message = 'Website content updated successfully.';

        if (!empty($clearedFields) && !$request->confirmed_clear) {
            $fieldLabels = [
                'terms_conditions' => 'Terms & Conditions',
                'delivery_returns' => 'Delivery & Returns',
                'privacy_policy' => 'Privacy Policy',
                'refund_cancellation' => 'Refund & Cancellation',
            ];
            $labels = array_map(fn($f) => $fieldLabels[$f] ?? $f, $clearedFields);
            $message = 'Updated. Note: ' . implode(', ', $labels) . ' content ' . (count($clearedFields) > 1 ? 'were' : 'was') . ' cleared.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }
}
