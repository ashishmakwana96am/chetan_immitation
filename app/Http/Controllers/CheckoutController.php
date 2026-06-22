<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Product;

class CheckoutController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $customer = $this->customer();

        // Get cart items for the logged-in customer
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

        // Retrieve user-wise addresses
        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        $productIds = $cartItems->pluck('product_id')->unique()->toArray();

        $relatedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->whereNotIn('id', $productIds ?: [0])
            ->with(['primaryImage', 'variants.attributeValue'])
            ->withSum('inventories', 'quantity')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        // Calculate totals for checkout summary
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;
            $subtotal += $price * $item->qty;
        }
        $discount = 0.0;
        $shipping = $subtotal > 1999 || $subtotal === 0.0 ? 0.0 : 99.0;
        $total = $subtotal + $shipping;

        return view('website.checkout', compact('cartItems', 'addresses', 'relatedProducts', 'subtotal', 'discount', 'shipping', 'total'));
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
            'type' => ['required', 'string', 'in:home,work'],
        ]);

        $customer = $this->customer();

        // If this is the first address, it should be default
        $hasAddress = CustomerAddress::where('customer_id', $customer->id)->exists();
        $isDefault = !$hasAddress;

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'type' => $request->type,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Address saved successfully.'
        ]);
    }
}
