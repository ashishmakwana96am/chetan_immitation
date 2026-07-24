<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    const FREE_SHIPPING_THRESHOLD = 1999;

    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Order Amount >= 1999 => free shipping.
     * Otherwise, use the shipping charge configured for the selected
     * address's state, unless the Free Shipping coupon is applied.
     */
    private function calculateShipping(float $subtotal, ?CustomerAddress $address, ?Coupon $coupon = null): float
    {
        return 0.0;

        /*
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($subtotal >= self::FREE_SHIPPING_THRESHOLD) {
            return 0.0;
        }

        if ($coupon && $coupon->code === Coupon::FREE_SHIPPING_CODE) {
            return 0.0;
        }

        if ($address && $address->state) {
            $state = State::where('name', $address->state)
                ->where('status', State::STATUS_ACTIVE)
                ->first();

            if ($state) {
                return (float) $state->shipping_charge;
            }
        }

        return 0.0;
        */
    }

    private function resolveDeliveryDays(?CustomerAddress $address): ?int
    {
        if (!$address || !$address->state) {
            return null;
        }

        return State::where('name', $address->state)
            ->where('status', State::STATUS_ACTIVE)
            ->value('delivery_days');
    }

    public function index()
    {
        $customer = $this->customer();

        $adjustment = $this->adjustCartToAvailableStock($customer->id);
        if ($adjustment['adjusted']) {
            session()->flash('warning', 'Some items in your cart were adjusted or removed due to stock updates: ' . implode(' ', $adjustment['messages']));
        }

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

        $deletedItemIds = [];
        foreach ($cartItems as $item) {
            if (!$item->product) {
                $deletedItemIds[] = $item->id;
            }
        }
        if (!empty($deletedItemIds)) {
            CartItem::whereIn('id', $deletedItemIds)->delete();
            $cartItems = $cartItems->reject(function ($item) use ($deletedItemIds) {
                return in_array($item->id, $deletedItemIds);
            });
        }

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
            ->hasImages()
            ->whereNotIn('id', $productIds ?: [0])
            ->with(['primaryImage', 'variants.attributeValue'])
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->getPrice();
            $subtotal += $price * $item->qty;
        }
        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = Coupon::where('code', session('applied_coupon_code'))
                ->where('status', Coupon::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', today());
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
        $states = State::where('status', State::STATUS_ACTIVE)->orderBy('name')->get();
        $activeStateNames = $states->pluck('name')->toArray();

        $defaultAddress = $addresses->first(function ($address) use ($activeStateNames) {
            return in_array($address->state, $activeStateNames);
        });

        $shipping = $this->calculateShipping($subtotal, $defaultAddress, $coupon);
        $total = round($subtotal - $discount + $shipping, 2);

        $paymentMethodCod = (bool) Setting::getValue('payment_method_cod', true);
        $paymentMethodRazorpay = (bool) Setting::getValue('payment_method_razorpay', true);

        if (! $paymentMethodCod && ! $paymentMethodRazorpay) {
            $paymentMethodCod = true;
        }

        return view('website.checkout', compact('cartItems', 'addresses', 'relatedProducts', 'subtotal', 'discount', 'shipping', 'total', 'coupon', 'states', 'paymentMethodCod', 'paymentMethodRazorpay', 'defaultAddress'));
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
            'state' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::exists('states', 'name')->where('status', State::STATUS_ACTIVE)],
            'pincode' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', 'in:home,work'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $customer = $this->customer();

        $hasAddress = CustomerAddress::where('customer_id', $customer->id)->exists();
        $isDefault = $request->input('is_default', false) ? true : ! $hasAddress;

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
            'address' => $newAddress,
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
            'state' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::exists('states', 'name')->where('status', State::STATUS_ACTIVE)],
            'pincode' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', 'in:home,work'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.',
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
            'address' => $address->fresh(),
        ]);
    }

    public function setDefaultAddress(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.',
            ], 404);
        }

        CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Default address set successfully.',
        ]);
    }

    public function deleteAddress(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found.',
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
            'new_default_id' => $newDefaultId,
        ]);
    }

    public function initializePayment(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        // Guard: Razorpay must be enabled in settings
        if (! (bool) Setting::getValue('payment_method_razorpay', true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online payment is currently unavailable.',
            ], 422);
        }

        $customer = $this->customer();

        $adjustment = $this->adjustCartToAvailableStock($customer->id);
        if ($adjustment['adjusted']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some items in your cart were adjusted or removed due to stock updates: ' . implode(' ', $adjustment['messages']) . ' Please review your cart and try again.'
            ], 422);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a valid shipping address.',
            ], 422);
        }

        $isStateActive = State::where('name', $address->state)->where('status', State::STATUS_ACTIVE)->exists();
        if (!$isStateActive) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery is temporarily unavailable at this address.',
            ], 422);
        }

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with([
                'product' => function ($q) {
                    $q->withSum('inventories', 'quantity');
                },
                'productVariant',
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->getPrice();
            $subtotal += $price * $item->qty;
        }
        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = Coupon::where('code', session('applied_coupon_code'))
                ->where('status', Coupon::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', today());
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
        $shipping = $this->calculateShipping($subtotal, $address, $coupon);
        $total = round($subtotal - $discount + $shipping, 2);

        $fulfillment = $this->isDefaultLocationWithStock($cartItems->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->product_variant_id,
            'quantity'   => $item->qty,
            'pair_type'  => $item->pair_type ?? 'single',
            'custom_size_value' => $item->custom_size_value,
        ])->all());

        $location = $fulfillment['location'];
        $hasStock = $fulfillment['has_stock'];

        if (! $location) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fulfillment location is active.',
            ], 422);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay payment gateway is not configured.',
            ], 500);
        }

        try {
            $orderNo = generate_invoice_no('OR', Order::class, 'order_no');

            $rzpResponse = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => (int) round($total * 100),
                    'currency' => 'INR',
                    'receipt' => 'rcpt_'.$orderNo,
                ]);

            if ($rzpResponse->failed()) {
                Log::error('Razorpay Order Creation Failed: '.$rzpResponse->body());
                throw new \Exception('Failed to generate order ID with Razorpay.');
            }

            $rzpOrder = $rzpResponse->json();

            session()->put('pending_payment', [
                'type' => 'checkout',
                'razorpay_order_id' => $rzpOrder['id'],
                'order_no' => $orderNo,
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'location_id' => $location->id,
                'is_default' => $hasStock,
                'total' => $total,
                'shipping_charge' => $shipping,
                'coupon_id' => $coupon ? $coupon->id : null,
                'discount_type' => $coupon ? 'COUPON' : 'MANUAL',
                'cart_items' => $cartItems->map(function ($item) {
                    $price = $item->getPrice();

                    return [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'pair_type'  => $item->pair_type ?? 'single',
                        'custom_size_value' => $item->custom_size_value,
                        'quantity' => $item->qty,
                        'price' => $price,
                        'total' => $price * $item->qty,
                    ];
                })->toArray(),
            ]);

            return response()->json([
                'status' => 'success',
                'key' => $razorpayKeyId,
                'amount' => $rzpOrder['amount'],
                'currency' => 'INR',
                'order_id' => $rzpOrder['id'],
                'prefill' => [
                    'name' => $address->name,
                    'email' => $address->email ?? $customer->email ?? '',
                    'contact' => $address->phone,
                ],
                'order' => [
                    'order_no' => $orderNo,
                    'final_amount' => number_format($total, 2, '.', ''),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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

        $pendingPayment = session('pending_payment');

        if (! $pendingPayment || $pendingPayment['razorpay_order_id'] !== $request->razorpay_order_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payment session. Please try again.',
            ], 400);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        $expectedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id.'|'.$request->razorpay_payment_id,
            $razorpayKeySecret
        );

        if (hash_equals($expectedSignature, $request->razorpay_signature)) {
            $order = DB::transaction(function () use ($pendingPayment, $request) {
                $isDefault = (bool) ($pendingPayment['is_default'] ?? true);
                $status = $isDefault ? Order::STATUS_APPROVE : Order::STATUS_PENDING;

                $totalVal = (float) $pendingPayment['total'];

                $order = Order::create([
                    'customer_id' => $pendingPayment['customer_id'],
                    'customer_address_id' => $pendingPayment['address_id'],
                    'location_id' => $pendingPayment['location_id'],
                    'is_default' => $isDefault,
                    'order_no' => $pendingPayment['order_no'],
                    'order_type' => 'sale',
                    'status' => $status,
                    'confirmed_at' => $isDefault ? now() : null,
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'payment_method' => 'online',
                    'is_gst' => false,
                    'tax_amount' => 0,
                    'final_amount' => $totalVal,
                    'shipping_charge' => $pendingPayment['shipping_charge'] ?? 0,
                    'source' => 'ONLINE',
                    'discount_type' => $pendingPayment['discount_type'],
                    'coupon_id' => $pendingPayment['coupon_id'],
                ]);

                \App\Models\OrderPayment::create([
                    'order_id'           => $order->id,
                    'gateway'            => 'razorpay',
                    'gateway_order_id'   => $pendingPayment['razorpay_order_id'],
                    'gateway_payment_id' => $request->razorpay_payment_id,
                    'gateway_signature'  => $request->razorpay_signature,
                    'status'             => \App\Models\OrderPayment::STATUS_CAPTURED,
                    'amount'             => $pendingPayment['total'],
                    'currency'           => 'INR',
                ]);

                foreach ($pendingPayment['cart_items'] as $item) {
                    OrderItem::create([
                        'order_id'           => $order->id,
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'pair_type'          => $item['pair_type'] ?? 'single',
                        'custom_size_value'  => $item['custom_size_value'] ?? null,
                        'quantity'           => $item['quantity'],
                        'price'              => $item['price'],
                        'discount'           => 0.0,
                        'total'              => $item['total'],
                    ]);

                    if ($isDefault) {
                        $itemProduct = Product::find($item['product_id']);
                        $deductQty = $this->stockMultiplier($itemProduct, $item['pair_type'] ?? 'single', $item['custom_size_value'] ?? null) * $item['quantity'];
                        Inventory::where('product_id', $item['product_id'])
                            ->where('location_id', $order->location_id)
                            ->decrement('quantity', $deductQty);
                    }
                }

                if ($pendingPayment['type'] === 'checkout') {
                    CartItem::where('customer_id', $pendingPayment['customer_id'])->delete();
                    session()->forget('applied_coupon_code');
                }

                return $order;
            });

            $customer = Customer::find($order->customer_id);
            try {
                Mail::to($customer->email)->send(new OrderConfirmationMail($order));
            } catch (\Exception $e) {
                Log::error('Order confirmation email failed: '.$e->getMessage());
            }

            session()->forget('pending_payment');

            $order->loadMissing('customerAddress');

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully.',
                'order' => [
                    'order_no' => $order->order_no,
                    'final_amount' => number_format($order->final_amount, 2, '.', ''),
                    'delivery_days' => $this->resolveDeliveryDays($order->customerAddress),
                ],
            ]);
        } else {
            session()->forget('pending_payment');

            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed.',
            ], 400);
        }
    }

    public function failedPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => ['required', 'string'],
        ]);

        $pendingPayment = session('pending_payment');

        if ($pendingPayment && $pendingPayment['razorpay_order_id'] === $request->razorpay_order_id) {
            session()->forget('pending_payment');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment session cleared.',
        ]);
    }

    public function buyNowInitialize(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'pair_type' => ['nullable', 'string', 'in:single,pair'],
            'custom_size_value' => ['nullable', 'numeric'],
        ]);

        if (! (bool) Setting::getValue('payment_method_razorpay', true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online payment is currently unavailable.',
            ], 422);
        }

        $customer = $this->customer();

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json(['status' => 'error', 'message' => 'Please select a valid shipping address.'], 422);
        }

        $isStateActive = State::where('name', $address->state)->where('status', State::STATUS_ACTIVE)->exists();
        if (!$isStateActive) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery is temporarily unavailable at this address.',
            ], 422);
        }

        $product = Product::where('status', Product::STATUS_ACTIVE)
            ->withSum('inventories', 'quantity')
            ->find($request->product_id);

        if (! $product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;
        $availableStock = $product->totalAvailableStock($variantId);
        if ($availableStock < 1) {
            return response()->json(['status' => 'error', 'message' => 'This product is currently out of stock.'], 422);
        }

        $qty = max(1, (int) ($request->qty ?? 1));
        $pairType = in_array($request->pair_type, ['single', 'pair']) ? $request->pair_type : 'single';
        if (!$product->pair_product) {
            $pairType = 'single';
        }

        $variant = null;
        if ($request->filled('variant_id')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('status', 1)
                ->find($request->variant_id);
            if (! $variant) {
                return response()->json(['status' => 'error', 'message' => 'Variant not found.'], 422);
            }
        }

        [$customSizeValue, $sizeError] = $this->resolveCustomSizeValue($product, $request->custom_size_value, $variant);
        if ($sizeError) {
            return response()->json(['status' => 'error', 'message' => $sizeError], 422);
        }
        if ($customSizeValue) {
            $pairType = 'single';
        }

        $unitPcs = $this->stockMultiplier($product, $pairType, $customSizeValue);
        $neededPcs = $unitPcs * $qty;
        if ($neededPcs > $availableStock) {
            $allowedQty = (int) floor($availableStock / $unitPcs);
            if ($allowedQty <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Only ' . $availableStock . ' Pc(s) left in stock.'], 422);
            }
            $unitLabel = $customSizeValue ? 'set(s)' : ($pairType === 'pair' ? 'pair(s)' : 'Pc(s)');
            return response()->json(['status' => 'error', 'message' => 'Only ' . $allowedQty . ' ' . $unitLabel . ' are available in stock.'], 422);
        }

        $price = (new CartItem([
            'pair_type'         => $pairType,
            'custom_size_value' => $customSizeValue,
        ]))->setRelation('product', $product)->setRelation('productVariant', $variant)->getPrice();

        $subtotal = round((float) $price * $qty, 2);
        $shipping = $this->calculateShipping($subtotal, $address, null);
        $total = round($subtotal + $shipping, 2);

        $fulfillment = $this->isDefaultLocationWithStock([[
            'product_id' => $product->id,
            'variant_id' => $request->filled('variant_id') ? (int) $request->variant_id : null,
            'quantity'   => $qty,
            'pair_type'  => $pairType,
            'custom_size_value' => $customSizeValue,
        ]]);

        $location = $fulfillment['location'];
        $hasStock = $fulfillment['has_stock'];

        if (! $location) {
            return response()->json(['status' => 'error', 'message' => 'No fulfillment location is active.'], 422);
        }

        $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
        $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

        if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
            return response()->json(['status' => 'error', 'message' => 'Razorpay payment gateway is not configured.'], 500);
        }

        try {
            $orderNo = generate_invoice_no('OR', Order::class, 'order_no');

            $rzpResponse = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => (int) round($total * 100),
                    'currency' => 'INR',
                    'receipt' => 'rcpt_'.$orderNo,
                ]);

            if ($rzpResponse->failed()) {
                Log::error('Razorpay BuyNow Order Creation Failed: '.$rzpResponse->body());
                throw new \Exception('Failed to generate order ID with Razorpay.');
            }

            $rzpOrder = $rzpResponse->json();

            $variantId = $request->filled('variant_id') ? $request->variant_id : null;

            session()->put('pending_payment', [
                'type' => 'buynow',
                'razorpay_order_id' => $rzpOrder['id'],
                'order_no' => $orderNo,
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'location_id' => $location->id,
                'is_default' => $hasStock,
                'total' => $total,
                'shipping_charge' => $shipping,
                'coupon_id' => null,
                'discount_type' => 'MANUAL',
                'cart_items' => [[
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'pair_type' => $pairType,
                    'custom_size_value' => $customSizeValue,
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $price * $qty,
                ]],
            ]);

            return response()->json([
                'status' => 'success',
                'key' => $razorpayKeyId,
                'amount' => $rzpOrder['amount'],
                'currency' => 'INR',
                'order_id' => $rzpOrder['id'],
                'prefill' => [
                    'name' => $address->name,
                    'email' => $address->email ?? $customer->email ?? '',
                    'contact' => $address->phone,
                ],
                'order' => [
                    'order_no' => $orderNo,
                    'final_amount' => number_format($total, 2, '.', ''),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function isDefaultLocationWithStock(array $items): array
    {
        $defaultLocation = Location::where('is_default', true)->first()
            ?? Location::where('status', Location::STATUS_ACTIVE)->first()
            ?? Location::first();

        if (! $defaultLocation) {
            return [
                'location' => null,
                'has_stock' => false,
            ];
        }

        $hasStock = $this->locationHasStockForItems($defaultLocation->id, $items);

        return [
            'location' => $defaultLocation,
            'has_stock' => $hasStock,
        ];
    }

    private function locationHasStockForItems(int $locationId, array $items): bool
    {
        $products = Product::with('variants')
            ->whereIn('id', collect($items)->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return false;
            }

            $pairType = $item['pair_type'] ?? 'single';
            $neededQty = $this->stockMultiplier($product, $pairType, $item['custom_size_value'] ?? null) * (int) $item['quantity'];

            if ($item['variant_id']) {
                $stockData = $product->getVariantStock($locationId);
                $available = (int) ($stockData['variants'][$item['variant_id']] ?? 0);
            } else {
                $available = (int) (Inventory::where('product_id', $product->id)
                    ->where('location_id', $locationId)
                    ->value('quantity') ?? 0);
            }

            if ($available < $neededQty) {
                return false;
            }
        }

        return true;
    }

    private function resolveAddressForShipping(Request $request): ?CustomerAddress
    {
        $customer = $this->customer();

        if ($request->filled('address_id')) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->where('id', $request->address_id)
                ->first();
            if ($address) {
                return $address;
            }
        }

        return CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->first();
    }

    public function recalculateShipping(Request $request)
    {
        $customer = $this->customer();
        $cartItems = CartItem::where('customer_id', $customer->id)->get();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $subtotal += $item->getPrice() * $item->qty;
        }

        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = Coupon::where('code', session('applied_coupon_code'))
                ->where('status', Coupon::STATUS_ACTIVE)
                ->first();
            if ($coupon) {
                $discount = $coupon->discount_type === 'percentage'
                    ? $subtotal * ((float) $coupon->discount_value / 100)
                    : (float) $coupon->discount_value;
                if ($discount > $subtotal) {
                    $discount = $subtotal;
                }
            }
        }

        $address = $this->resolveAddressForShipping($request);
        $shipping = $this->calculateShipping($subtotal, $address, $coupon);
        $total = round($subtotal - $discount + $shipping, 2);

        return response()->json([
            'status' => 'success',
            'shipping_amount' => $shipping,
            'total_amount' => $total,
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('status', Coupon::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->first();

        if (! $coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired coupon code.',
            ], 422);
        }

        if ($coupon->usage_limit !== null) {
            $usedCount = Order::where('coupon_id', $coupon->id)
                ->count();
            if ($usedCount >= $coupon->usage_limit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This coupon has reached its usage limit.',
                ], 422);
            }
        }

        session()->put('applied_coupon_code', $coupon->code);

        $customer = $this->customer();
        $cartItems = CartItem::where('customer_id', $customer->id)->get();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->getPrice();
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
            $discountDesc = 'Discount ('.(int) $coupon->discount_value.'% Off)';
        } else {
            $discountDesc = 'Discount (Flat ₹'.(int) $coupon->discount_value.' Off)';
        }

        $address = $this->resolveAddressForShipping($request);
        $shipping = $this->calculateShipping($subtotal, $address, $coupon);
        $total = round($subtotal - $discount + $shipping, 2);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully.',
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'total_amount' => $total,
        ]);
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('applied_coupon_code');

        $customer = $this->customer();
        $cartItems = CartItem::where('customer_id', $customer->id)->get();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->getPrice();
            $subtotal += $price * $item->qty;
        }

        $address = $this->resolveAddressForShipping($request);
        $shipping = $this->calculateShipping($subtotal, $address, null);
        $total = round($subtotal + $shipping, 2);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon removed successfully.',
            'shipping_amount' => $shipping,
            'total_amount' => $total,
            'total_label' => '₹'.number_format($total, 2, '.', ''),
        ]);
    }

    public function placeCodOrder(Request $request)
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        if (! (bool) Setting::getValue('payment_method_cod', true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cash on Delivery is currently unavailable.',
            ], 422);
        }

        $customer = $this->customer();

        $adjustment = $this->adjustCartToAvailableStock($customer->id);
        if ($adjustment['adjusted']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some items in your cart were adjusted or removed due to stock updates: ' . implode(' ', $adjustment['messages']) . ' Please review your cart and try again.'
            ], 422);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $request->address_id)
            ->first();

        if (! $address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a valid shipping address.',
            ], 422);
        }

        $isStateActive = State::where('name', $address->state)->where('status', State::STATUS_ACTIVE)->exists();
        if (!$isStateActive) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery is temporarily unavailable at this address.',
            ], 422);
        }

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with([
                'product' => function ($q) {
                    $q->withSum('inventories', 'quantity');
                },
                'productVariant',
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->getPrice();
            $subtotal += $price * $item->qty;
        }

        $discount = 0.0;
        $coupon = null;
        if (session()->has('applied_coupon_code')) {
            $coupon = Coupon::where('code', session('applied_coupon_code'))
                ->where('status', Coupon::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', today());
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

        $shipping = $this->calculateShipping($subtotal, $address, $coupon);
        $total = round($subtotal - $discount + $shipping, 2);

        $fulfillment = $this->isDefaultLocationWithStock($cartItems->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->product_variant_id,
            'quantity'   => $item->qty,
            'pair_type'  => $item->pair_type ?? 'single',
            'custom_size_value' => $item->custom_size_value,
        ])->all());

        $location = $fulfillment['location'];
        $hasStock = $fulfillment['has_stock'];

        if (! $location) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fulfillment location is active.',
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($customer, $location, $hasStock, $total, $shipping, $cartItems, $coupon, $address) {
                $orderNo = generate_invoice_no('OR', Order::class, 'order_no');

                $status = $hasStock ? Order::STATUS_APPROVE : Order::STATUS_PENDING;

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'customer_address_id' => $address->id,
                    'location_id' => $location->id,
                    'is_default' => $hasStock,
                    'order_no' => $orderNo,
                    'order_type' => 'sale',
                    'status' => $status,
                    'confirmed_at' => $hasStock ? now() : null,
                    'payment_status' => Order::PAYMENT_STATUS_PENDING,
                    'payment_method' => 'cod',
                    'is_gst' => false,
                    'tax_amount' => 0,
                    'final_amount' => $total,
                    'shipping_charge' => $shipping,
                    'source' => 'ONLINE',
                    'discount_type' => $coupon ? 'COUPON' : 'MANUAL',
                    'coupon_id' => $coupon ? $coupon->id : null,
                ]);

                foreach ($cartItems as $item) {
                    $price = $item->getPrice();

                    OrderItem::create([
                        'order_id'           => $order->id,
                        'product_id'         => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'pair_type'          => $item->pair_type ?? 'single',
                        'custom_size_value'  => $item->custom_size_value,
                        'quantity'           => $item->qty,
                        'price'              => $price,
                        'discount'           => 0.0,
                        'total'              => $price * $item->qty,
                    ]);

                    if ($hasStock) {
                        $deductQty = $this->stockMultiplier($item->product, $item->pair_type ?? 'single', $item->custom_size_value) * $item->qty;
                        Inventory::where('product_id', $item->product_id)
                            ->where('location_id', $order->location_id)
                            ->decrement('quantity', $deductQty);
                    }
                }

                CartItem::where('customer_id', $customer->id)->delete();
                session()->forget('applied_coupon_code');

                try {
                    Mail::to($customer->email)->send(new OrderConfirmationMail($order));
                } catch (\Exception $e) {
                    Log::error('COD order confirmation email failed: '.$e->getMessage());
                }

                return $order;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully.',
                'order' => [
                    'order_no' => $order->order_no,
                    'final_amount' => number_format($order->final_amount, 2, '.', ''),
                    'delivery_days' => $this->resolveDeliveryDays($address),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function adjustCartToAvailableStock(int $customerId): array
    {
        $cartItems = CartItem::where('customer_id', $customerId)
            ->with(['product', 'productVariant'])
            ->get();

        $adjusted = false;
        $messages = [];

        $grouped = $cartItems->groupBy(function ($item) {
            return $item->product_id . '_' . ($item->product_variant_id ?? '0');
        });

        foreach ($grouped as $key => $items) {
            $first = $items->first();
            $product = $first->product;
            $variant = $first->productVariant;
            $variantId = $variant ? $variant->id : null;

            if (!$product || $product->status !== Product::STATUS_ACTIVE) {
                foreach ($items as $item) {
                    $item->delete();
                }
                $adjusted = true;
                $messages[] = "Product '" . ($product->name ?? 'Unknown') . "' is no longer available and has been removed from your cart.";
                continue;
            }

            $availableStock = $product->totalAvailableStock($variantId);
            $totalPcsNeeded = 0;
            foreach ($items as $item) {
                $totalPcsNeeded += $this->stockMultiplier($product, $item->pair_type, $item->custom_size_value) * $item->qty;
            }

            if ($totalPcsNeeded > $availableStock) {
                $adjusted = true;
                $remainingStock = $availableStock;

                foreach ($items as $item) {
                    $unitPcs = $this->stockMultiplier($product, $item->pair_type, $item->custom_size_value);
                    $allowedQty = $unitPcs > 0 ? (int) floor($remainingStock / $unitPcs) : $remainingStock;

                    $label = $item->custom_size_value
                        ? (rtrim(rtrim(number_format((float) $item->custom_size_value, 2), '0'), '.') . ' pcs set')
                        : (($item->pair_type === 'pair') ? 'Pair' : 'Pcs');

                    if ($allowedQty < $item->qty) {
                        $oldQty = $item->qty;
                        if ($allowedQty > 0) {
                            $item->update(['qty' => $allowedQty]);
                            $messages[] = "Quantity of '" . $product->name . "' (" . $label . ") was reduced from " . $oldQty . " to " . $allowedQty . " due to limited stock.";
                        } else {
                            $item->delete();
                            $messages[] = "'" . $product->name . "' (" . $label . ") was removed from your cart as it is out of stock.";
                        }
                    }
                    $remainingStock -= $unitPcs * $item->qty;
                }
            }
        }

        return [
            'adjusted' => $adjusted,
            'messages' => $messages,
        ];
    }

    /**
     * How many stock units one order "quantity" unit represents: a chosen
     * custom size (e.g. 6 pcs) for custom_size-mode pair products, 2 for
     * plain pair products bought as a pair, or 1 otherwise. Mirrors
     * SaleController::stockMultiplierFor() / CartController::stockMultiplier().
     */
    private function stockMultiplier(Product $product, ?string $pairType, $customSizeValue): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$product->pair_product) {
            return 1.0;
        }

        return $pairType === 'pair' ? 2.0 : 1.0;
    }

    /**
     * For custom_size-mode pair products, make sure the requested size
     * matches one of the product's configured custom sizes. Mirrors
     * SaleController::getCustomSizeError(). Returns [value, error].
     */
    private function resolveCustomSizeValue(Product $product, $requested, ?ProductVariant $variant = null): array
    {
        if (!$product->pair_product) {
            return [null, null];
        }

        $sizesSource = ($variant && !empty($variant->custom_sizes)) ? $variant->custom_sizes : ($product->custom_sizes ?? []);

        $value = ($requested !== null && $requested !== '') ? (float) $requested : null;
        $validSizes = collect($sizesSource)->pluck('size')->map(fn ($s) => (float) $s);

        if (!$value || !$validSizes->contains(fn ($s) => abs($s - $value) < 0.001)) {
            $minSize = collect($sizesSource)->sortBy(fn ($s) => (float) ($s['size'] ?? 0))->first();
            if ($minSize && isset($minSize['size'])) {
                return [(float) $minSize['size'], null];
            }

            return [null, 'Please select a valid pair option for "' . $product->name . '".'];
        }

        return [$value, null];
    }
}
