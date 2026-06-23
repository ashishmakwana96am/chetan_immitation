<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductReview;

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
     * Update customer avatar photo.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $customer = $this->customer();

        if ($customer->avatar) {
            $oldPath = public_path($customer->avatar);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $avatarsDir = public_path('avatars');
        if (!is_dir($avatarsDir)) {
            mkdir($avatarsDir, 0755, true);
        }

        $filename     = 'avatar_' . $customer->id . '_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
        $request->file('avatar')->move($avatarsDir, $filename);
        $relativePath = 'avatars/' . $filename;

        $customer->update(['avatar' => $relativePath]);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Profile photo updated successfully.',
            'avatar_url' => asset($relativePath) . '?t=' . time(),
        ]);
    }

    /**
     * Update customer profile details.
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->customer();
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:20'],
        ]);

        $name        = trim($request->name);
        $displayName = trim((string) $request->input('display_name', ''));

        $customer->update([
            'name'         => $name,
            'display_name' => ($displayName !== '' && $displayName !== $name) ? $displayName : null,
            'phone'        => $request->phone,
        ]);

        $customer->refresh();

        return response()->json([
            'status'       => 'success',
            'message'      => 'Profile details updated successfully.',
            'name'         => $customer->name,
            'display_name' => $customer->display_name,
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

        $reviewsByProduct = ProductReview::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('product_id');

        return view('website.view-order', compact('order', 'reviewsByProduct'));
    }
}
