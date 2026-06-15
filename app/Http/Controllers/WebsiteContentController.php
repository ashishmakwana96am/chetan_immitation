<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;
use App\Models\Location;

class WebsiteContentController extends Controller
{
    private array $settingKeys = ['site_name', 'emails', 'mobiles', 'business_hours', 'locations', 'terms_conditions', 'delivery_returns', 'privacy_policy', 'refund_cancellation'];

    public function index()
    {
        $this->authorize('view website content');

        $siteName = Setting::getValue('site_name', '');
        $emails = Setting::getValue('emails', []);
        $mobiles = Setting::getValue('mobiles', []);
        $businessHours = Setting::getValue('business_hours', []);
        $locations = Setting::getValue('locations', []);
        $termsConditions = Setting::getValue('terms_conditions', '');
        $deliveryReturns = Setting::getValue('delivery_returns', '');
        $privacyPolicy = Setting::getValue('privacy_policy', '');
        $refundCancellation = Setting::getValue('refund_cancellation', '');
        $locationModels = Location::where('status', 1)->orderBy('name')->get();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('website-content.index', compact(
            'siteName', 'emails', 'mobiles', 'businessHours',
            'locations', 'locationModels',
            'termsConditions', 'deliveryReturns', 'privacyPolicy', 'refundCancellation',
            'days'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit website content');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['nullable', 'email', 'max:255'],
            'mobiles' => ['nullable', 'array'],
            'mobiles.*' => ['nullable', 'digits:10'],
            'locations' => ['nullable', 'array'],
            'locations.*' => ['exists:locations,id'],
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

        Setting::setValue('site_name', $request->name);
        Setting::setValue('emails', array_values(array_filter($request->emails ?? [])));
        Setting::setValue('mobiles', array_values(array_filter($request->mobiles ?? [])));
        Setting::setValue('business_hours', $this->buildBusinessHours($request));
        Setting::setValue('locations', $request->locations ?? []);
        Setting::setValue('terms_conditions', $request->terms_conditions ?? '');
        Setting::setValue('delivery_returns', $request->delivery_returns ?? '');
        Setting::setValue('privacy_policy', $request->privacy_policy ?? '');
        Setting::setValue('refund_cancellation', $request->refund_cancellation ?? '');

        Setting::where('key', 'website_content')->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Website content updated successfully.',
        ]);
    }

    private function buildBusinessHours(Request $request): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        $openTime = $request->open_time ?? '09:00';
        $closeTime = $request->close_time ?? '18:00';

        foreach ($days as $day) {
            $dayData = $request->business_hours[$day] ?? [];
            $isOpen = isset($dayData['open_day']) && $dayData['open_day'] == 1;

            $hours[$day] = [
                'closed' => !$isOpen,
                'open' => $isOpen ? $openTime : '',
                'close' => $isOpen ? $closeTime : '',
            ];
        }

        return $hours;
    }
}
