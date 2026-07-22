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
            
            // Remove cart items with deleted products
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
                        'pair_type'          => $itemData['pair_type'] ?? 'single',
                        'custom_size_value'  => $itemData['custom_size_value'] ?? null,
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
            ->hasImages()
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
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'variant_id'        => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty'               => ['nullable', 'integer', 'min:1'],
            'pair_type'         => ['nullable', 'string', 'in:single,pair'],
            'custom_size_value' => ['nullable', 'numeric'],
        ]);

        $customer  = $this->customer();
        $productId = (int) $request->product_id;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;
        $qty       = max(1, (int) ($request->qty ?? 1));
        $pairType  = in_array($request->pair_type, ['single', 'pair']) ? $request->pair_type : 'single';

        // Verify product is active
        $product = Product::where('status', Product::STATUS_ACTIVE)
            ->withSum('inventories', 'quantity')
            ->find($productId);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        // If not a pair product, force single
        if (!$product->pair_product) {
            $pairType = 'single';
        }

        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::where('product_id', $productId)->where('status', 1)->find($variantId);
            if (!$variant) {
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

        // Verify stock
        $availableStock = $product->totalAvailableStock($variantId);
        if ($availableStock < 1) {
            $count = $customer ? CartItem::where('customer_id', $customer->id)->sum('qty') : array_sum(array_column($this->getGuestCart($request), 'qty'));
            return response()->json([
                'status'  => 'success',
                'message' => 'Item added to cart.',
                'count'   => $count,
            ]);
        }

        // Calculate total quantity (in Pcs) of this product/variant already in the user's cart
        $cartPcs = 0;
        if ($customer) {
            $cartItems = CartItem::where('customer_id', $customer->id)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->get();
            foreach ($cartItems as $cItem) {
                $cartPcs += $this->stockMultiplier($product, $cItem->pair_type, $cItem->custom_size_value) * $cItem->qty;
            }
        } else {
            $guestCart = $this->getGuestCart($request);
            foreach ($guestCart as $cItem) {
                if ($cItem['product_id'] === $productId && $cItem['variant_id'] === $variantId) {
                    $cartPcs += $this->stockMultiplier($product, $cItem['pair_type'] ?? 'single', $cItem['custom_size_value'] ?? null) * $cItem['qty'];
                }
            }
        }

        // The proposed new addition Pcs
        $unitPcs = $this->stockMultiplier($product, $pairType, $customSizeValue);
        $newPcs  = $unitPcs * $qty;

        if (($cartPcs + $newPcs) > $availableStock) {
            $allowedPcs = $availableStock - $cartPcs;
            $qty = $allowedPcs > 0 ? (int) floor($allowedPcs / $unitPcs) : 0;
        }

        if ($qty > 0) {
            if ($customer) {
                $existing = CartItem::withTrashed()
                    ->where('customer_id', $customer->id)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantId)
                    ->where('pair_type', $pairType)
                    ->where('custom_size_value', $customSizeValue)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                        $existing->update(['qty' => $qty]);
                    } else {
                        $existing->increment('qty', $qty);
                    }
                } else {
                    CartItem::create([
                        'customer_id'        => $customer->id,
                        'product_id'         => $productId,
                        'product_variant_id' => $variantId,
                        'pair_type'          => $pairType,
                        'custom_size_value'  => $customSizeValue,
                        'qty'                => $qty,
                    ]);
                }
            } else {
                // Guest user
                $guestCart = $this->getGuestCart($request);

                $found = false;
                foreach ($guestCart as &$item) {
                    if ($item['product_id'] === $productId && $item['variant_id'] === $variantId && ($item['pair_type'] ?? 'single') === $pairType && $this->sameSize($item['custom_size_value'] ?? null, $customSizeValue)) {
                        $item['qty'] += $qty;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $guestCart[] = [
                        'product_id'        => $productId,
                        'variant_id'        => $variantId,
                        'pair_type'         => $pairType,
                        'custom_size_value' => $customSizeValue,
                        'qty'               => $qty,
                    ];
                }

                session()->put('guest_cart', $guestCart);
            }
        }

        $count = $customer ? CartItem::where('customer_id', $customer->id)->sum('qty') : array_sum(array_column($this->getGuestCart($request), 'qty'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Item added to cart.',
            'count'   => $count,
        ]);
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

            $product = $item->product;
            $variant = $item->productVariant;
            $productId = $product->id;
            $variantId = $variant ? $variant->id : null;
            $pairType = $item->pair_type ?? 'single';
            $customSizeValue = $item->custom_size_value;

            $availableStock = $product->totalAvailableStock($variantId);

            // Get other cart items of the same product/variant
            $otherCartPcs = 0;
            $cartItems = CartItem::where('customer_id', $customer->id)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->get();
            foreach ($cartItems as $cItem) {
                if ($cItem->id !== $item->id) {
                    $otherCartPcs += $this->stockMultiplier($product, $cItem->pair_type, $cItem->custom_size_value) * $cItem->qty;
                }
            }

            $unitPcs = $this->stockMultiplier($product, $pairType, $customSizeValue);
            $proposedPcs = $unitPcs * $qty;
            if (($otherCartPcs + $proposedPcs) > $availableStock) {
                $allowedPcs = $availableStock - $otherCartPcs;
                $qty = $allowedPcs > 0 ? (int) floor($allowedPcs / $unitPcs) : 0;
            }

            if ($qty > 0) {
                $item->update(['qty' => $qty]);
            } else {
                $item->delete();
            }

            $count  = CartItem::where('customer_id', $customer->id)->sum('qty');
            $totals = $this->calculateTotals($customer->id);

            $price = $item->getPrice();

            return response()->json([
                'status'     => 'success',
                'count'      => $count,
                'qty'        => $qty,
                'item_total' => $price * $qty,
                'totals'     => $totals,
            ]);
        } else {
            // Guest Update
            $guestCart = $this->getGuestCart($request);
            $cartItemId = (int) $request->cart_item_id;

            if (!isset($guestCart[$cartItemId])) {
                return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
            }

            $itemData = $guestCart[$cartItemId];
            $productId = $itemData['product_id'];
            $variantId = isset($itemData['variant_id']) && $itemData['variant_id'] !== '' ? (int) $itemData['variant_id'] : null;
            $pairType = $itemData['pair_type'] ?? 'single';
            $customSizeValue = $itemData['custom_size_value'] ?? null;

            $product = Product::find($productId);
            $availableStock = $product->totalAvailableStock($variantId);

            // Get other cart items of the same product/variant
            $otherCartPcs = 0;
            foreach ($guestCart as $idx => $cItem) {
                if ($idx !== $cartItemId && $cItem['product_id'] === $productId && $cItem['variant_id'] === $variantId) {
                    $otherCartPcs += $this->stockMultiplier($product, $cItem['pair_type'] ?? 'single', $cItem['custom_size_value'] ?? null) * $cItem['qty'];
                }
            }

            $unitPcs = $this->stockMultiplier($product, $pairType, $customSizeValue);
            $proposedPcs = $unitPcs * $qty;
            if (($otherCartPcs + $proposedPcs) > $availableStock) {
                $allowedPcs = $availableStock - $otherCartPcs;
                $qty = $allowedPcs > 0 ? (int) floor($allowedPcs / $unitPcs) : 0;
            }

            if ($qty > 0) {
                $guestCart[$cartItemId]['qty'] = $qty;
            } else {
                unset($guestCart[$cartItemId]);
                $guestCart = array_values($guestCart);
            }

            session()->put('guest_cart', $guestCart);
            $totals = $this->calculateGuestTotals($guestCart);
            $count = array_sum(array_column($guestCart, 'qty'));

            $variant = $variantId ? ProductVariant::find($variantId) : null;
            $price = (new CartItem([
                'pair_type'         => $pairType,
                'custom_size_value' => $customSizeValue,
            ]))->setRelation('product', $product)->setRelation('productVariant', $variant)->getPrice();

            return response()->json([
                'status'     => 'success',
                'count'      => $count,
                'qty'        => $qty,
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
            $subtotal += $item->getPrice() * $item->qty;
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
            $pairType  = $item['pair_type'] ?? 'single';
            $customSizeValue = $item['custom_size_value'] ?? null;

            $product = Product::find($productId);
            if ($product) {
                $variant = $variantId ? ProductVariant::where('product_id', $productId)->find($variantId) : null;
                $price = (new CartItem([
                    'pair_type'         => $pairType,
                    'custom_size_value' => $customSizeValue,
                ]))->setRelation('product', $product)->setRelation('productVariant', $variant)->getPrice();

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

    /**
     * How many stock units one cart "qty" unit represents: a chosen custom
     * size (e.g. 6 pcs) for custom_size-mode pair products, 2 for plain pair
     * products bought as a pair, or 1 otherwise. Mirrors SaleController::stockMultiplierFor().
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

    /**
     * Null-safe, float-tolerant comparison of two custom size values coming
     * from session-stored guest cart data (which may be strings).
     */
    private function sameSize($a, $b): bool
    {
        if ($a === null || $a === '') {
            return $b === null || $b === '';
        }
        if ($b === null || $b === '') {
            return false;
        }

        return abs((float) $a - (float) $b) < 0.001;
    }
}
