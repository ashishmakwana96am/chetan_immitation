<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Wishlist;
use Illuminate\Http\Request;

trait MergesGuestCustomerState
{
    /**
     * Merge the guest session cart and any pending wishlist action into the
     * now-authenticated customer's account. Returns the customer's current
     * wishlist count.
     */
    protected function mergeGuestCartAndWishlist(Request $request, Customer $customer): int
    {
        $guestCart = session()->get('guest_cart', []);

        if (!empty($guestCart)) {
            foreach ($guestCart as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = isset($item['variant_id']) && $item['variant_id'] !== '' ? (int) $item['variant_id'] : null;
                $qty       = (int) ($item['qty'] ?? 1);

                if ($productId > 0) {
                    $pairType = $item['pair_type'] ?? 'single';
                    $existing = CartItem::where('customer_id', $customer->id)
                        ->where('product_id', $productId)
                        ->where('product_variant_id', $variantId)
                        ->where('pair_type', $pairType)
                        ->first();

                    if ($existing) {
                        $existing->increment('qty', $qty);
                    } else {
                        CartItem::create([
                            'customer_id'        => $customer->id,
                            'product_id'         => $productId,
                            'product_variant_id' => $variantId,
                            'pair_type'          => $pairType,
                            'qty'                => $qty,
                        ]);
                    }
                }
            }
            session()->forget('guest_cart');
        }

        $wishlistCount = $customer->wishlists()->count();

        $pendingWishlist = $request->session()->pull('pending_wishlist')
            ?? session()->pull('pending_wishlist')
            ?? $request->input('pending_wishlist');

        if ($pendingWishlist) {
            $data = json_decode($pendingWishlist, true);
            if ($data && isset($data['product_id'])) {
                $existing = Wishlist::withTrashed()
                    ->where('customer_id', $customer->id)
                    ->where('product_id', $data['product_id'])
                    ->first();
                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update([
                        'product_variant_id' => $data['product_variant_id'] ?? null,
                    ]);
                } else {
                    Wishlist::create([
                        'customer_id'        => $customer->id,
                        'product_id'         => $data['product_id'],
                        'product_variant_id' => $data['product_variant_id'] ?? null,
                    ]);
                }
                $wishlistCount = $customer->wishlists()->count();
            }
        }

        return $wishlistCount;
    }

    protected function resolveIntendedUrl(Request $request): string
    {
        return $request->query('intended')
            ?? session()->pull('url.intended')
            ?? route('home');
    }
}
