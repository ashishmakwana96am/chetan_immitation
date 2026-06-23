<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerAddress;
use App\Models\Order;

class ProfileController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Display the customer profile page.
     */
    public function index()
    {
        $customer = $this->customer();
        
        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();
            
        $orders = Order::where('customer_id', $customer->id)
            ->with(['items.product.primaryImage'])
            ->latest()
            ->get();

        return view('website.profile', compact('addresses', 'orders'));
    }

    /**
     * Update customer profile details.
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->customer();
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile details updated successfully.'
        ]);
    }

    /**
     * Update customer password.
     */
    public function updateCustomerPassword(Request $request)
    {
        $customer = $this->customer();
        
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The current password you entered is incorrect.'
            ], 422);
        }

        $customer->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ]);
    }

    /**
     * View specific order details page.
     */
    public function viewOrder($id)
    {
        $customer = $this->customer();

        $order = Order::where('customer_id', $customer->id)
            ->where('id', $id)
            ->with(['items.product.primaryImage', 'items.product.category', 'customerAddress'])
            ->firstOrFail();

        return view('website.view-order', compact('order'));
    }
}
