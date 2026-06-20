@extends('layouts.website')

@section('title', 'Shopping Cart - Chetan Imitation')

@section('content')
<div id="cartPageContent">
<section class="section-space">
    <div class="container-1440">

        <div class="text-center mb-7">
            <h2 class="font-moglan hero-title">Shopping Cart</h2>
            <p class="hero-para">Review your selected jewelry before proceeding to checkout.</p>
        </div>

        {{-- Empty cart --}}
        <div id="cartEmptyState" class="text-center py-20 {{ $cartItems->isEmpty() ? '' : 'hidden' }}">
            <svg class="mx-auto mb-6 w-20 h-20 text-[#D5D5D5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h.01M15 21h.01" />
            </svg>
            <h2 class="text-[#131615] text-2xl font-semibold mb-3">Your cart is empty</h2>
            <p class="text-[#757575] text-lg mb-8">Looks like you haven't added anything yet.</p>
            <a href="{{ route('shop-by-category') }}" class="common-btn inline-flex">Continue Shopping</a>
        </div>

        <div id="cartWrapper" class="grid xl:grid-cols-[953px_1fr] gap-6 items-start {{ $cartItems->isEmpty() ? 'hidden' : '' }}">

            {{-- Cart Items --}}
            <div class="space-y-[30px]" id="cartItemsList">
                @foreach($cartItems as $item)
                @php
                    $product  = $item->product;
                    $variant  = $item->productVariant;
                    $price    = $variant ? (float)$variant->sale_price : (float)$product->sale_price;
                    $mrp      = (float)$product->mrp;
                    $imgUrl   = $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png');
                    $attrLabel = null;
                    if ($variant && $variant->attributeValue) {
                        $attrLabel = optional($variant->attributeValue->attribute)->name
                            ? $variant->attributeValue->attribute->name . ': ' . $variant->attributeValue->value
                            : $variant->attributeValue->value;
                    }
                    $stockQty = $product->inventories_sum_quantity ?? 0;
                    $inWishlist = auth('customer')->check()
                        && auth('customer')->user()->wishlists->contains('product_id', $product->id);
                @endphp

                <div class="cart-item group border border-[#D5D5D5] p-3 lg:p-[25px]" data-id="{{ $item->id }}" data-price="{{ $price }}">
                    <div class="flex flex-col md:flex-row gap-4">

                        {{-- Image --}}
                        <div class="relative shrink-0 w-full md:w-[240px] h-[240px] overflow-hidden">
                            @if($stockQty < 1)
                            <div class="absolute top-[25px] left-[-42px] z-10 rotate-[-20deg]">
                                <span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">OUT OF STOCK</span>
                            </div>
                            @elseif($product->sale)
                            <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                                <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                            </div>
                            @endif
                            <a href="{{ route('product.detail', $product->slug) }}">
                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                            </a>

                            <button class="wishlist-btn absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center outline-none focus:outline-none focus:ring-0"
                                data-product-id="{{ $product->id }}"
                                data-variant-id="{{ $variant?->id ?? '' }}"
                                data-login-url="{{ route('login') }}"
                                data-toggle-url="{{ route('wishlist.toggle') }}"
                                data-current-url="{{ url()->current() }}"
                                data-in-wishlist="{{ $inWishlist ? '1' : '0' }}">
                               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
                                    class="wishlist-icon w-5 h-5 transition-all duration-300 {{ $inWishlist ? 'fill-[#E01B1B] text-[#E01B1B]' : 'fill-transparent text-[#131615]' }}">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                               </svg>
                            </button>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('product.detail', $product->slug) }}">
                                <h3 class="max-w-[280px] md:max-w-[500px] truncate text-base md:text-[22px] lg:text-[26px] leading-[26px] lg:leading-[36px] font-semibold text-[#131615] hover:text-[#B4771E] transition">
                                    {{ $product->name }}
                                </h3>
                            </a>

                            <div class="flex items-center gap-2 mt-5">
                                <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                    ₹{{ number_format($price, 0) }}
                                </span>
                                @if($mrp > $price)
                                <span class="text-[#999] line-through text-base md:text-lg">
                                    ₹{{ number_format($mrp, 0) }}
                                </span>
                                @endif
                            </div>

                            <div class="flex justify-between sm:items-center flex-col sm:flex-row gap-4">
                                <div class="sm:mt-[20px] text-[14px]">
                                    @if($product->category)
                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                        <span class="text-[#757575] ml-2">{{ $product->category->name }}</span>
                                    </p>
                                    @endif
                                    @if($attrLabel)
                                    @php
                                        $parts = explode(':', $attrLabel, 2);
                                        $labelName = count($parts) > 1 ? trim($parts[0]) : 'Variant';
                                        $labelVal = count($parts) > 1 ? trim($parts[1]) : trim($parts[0]);
                                    @endphp
                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">{{ $labelName }}:</span>
                                        <span class="text-[#757575] ml-2">{{ $labelVal }}</span>
                                    </p>
                                    @endif
                                    @if($stockQty > 0)
                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Availability:</span>
                                        <span class="text-[#777] ml-2">In Stock</span>
                                    </p>
                                    @endif
                                    <p class="text-base sm:text-lg sm:leading-[18px] flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Item Total:</span>
                                        <span class="text-[#B4771E] font-semibold ml-2 item-total-display">
                                            ₹{{ number_format($price * $item->qty, 0) }}
                                        </span>
                                    </p>
                                </div>

                                {{-- Qty stepper --}}
                                <div class="flex items-center border border-[#D5D5D5] py-[8px] sm:py-[10px] px-[8px] sm:px-[10px] md:px-[15px] gap-[15px] w-max">
                                    <button type="button" onclick="changeQty({{ $item->id }}, -1)"
                                        class="text-[#757575] text-base font-bold hover:text-[#131615] transition">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <span class="text-base md:text-[22px] text-center text-[#131615] w-6 qty-display">
                                        {{ $item->qty }}
                                    </span>
                                    <button type="button" onclick="changeQty({{ $item->id }}, 1)"
                                        class="text-[#757575] text-base hover:text-[#131615] transition">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-[#D5D5D5] mt-5 pt-[15px]">
                                <div class="flex items-center flex-wrap gap-2">
                                    <button type="button" onclick="removeItem({{ $item->id }})"
                                        class="after:border-r after:border-[#3D403F] after:h-5 after:mx-5 after:content-[''] after:inline-block hover:text-red-500 flex items-center gap-[10px] text-base md:text-lg transition">
                                        <img src="{{ asset('website/assets/images/delete.png') }}" alt="">
                                        Remove From Cart
                                    </button>
                                    <button type="button" onclick="moveToWishlist({{ $item->id }}, {{ $product->id }}, {{ $variant?->id ?? 'null' }}, this)"
                                        class="hover:text-[#B4771E] flex items-center gap-[10px] text-base md:text-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5 text-[#131615]">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"></path>
                                        </svg>
                                        Move to Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Order Summary --}}
            @php
                $subtotal = 0; $mrpTotal = 0;
                foreach($cartItems as $item) {
                    $p = $item->productVariant ? (float)$item->productVariant->sale_price : (float)$item->product->sale_price;
                    $m = (float)($item->product->mrp ?: $p);
                    $subtotal += $p * $item->qty;
                    $mrpTotal += $m * $item->qty;
                }
                $discount = 0;
                $shipping = 0;
                $total    = $subtotal + $shipping;
            @endphp

            <div class="space-y-4">
                <div class="border border-[#D5D5D5] p-5">
                    <h3 class="text-lg md:text-[22px] md:leading-[22px] font-medium text-[#131615]">Price Details</h3>

                    <div class="border-t border-[#D5D5D5] mt-[20px] pt-[20px] space-y-5">
                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px]">
                            <span class="font-medium text-[#131615]">Subtotal</span>
                            <span class="font-normal text-[#3D403F]" id="summarySubtotal">₹{{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px]" id="summaryDiscountRow">
                            <span class="font-medium text-[#131615]">Discount</span>
                            <span class="font-normal text-green-600" id="summaryDiscount">
                                @if($discount > 0)
                                    -₹{{ number_format($discount, 0) }}
                                @else
                                    ₹0
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px]">
                            <span class="font-medium text-[#131615]">Shipping</span>
                            <span class="font-normal text-[#3D403F]" id="summaryShipping">₹{{ number_format($shipping, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px]">
                            <span class="font-medium text-[#131615]">Estimated Tax</span>
                            <span class="font-normal text-[#3D403F]">₹0</span>
                        </div>
                    </div>

                    <div class="border-t border-[#D5D5D5] mt-4 pt-4 flex justify-between">
                        <span class="font-medium text-lg md:text-[22px] lg:text-[24px]">Total</span>
                        <span class="font-bold text-[#B4771E] text-lg md:text-[22px] lg:text-[24px]" id="summaryTotal">
                            ₹{{ number_format($total, 0) }}
                        </span>
                    </div>

                    <a href="{{ auth('customer')->check() ? '#' : route('login') . '?intended=' . urlencode(route('cart')) }}"
                        class="!w-full common-btn mt-[30px] flex items-center justify-center">
                        Process To Checkout
                    </a>
                    <a href="{{ route('shop-by-category') }}"
                        class="!w-full common-btn mt-3 !bg-transparent !text-[#131615] border-2 border-[#131615] !font-semibold flex items-center justify-center">
                        Continue Shopping
                    </a>
                </div>

                {{-- Delivery Info --}}
                <div class="border border-[#D5D5D5] p-5">
                    <h3 class="text-xl md:text-[26px] md:leading-[26px] font-semibold text-[#131615] mb-[24px]">
                        Delivery Information
                    </h3>
                    <div class="space-y-4 text-[#131615]">
                        <div class="flex gap-3 text-base md:text-xl font-normal">
                            <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                            <p>Free shipping on orders above ₹1999</p>
                        </div>
                        <div class="flex gap-3 text-base md:text-xl font-normal">
                            <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                            <p>Estimated delivery within 4–7 business days</p>
                        </div>
                        <div class="flex gap-3 text-base md:text-xl font-normal">
                            <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                            <p>Secure packaging for safe delivery</p>
                        </div>
                        <div class="flex gap-3 text-base md:text-xl font-normal">
                            <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                            <p>Easy return & exchange available</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

{{-- You May Also Like --}}
@if($relatedProducts->isNotEmpty())
<section class="section-space">
    <div class="container-1440">
        <div class="text-center mb-10">
            <h2 class="font-moglan hero-title">You May Also Like</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('website.partials.product-grid-items', ['products' => $relatedProducts])
        </div>
    </div>
</section>
@endif
</div>
@endsection

@section('page-js')
<script>
    const CART_ADD_URL    = '{{ route('cart.add') }}';
    const CART_UPDATE_URL = '{{ route('cart.update') }}';
    const CART_REMOVE_URL = '{{ route('cart.remove') }}';
    const LOGIN_URL       = '{{ route('login') }}?intended={{ urlencode(route('cart')) }}';
    const CSRF            = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    /* ── Helpers ── */
    function formatPrice(val) {
        return '₹' + Math.round(parseFloat(val)).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }



    function showToast(msg, type) {
        if (window.showWishlistToast) window.showWishlistToast(msg, type !== 'error');
    }

    /* ── Qty change ── */
    function changeQty(cartItemId, delta) {
        const row     = document.querySelector(`.cart-item[data-id="${cartItemId}"]`);
        const qtyEl   = row.querySelector('.qty-display');
        const totalEl = row.querySelector('.item-total-display');
        const price   = parseFloat(row.dataset.price);
        let   qty     = parseInt(qtyEl.textContent) + delta;
        if (qty < 1) qty = 1;

        fetch(CART_UPDATE_URL, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ cart_item_id: cartItemId, qty }),
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => { throw new Error(text || 'HTTP ' + r.status); });
            }
            return r.json();
        })
        .then(data => {
            if (data.status === 'success') {
                qtyEl.textContent   = qty;
                totalEl.textContent = formatPrice(data.item_total);
                if (window.updateCartBadge) window.updateCartBadge(data.count);
                updateSummary(data.totals);
            } else {
                showToast(data.message || 'Failed to update quantity.', 'error');
            }
        })
        .catch(err => {
            console.error('changeQty error:', err);
            showToast('Something went wrong: ' + err.message, 'error');
        });
    }

    /* ── Remove item ── */
    function fetchAndSwapCart() {
        fetch('/cart')
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var newContent = doc.getElementById('cartPageContent');
                var oldContent = document.getElementById('cartPageContent');
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                }
            });
    }

    document.addEventListener('cartUpdated', function () {
        fetchAndSwapCart();
    });

    /* ── Remove item ── */
    function removeItem(cartItemId) {
        fetch(CART_REMOVE_URL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ cart_item_id: cartItemId }),
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => { throw new Error(text || 'HTTP ' + r.status); });
            }
            return r.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const row = document.querySelector(`.cart-item[data-id="${cartItemId}"]`);
                if (row) row.remove();
                if (window.updateCartBadge) window.updateCartBadge(data.count);
                updateSummary(data.totals);
                showToast('Item removed from cart. 🗑️');
                if (data.empty) fetchAndSwapCart();
            } else {
                showToast(data.message || 'Failed to remove item.', 'error');
            }
        })
        .catch(err => {
            console.error('removeItem error:', err);
            showToast('Something went wrong: ' + err.message, 'error');
        });
    }

    function moveToWishlist(cartItemId, productId, variantId, btn) {
        var toggleUrl = '{{ route('wishlist.toggle') }}';
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }

        var isLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};
        if (!isLoggedIn) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
            window.location.href = LOGIN_URL;
            return;
        }
        
        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                product_variant_id: variantId || null
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            fetch(CART_REMOVE_URL, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ cart_item_id: cartItemId }),
            })
            .then(function (r) { return r.json(); })
            .then(function (cartData) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                if (cartData.status === 'success') {
                    if (window.updateCartBadge) window.updateCartBadge(cartData.count);
                    if (window.updateWishlistBadge) window.updateWishlistBadge(data.count);
                    showToast('Item moved to Wishlist! ❤️');
                    fetchAndSwapCart();
                }
            });
        })
        .catch(function (err) {
            console.error('moveToWishlist error:', err);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
            showToast('Something went wrong: ' + err.message, 'error');
        });
    }

    /* ── Update summary panel ── */
    function updateSummary(totals) {
        const subtotalEl  = document.getElementById('summarySubtotal');
        const discountEl  = document.getElementById('summaryDiscount');
        const discRow     = document.getElementById('summaryDiscountRow');
        const shippingEl  = document.getElementById('summaryShipping');
        const totalEl     = document.getElementById('summaryTotal');

        if (subtotalEl)  subtotalEl.textContent  = formatPrice(totals.subtotal);
        if (shippingEl)  shippingEl.textContent  = totals.shipping_label;
        if (totalEl)     totalEl.textContent      = formatPrice(totals.total);

        if (discountEl) {
            if (parseFloat(totals.discount) > 0) {
                discountEl.textContent = '-' + formatPrice(totals.discount);
            } else {
                discountEl.textContent = '₹0';
            }
            if (discRow) {
                discRow.classList.remove('hidden');
            }
        }
    }
</script>
@endsection
