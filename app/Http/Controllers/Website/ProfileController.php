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
use App\Models\ProductReview;
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
            'phone'        => ['required', 'string', 'max:20'],
        ]);

        $name        = trim($request->name);

        $customer->update([
            'name'         => $name,
            'phone'        => $request->phone,
        ]);

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
            ->with(['items.product.variants.attributeValue.attribute', 'customer', 'location', 'coupon', 'customerAddress', 'user'])
            ->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', ['order' => $order])
            ->setPaper('a4', 'portrait');

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
            ->with(['items.product.primaryImage', 'items.product.category', 'customerAddress', 'coupon'])
            ->firstOrFail();

        $reviewsByProduct = ProductReview::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('product_id');

        return view('website.view-order', compact('order', 'reviewsByProduct'));
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

        if (!in_array((int) $order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVE], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This order can no longer be cancelled from your account.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($order, $request) {
                if ((int) $order->status === Order::STATUS_APPROVE) {
                    foreach ($order->items as $item) {
                        $stockRestore = ($item->pair_type === 'pair') ? $item->quantity * 2 : $item->quantity;
                        Inventory::where('product_id', $item->product_id)
                            ->where('location_id', $order->location_id)
                            ->increment('quantity', $stockRestore);
                    }
                }

                $order->update([
                    'status' => Order::STATUS_DECLINE,
                    'cancellation_reason' => trim($request->cancellation_reason),
                ]);
            });

            if ($order->customer && $order->customer->email) {
                try {
                    Mail::to($order->customer->email)->send(new OrderStatusMail($order->fresh(['customer'])));
                } catch (\Exception $mailEx) {
                    Log::error('Failed to send customer cancellation status mail: ' . $mailEx->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Your order has been cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Customer order cancellation failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to cancel this order right now. Please try again.',
            ], 500);
        }
    }
}
