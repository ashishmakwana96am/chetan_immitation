<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MergesGuestCustomerState;
use App\Mail\WelcomeMemberMail;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MemberRegisterController extends Controller
{
    use MergesGuestCustomerState;


    public function store(Request $request)
    {
        $request->merge([
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'name'  => $request->name ? trim($request->name) : null,
            'phone' => $request->phone ? trim($request->phone) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'name'                  => ['required', 'string', 'max:100'],
            'phone'                 => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('customer_phones', 'phone')->whereNull('deleted_at')],
            'email'                 => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->whereNull('deleted_at')],
            'password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => ['required'],
        ], [
            'name.required'                  => 'Full name is required.',
            'name.max'                       => 'Name may not be greater than 100 characters.',
            'phone.required'                 => 'Mobile number is required.',
            'phone.regex'                    => 'Please enter a valid 10-digit mobile number.',
            'phone.unique'                   => 'This mobile number is already registered.',
            'email.required'                 => 'Email address is required.',
            'email.email'                    => 'Please enter a valid email address.',
            'email.unique'                   => 'This email is already registered. Please login.',
            'password.required'              => 'Password is required.',
            'password.min'                   => 'Password must be at least 8 characters.',
            'password.confirmed'             => 'Passwords do not match.',
            'password_confirmation.required' => 'Please confirm your password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $defaultLocation = Location::where('is_default', true)->first() ?? Location::first();

        $customer = Customer::create([
            'location_id' => $defaultLocation?->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password,
            'is_website' => true,
            'status'     => Customer::STATUS_ACTIVE,
        ]);

        $customer->phones()->create(['phone' => $request->phone]);

        try {
            Mail::to($customer->email)->send(new WelcomeMemberMail($customer));
        } catch (\Throwable $e) {
            logger()->error('Welcome email failed: ' . $e->getMessage());
        }

        $intended = $this->resolveIntendedUrl($request);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        $wishlistCount = $this->mergeGuestCartAndWishlist($request, $customer);

        return response()->json([
            'status'         => 'success',
            'redirect_url'   => $intended,
            'wishlist_count' => $wishlistCount,
        ]);
    }
}
