<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Show the wishlist page (auth:customer required).
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $wishlists = Wishlist::where('customer_id', $customer->id)
            ->with([
                'product.primaryImage',
                'product.category',
                'product.variants.attributeValue.attribute',
                'productVariant.attributeValue.attribute',
            ])
            ->latest()
            ->get();

        return view('website.wishlist', compact('wishlists'));
    }

    /**
     * Toggle wishlist (add / remove). Returns JSON.
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity'           => ['nullable', 'integer', 'min:1'],
        ]);

        $customer  = Auth::guard('customer')->user();
        $variantId = $request->input('product_variant_id') ?: null;
        $quantity  = $request->input('quantity') ?: 1;

        $existing = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->delete();
            $status = 'removed';
        } else {
            Wishlist::create([
                'customer_id'        => $customer->id,
                'product_id'         => $request->product_id,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
            ]);
            $status = 'added';
        }

        $count = Wishlist::where('customer_id', $customer->id)->count();

        return response()->json([
            'status' => $status,
            'count'  => $count,
        ]);
    }
}
