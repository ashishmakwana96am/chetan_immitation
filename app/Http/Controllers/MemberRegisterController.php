<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMemberMail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MemberRegisterController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => ['required', 'string', 'max:100'],
            'phone'                 => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'email'                 => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#^()\-+=])[A-Za-z\d@$!%*?&_#^()\-+=]{8,}$/',
            ],
            'password_confirmation' => ['required'],
        ], [
            'name.required'                  => 'Full name is required.',
            'name.max'                       => 'Name may not be greater than 100 characters.',
            'phone.required'                 => 'Mobile number is required.',
            'phone.regex'                    => 'Please enter a valid 10-digit mobile number.',
            'email.required'                 => 'Email address is required.',
            'email.email'                    => 'Please enter a valid email address.',
            'email.unique'                   => 'This email is already registered. Please login.',
            'password.required'              => 'Password is required.',
            'password.min'                   => 'Password must be at least 8 characters.',
            'password.confirmed'             => 'Passwords do not match.',
            'password.regex'                 => 'Password must contain at least 1 uppercase, 1 lowercase, 1 digit, and 1 special character.',
            'password_confirmation.required' => 'Please confirm your password.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $customer = Customer::create([
            'name'       => $request->name,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'password'   => $request->password,
            'is_website' => true,
            'status'     => Customer::STATUS_ACTIVE,
        ]);

        // Send welcome email (silently fail so registration still succeeds)
        try {
            Mail::to($customer->email)->send(new WelcomeMemberMail($customer));
        } catch (\Throwable $e) {
            // Log but don't block registration
            logger()->error('Welcome email failed: ' . $e->getMessage());
        }

        return redirect()->route('login')
            ->with('success', 'Account created successfully! Welcome to Chetan Imitation. Please log in.');
    }
}
