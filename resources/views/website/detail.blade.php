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

                <div class="flex items-center gap-2 mt-3">
                    <div class="flex text-[#B4771E] text-[14px]">
                       <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                       <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                       <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                       <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                       <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                    </div>
                    <span class="text-[#757575] text-base md:text-xl">(0)</span>
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
                    <button class="common-btn h-[50px]  w-full max-w-[300px]">Add To Cart</button>
                    <button class="border border-[#131615] common-btn bg-transparent text-[#131615] hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E] h-[50px] w-full max-w-[300px]">Buy Now</button>
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
                                $isActive = $hasSelectedVariant ? ($variant->id == $queryVariantId) : $loop->first;
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
                        <summary class="list-none flex items-center justify-between pt-[22px] pb-3 cursor-pointer border-b border-[#D9D9D9]">
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
                        <summary class="list-none flex items-center justify-between pt-[22px] pb-3 cursor-pointer border-b border-[#D9D9D9]">
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

<!-- <section>

    <div class="container-1440">

        <div class="text-center mb-10 lg:mb-14">
            <h2 class="font-moglan hero-title">Customer Reviews & Ratings</h2>
            <p class="hero-para">See what our customers are saying about this product.</p>
        </div>

        <div class="space-y-5">

            <div class="border border-[#D5D5D5] p-4 lg:p-5">
                <h4 class="text-[#131615] text-lg md:text-xl font-medium">Friday, May 15, 2026</h4>
                <p class="mt-[14px] md:mt-[17px] text-[#3D403F] text-base md:text-lg">The necklace looked stunning and elegant for my wedding day.</p>
                <div class="border-t border-[#e3e3e3] mt-4 md:mt-5 pt-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('website/assets/images/star1.jpg') }}" alt="" class="w-[60px] h-[60px] rounded-full">
                        <div>
                            <h5 class="text-[#131615] text-lg md:text-xl">Meera Patel</h5>
                            <div class="flex mt-1 text-[#B4771E] text-[18px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="" class="w-full max-w-[25px]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border border-[#D5D5D5] p-4 lg:p-5">
                <h4 class="text-[#131615] text-lg md:text-xl font-medium">Thursday, May 28, 2026</h4>
                <p class="mt-[14px] md:mt-[17px] text-[#3D403F] text-base md:text-lg">The necklace exceeded my expectations with its elegant design, premium finishing, and beautiful detailing.</p>
                <div class="border-t border-[#e3e3e3] mt-4 md:mt-5 pt-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('website/assets/images/star2.jpg') }}" alt="" class="w-[60px] h-[60px] rounded-full object-cover">
                        <div>
                            <h5 class="text-[#131615] text-lg md:text-xl">Anjali Verma</h5>
                            <div class="flex mt-1 text-[#B4771E] text-[18px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="" class="w-full max-w-[25px]">
                               <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="" class="w-full max-w-[25px]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</section> -->

<!-- You May Also Like -->
<section class="section-space-bottom">

    <div class="container-1440">

        <div class="text-center mb-10 lg:mb-10">
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
        .catch(function () {});
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

        // Skip related products / grid item wishlist buttons (handled by layouts/website.blade.php)
        if (!btn.closest('.mainSwiper')) return;

        e.preventDefault();
        e.stopPropagation();

        var productId = btn.dataset.productId;
        var toggleUrl = btn.dataset.toggleUrl;

        var variantId = (wishlistedVariantId !== false)
            ? (wishlistedVariantId !== null ? wishlistedVariantId : null)
            : getActiveVariantId();

        // ── Not logged in ──
        if (!isLoggedIn) {
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

@endsection
