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
                'product' => function ($query) {
                    $query->withSum('inventories', 'quantity');
                },
                'product.primaryImage',
                'product.category',
                'product.variants.attributeValue.attribute',
                'productVariant.attributeValue.attribute',
            ])
            ->latest()
            ->get();

        $wishlistProductIds = $wishlists->pluck('product_id')->toArray();

        $relatedProducts = \App\Models\Product::where('status', 1)
            ->whereNotIn('id', $wishlistProductIds)
            ->with(['primaryImage', 'variants.attributeValue'])
            ->withSum('inventories', 'quantity')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('website.wishlist', compact('wishlists', 'relatedProducts'));
    }

    /**
     * Toggle wishlist (add / remove / update variant). Returns JSON.
     *
     * Rules:
     *  - No existing record          → create  (status: added)
     *  - Existing, same variant      → delete  (status: removed)
     *  - Existing, different variant → update  (status: updated)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $customer  = Auth::guard('customer')->user();
        $variantId = $request->input('product_variant_id') ?: null;

        $rows = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $request->product_id)
            ->orderByDesc('id')
            ->get();

        if ($rows->count() > 1) {
            $keep = $rows->first();
            Wishlist::where('customer_id', $customer->id)
                ->where('product_id', $request->product_id)
                ->where('id', '!=', $keep->id)
                ->delete();
            $existing = $keep->fresh();
        } else {
            $existing = $rows->first();
        }

        if ($existing) {
            if ((string) $existing->product_variant_id === (string) $variantId) {
                $existing->delete();
                $status = 'removed';
            } else {
                $existing->update(['product_variant_id' => $variantId]);
                $status = 'updated';
            }
        } else {
            Wishlist::create([
                'customer_id'        => $customer->id,
                'product_id'         => $request->product_id,
                'product_variant_id' => $variantId,
            ]);
            $status = 'added';
        }

        $count = Wishlist::where('customer_id', $customer->id)->count();

        return response()->json([
            'status'     => $status,
            'count'      => $count,
            'variant_id' => $variantId,
        ]);
    }
}
