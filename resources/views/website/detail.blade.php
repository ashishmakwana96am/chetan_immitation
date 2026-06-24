@extends('layouts.website')

@section('title', $product->name . ' - Chetan Imitation')

@section('page-css')
<style>
    .product-detail-panel,
    .product-detail-accordion,
    .product-detail-content {
        min-width: 0;
        max-width: 100%;
    }

    .product-detail-content,
    .product-detail-content * {
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal !important;
    }

    .product-detail-content pre {
        white-space: pre-wrap !important;
        overflow-x: hidden;
    }

    .product-detail-content p {
        margin: 0 0 10px;
    }
</style>
@endsection

@section('content')

@php
    $queryVariantId = request('variant');
    if (!$queryVariantId && isset($wishlistItem) && $wishlistItem && $wishlistItem->product_variant_id) {
        $queryVariantId = $wishlistItem->product_variant_id;
    }
@endphp

<section class="pt-[50px] pb-[60px] md:pb-[80px] lg:pb-[100px]">

    <div class="container-1440 overflow-visible">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-[30px] items-start">

            <!-- LEFT SIDE -->
            <div class="lg:col-span-5 w-full  lg:sticky lg:top-[80px]">
                    <div class="swiper mainSwiper relative 2xl:min-h-[60vh]">

                        <div class="swiper-wrapper">
                            @forelse($product->images as $img)
                            <div class="swiper-slide">
                                <img src="{{ $img->image_url }}" class="w-full h-auto ">
                            </div>
                            @empty
                            <div class="swiper-slide">
                                <img src="{{ asset('website/assets/images/detailpage.png') }}" class="w-full h-auto ">
                            </div>
                            @endforelse
                        </div>

                        <button class="wishlist-btn absolute top-3 right-3 z-20 w-[42px] h-[42px] bg-white rounded-lg shadow flex items-center justify-center outline-none focus:outline-none focus:ring-0"
                            data-is-main-wishlist="1"
                            data-product-id="{{ $product->id }}"
                            data-variant-id="{{ $queryVariantId }}"
                            data-login-url="{{ route('login') }}"
                            data-toggle-url="{{ route('wishlist.toggle') }}"
                            data-current-url="{{ url()->current() }}"
                            @php
                                $inWishlist = false;
                                if (auth('customer')->check()) {
                                    $inWishlistQuery = \App\Models\Wishlist::where('customer_id', auth('customer')->id())
                                        ->where('product_id', $product->id);
                                    if ($queryVariantId) {
                                        $inWishlistQuery->where('product_variant_id', $queryVariantId);
                                    }
                                    // No variant in URL → check if product is wishlisted in any form
                                    $inWishlist = $inWishlistQuery->exists();
                                }
                            @endphp
                            data-in-wishlist="{{ $inWishlist ? '1' : '0' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
                                class="wishlist-icon w-5 h-5 transition-all duration-300 {{ $inWishlist ? 'fill-[#E01B1B] text-[#E01B1B]' : 'text-[#131615] fill-transparent' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                        </button>

                    </div>
                    <div class="swiper thumbSwiper mt-4">
                        <div class="swiper-wrapper">
                            @forelse($product->images as $img)
                            <div class="swiper-slide border border-[#D5D5D5] cursor-pointer">
                                <img src="{{ $img->image_url }}" class="w-full h-full object-contain">
                            </div>
                            @empty
                            <div class="swiper-slide border border-[#D5D5D5] cursor-pointer">
                                <img src="{{ asset('website/assets/images/detailpage.png') }}" class="w-full h-full object-cover">
                            </div>
                            @endforelse
                        </div>
                    </div>
            </div>

            <!-- RIGHT SIDE -->

            <div class="lg:col-span-7 product-detail-panel min-w-0">

                <h1 class="text-[#131615] font-bold text-lg md:text-[22px] sm:text-[28px] sm:leading-[34px]">
                    {{ $product->name }}
                </h1>

                @php
                    $avgRating = round((float) ($product->reviews_avg_rating ?? 0), 1);
                    $reviewCount = (int) ($product->reviews_count ?? 0);
                @endphp
                <div class="flex items-center gap-2 mt-3">
                    @include('website.partials.star-rating', ['rating' => $avgRating, 'size' => 'md'])
                    <span class="text-[#757575] text-base md:text-xl">{{ number_format($avgRating, 1) }} ({{ $reviewCount }})</span>
                </div>

                @php
                    $activeVariant = null;
                    if ($queryVariantId && $product->variants->isNotEmpty()) {
                        $activeVariant = $product->variants->firstWhere('id', $queryVariantId);
                    }
                    $salePriceDisplay = $activeVariant ? $activeVariant->sale_price : $product->sale_price;
                    $mrpDisplay = $activeVariant ? ($activeVariant->product->mrp ?? $product->mrp) : $product->mrp;
                @endphp
                <div class="flex items-center gap-[10px] mt-4 sm:mt-6 ">
                    <span id="productSalePrice" class="text-[#B4771E] text-[22px] leading-[24px] sm:text-[30px] font-bold">
                        ₹{{ number_format($salePriceDisplay, 0) }}
                    </span>
                    @if($mrpDisplay && $mrpDisplay > $salePriceDisplay)
                    <span id="productMrp" class="line-through text-[#757575] text-[22px] md:text-2xl leading-[24px]">
                        ₹{{ number_format($mrpDisplay, 0) }}
                    </span>
                    @endif
                </div>

                <p class="text-[#3D403F] mt-4 md:mt-5 text-base sm:text-xl">
                    Inclusive of all taxes
                </p>

                <div class="flex items-center gap-4 mt-5 lg:mt-5">
                    <span class="text-[#131615] text-base md:text-xl sm:text-[22px] leading-[22px]">
                        Quantity:
                    </span>
                    <div class="flex items-center border border-[#D5D5D5] py-[10px] px-[10px] md:px-[15px] gap-[15px]">
                        <button id="minusBtn" class="text-[#757575] text-base md:text-lg font-bold">
                          <i class="fa-solid fa-minus"></i>
                        </button>
                        <span id="qty" class="text-base md:text-xl text-center text-[#131615] w-5">1</span>
                        <button id="plusBtn" class="text-[#757575] text-base md:text-lg">
                           <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    @php $detailStock = $product->inventories_sum_quantity ?? 0; @endphp
                    @if($detailStock < 1)
                    <button id="addToCartBtn"
                        class="common-btn h-[50px] w-full max-w-[300px] opacity-50 cursor-not-allowed pointer-events-none"
                        data-product-id="{{ $product->id }}"
                        data-login-url="{{ route('login') }}?intended={{ urlencode(route('cart')) }}"
                        disabled>
                        Sold Out
                    </button>
                    @else
                    <button id="addToCartBtn"
                        class="common-btn h-[50px] w-full max-w-[300px]"
                        data-product-id="{{ $product->id }}"
                        data-login-url="{{ route('login') }}?intended={{ urlencode(route('cart')) }}">
                        Add To Cart
                    </button>
                    @endif
                    <button id="buyNowBtn"
                        class="border border-[#131615] common-btn bg-transparent text-[#131615] hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E] h-[50px] w-full max-w-[300px] {{ $detailStock < 1 ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                        data-product-id="{{ $product->id }}"
                        data-login-url="{{ route('login') }}?intended={{ urlencode(url()->current()) }}"
                        {{ $detailStock < 1 ? 'disabled' : '' }}>
                        Buy Now
                    </button>
                </div>

                @php
                    $variantGroups = $product->variants
                        ->filter(fn ($variant) => $variant->attributeValue && $variant->attributeValue->attribute)
                        ->groupBy(fn ($variant) => $variant->attributeValue->attribute->id);
                    $hasAdditionalInformation = filled(trim(html_entity_decode(strip_tags($product->additional_information ?? ''))));
                    $hasProductHighlights = filled(trim(html_entity_decode(strip_tags($product->product_highlights ?? ''))));
                @endphp

                @if($variantGroups->isNotEmpty())
                    @php
                        $allProductVariants = $product->variants;
                        $hasSelectedVariant = $queryVariantId && $allProductVariants->contains('id', $queryVariantId);
                    @endphp
                    @foreach($variantGroups as $variants)
                    @php
                        $attribute = $variants->first()->attributeValue->attribute;
                        $attributeValues = $variants->unique('attribute_value_id');
                    @endphp
                    <div class="mt-5">
                        <h4 class="text-xl md:text-[22px] font-medium mb-[15px] text-[#131615]">
                            {{ $attribute->name }}:
                        </h4>
                        <div class="flex flex-wrap gap-3 md:gap-4">
                            @foreach($attributeValues as $variant)
                            @php
                                $isActive = $hasSelectedVariant ? ($variant->id == $queryVariantId) : false;
                            @endphp
                            <button class="variant-selector min-w-[69px] px-2 py-1 md:py-2 border text-base leading-tight whitespace-normal text-center transition-all duration-300 {{ $isActive ? 'bg-[#B4771E] text-white border-[#B4771E] active' : 'border-[#D5D5D5] text-[#131615] hover:border-[#B4771E]' }}"
                                data-variant-id="{{ $variant->id }}"
                                data-sale-price="{{ $variant->sale_price }}"
                                data-mrp="{{ $variant->product->mrp ?? '' }}">
                                {{ $variant->attributeValue->value }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @endif

                <div class="product-detail-accordion min-w-0 max-w-full ">
                    <details class="group" open>
                        <summary class="list-none flex items-center justify-between pt-[26px] pb-3 cursor-pointer border-b border-[#D9D9D9]">
                            <h3 class="text-xl md:text-[22px] font-medium text-[#1A1A1A]">Product Description</h3>
                            <svg class="w-5 h-5 transition-transform duration-300" data-detail-chevron fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="product-detail-content text-base md:text-lg text-[#3D403F] pt-5">
                            {!! $product->description ?? 'No description available.' !!}
                        </div>
                    </details>

                    @if($hasAdditionalInformation)
                    <details class="group">
                        <summary class="list-none flex items-center justify-between pt-5 pb-3 cursor-pointer border-b border-[#D9D9D9]">
                            <h3 class="text-xl md:text-[22px] font-medium text-[#1A1A1A]">Product Information</h3>
                            <svg class="w-5 h-5 transition-transform duration-300" data-detail-chevron fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="product-detail-content text-base md:text-lg text-[#3D403F] pt-5">
                            {!! $product->additional_information !!}
                        </div>
                    </details>
                    @endif

                    @if($hasProductHighlights)
                    <details class="group">
                        <summary class="list-none flex items-center justify-between pt-5 pb-3 cursor-pointer border-b border-[#D9D9D9]">
                            <h3 class="text-xl md:text-[22px] font-medium text-[#1A1A1A]">Product Highlights</h3>
                            <svg class="w-5 h-5 transition-transform duration-300" data-detail-chevron fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="product-detail-content text-base md:text-lg text-[#3D403F] pt-5">
                            {!! $product->product_highlights !!}
                        </div>
                    </details>
                    @endif
                </div>

            </div>

        </div>

    </div>

</section>

@if($topReviews->isNotEmpty())
<section class="section-space-bottom">
    <div class="container-1440">
        <div class="text-center mb-10 lg:mb-14">
            <h2 class="font-moglan hero-title">Customer Reviews & Ratings</h2>
            <p class="hero-para">See what our customers are saying about this product.</p>
        </div>

        <div class="space-y-5">
            @foreach($topReviews as $review)
            @php
                $authorName = $review->customer->display_name ?: $review->customer->name;
                $authorAvatar = $review->customer->avatar
                    ? asset($review->customer->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($authorName) . '&background=B4771E&color=fff&size=120&bold=true';
            @endphp
            <div class="border border-[#D5D5D5] p-4 lg:p-5">
                <h4 class="text-[#131615] text-lg md:text-xl font-medium">{{ $review->created_at->format('l, F j, Y') }}</h4>
                @if($review->comment)
                <p class="mt-[14px] md:mt-[17px] text-[#3D403F] text-base md:text-lg">{{ $review->comment }}</p>
                @endif
                <div class="border-t border-[#e3e3e3] mt-4 md:mt-5 pt-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" class="w-[60px] h-[60px] rounded-full object-cover">
                        <div>
                            <h5 class="text-[#131615] text-lg md:text-xl">{{ $authorName }}</h5>
                            <div class="flex mt-1">
                                @include('website.partials.star-rating', ['rating' => $review->rating, 'size' => 'lg'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- You May Also Like -->
<section class="section-space-bottom">

    <div class="container-1440">

        <div class="text-center mb-8 lg:mb-10">
            <h2 class="font-moglan hero-title">You May Also Like</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('website.partials.product-grid-items', ['products' => $relatedProducts])
        </div>

    </div>

</section>

@endsection

@section('page-js')

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>

const thumbSwiper = new Swiper(".thumbSwiper", {
    spaceBetween: 15,
    slidesPerView: 5,
    watchSlidesProgress: true,
    breakpoints: {
        0: { slidesPerView: 3 },
        992: { slidesPerView: 3 },
        1199: { slidesPerView: 5 }
    }
});

const mainSwiper = new Swiper(".mainSwiper", {
    spaceBetween: 10,
    loop: true,
    thumbs: { swiper: thumbSwiper },
});

</script>

<script>

function syncProductDetailChevron(details) {
    const chevron = details.querySelector('[data-detail-chevron]');
    if (!chevron) return;

    chevron.style.rotate = '';
    chevron.style.transform = details.open ? 'rotate(180deg)' : 'rotate(0deg)';
    chevron.setAttribute('aria-expanded', details.open ? 'true' : 'false');
}

document.querySelectorAll('details').forEach((details) => {
    syncProductDetailChevron(details);
    details.addEventListener('toggle', () => syncProductDetailChevron(details));
});

const qty = document.getElementById("qty");
const plusBtn = document.getElementById("plusBtn");
const minusBtn = document.getElementById("minusBtn");
let count = parseInt(qty ? qty.innerText : 1) || 1;

if (plusBtn) {
    plusBtn.addEventListener("click", () => {
        count++;
        if (qty) qty.innerText = count;
    });
}
if (minusBtn) {
    minusBtn.addEventListener("click", () => {
        if (count > 1) {
            count--;
            if (qty) qty.innerText = count;
        }
    });
}
</script>

<script>
// ─── Detail page wishlist handler ────────────────────────────────────────────
(function () {
    var isLoggedIn    = {{ auth('customer')->check() ? 'true' : 'false' }};
    var csrfToken     = '{{ csrf_token() }}';
    var mainProductId = '{{ $product->id }}';

    // Track which variant (or null) is currently wishlisted for this product.
    // "null" means product is wishlisted without a variant.
    // "false" means not wishlisted at all.
    @php
        $wl = auth('customer')->check()
            ? auth('customer')->user()->wishlists()->where('product_id', $product->id)->first()
            : null;
    @endphp
    var wishlistedVariantId = {!! $wl ? ($wl->product_variant_id ? $wl->product_variant_id : 'null') : 'false' !!};

    // ── helpers ──────────────────────────────────────────────────────────────

    function getMainWishlistBtn() {
        return document.querySelector('.mainSwiper .wishlist-btn');
    }

    function getActiveVariantId() {
        var btn = document.querySelector('.variant-selector.active');
        return btn ? (btn.dataset.variantId || null) : null;
    }

    function setHeartState(btn, inWishlist) {
        if (!btn) return;
        var svg = btn.querySelector('svg');
        btn.dataset.inWishlist = inWishlist ? '1' : '0';
        if (!svg) return;
        if (inWishlist) {
            svg.classList.remove('fill-transparent', 'text-[#131615]');
            svg.classList.add('fill-[#E01B1B]', 'text-[#E01B1B]');
        } else {
            svg.classList.remove('fill-[#E01B1B]', 'text-[#E01B1B]');
            svg.classList.add('fill-transparent', 'text-[#131615]');
        }
    }

    function toast(msg) {
        if (window.showWishlistToast) window.showWishlistToast(msg);
    }

    // ── direct AJAX update helper ──
    function syncWishlistVariantAndQty(variantId, forceUpdate) {
        var toggleUrl = '{{ route('wishlist.toggle') }}';
        var mainBtn = getMainWishlistBtn();
        if (mainBtn && mainBtn.dataset.toggleUrl) {
            toggleUrl = mainBtn.dataset.toggleUrl;
        }

        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                product_id:         mainProductId,
                product_variant_id: variantId || null,
                force_update:       forceUpdate ? 1 : 0
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (mainBtn) mainBtn.dataset.loading = '0';
            if (data.status === 'added' || data.status === 'updated') {
                wishlistedVariantId = variantId !== null ? variantId : null;
                if (mainBtn) setHeartState(mainBtn, true);
                if (window.updateWishlistBadge) window.updateWishlistBadge(data.count);
                toast(data.status === 'updated' ? 'Wishlist updated! ❤️' : 'Product added to your wishlist! ❤️');
            } else {
                wishlistedVariantId = false;
                if (mainBtn) setHeartState(mainBtn, false);
                if (window.updateWishlistBadge) window.updateWishlistBadge(data.count);
                toast('Product removed from your wishlist.');
            }
        })
        .catch(function () {
            if (mainBtn) mainBtn.dataset.loading = '0';
        });
    }

    // ── variant selector clicks ───────────────────────────────────────────────
    // When user switches variant, update the heart to reflect wishlist state or auto-update if wishlisted
    document.querySelectorAll('.variant-selector').forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Visual active state
            var parent = btn.parentElement;
            parent.querySelectorAll('.variant-selector').forEach(function (s) {
                s.classList.remove('bg-[#B4771E]', 'text-white', 'border-[#B4771E]', 'active');
                s.classList.add('border-[#D5D5D5]', 'text-[#131615]');
            });
            btn.classList.add('bg-[#B4771E]', 'text-white', 'border-[#B4771E]', 'active');
            btn.classList.remove('border-[#D5D5D5]', 'text-[#131615]');

            // Price update
            var priceSpan = document.getElementById('productSalePrice');
            if (priceSpan && btn.dataset.salePrice) {
                priceSpan.textContent = '₹' + parseFloat(btn.dataset.salePrice)
                    .toLocaleString('en-IN', { maximumFractionDigits: 0 });
            }

            var variantId = btn.dataset.variantId || null;
            var mainBtn   = getMainWishlistBtn();

            if (mainBtn) {
                mainBtn.dataset.variantId = variantId || '';
            }

            if (
                isLoggedIn &&
                wishlistedVariantId !== false &&
                String(wishlistedVariantId) !== String(variantId)
            ) {
                // If already wishlisted and changing variant, update on server directly
                syncWishlistVariantAndQty(variantId, true);
            } else {
                if (mainBtn) {
                    var inWishlist = (wishlistedVariantId !== false) &&
                        (String(wishlistedVariantId) === String(variantId) ||
                         (wishlistedVariantId === null && !variantId));
                    setHeartState(mainBtn, inWishlist);
                }
            }
        });
    });

    // ── wishlist button clicks ────────────────────────────────────────────────
    // One listener handles details page heart click (main product)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.wishlist-btn[data-toggle-url]');
        if (!btn) return;

        // Only handle the main product wishlist button on details page
        if (btn.dataset.isMainWishlist !== '1') return;

        e.preventDefault();
        e.stopPropagation();

        if (btn.dataset.loading === '1') return;
        btn.dataset.loading = '1';

        var productId = btn.dataset.productId;
        var toggleUrl = btn.dataset.toggleUrl;

        var variantId = (wishlistedVariantId !== false)
            ? (wishlistedVariantId !== null ? wishlistedVariantId : null)
            : getActiveVariantId();

        // ── Not logged in ──
        if (!isLoggedIn) {
            btn.dataset.loading = '0';
            sessionStorage.setItem('pendingWishlist', JSON.stringify({
                product_id:         productId,
                product_variant_id: getActiveVariantId(),
            }));
            var intended = btn.dataset.currentUrl || window.location.href;
            window.location.href = btn.dataset.loginUrl + '?intended=' + encodeURIComponent(intended);
            return;
        }

        // Toggle on/off normally
        syncWishlistVariantAndQty(variantId, false);
    });
}());
</script>

