<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Inventory;
use App\Models\Coupon;
use App\Models\Customer;

class CheckoutController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $customer = $this->customer();

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with([
                'product' => function ($q) {
                    $q->withSum('inventories', 'quantity');
                },
                'product.primaryImage',
                'product.category',
                'product.variants.attributeValue.attribute',
                'productVariant.attributeValue.attribute',
            ])
            ->latest()
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop-by-category')
                ->with('error', 'Your cart is empty. Please add items before proceeding to checkout.');
        }

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        $productIds = $cartItems->pluck('product_id')->unique()->toArray();

        $relatedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->whereNotIn('id', $productIds ?: [0])
            ->with(['primaryImage', 'variants.attributeValue'])
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }
        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = \App\Models\Coupon::where('code', session('applied_coupon_code'))
                ->where('status', \App\Models\Coupon::STATUS_ACTIVE)
                ->where(function($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->first();
            if ($coupon) {
                if ($coupon->discount_type === 'percentage') {
                    $discount = $subtotal * ((float) $coupon->discount_value / 100);
                } else {
                    $discount = (float) $coupon->discount_value;
                }
                if ($discount > $subtotal) {
                    $discount = $subtotal;
                }
            } else {
                session()->forget('applied_coupon_code');
            }
        }
        $shipping = $subtotal > 1999 || $subtotal === 0.0 ? 0.0 : 99.0;
        $total = round($subtotal - $discount);

        $paymentMethodCod      = (bool) Setting::getValue('payment_method_cod', true);
        $paymentMethodRazorpay = (bool) Setting::getValue('payment_method_razorpay', true);

        // Ensure at least one method is on (safety fallback)
        if (!$paymentMethodCod && !$paymentMethodRazorpay) {
            $paymentMethodCod = true;
        }

        return view('website.checkout', compact('cartItems', 'addresses', 'relatedProducts', 'subtotal', 'discount', 'shipping', 'total', 'coupon', 'paymentMethodCod', 'paymentMethodRazorpay'));
    }

    public function saveAddress(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', 'in:home,work'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $customer = $this->customer();

        $hasAddress = CustomerAddress::where('customer_id', $customer->id)->exists();
        $isDefault = $request->input('is_default', false) ? true : !$hasAddress;

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        $newAddress = CustomerAddress::create([
            'customer_id' => $customer->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'type' => $request->type,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Address saved successfully.',
            'address' => $newAddress
        ]);
    }

    public function updateAddress(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', 'in:home,work'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.'
            ], 404);
        }

        $isDefault = $request->input('is_default', false) ? true : $address->is_default;

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'type' => $request->type,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully.',
            'address' => $address->fresh()
        ]);
    }

    public function setDefaultAddress(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer']
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.'
            ], 404);
        }

        CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Default address set successfully.'
        ]);
    }

    public function deleteAddress(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer']
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.'
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        $newDefaultId = null;
        if ($wasDefault) {
            $nextAddress = CustomerAddress::where('customer_id', $customer->id)
                ->latest()
                ->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
                $newDefaultId = $nextAddress->id;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully.',
            'new_default_id' => $newDefaultId
        ]);
    }

    public function initializePayment(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer']
        ]);

        // Guard: Razorpay must be enabled in settings
        if (!(bool) Setting::getValue('payment_method_razorpay', true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Online payment is currently unavailable.'
            ], 422);
        }

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a valid shipping address.'
            ], 422);
        }

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with([
                'product' => function ($q) {
                    $q->withSum('inventories', 'quantity');
                },
                'productVariant'
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.'
            ], 422);
        }

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }
        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = \App\Models\Coupon::where('code', session('applied_coupon_code'))
                ->where('status', \App\Models\Coupon::STATUS_ACTIVE)
                ->where(function($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->first();
            if ($coupon) {
                if ($coupon->discount_type === 'percentage') {
                    $discount = $subtotal * ((float) $coupon->discount_value / 100);
                } else {
                    $discount = (float) $coupon->discount_value;
                }
                if ($discount > $subtotal) {
                    $discount = $subtotal;
                }
            } else {
                session()->forget('applied_coupon_code');
            }
        }
        $shipping = $subtotal > 1999 || $subtotal === 0.0 ? 0.0 : 99.0;
        $total = round($subtotal - $discount);

        $location = Location::where('is_default', true)->first()
            ?? Location::where('status', Location::STATUS_ACTIVE)->first()
            ?? Location::first();

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fulfillment location is active.'
            ], 422);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay payment gateway is not configured.'
            ], 500);
        }

        try {
            $orderData = DB::transaction(function () use ($customer, $location, $total, $cartItems, $razorpayKeyId, $razorpayKeySecret, $coupon, $address) {
                $orderNo = generate_invoice_no('ORD', Order::class, 'order_no');

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'customer_address_id' => $address->id,
                    'location_id' => $location->id,
                    'order_no' => $orderNo,
                    'order_type' => 'sale',
                    'status' => Order::STATUS_PENDING,
                    'payment_status' => Order::PAYMENT_STATUS_PENDING,
                    'payment_method' => 'razorpay',
                    'final_amount' => $total,
                    'source' => 'ONLINE',
                    'discount_type' => $coupon ? 'COUPON' : 'MANUAL',
                    'coupon_id' => $coupon ? $coupon->id : null,
                ]);

                foreach ($cartItems as $item) {
                    $price = $item->productVariant
                        ? (float) $item->productVariant->sale_price
                        : (float) $item->product->sale_price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->qty,
                        'price' => $price,
                        'discount' => 0.0,
                        'total' => $price * $item->qty,
                    ]);
                }

                $rzpResponse = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => (int) round($total * 100),
                        'currency' => 'INR',
                        'receipt' => 'rcpt_' . $orderNo,
                    ]);

                if ($rzpResponse->failed()) {
                    Log::error('Razorpay Order Creation Failed: ' . $rzpResponse->body());
                    throw new \Exception('Failed to generate order ID with Razorpay.');
                }

                $rzpOrder = $rzpResponse->json();

                $order->update([
                    'razorpay_order_id' => $rzpOrder['id']
                ]);

                return [
                    'order_id' => $rzpOrder['id'],
                    'amount' => $rzpOrder['amount'],
                    'order_no' => $order->order_no,
                    'final_amount' => $order->final_amount,
                ];
            });

            return response()->json([
                'status' => 'success',
                'key' => $razorpayKeyId,
                'amount' => $orderData['amount'],
                'currency' => 'INR',
                'order_id' => $orderData['order_id'],
                'prefill' => [
                    'name' => $address->name,
                    'email' => $address->email ?? $customer->email ?? '',
                    'contact' => $address->phone,
                ],
                'order' => [
                    'order_no' => $orderData['order_no'],
                    'final_amount' => number_format($orderData['final_amount'], 0),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully.',
                'order' => [
                    'order_no' => $order->order_no,
                    'final_amount' => number_format($order->final_amount, 0),
                ]
            ]);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        $expectedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            $razorpayKeySecret
        );

        if (hash_equals($expectedSignature, $request->razorpay_signature)) {
            DB::transaction(function () use ($order, $request) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                ]);

                $customer = Customer::find($order->customer_id);
                CartItem::where('customer_id', $order->customer_id)->delete();
                session()->forget('applied_coupon_code');

                try {
                    Mail::to($customer->email)->send(new \App\Mail\OrderConfirmationMail($order));
                } catch (\Exception $e) {
                    Log::error('Order confirmation email failed: ' . $e->getMessage());
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully.',
                'order' => [
                    'order_no' => $order->order_no,
                    'final_amount' => number_format($order->final_amount, 0),
                ]
            ]);
        } else {
            $order->update([
                'status' => Order::STATUS_DECLINE,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed.'
            ], 400);
        }
    }

    public function failedPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => ['required', 'string']
        ]);

        $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();

        if ($order && $order->status == Order::STATUS_PENDING && $order->payment_status == Order::PAYMENT_STATUS_PENDING) {
            DB::transaction(function () use ($order) {
                $order->items()->delete();
                $order->delete();
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully.'
        ]);
    }

    /**
     * POST /buy-now/payment/initialize
     * Direct Buy Now — single product, bypasses cart.
     */
    public function buyNowInitialize(Request $request)
    {
        $request->validate([
            'address_id'  => ['required', 'integer'],
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'variant_id'  => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty'         => ['nullable', 'integer', 'min:1'],
        ]);

        // Guard: Razorpay must be enabled in settings
        if (!(bool) Setting::getValue('payment_method_razorpay', true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Online payment is currently unavailable.'
            ], 422);
        }

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json(['status' => 'error', 'message' => 'Please select a valid shipping address.'], 422);
        }

        $product = Product::where('status', Product::STATUS_ACTIVE)
            ->withSum('inventories', 'quantity')
            ->find($request->product_id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        if (($product->inventories_sum_quantity ?? 0) < 1) {
            return response()->json(['status' => 'error', 'message' => 'This product is currently out of stock.'], 422);
        }

        $qty = max(1, (int) ($request->qty ?? 1));

        $price = $product->sale_price;
        if ($request->filled('variant_id')) {
            $variant = \App\Models\ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->find($request->variant_id);
            if (!$variant) {
                return response()->json(['status' => 'error', 'message' => 'Variant not found.'], 422);
            }
            $price = $variant->sale_price;
        }

        $total = round((float) $price * $qty);

        $location = Location::where('is_default', true)->first()
            ?? Location::where('status', Location::STATUS_ACTIVE)->first()
            ?? Location::first();

        if (!$location) {
            return response()->json(['status' => 'error', 'message' => 'No fulfillment location is active.'], 422);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
            return response()->json(['status' => 'error', 'message' => 'Razorpay payment gateway is not configured.'], 500);
        }

        try {
            $orderData = DB::transaction(function () use (
                $customer, $location, $total, $product, $request, $qty, $price,
                $razorpayKeyId, $razorpayKeySecret, $address
            ) {
                $orderNo = generate_invoice_no('ORD', Order::class, 'order_no');

                $order = Order::create([
                    'customer_id'          => $customer->id,
                    'customer_address_id'  => $address->id,
                    'location_id'          => $location->id,
                    'order_no'             => $orderNo,
                    'order_type'           => 'sale',
                    'status'               => Order::STATUS_PENDING,
                    'payment_status'       => Order::PAYMENT_STATUS_PENDING,
                    'payment_method'       => 'razorpay',
                    'final_amount'         => $total,
                    'source'               => 'ONLINE',
                    'discount_type'        => 'MANUAL',
                    'coupon_id'            => null,
                ]);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'price'      => $price,
                    'discount'   => 0.0,
                    'total'      => $price * $qty,
                ]);

                $rzpResponse = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount'   => (int) round($total * 100),
                        'currency' => 'INR',
                        'receipt'  => 'rcpt_' . $orderNo,
                    ]);

                if ($rzpResponse->failed()) {
                    Log::error('Razorpay BuyNow Order Creation Failed: ' . $rzpResponse->body());
                    throw new \Exception('Failed to generate order ID with Razorpay.');
                }

                $rzpOrder = $rzpResponse->json();
                $order->update(['razorpay_order_id' => $rzpOrder['id']]);

                return [
                    'order_id'     => $rzpOrder['id'],
                    'amount'       => $rzpOrder['amount'],
                    'order_no'     => $order->order_no,
                    'final_amount' => $order->final_amount,
                ];
            });

            return response()->json([
                'status'   => 'success',
                'key'      => $razorpayKeyId,
                'amount'   => $orderData['amount'],
                'currency' => 'INR',
                'order_id' => $orderData['order_id'],
                'prefill'  => [
                    'name'    => $address->name,
                    'email'   => $address->email ?? $customer->email ?? '',
                    'contact' => $address->phone,
                ],
                'order' => [
                    'order_no'     => $orderData['order_no'],
                    'final_amount' => number_format($orderData['final_amount'], 0),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string']
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('status', Coupon::STATUS_ACTIVE)
            ->where(function($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired coupon code.'
            ], 422);
        }

        if ($coupon->usage_limit !== null) {
            $usedCount = Order::where('coupon_id', $coupon->id)
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->count();
            if ($usedCount >= $coupon->usage_limit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This coupon has reached its usage limit.'
                ], 422);
            }
        }

        session()->put('applied_coupon_code', $coupon->code);

        $customer = $this->customer();
        $cartItems = CartItem::where('customer_id', $customer->id)->get();
        
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }

        $discount = 0.0;
        if ($coupon->discount_type === 'percentage') {
            $discount = $subtotal * ((float) $coupon->discount_value / 100);
        } else {
            $discount = (float) $coupon->discount_value;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $discountDesc = 'Discount';
        if ($coupon->discount_type === 'percentage') {
            $discountDesc = 'Discount (' . (int)$coupon->discount_value . '% Off)';
        } else {
            $discountDesc = 'Discount (Flat ₹' . (int)$coupon->discount_value . ' Off)';
        }

        $total = round($subtotal - $discount);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully.',
            'discount_amount' => $discount,
            'discount_label' => '-₹' . number_format($discount, 0),
            'discount_desc' => $discountDesc,
            'total_amount' => $total,
            'total_label' => '₹' . number_format($total, 0),
        ]);
    }

    public function removeCoupon()
    {
        session()->forget('applied_coupon_code');

        $customer = $this->customer();
        $cartItems = CartItem::where('customer_id', $customer->id)->get();
        
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }

        $shipping = $subtotal > 1999 || $subtotal === 0.0 ? 0.0 : 99.0;
        $total = round($subtotal);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon removed successfully.',
            'total_amount' => $total,
            'total_label' => '₹' . number_format($total, 0),
        ]);
    }

    public function placeCodOrder(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer']
        ]);

        // Guard: COD must be enabled in settings
        if (!(bool) Setting::getValue('payment_method_cod', true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cash on Delivery is currently unavailable.'
            ], 422);
        }

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a valid shipping address.'
            ], 422);
        }

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with([
                'product' => function ($q) {
                    $q->withSum('inventories', 'quantity');
                },
                'productVariant'
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.'
            ], 422);
        }

        // Calculate order amount
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }
        
        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = Coupon::where('code', session('applied_coupon_code'))
                ->where('status', Coupon::STATUS_ACTIVE)
                ->where(function($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->first();
            if ($coupon) {
                if ($coupon->discount_type === 'percentage') {
                    $discount = $subtotal * ((float) $coupon->discount_value / 100);
                } else {
                    $discount = (float) $coupon->discount_value;
                }
                if ($discount > $subtotal) {
                    $discount = $subtotal;
                }
            } else {
                session()->forget('applied_coupon_code');
            }
        }
        
        $shipping = $subtotal > 1999 || $subtotal === 0.0 ? 0.0 : 99.0;
        $total = round($subtotal - $discount);

        // Resolve default location
        $location = Location::where('is_default', true)->first()
            ?? Location::where('status', Location::STATUS_ACTIVE)->first()
            ?? Location::first();

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fulfillment location is active.'
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($customer, $location, $total, $cartItems, $coupon, $address) {
                // Generate Invoice No
                $orderNo = generate_invoice_no('ORD', Order::class, 'order_no');

                // Create Order
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'customer_address_id' => $address->id,
                    'location_id' => $location->id,
                    'order_no' => $orderNo,
                    'order_type' => 'sale',
                    'status' => Order::STATUS_PENDING,
                    'payment_status' => Order::PAYMENT_STATUS_PENDING,
                    'payment_method' => 'cod',
                    'final_amount' => $total,
                    'source' => 'ONLINE',
                    'discount_type' => $coupon ? 'COUPON' : 'MANUAL',
                    'coupon_id' => $coupon ? $coupon->id : null,
                ]);

                // Create Order Items (NO inventory deduction - will be done when admin approves)
                foreach ($cartItems as $item) {
                    $price = $item->productVariant
                        ? (float) $item->productVariant->sale_price
                        : (float) $item->product->sale_price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->qty,
                        'price' => $price,
                        'discount' => 0.0,
                        'total' => $price * $item->qty,
                    ]);
                }

                // Clear customer cart and clear coupon session
                CartItem::where('customer_id', $customer->id)->delete();
                session()->forget('applied_coupon_code');

                // Send order confirmation email
                try {
                    Mail::to($customer->email)->send(new \App\Mail\OrderConfirmationMail($order));
                } catch (\Exception $e) {
                    Log::error('COD order confirmation email failed: ' . $e->getMessage());
                }

                return $order;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully.',
                'order' => [
                    'order_no' => $order->order_no,
                    'final_amount' => number_format($order->final_amount, 0),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
