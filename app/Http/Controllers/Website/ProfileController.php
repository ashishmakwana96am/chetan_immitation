<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Models\ProductReview;
use App\Models\State;
use App\Models\OrderCancellationRequest;
use App\Mail\OrderStatusMail;

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
            ->where('source', 'ONLINE')
            ->with(['items.product.primaryImage'])
            ->latest()
            ->get();

        $states = State::where('status', State::STATUS_ACTIVE)->orderBy('name')->get();

        return view('website.profile', compact('addresses', 'orders', 'states'));
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
            'phone'        => ['required', 'digits:10'],
        ]);

        $name        = trim($request->name);

        $customer->update([
            'name'         => $name,
        ]);

        $customer->syncPrimaryPhone($request->phone);

        $customer->refresh();

        return response()->json([
            'status'       => 'success',
            'message'      => 'Profile details updated successfully.',
            'name'         => $customer->name,
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
     * Download invoice PDF for a customer order.
     */
    public function downloadInvoice($id)
    {
        $customer = $this->customer();

        $order = Order::where('customer_id', $customer->id)
            ->where('source', 'ONLINE')
            ->where('id', $id)
            ->with(['items.product.variants.attributeValue.attribute', 'customer', 'location', 'coupon', 'customerAddress', 'user', 'cancellationRequest'])
            ->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', ['order' => $order])
            ->setPaper('a4', 'portrait');

        ActivityLogger::log('Sales', 'export', $order, null, null, 'Invoice PDF downloaded by customer for order #' . $order->order_no);

        return $pdf->download('invoice-' . $order->order_no . '.pdf');
    }
    
    /**
     * View specific order details page.
     */
    public function viewOrder($id)
    {
        $customer = $this->customer();

        $order = Order::where('customer_id', $customer->id)
            ->where('source', 'ONLINE')
            ->where('id', $id)
            ->with(['items.product.primaryImage', 'items.product.category', 'customerAddress', 'coupon', 'cancellationRequest'])
            ->firstOrFail();

        $reviewsByProduct = ProductReview::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->with('images')
            ->get()
            ->keyBy('product_id');

        $instagramPosts = \App\Models\Setting::getInstagramPosts();
        $instagramProfileUrl = \App\Models\Setting::getInstagramProfileUrl();

        return view('website.view-order', compact('order', 'reviewsByProduct', 'instagramPosts', 'instagramProfileUrl'));
    }

    /**
     * Cancel a customer order before it enters shipment.
     */
    public function cancelOrder(Request $request, $id)
    {
        $customer = $this->customer();

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancellation_reason.required' => 'Please enter a cancellation remark.',
            'cancellation_reason.min' => 'Cancellation remark must be at least 5 characters.',
            'cancellation_reason.max' => 'Cancellation remark cannot be more than 500 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first('cancellation_reason'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::where('customer_id', $customer->id)
            ->where('source', 'ONLINE')
            ->where('id', $id)
            ->with(['items', 'customer'])
            ->firstOrFail();

        $existingRequest = OrderCancellationRequest::where('order_id', $order->id)
            ->first();

        if ($existingRequest) {
            $msg = 'A cancellation request has already been submitted for this order.';
            if ($existingRequest->status === 'pending') {
                $msg = 'A cancellation request for this order is already pending.';
            } elseif ($existingRequest->status === 'approved') {
                $msg = 'This order has already been cancelled.';
            } elseif ($existingRequest->status === 'rejected') {
                $msg = 'Your cancellation request for this order has been rejected.';
            }
            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], 422);
        }

        if (!in_array((int) $order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This order can no longer be cancelled.',
            ], 422);
        }

        if ((int) $order->status === Order::STATUS_DELIVERED) {
            if (!$order->delivered_at || now()->diffInHours($order->delivered_at) > 24) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Orders can only be cancelled within 24 hours of delivery.',
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($order, $request) {
                OrderCancellationRequest::create([
                    'order_id'            => $order->id,
                    'cancellation_reason' => trim($request->cancellation_reason),
                    'status'              => OrderCancellationRequest::STATUS_PENDING,
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Your cancellation request has been submitted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to request cancellation right now. Please try again.',
            ], 500);
        }
    }
}
