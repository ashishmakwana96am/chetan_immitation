<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

class CartController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    private function getGuestCart($request = null)
    {
        return session()->get('guest_cart', []);
    }

    /**
     * GET /cart — show cart page
     */
    public function index()
    {
        $customer = $this->customer();

        if ($customer) {
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
            $customer->load('wishlists');
        } else {
            // Guest User
            $guestCart = $this->getGuestCart();
            $cartItems = collect();
            foreach ($guestCart as $index => $itemData) {
                $productId = (int) ($itemData['product_id'] ?? 0);
                $variantId = isset($itemData['variant_id']) && $itemData['variant_id'] !== '' ? (int) $itemData['variant_id'] : null;
                $qty       = (int) ($itemData['qty'] ?? 1);

                $product = Product::withSum('inventories', 'quantity')
                    ->with([
                        'primaryImage',
                        'category',
                        'variants.attributeValue.attribute'
                    ])
                    ->find($productId);

                if ($product) {
                    $item = new CartItem([
                        'product_id'         => $productId,
                        'product_variant_id' => $variantId,
                        'qty'                => $qty
                    ]);
                    $item->id = $index; // Use array index as item ID for guest actions
                    $item->setRelation('product', $product);

                    if ($variantId) {
                        $variant = ProductVariant::with('attributeValue.attribute')->find($variantId);
                        if ($variant) {
                            $item->setRelation('productVariant', $variant);
                        }
                    }
                    $cartItems->push($item);
                }
            }
        }

        $productIds = $cartItems->pluck('product_id')->unique()->toArray();

        $relatedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->whereNotIn('id', $productIds ?: [0])
            ->with(['primaryImage', 'variants.attributeValue'])
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('website.cart', compact('cartItems', 'relatedProducts'));
    }

    /**
     * POST /cart/add
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty'        => ['nullable', 'integer', 'min:1'],
        ]);

        $customer  = $this->customer();
        $productId = (int) $request->product_id;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;
        $qty       = max(1, (int) ($request->qty ?? 1));

        // Verify product is active
        $product = Product::where('status', Product::STATUS_ACTIVE)
            ->withSum('inventories', 'quantity')
            ->find($productId);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        // Verify stock
        if (($product->inventories_sum_quantity ?? 0) < 1) {
            return response()->json(['status' => 'error', 'message' => 'This product is currently out of stock.'], 422);
        }

        // Verify variant belongs to product
        if ($variantId) {
            $variant = ProductVariant::where('product_id', $productId)->where('status', 1)->find($variantId);
            if (!$variant) {
                return response()->json(['status' => 'error', 'message' => 'Variant not found.'], 422);
            }
        }

        if ($customer) {
            $existing = CartItem::where('customer_id', $customer->id)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($existing) {
                $existing->increment('qty', $qty);
            } else {
                CartItem::create([
                    'customer_id'        => $customer->id,
                    'product_id'         => $productId,
                    'product_variant_id' => $variantId,
                    'qty'                => $qty,
                ]);
            }

            $count = CartItem::where('customer_id', $customer->id)->sum('qty');

            return response()->json([
                'status'  => 'success',
                'message' => 'Item added to cart.',
                'count'   => $count,
            ]);
        } else {
            // Guest user
            $guestCart = $this->getGuestCart($request);

            $found = false;
            foreach ($guestCart as &$item) {
                if ($item['product_id'] === $productId && $item['variant_id'] === $variantId) {
                    $item['qty'] += $qty;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $guestCart[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty'        => $qty,
                ];
            }

            session()->put('guest_cart', $guestCart);
            $count = array_sum(array_column($guestCart, 'qty'));

            return response()->json([
                'status'  => 'success',
                'message' => 'Item added to cart.',
                'count'   => $count,
            ]);
        }
    }

    /**
     * PATCH /cart/update
     */
    public function update(Request $request)
    {
        $request->validate([
            'cart_item_id' => ['required', 'integer'],
            'qty'          => ['required', 'integer', 'min:1'],
        ]);

        $customer = $this->customer();
        $qty = (int) $request->qty;

        if ($customer) {
            $item = CartItem::where('id', $request->cart_item_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$item) {
                return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
            }

            $item->update(['qty' => $qty]);

            $count  = CartItem::where('customer_id', $customer->id)->sum('qty');
            $totals = $this->calculateTotals($customer->id);

            // Price for this item
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;

            return response()->json([
                'status'     => 'success',
                'count'      => $count,
                'item_total' => $price * $item->qty,
                'totals'     => $totals,
            ]);
        } else {
            // Guest Update
            $guestCart = $this->getGuestCart($request);
            $cartItemId = (int) $request->cart_item_id;

            if (!isset($guestCart[$cartItemId])) {
                return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
            }

            $guestCart[$cartItemId]['qty'] = $qty;

            session()->put('guest_cart', $guestCart);
            $totals = $this->calculateGuestTotals($guestCart);
            $count = array_sum(array_column($guestCart, 'qty'));

            // Price for this item
            $itemData = $guestCart[$cartItemId];
            $product = Product::find($itemData['product_id']);
            $variant = isset($itemData['variant_id']) && $itemData['variant_id'] !== '' ? ProductVariant::find($itemData['variant_id']) : null;
            $price = $variant ? (float) $variant->sale_price : (float) $product->sale_price;

            return response()->json([
                'status'     => 'success',
                'count'      => $count,
                'item_total' => $price * $qty,
                'totals'     => $totals,
            ]);
        }
    }

    /**
     * DELETE /cart/remove
     */
    public function remove(Request $request)
    {
        $request->validate(['cart_item_id' => ['required', 'integer']]);

        $customer = $this->customer();
        $cartItemId = (int) $request->cart_item_id;

        if ($customer) {
            CartItem::where('id', $cartItemId)
                ->where('customer_id', $customer->id)
                ->delete();

            $count  = CartItem::where('customer_id', $customer->id)->sum('qty');
            $totals = $this->calculateTotals($customer->id);

            return response()->json([
                'status' => 'success',
                'count'  => $count,
                'totals' => $totals,
                'empty'  => $count === 0,
            ]);
        } else {
            // Guest Remove
            $guestCart = $this->getGuestCart($request);

            if (isset($guestCart[$cartItemId])) {
                unset($guestCart[$cartItemId]);
                $guestCart = array_values($guestCart);
            }

            session()->put('guest_cart', $guestCart);
            $count = array_sum(array_column($guestCart, 'qty'));
            $totals = $this->calculateGuestTotals($guestCart);

            return response()->json([
                'status' => 'success',
                'count'  => $count,
                'totals' => $totals,
                'empty'  => $count === 0,
            ]);
        }
    }

    /**
     * GET /cart/count
     */
    public function count()
    {
        $customer = $this->customer();
        if ($customer) {
            $count = CartItem::where('customer_id', $customer->id)->sum('qty');
        } else {
            $guestCart = $this->getGuestCart();
            $count = array_sum(array_column($guestCart, 'qty'));
        }
        return response()->json(['count' => $count]);
    }

    private function calculateTotals(int $customerId): array
    {
        $items = CartItem::where('customer_id', $customerId)
            ->with('product', 'productVariant')
            ->get();

        $subtotal = 0.0;

        foreach ($items as $item) {
            $price = $item->productVariant
                ? (float) $item->productVariant->sale_price
                : (float) $item->product->sale_price;

            $subtotal += $price * $item->qty;
        }

        $discount = 0.0;
        $shipping = 0.0;
        $total    = $subtotal + $shipping;

        return [
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'shipping'       => $shipping,
            'shipping_label' => '₹0',
            'total'          => $total,
        ];
    }

    private function calculateGuestTotals(array $guestCart): array
    {
        $subtotal = 0.0;

        foreach ($guestCart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = isset($item['variant_id']) && $item['variant_id'] !== '' ? (int) $item['variant_id'] : null;
            $qty       = (int) ($item['qty'] ?? 1);

            $product = Product::find($productId);
            if ($product) {
                $price = $variantId
                    ? (float) ProductVariant::where('product_id', $productId)->find($variantId)?->sale_price
                    : (float) $product->sale_price;

                if (!$price) {
                    $price = (float) $product->sale_price;
                }

                $subtotal += $price * $qty;
            }
        }

        $discount = 0.0;
        $shipping = 0.0;
        $total    = $subtotal + $shipping;

        return [
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'shipping'       => $shipping,
            'shipping_label' => '₹0',
            'total'          => $total,
        ];
    }
}