<script>
// ─── Detail page Add to Cart ──────────────────────────────────────────────────
(function () {
    var addBtn = document.getElementById('addToCartBtn');
    if (!addBtn) return;

    addBtn.addEventListener('click', function () {
        var productId = addBtn.dataset.productId;
        var loginUrl  = addBtn.dataset.loginUrl;
        var activeVariant = document.querySelector('.variant-selector.active');
        var variantId = activeVariant ? (activeVariant.dataset.variantId || null) : null;

        var hasVariants = document.querySelectorAll('.variant-selector').length > 0;
        if (hasVariants && !variantId) {
            if (window.showWishlistToast) {
                window.showWishlistToast('Please select attribute first', false);
            } else {
                alert('Please select attribute first');
            }
            return;
        }

        var qty = parseInt(document.getElementById('qty')?.innerText || 1) || 1;

        window.addToCart(productId, variantId, qty, addBtn, loginUrl);
    });
}());
</script>

{{-- ══ BUY NOW — Address Modal ══ --}}
<div id="buyNowAddressModal" class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4">
    <div class="min-h-full flex items-center justify-center py-5">
        <div class="relative w-full max-w-[560px] bg-white rounded-[8px] p-5 md:p-7 max-h-[90vh] overflow-y-auto border border-[#D5D5D5]">
            <button onclick="closeBuyNowAddressModal()" class="absolute top-4 right-4 text-[32px] leading-none text-[#131615]">&times;</button>
            <h2 class="text-xl md:text-[24px] font-medium text-[#131615] mb-5">Select Delivery Address</h2>

            {{-- No addresses --}}
            @if(auth('customer')->check() && \App\Models\CustomerAddress::where('customer_id', auth('customer')->id())->count() === 0)
            <p class="text-[#757575] text-base mb-5">No saved addresses. Please <a href="{{ route('customer.profile') }}#addresses" class="text-[#B4771E] underline">add an address</a> first.</p>
            @else
            <div id="buyNowAddressList" class="space-y-3 mb-6">
                @if(auth('customer')->check())
                @foreach(\App\Models\CustomerAddress::where('customer_id', auth('customer')->id())->orderByDesc('is_default')->get() as $addr)
                <label class="flex items-start gap-3 border border-[#D5D5D5] p-4 cursor-pointer rounded has-[:checked]:border-[#B4771E] has-[:checked]:bg-[#B4771E0A]">
                    <input type="radio" name="buynow_address" value="{{ $addr->id }}" class="mt-1 accent-[#B4771E]" {{ $addr->is_default ? 'checked' : '' }}>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-[#131615] text-base">{{ $addr->name }} — {{ $addr->phone }}</p>
                        <p class="text-[#757575] text-sm mt-1">{{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }}</p>
                        @if($addr->is_default)
                        <span class="inline-block mt-1 bg-[#B4771E29] text-[#B4771E] text-xs px-2 py-0.5">Default</span>
                        @endif
                    </div>
                </label>
                @endforeach
                @endif
            </div>
            <button onclick="startBuyNowPayment()" id="buyNowProceedBtn"
                class="w-full h-[50px] bg-[#B4771E] hover:bg-[#9d6719] text-white text-lg font-medium transition rounded-sm">
                Proceed to Pay
            </button>
            @endif
        </div>
    </div>
</div>

{{-- ══ BUY NOW — Payment Loader ══ --}}
<div id="buyNowLoader" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 backdrop-blur-sm !mt-0">
    <div class="flex flex-col items-center gap-6 px-8 py-10 bg-white rounded-2xl shadow-2xl max-w-[340px] w-full mx-4 text-center !mt-0">
        <div class="relative w-20 h-20">
            <svg class="animate-spin w-20 h-20 text-[#B4771E]" viewBox="0 0 50 50" fill="none">
                <circle cx="25" cy="25" r="20" stroke="#F3E3C8" stroke-width="5"/>
                <path d="M25 5 A20 20 0 0 1 45 25" stroke="#B4771E" stroke-width="5" stroke-linecap="round"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
             <svg xmlns="http://www.w3.org/2000/svg"
     width="20"
     height="20"
     viewBox="0 0 32 32"
     fill="#B4771E">
    
    <path d="M8 10a1 1 0 0 1 1-1h16a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1z"/>
    
    <path d="M8 5a1 1 0 0 1 1-1h16a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1z"/>
    
    <path d="M12.5 5a1 1 0 0 1 1-1 7.5 7.5 0 0 1 0 15h-1.913l9.086 8.26a1 1 0 1 1-1.346 1.48l-11-10A1 1 0 0 1 9 17h4.5a5.5 5.5 0 0 0 0-11 1 1 0 0 1-1-1z"/>
</svg>

            </div>
        </div>
        <div>
            <h3 class="text-xl font-semibold text-[#131615] mb-2">Verifying Payment</h3>
            <p class="text-sm text-[#757575] leading-relaxed">Please wait while we confirm your payment. Do not close or refresh the page.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0s"></span>
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0.15s"></span>
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0.3s"></span>
        </div>
    </div>
</div>

{{-- ══ BUY NOW — Success Modal ══ --}}
<div id="buyNowSuccessModal" class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4 !mt-0">
    <div class="min-h-full flex items-center justify-center py-5 !mt-0">
        <div class="relative w-full max-w-[720px] bg-white rounded-[8px] p-4 sm:p-6 md:p-[33px] max-h-[90vh] overflow-y-auto scrollbar-hide">
            <button onclick="closeBuyNowSuccessModal()" class="absolute top-4 right-4 text-[32px] text-[#131615]">&times;</button>
            <div class="flex justify-center">
                <img src="{{ asset('website/assets/images/rightcheck.png') }}" alt="" class="w-[80px] md:w-[90px] text-red-500">
            </div>
            <h2 class="text-center font-moglan text-[30px] sm:text-[40px]
                leading-tight text-[#131615] mt-2">Order Placed Successfully!</h2>
            <p class="text-center text-[#3D403F] text-sm md:text-base font-normal
                max-w-[520px] mx-auto mt-3">
                Thank you for shopping with Chetan Imitation. Your order has been confirmed and is now being processed.
            </p>
            <div class="border border-[#D5D5D5] mt-4 p-4 rounded-sm">
                <div class="flex justify-between items-center border-b border-[#D5D5D5] pb-3">
                      <div class="flex items-center gap-[15px]">
                        <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center">
                        <svg width="16" height="20" viewBox="0 0 19 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.25 14.75V11.6875C18.25 10.6432 17.8352 9.64169 17.0967 8.90327C16.3583 8.16484 15.3568 7.75 14.3125 7.75H12.5625C12.2144 7.75 11.8806 7.61172 11.6344 7.36558C11.3883 7.11944 11.25 6.7856 11.25 6.4375V4.6875C11.25 3.64321 10.8352 2.64169 10.0967 1.90327C9.35831 1.16484 8.35679 0.75 7.3125 0.75H5.125M5.125 15.625H13.875M5.125 19.125H9.5M7.75 0.75H2.0625C1.338 0.75 0.75 1.338 0.75 2.0625V22.1875C0.75 22.912 1.338 23.5 2.0625 23.5H16.9375C17.662 23.5 18.25 22.912 18.25 22.1875V11.25C18.25 8.46523 17.1438 5.79451 15.1746 3.82538C13.2055 1.85625 10.5348 0.75 7.75 0.75Z" stroke="#B4771E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        </div>
                        <span class="text-base md:text-lg font-semibold">
                            Order ID
                        </span>
                    </div>
                    <span id="buyNowSuccessOrderId" class="text-[#3D403F] text-base sm:text-lg font-mono font-semibold text-end">-</span>
                </div>
                <div class="flex justify-between items-center border-b border-[#D5D5D5] py-5">
                     <div class="flex items-center gap-[15px]">
                      <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center text-[#B4771E]">
                        
                    <svg width="16" height="16" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.75 6.875H7.75M14.75 10.375H7.75M11.25 17.375L7.75 13.875H9.5C10.4283 13.875 11.3185 13.5063 11.9749 12.8499C12.6313 12.1935 13 11.3033 13 10.375C13 9.44674 12.6313 8.5565 11.9749 7.90013C11.3185 7.24375 10.4283 6.875 9.5 6.875M21.75 11.25C21.75 12.6289 21.4784 13.9943 20.9507 15.2682C20.4231 16.5421 19.6496 17.6996 18.6746 18.6746C17.6996 19.6496 16.5421 20.4231 15.2682 20.9507C13.9943 21.4784 12.6289 21.75 11.25 21.75C9.87112 21.75 8.50574 21.4784 7.23182 20.9507C5.95791 20.4231 4.80039 19.6496 3.82538 18.6746C2.85036 17.6996 2.07694 16.5421 1.54926 15.2682C1.02159 13.9943 0.75 12.6289 0.75 11.25C0.75 8.46523 1.85625 5.79451 3.82538 3.82538C5.79451 1.85625 8.46523 0.75 11.25 0.75C14.0348 0.75 16.7055 1.85625 18.6746 3.82538C20.6438 5.79451 21.75 8.46523 21.75 11.25Z" stroke="#B4771E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                        </div>
                        <span class="text-base md:text-lg font-semibold">
Order Amount
                        </span>
                    </div>
                    <span id="buyNowSuccessOrderAmount" class="text-[#3D403F] text-base font-semibold text-end">-</span>
                </div>
                <div class="flex justify-between items-center py-5">
                    <div class="flex items-center gap-[15px]">
                      <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center">
                        
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"  stroke="#B4771E" />
                    </svg>


                        </div>
                        <span class="text-base md:text-lg font-semibold">
                         Estimated Delivery
                        </span>
                    </div>
                   <span class="text-[#3D403F] text-base text-end">
                        4–7 Business Days
                    </span>
                </div>
            </div>
             <div
                class="bg-[#B4771E]/10 p-4 rounded-[5px] mt-3 flex gap-[15px] items-start">
               
               <div>
                 <svg width="40" height="40" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_292_2508)">
                <path d="M29 0.150391C44.9078 0.150391 57.8496 13.0922 57.8496 29C57.8496 44.9078 44.9078 57.8496 29 57.8496C13.0922 57.8496 0.150391 44.9078 0.150391 29C0.150391 13.0922 13.0922 0.150391 29 0.150391ZM29 2.56836C14.4257 2.56836 2.56836 14.4257 2.56836 29C2.56836 43.5743 14.4257 55.4316 29 55.4316C43.5743 55.4316 55.4316 43.5743 55.4316 29C55.4316 14.4257 43.5743 2.56836 29 2.56836ZM15.3711 17.1992H42.6289C44.3692 17.1992 45.7852 18.6152 45.7852 20.3555V37.6445C45.7852 39.3848 44.3692 40.8018 42.6289 40.8018H15.3711C13.6307 40.8018 12.2139 39.3848 12.2139 37.6445V20.3555C12.2139 18.6152 13.6308 17.1992 15.3711 17.1992ZM43.123 21.0283L29.752 31.6465C29.5319 31.8214 29.2659 31.9082 29 31.9082C28.7342 31.9082 28.4682 31.8212 28.248 31.6465L14.876 21.0283L14.6328 20.835V37.6445C14.633 37.8401 14.7113 38.0278 14.8496 38.166C14.9879 38.3042 15.1756 38.3826 15.3711 38.3828H42.6289C42.8244 38.3826 43.0121 38.3043 43.1504 38.166C43.2887 38.0278 43.367 37.8401 43.3672 37.6445V20.835L43.123 21.0283ZM17.3262 19.8848L28.9062 29.0811L29 29.1553L29.0938 29.0811L40.6729 19.8848L41.0098 19.6172H16.9902L17.3262 19.8848Z" fill="#B4771E" stroke="#F8F2E9" stroke-width="0.3"/>
                </g>
                <defs>
                <clipPath id="clip0_292_2508">
                <rect width="58" height="58" fill="white"/>
                </clipPath>
                </defs>
                </svg>
               </div>

                <p class="text-[#131615] text-base md:text-lg">
                    A confirmation email and order details have been sent to your
                    registered email address.
                </p>
            </div>
            <button onclick="window.location.href='{{ route('customer.profile') }}'"
                class="w-full h-[52px] bg-[#B4771E] text-white
                text-base md:text-[22px] md:leading-[24px] mt-7">
                View My Orders
            </button>
            <button onclick="closeBuyNowSuccessModal()"
                class="common-btn h-[52px] mt-4 md:mt-5 w-full border-2 border-[#131615] text-[#131615] font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                Continue Shopping
            </button>
        </div>
    </div>
</div>

{{-- ══ BUY NOW — Failure Modal ══ --}}
<div id="buyNowFailureModal" class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4 !mt-0">
    <div class="min-h-full flex items-center justify-center !mt-0">
        <div class="relative w-full max-w-[720px] bg-white rounded-[8px] p-4 sm:p-6 md:p-[33px] max-h-[90vh] overflow-y-auto scrollbar-hide">
            <button onclick="closeBuyNowFailureModal()" class="absolute top-4 right-4 text-[32px] text-[#131615]">&times;</button>
            <div class="flex justify-center">
                <svg class="w-[120px] md:w-[150px] text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
             <h2
                class=" text-red-600 font-moglan text-[30px] sm:text-[40px]
                leading-tight mt-2 text-center">
                Payment Failed!
            </h2>
              <p class="text-center text-[#3D403F] text-sm md:text-base font-normal
                max-w-[520px] mx-auto mt-3">
                We were unable to process your payment. Please try again or select a different payment option.
            </p>
            <div
                class="bg-red-50 border border-red-200 p-3 rounded-[5px] mt-4 flex gap-[15px] items-start">
                <p id="failureReason" class="text-red-700 text-base">
                    The payment request was cancelled or declined.
                </p>
            </div>
            <button onclick="retryBuyNow()"
                 class="w-full common-btn mt-5 h-[52px]">
                Retry Payment
            </button>
            <button onclick="closeBuyNowFailureModal()"
                  class="common-btn h-[52px] mt-4 md:mt-5 w-full border-2 border-[#131615] text-[#131615] font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                Cancel
            </button>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    var isLoggedIn    = {{ auth('customer')->check() ? 'true' : 'false' }};
    var csrfToken     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var buyNowRzpInstance  = null;
    var buyNowRazorpayOrderId = null;
    var buyNowPaymentCompleted = false;

    // ── helpers ──────────────────────────────────────────────────────────────
    function getActiveVariantId() {
        var btn = document.querySelector('.variant-selector.active');
        return btn ? (btn.dataset.variantId || null) : null;
    }

    function showLoader() {
        var el = document.getElementById('buyNowLoader');
        el.classList.remove('hidden');
        el.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function hideLoader() {
        var el = document.getElementById('buyNowLoader');
        el.classList.add('hidden');
        el.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    window.openBuyNowAddressModal = function () {
        document.getElementById('buyNowAddressModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };
    window.closeBuyNowAddressModal = function () {
        document.getElementById('buyNowAddressModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };
    window.closeBuyNowSuccessModal = function () {
        document.getElementById('buyNowSuccessModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        window.location.href = '{{ route('shop-by-category') }}';
    };
    window.closeBuyNowFailureModal = function () {
        document.getElementById('buyNowFailureModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    function openSuccessModal(orderNo, amount) {
        document.getElementById('buyNowSuccessOrderId').textContent = '#' + orderNo;
        document.getElementById('buyNowSuccessOrderAmount').textContent = '₹' + amount;
        document.getElementById('buyNowSuccessModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    function openFailureModal(reason) {
        document.getElementById('buyNowFailureReason').textContent = reason || 'Payment was cancelled or declined.';
        document.getElementById('buyNowFailureModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    // ── Buy Now button click ──────────────────────────────────────────────────
    var buyNowBtn = document.getElementById('buyNowBtn');
    if (buyNowBtn) {
        buyNowBtn.addEventListener('click', function () {
            if (!isLoggedIn) {
                window.location.href = buyNowBtn.dataset.loginUrl;
                return;
            }
            var hasVariants = document.querySelectorAll('.variant-selector').length > 0;
            if (hasVariants && !getActiveVariantId()) {
                if (window.showWishlistToast) window.showWishlistToast('Please select attribute first', false);
                return;
            }
            openBuyNowAddressModal();
        });
    }

    // ── Proceed to Pay ────────────────────────────────────────────────────────
    window.startBuyNowPayment = function () {
        var selectedAddr = document.querySelector('input[name="buynow_address"]:checked');
        if (!selectedAddr) {
            if (window.showWishlistToast) window.showWishlistToast('Please select a delivery address.', false);
            return;
        }

        var productId = document.getElementById('buyNowBtn').dataset.productId;
        var variantId = getActiveVariantId();
        var qty       = parseInt(document.getElementById('qty')?.innerText || 1) || 1;
        var addressId = selectedAddr.value;

        var proceedBtn = document.getElementById('buyNowProceedBtn');
        proceedBtn.disabled = true;
        proceedBtn.textContent = 'Processing...';

        buyNowPaymentCompleted = false;

        fetch('{{ route('buynow.payment.initialize') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                address_id: addressId,
                product_id: productId,
                variant_id: variantId || null,
                qty: qty
            })
        })
        .then(function (r) {
            if (!r.ok) return r.json().then(function (e) { throw e; });
            return r.json();
        })
        .then(function (data) {
            proceedBtn.disabled = false;
            proceedBtn.textContent = 'Proceed to Pay';

            if (data.status !== 'success') throw new Error(data.message || 'Initialization failed.');

            buyNowRazorpayOrderId = data.order_id;
            closeBuyNowAddressModal();

            var options = {
                key:         data.key,
                amount:      data.amount,
                currency:    data.currency,
                name:        'Chetan Imitation',
                description: 'Order Payment (ORD: ' + data.order.order_no + ')',
                order_id:    data.order_id,
                handler: function (response) {
                    buyNowPaymentCompleted = true;
                    showLoader();
                    fetch('{{ route('checkout.payment.verify') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            razorpay_payment_id:  response.razorpay_payment_id,
                            razorpay_order_id:    response.razorpay_order_id,
                            razorpay_signature:   response.razorpay_signature
                        })
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        hideLoader();
                        if (d.status === 'success') {
                            openSuccessModal(d.order.order_no, d.order.final_amount);
                        } else {
                            openFailureModal(d.message || 'Verification failed.');
                        }
                    })
                    .catch(function (err) {
                        hideLoader();
                        openFailureModal(err.message || 'Payment signature verification failed.');
                    });
                },
                prefill: { name: data.prefill.name, email: data.prefill.email, contact: data.prefill.contact },
                theme: { color: '#B4771E' },
                modal: {
                    ondismiss: function () {
                        if (buyNowPaymentCompleted) return;
                        if (buyNowRazorpayOrderId) {
                            fetch('{{ route('checkout.payment.failed') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                body: JSON.stringify({ razorpay_order_id: buyNowRazorpayOrderId })
                            }).catch(function () {});
                        }
                        openFailureModal('Payment window closed before completing transaction.');
                    }
                }
            };

            buyNowRzpInstance = new Razorpay(options);
            buyNowRzpInstance.on('payment.failed', function (resp) {
                if (buyNowPaymentCompleted) return;
                if (buyNowRazorpayOrderId) {
                    fetch('{{ route('checkout.payment.failed') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ razorpay_order_id: buyNowRazorpayOrderId })
                    }).catch(function () {});
                }
                openFailureModal(resp.error.description || 'Payment transaction failed.');
            });
            buyNowRzpInstance.open();
        })
        .catch(function (err) {
            proceedBtn.disabled = false;
            proceedBtn.textContent = 'Proceed to Pay';
            if (window.showWishlistToast) window.showWishlistToast(err.message || 'Something went wrong.', false);
        });
    };

    window.retryBuyNow = function () {
        closeBuyNowFailureModal();
        openBuyNowAddressModal();
    };

    // Overlay / ESC close
    ['buyNowAddressModal', 'buyNowSuccessModal', 'buyNowFailureModal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', function (e) {
            if (e.target === el) {
                if (id === 'buyNowSuccessModal') { closeBuyNowSuccessModal(); }
                else if (id === 'buyNowFailureModal') { closeBuyNowFailureModal(); }
                else { closeBuyNowAddressModal(); }
            }
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('buyNowSuccessModal').classList.contains('hidden')) { closeBuyNowSuccessModal(); return; }
        if (!document.getElementById('buyNowFailureModal').classList.contains('hidden')) { closeBuyNowFailureModal(); return; }
        if (!document.getElementById('buyNowAddressModal').classList.contains('hidden')) { closeBuyNowAddressModal(); return; }
    });
}());
</script>
@endsection
