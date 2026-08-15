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
                            <div class="swiper-slide overflow-hidden relative">
                                <img src="{{ $img->image_url }}" class="zoom-main-img w-full h-[573px] object-cover transition-transform duration-150 ease-out select-none">
                            </div>
                            @empty
                            <div class="swiper-slide overflow-hidden relative">
                                <img src="{{ asset('website/assets/images/no-image.svg') }}" class="zoom-main-img w-full h-[573px] object-cover transition-transform duration-150 ease-out select-none">
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
                                <img src="{{ $img->image_url }}" class="w-full h-full object-cover">
                            </div>
                            @empty
                            <div class="swiper-slide border border-[#D5D5D5] cursor-pointer">
                                <img src="{{ asset('website/assets/images/no-image.svg') }}" class="w-full h-full object-cover">
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
                    <span class="text-[#757575] text-base md:text-xl !leading-[20px] mt-1">{{ number_format($avgRating, 1) }} ({{ $reviewCount }})</span>
                </div>

                @php
                    $activeVariant = null;
                    if ($queryVariantId && $product->variants->isNotEmpty()) {
                        $activeVariant = $product->variants->firstWhere('id', $queryVariantId);
                    }

                    $customSizesSorted = collect($product->custom_sizes ?? [])->sortBy(fn($s) => (float)($s['size'] ?? 0));
                    $maxSizeRow = $customSizesSorted->last();

                    if ($product->pair_product && $maxSizeRow) {
                        $salePriceDisplay = $maxSizeRow['sale_price'];
                        $mrpDisplay = $maxSizeRow['mrp'];
                    } elseif ($activeVariant) {
                        $salePriceDisplay = $activeVariant->sale_price;
                        $mrpDisplay = $activeVariant->product->mrp ?? $product->mrp;
                    } else {
                        $salePriceDisplay = $product->sale_price;
                        $mrpDisplay = $product->mrp;
                    }
                @endphp
                @php
                    $detailDiscountPercent = 0;
                    if ($mrpDisplay && $mrpDisplay > $salePriceDisplay && $salePriceDisplay > 0) {
                        $detailDiscountPercent = (int) round((($mrpDisplay - $salePriceDisplay) / $mrpDisplay) * 100);
                    }
                @endphp
                <div class="flex items-center gap-[10px] mt-4 sm:mt-6 flex-wrap">
                    <span id="productSalePrice" class="text-[#B4771E] text-[22px] leading-[24px] sm:text-[30px] font-bold">
                        {{ website_price($salePriceDisplay) }}
                    </span>
                    @if($mrpDisplay && $mrpDisplay > $salePriceDisplay)
                    <span id="productMrp" class="line-through text-[#757575] text-[22px] md:text-2xl leading-[24px]">
                        {{ website_price($mrpDisplay) }}
                    </span>
                    <span id="productDiscountBadge" class="bg-[#EF1B1B] text-white text-xs sm:text-sm font-semibold px-2 py-0.5 rounded {{ $detailDiscountPercent > 0 ? '' : 'hidden' }}">
                        {{ $detailDiscountPercent }}% OFF
                    </span>
                    @endif
                </div>

                <p class="text-[#3D403F] mt-4 md:mt-5 text-base sm:text-xl">
                    Inclusive of all taxes
                </p>                
                
                @if($product->pair_product && !empty($product->custom_sizes))
                @php
                    $maxSizeRow = collect($product->custom_sizes ?? [])->sortBy(fn($s) => (float)($s['size'] ?? 0))->last();
                    $defaultSize = $maxSizeRow['size'] ?? ($product->custom_sizes[0]['size'] ?? '');
                @endphp
                <div class="flex items-center gap-4 mt-4">
                    <span class="text-[#131615] text-base md:text-xl sm:text-[22px] leading-[22px]">Pair:</span>
                    <div id="customSizeToggle" class="flex flex-wrap gap-2">
                        @foreach($product->custom_sizes ?? [] as $index => $sizeRow)
                        <button type="button"
                            data-value="{{ $sizeRow['size'] }}"
                            data-price="{{ $sizeRow['sale_price'] }}"
                            data-mrp="{{ $sizeRow['mrp'] }}"
                            class="custom-size-btn px-4 py-1 text-sm font-semibold border"
                            style="{{ (float)$sizeRow['size'] === (float)$defaultSize ? 'background:#B4771E; color:#fff; border-color:#B4771E;' : 'background:#fff; color:#B4771E; border-color:#B4771E;' }} border-radius:6px; cursor:pointer;">
                            {{ rtrim(rtrim(number_format((float) $sizeRow['size'], 2), '0'), '.') }} pcs
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="selectedPairType" value="single">
                    <input type="hidden" id="selectedCustomSize" value="{{ $defaultSize }}">
                </div>
                @endif

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
                    <div class="mt-4">
                        <h4 class="text-base md:text-lg font-medium mb-2 text-[#131615]">
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
                                data-mrp="{{ $variant->product->mrp ?? '' }}"
                                data-stock="{{ $product->totalAvailableStock($variant->id) }}"
                                @if($product->pair_product)
                                data-custom-sizes="{{ json_encode($variant->custom_sizes ?? []) }}"
                                @endif>
                                {{ $variant->attributeValue->value }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @endif

                <div class="flex gap-3 mt-6">
                    @php $detailStock = $product->totalAvailableStock(); @endphp
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
                $authorName = $review->customer->name;
                $authorAvatar = $review->customer->avatar
                    ? asset($review->customer->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($authorName) . '&background=B4771E&color=fff&size=120&bold=true';
            @endphp
            <div class="border border-[#D5D5D5] p-4 lg:p-5">
                <h4 class="text-[#131615] text-lg md:text-xl font-medium">{{ $review->created_at->format('l, F j, Y') }}</h4>
                @if($review->comment)
                <p class="mt-[14px] md:mt-[17px] text-[#3D403F] text-base md:text-lg">{{ $review->comment }}</p>
                @endif
                @if($review->images->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($review->images as $reviewImage)
                        <img src="{{ $reviewImage->image_url }}" alt="Review photo" class="w-[100px] h-[100px] object-cover rounded-sm border border-[#D5D5D5]">
                    @endforeach
                </div>
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

        <div class="product-list-toggle-wrapper" data-product-view-toggle>
            <div class="mb-3 flex justify-end sm:hidden">
                <div class="inline-flex border border-[#D5D5D5] rounded-md overflow-hidden bg-white shadow-sm">
                    <button type="button" data-grid-view="single" class="w-8 h-8 flex items-center justify-center border-r border-[#D5D5D5] bg-white text-[#131615]" aria-label="Single column view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1.5"></rect></svg>
                    </button>
                    <button type="button" data-grid-view="dual" class="w-8 h-8 flex items-center justify-center bg-[#131615] text-white" aria-label="Two column view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="4" rx="1"></rect><rect x="14" y="11" width="7" height="10" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                    </button>
                </div>
            </div>
            <div data-product-grid class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-4 gap-2 sm:gap-5">
                @include('website.partials.product-grid-items', ['products' => $relatedProducts])
            </div>
        </div>

    </div>

</section>

@endsection

@section('page-js')

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
/**
 * Format a price for display — shows decimals only when needed.
 * Mirrors PHP website_price() helper.
 * e.g. 412.5 → "₹412.5", 413.0 → "₹413"
 */
function fmtPrice(amount) {
    amount = parseFloat(amount) || 0;
    // Determine if decimal is needed
    var hasDec = (amount % 1 !== 0);
    var str;
    if (hasDec) {
        // Format to 2 decimals, then trim trailing zeros
        str = amount.toFixed(2).replace(/\.?0+$/, '');
    } else {
        str = Math.round(amount).toString();
    }
    // Apply Indian number format to integer part
    var parts = str.split('.');
    var intPart = parts[0];
    var decPart = parts[1] ? '.' + parts[1] : '';
    // Indian format: last 3 digits, then groups of 2
    if (intPart.length > 3) {
        var lastThree = intPart.slice(-3);
        var rest = intPart.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
        intPart = rest + ',' + lastThree;
    }
    return '₹' + intPart + decPart;
}
</script>

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

function getMaxAllowedStock() {
    const activeVariant = document.querySelector('.variant-selector.active');
    if (activeVariant) {
        let stock = parseInt(activeVariant.dataset.stock) || 0;
        const selectedCustomSize = document.getElementById('selectedCustomSize');
        if (selectedCustomSize && parseFloat(selectedCustomSize.value) > 0) {
            return Math.floor(stock / parseFloat(selectedCustomSize.value));
        }
        const selectedPairType = document.getElementById('selectedPairType');
        if (selectedPairType && selectedPairType.value === 'pair') {
            return Math.floor(stock / 2);
        }
        return stock;
    }
    let stock = parseInt('{{ $product->totalAvailableStock() }}') || 0;
    const selectedCustomSize = document.getElementById('selectedCustomSize');
    if (selectedCustomSize && parseFloat(selectedCustomSize.value) > 0) {
        return Math.floor(stock / parseFloat(selectedCustomSize.value));
    }
    const selectedPairType = document.getElementById('selectedPairType');
    if (selectedPairType && selectedPairType.value === 'pair') {
        return Math.floor(stock / 2);
    }
    return stock;
}

function updateSoldOutState() {
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');
    if (!addToCartBtn || !buyNowBtn) return;

    const maxStock = getMaxAllowedStock();

    if (maxStock <= 0) {
        addToCartBtn.disabled = true;
        addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
        addToCartBtn.textContent = 'Sold Out';

        buyNowBtn.disabled = true;
        buyNowBtn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
    } else {
        addToCartBtn.disabled = false;
        addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
        addToCartBtn.textContent = 'Add To Cart';

        buyNowBtn.disabled = false;
        buyNowBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
    }
}

window.getMaxAllowedStock = getMaxAllowedStock;
window.updateSoldOutState = updateSoldOutState;
window.getQtyCount = () => count;
window.setQtyCount = (val) => {
    count = val;
    if (qty) qty.innerText = count;
};

// Initialize count and button states based on initial stock
const initialMaxStock = getMaxAllowedStock();
if (initialMaxStock <= 0) {
    count = 0;
    if (qty) qty.innerText = count;
}
updateSoldOutState();

if (plusBtn) {
    plusBtn.addEventListener("click", () => {
        const maxStock = getMaxAllowedStock();
        if (count < maxStock) {
            count++;
            if (qty) qty.innerText = count;
        }
    });
}
if (minusBtn) {
    minusBtn.addEventListener("click", () => {
        const maxStock = getMaxAllowedStock();
        const minQty = maxStock > 0 ? 1 : 0;
        if (count > minQty) {
            count--;
            if (qty) qty.innerText = count;
        }
    });
}
</script>

<script>
window.productCustomSizes = @json($product->pair_product ? ($product->custom_sizes ?? []) : []);

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
            document.querySelectorAll('.variant-selector').forEach(function (s) {
                s.classList.remove('bg-[#B4771E]', 'text-white', 'border-[#B4771E]', 'active');
                s.classList.add('border-[#D5D5D5]', 'text-[#131615]');
            });
            btn.classList.add('bg-[#B4771E]', 'text-white', 'border-[#B4771E]', 'active');
            btn.classList.remove('border-[#D5D5D5]', 'text-[#131615]');

            var sizeToggleEl = document.getElementById('customSizeToggle');
            var selectedCustomSizeInput = document.getElementById('selectedCustomSize');
            if (sizeToggleEl && selectedCustomSizeInput) {
                var rawSizes = btn.dataset.customSizes;
                var variantSizes = [];
                try { variantSizes = rawSizes ? JSON.parse(rawSizes) : []; } catch (e) { variantSizes = []; }
                var effectiveSizes = variantSizes.length ? variantSizes : (window.productCustomSizes || []);
                if (effectiveSizes.length) {
                    var currentSize = parseFloat(selectedCustomSizeInput.value);
                    var stillValid = effectiveSizes.some(function (s) { return s.size == currentSize; });
                    var sortedSizes = effectiveSizes.slice().sort(function(a, b){ return parseFloat(a.size || 0) - parseFloat(b.size || 0); });
                    var maxSize = sortedSizes.length ? sortedSizes[sortedSizes.length - 1].size : '';
                    var defSize = stillValid ? currentSize : maxSize;
                    var html = '';
                    effectiveSizes.forEach(function (s) {
                        var isActive = s.size == defSize;
                        var style = isActive
                            ? 'background:#B4771E; color:#fff; border-color:#B4771E; border-radius:6px; cursor:pointer;'
                            : 'background:#fff; color:#B4771E; border-color:#B4771E; border-radius:6px; cursor:pointer;';
                        var label = (String(s.size).replace(/\.?0+$/, '')) + ' pcs';
                        html += '<button type="button" data-value="' + s.size + '" data-price="' + s.sale_price + '" data-mrp="' + s.mrp + '" class="custom-size-btn px-4 py-1 text-sm font-semibold border" style="' + style + '">' + label + '</button>';
                    });
                    sizeToggleEl.innerHTML = html;
                    selectedCustomSizeInput.value = defSize;
                }
            }

            // Price update — a pack-size tier price always wins over the variant's flat price.
            var priceSpan = document.getElementById('productSalePrice');
            var mrpSpan = document.getElementById('productMrp');
            var activeSizeBtn = null;
            if (selectedCustomSizeInput && selectedCustomSizeInput.value) {
                var sizeToggle = document.getElementById('customSizeToggle');
                if (sizeToggle) {
                    activeSizeBtn = sizeToggle.querySelector('.custom-size-btn[data-value="' + selectedCustomSizeInput.value + '"]');
                }
            }
            if (activeSizeBtn) {
                var sizePrice = parseFloat(activeSizeBtn.dataset.price) || 0;
                var sizeMrp = parseFloat(activeSizeBtn.dataset.mrp) || 0;
                if (priceSpan) priceSpan.textContent = fmtPrice(sizePrice);
                if (mrpSpan) {
                    if (sizeMrp && sizeMrp > sizePrice) {
                        mrpSpan.textContent = fmtPrice(sizeMrp);
                        mrpSpan.style.display = '';
                    } else {
                        mrpSpan.style.display = 'none';
                    }
                }
            } else if (priceSpan && btn.dataset.salePrice) {
                priceSpan.textContent = fmtPrice(parseFloat(btn.dataset.salePrice));
            }

            // Check if current count exceeds new variant's stock
            if (typeof window.getMaxAllowedStock === 'function') {
                const maxStock = window.getMaxAllowedStock();
                if (maxStock <= 0) {
                    window.setQtyCount(0);
                } else {
                    const currentCount = window.getQtyCount();
                    if (currentCount <= 0) {
                        window.setQtyCount(1);
                    } else if (currentCount > maxStock) {
                        window.setQtyCount(maxStock);
                    }
                }
            }
            if (typeof window.updateSoldOutState === 'function') {
                window.updateSoldOutState();
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
// ─── Detail page pair type toggle ────────────────────────────────────────────
(function () {
    var toggle = document.getElementById('pairTypeToggle');
    if (!toggle) return;

    toggle.addEventListener('click', function (e) {
        var btn = e.target.closest('.pair-type-btn');
        if (!btn) return;

        var value    = btn.dataset.value;
        var price    = parseFloat(btn.dataset.price) || 0;
        var mrp      = parseFloat(btn.dataset.mrp) || 0;

        // Update hidden input
        document.getElementById('selectedPairType').value = value;

        // Check if current count exceeds stock
        if (typeof window.getMaxAllowedStock === 'function') {
            const maxStock = window.getMaxAllowedStock();
            if (maxStock <= 0) {
                window.setQtyCount(0);
            } else {
                const currentCount = window.getQtyCount();
                if (currentCount <= 0) {
                    window.setQtyCount(1);
                } else if (currentCount > maxStock) {
                    window.setQtyCount(maxStock);
                }
            }
        }
        if (typeof window.updateSoldOutState === 'function') {
            window.updateSoldOutState();
        }

        // Update button styles
        toggle.querySelectorAll('.pair-type-btn').forEach(function (b) {
            if (b.dataset.value === value) {
                b.style.background = '#B4771E';
                b.style.color      = '#fff';
            } else {
                b.style.background = '#fff';
                b.style.color      = '#B4771E';
            }
        });

        // Update displayed price
        var priceEl = document.getElementById('productSalePrice');
        var mrpEl   = document.getElementById('productMrp');
        if (priceEl) priceEl.textContent = fmtPrice(price);
        if (mrpEl) {
            if (mrp && mrp > price) {
                mrpEl.textContent = fmtPrice(mrp);
                mrpEl.style.display = '';
            } else {
                mrpEl.style.display = 'none';
            }
        }
    });
}());
</script>

<script>
// ─── Detail page custom size toggle ──────────────────────────────────────────
(function () {
    var toggle = document.getElementById('customSizeToggle');
    if (!toggle) return;

    toggle.addEventListener('click', function (e) {
        var btn = e.target.closest('.custom-size-btn');
        if (!btn) return;

        var value = btn.dataset.value;
        var price = parseFloat(btn.dataset.price) || 0;
        var mrp   = parseFloat(btn.dataset.mrp) || 0;

        // Update hidden input
        document.getElementById('selectedCustomSize').value = value;

        // Check if current count exceeds stock
        if (typeof window.getMaxAllowedStock === 'function') {
            const maxStock = window.getMaxAllowedStock();
            if (maxStock <= 0) {
                window.setQtyCount(0);
            } else {
                const currentCount = window.getQtyCount();
                if (currentCount <= 0) {
                    window.setQtyCount(1);
                } else if (currentCount > maxStock) {
                    window.setQtyCount(maxStock);
                }
            }
        }
        if (typeof window.updateSoldOutState === 'function') {
            window.updateSoldOutState();
        }

        // Update button styles
        toggle.querySelectorAll('.custom-size-btn').forEach(function (b) {
            if (b.dataset.value === value) {
                b.style.background = '#B4771E';
                b.style.color      = '#fff';
            } else {
                b.style.background = '#fff';
                b.style.color      = '#B4771E';
            }
        });

        // Update displayed price
        var priceEl = document.getElementById('productSalePrice');
        var mrpEl   = document.getElementById('productMrp');
        if (priceEl) priceEl.textContent = fmtPrice(price);
        if (mrpEl) {
            if (mrp && mrp > price) {
                mrpEl.textContent = fmtPrice(mrp);
                mrpEl.style.display = '';
            } else {
                mrpEl.style.display = 'none';
            }
        }
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
        var pairTypeInput = document.getElementById('selectedPairType');
        var pairType = pairTypeInput ? pairTypeInput.value : 'single';
        var customSizeInput = document.getElementById('selectedCustomSize');
        var customSizeValue = customSizeInput ? customSizeInput.value : null;

        window.addToCart(productId, variantId, qty, addBtn, loginUrl, pairType, customSizeValue);
    });
}());
</script>

{{-- ══ BUY NOW — Address Modal ══ --}}
@php
    $states = \App\Models\State::where('status', \App\Models\State::STATUS_ACTIVE)->orderBy('name')->get();
@endphp
<div id="buyNowAddressModal" class="fixed inset-0 z-[9999] hidden bg-black/50 overflow-y-auto p-4">
    <div class="min-h-full flex items-center justify-center py-5">
        <div class="relative w-full max-w-[620px] bg-white rounded-[8px] p-5 md:p-7 border border-[#D5D5D5] overflow-visible shadow-xl">
            <button onclick="closeBuyNowAddressModal()" class="absolute top-4 right-4 text-[32px] leading-none text-[#131615] hover:text-[#B4771E] transition">&times;</button>
            
            <h2 class="text-xl md:text-[22px] font-semibold text-[#131615] mb-4 pb-3 border-b border-[#E5E7EB]">Direct Checkout</h2>

            <!-- 2. Coupon Code Section -->
            <div class="mb-5 bg-white border border-[#D5D5D5] p-4 rounded-md">
                <label class="block text-xs font-semibold text-[#131615] uppercase tracking-wider mb-2">Have a Coupon Code?</label>
                <div id="buyNowCouponForm" class="flex gap-2 min-w-0">
                    <input type="text" id="buyNowCouponInput" placeholder="Enter coupon code" class="flex-1 min-w-0 border border-[#D5D5D5] rounded px-3 py-2 text-sm uppercase outline-none focus:border-[#B4771E]">
                    <button type="button" id="buyNowApplyCouponBtn" onclick="applyBuyNowCoupon()" class="bg-[#B4771E] hover:bg-[#9d6719] text-white text-xs font-semibold px-4 py-2 rounded transition shrink-0">Apply</button>
                </div>
                <div id="buyNowCouponMsg" class="mt-2 text-xs hidden"></div>
                <div id="buyNowCouponAppliedTag" class="hidden mt-2 p-2.5 bg-[#F0FDF4] border border-[#BBF7D0] text-[#166534] text-xs rounded flex items-center justify-between">
                    <span class="font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span id="buyNowCouponAppliedText"></span>
                    </span>
                    <button type="button" onclick="removeBuyNowCoupon()" class="text-red-600 hover:underline font-semibold text-xs ml-2">Remove</button>
                </div>
            </div>

            <!-- 3. Delivery Address Selection -->
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xs font-semibold text-[#131615] uppercase tracking-wider">Select Delivery Address</h3>
                <button class="bg-[#B4771E] hover:bg-[#b67d1f] text-white text-xs font-medium px-3 py-1.5 transition flex gap-1.5 items-center rounded-sm" onclick="openAddAddressModal()">
                    <svg width="12" height="12" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 1.16663V12.8333M1.16663 7H12.8333" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    New Address
                </button>
            </div>

            @php
                $addressesCount = auth('customer')->check() ? \App\Models\CustomerAddress::where('customer_id', auth('customer')->id())->count() : 0;
            @endphp

            <div id="buyNowAddressList" class="space-y-3 mb-5 max-h-[220px] overflow-y-auto pr-1">
                @if($addressesCount === 0)
                <p class="text-[#757575] text-base mb-5" id="noAddressesPlaceholder">No saved addresses. Please click "New Address" to add one.</p>
                @else
                @if(auth('customer')->check())
                @foreach(\App\Models\CustomerAddress::where('customer_id', auth('customer')->id())->orderByDesc('is_default')->get() as $addr)
                <div class="address-card border border-[#D5D5D5] p-4 rounded relative hover:border-[#B4771E] has-[:checked]:border-[#B4771E] has-[:checked]:bg-[#B4771E0A]" data-address-id="{{ $addr->id }}" data-state="{{ $addr->state }}">
                    <div class="flex justify-between items-start">
                        <label class="flex-1 flex items-start gap-3 cursor-pointer min-w-0">
                            <input type="radio" name="buynow_address" value="{{ $addr->id }}" class="mt-1 accent-[#B4771E] address-radio" {{ $addr->is_default ? 'checked' : '' }}>
                            <div class="min-w-0">
                                <p class="font-semibold text-[#131615] text-base customer-name-phone">{{ $addr->name }} — {{ $addr->phone }}</p>
                                <p class="text-[#757575] text-sm mt-1 address-text">{{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }}{{ $addr->pincode ? ' - ' . $addr->pincode : '' }}</p>
                                @if($addr->is_default)
                                <span class="inline-block mt-1 bg-[#B4771E29] text-[#B4771E] text-xs px-2 py-0.5 default-badge">Default</span>
                                @endif
                            </div>
                        </label>
                        {{-- Dropdown options --}}
                        <div class="relative address-menu-container">
                            <button class="w-6 h-6 flex justify-center items-center address-menu-btn p-2 hover:bg-black/5 rounded-full transition focus:outline-none" onclick="toggleAddressDropdown({{ $addr->id }}, event)">
                                <i class="fa-solid fa-ellipsis-vertical text-[#3D403F]"></i>
                            </button>
                            <div id="dropdown-{{ $addr->id }}" class="absolute right-0 top-full mt-2 w-[150px] bg-white border border-[#D5D5D5] rounded-[8px] shadow-[0_4px_20px_rgba(0,0,0,0.12)] overflow-hidden z-20 hidden address-dropdown">
                                <button onclick="editAddress({{ $addr->id }}, event)" class="w-full flex items-center gap-3 p-3 text-sm text-[#4A4A4A] hover:bg-[#FAFAFA] transition">
                                    <svg width="14" height="14" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.1767 2.48937L13.5825 1.08271C13.8756 0.789642 14.273 0.625 14.6875 0.625C15.102 0.625 15.4994 0.789642 15.7925 1.08271C16.0856 1.37577 16.2502 1.77325 16.2502 2.18771C16.2502 2.60216 16.0856 2.99964 15.7925 3.29271L6.94333 12.1419C6.50277 12.5822 5.95947 12.9058 5.3625 13.0835L3.125 13.7502L3.79167 11.5127C3.9694 10.9157 4.29303 10.3724 4.73333 9.93187L12.1767 2.48937ZM12.1767 2.48937L14.375 4.68771M13.125 10.4169V14.3752C13.125 14.8725 12.9275 15.3494 12.5758 15.701C12.2242 16.0527 11.7473 16.2502 11.25 16.2502H2.5C2.00272 16.2502 1.52581 16.0527 1.17417 15.701C0.822544 15.3494 0.625 14.8725 0.625 14.3752V5.62521C0.625 5.12793 0.822544 4.65101 1.17417 4.29938C1.52581 3.94775 2.00272 3.75021 2.5 3.75021H6.45833" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Edit
                                </button>
                                <div class="mx-3 border-t border-[#D5D5D5]"></div>
                                <button onclick="deleteAddress({{ $addr->id }}, event)" class="w-full flex items-center gap-3 p-3 text-sm text-red-600 hover:bg-[#FFF7F7] transition">
                                    <svg width="13" height="15" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.78345 6.25043L9.49512 13.7504M5.50512 13.7504L5.21679 6.25043M10.6251 3.2446C11.5949 3.31968 12.5617 3.43003 13.5235 3.57543C13.8085 3.61877 14.0918 3.6646 14.3751 3.71377M13.5235 3.57543L12.6335 15.1446C12.5971 15.6156 12.3843 16.0556 12.0376 16.3765C11.6909 16.6974 11.2359 16.8756 10.7635 16.8754H4.23679C3.76437 16.8756 3.30931 16.6974 2.9626 16.3765C2.6159 16.0556 2.40311 15.6156 2.36679 15.1446L1.47679 3.57543M1.47679 3.57543C1.19179 3.61793 0.908455 3.66377 0.625122 3.71293M1.47679 3.57543C2.43857 3.43003 3.40532 3.31968 4.37512 3.2446M10.6251 3.2446V2.48127C10.6251 1.49793 9.86679 0.677934 8.88346 0.647101C7.96147 0.617633 7.03878 0.617633 6.11679 0.647101C5.13346 0.677934 4.37512 1.49877 4.37512 2.48127V3.2446M10.6251 3.2446C8.54489 3.08383 6.45535 3.08383 4.37512 3.2446" stroke="#ea5455" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Remove
                                </button>
                                @if(!$addr->is_default)
                                <div class="mx-3 border-t border-[#D5D5D5]"></div>
                                <button onclick="setAddressAsDefault({{ $addr->id }}, event)" class="w-full flex items-center gap-3 p-3 text-sm text-[#4A4A4A] hover:bg-[#FAFAFA] transition">
                                    <svg width="14" height="14" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.625 8.75L7.5 10.625L10.625 6.25M15.625 8.125C15.625 9.10991 15.431 10.0852 15.0541 10.9951C14.6772 11.9051 14.1247 12.7319 13.4283 13.4283C12.7319 14.1247 11.9051 14.6772 10.9951 15.0541C10.0852 15.431 9.10991 15.625 8.125 15.625C7.14009 15.625 6.16482 15.431 5.25487 15.0541C4.34493 14.6772 3.51814 14.1247 2.8217 13.4283C2.12526 12.7319 1.57281 11.9051 1.1959 10.9951C0.818993 10.0852 0.625 9.10991 0.625 8.125C0.625 6.13588 1.41518 4.22822 2.8217 2.8217C4.22822 1.41518 6.13588 0.625 8.125 0.625C10.1141 0.625 12.0218 1.41518 13.4283 2.8217C14.8348 4.22822 15.625 6.13588 15.625 8.125Z" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Set Default
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
                @endif
            </div>

            <!-- 4. Price Breakdown Summary -->
            <div class="border-t border-b border-[#E5E7EB] py-3 mb-5 space-y-1.5 text-sm">
                <div class="flex justify-between text-[#757575]">
                    <span>Item Subtotal</span>
                    <span id="buyNowSubtotalTxt" class="font-medium text-[#131615]">₹0.00</span>
                </div>
                <div id="buyNowDiscountRow" class="flex justify-between text-green-600 hidden">
                    <span>Coupon Discount</span>
                    <span id="buyNowDiscountTxt" class="font-medium">-₹0.00</span>
                </div>
                <div class="flex justify-between text-[#757575]">
                    <span>Shipping Charge</span>
                    <span id="buyNowShippingTxt" class="font-medium text-[#131615]">Calculated at checkout</span>
                </div>
                <div class="flex justify-between text-base font-bold text-[#131615] pt-2 border-t border-[#E5E7EB]">
                    <span>Total Amount</span>
                    <span id="buyNowTotalTxt" class="text-[#B4771E]">₹0.00</span>
                </div>
            </div>
            
            <button onclick="startBuyNowPayment()" id="buyNowProceedBtn"
                class="w-full h-[50px] bg-[#B4771E] hover:bg-[#9d6719] text-white text-base font-semibold transition rounded-sm flex items-center justify-center gap-2 {{ $addressesCount === 0 ? 'hidden' : '' }}">
                Proceed to Pay
            </button>
        </div>
    </div>
</div>

{{-- ══ ADDRESS DETAILS MODAL (Add/Edit) ══ --}}
<div id="addressModal"
   class="fixed inset-0 z-[9999] hidden bg-black/50 overflow-hidden p-4 !mt-0">
    <div class="min-h-full flex items-center justify-center !mt-0 py-5">
        <div class="relative w-full max-w-[750px] bg-white rounded-[8px] p-4 sm:p-5 max-h-[90vh] border border-[#D5D5D5] overflow-y-auto scrollbar-hide">
            <button onclick="closeModal()" class="absolute top-4 right-4 md:top-6 md:right-6 text-[35px] leading-none text-[#131615]">&times;</button>
            
            <h2 id="addrModalTitle" class="text-xl lg:text-[22px] lg:leading-[24px] font-medium text-[#131615] mb-4">
                Deliver To
            </h2>

            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="addr_name" name="name" placeholder="Enter Your Full Name" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="name"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Mobile Number <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_phone" name="phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="phone"></p>
                </div>
                <div>
                     <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Alternate Phone Number
                    </label>
                    <input type="text" id="addr_alternate_phone" name="alternate_phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="alternate_phone"></p>
                </div>
            </div>

            <div class="mb-4">
                 <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Email address
                </label>
                <input type="email" id="addr_email" name="email" placeholder="Enter Email address" value="{{ auth('customer')->user()->email ?? '' }}" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="email"></p>
            </div>

            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Flat/House/Building Name <span class="text-red-600">*</span>
                </label>
                <textarea id="addr_address" name="address" rows="3" placeholder="Enter Flat/House/Building Name" class="addr-input w-full text-[#757575] text-base min-h-28 placeholder:text-base border border-[#D5D5D5] px-4 outline-none py-3 focus:border-[#B4771E] resize-y rounded-sm"></textarea>
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="address"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Town / City <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_city" name="city" placeholder="Town / City" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="city"></p>
                </div>
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        State <span class="text-red-600">*</span>
                    </label>
                    <select id="addr_state" name="state" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                        <option value="">Select an Option...</option>
                        @foreach($states as $stateOption)
                            <option value="{{ $stateOption->name }}">{{ $stateOption->name }}</option>
                        @endforeach
                    </select>
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="state"></p>
                </div>
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Pincode <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_pincode" name="pincode" placeholder="Pincode" maxlength="6" inputmode="numeric" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="pincode"></p>
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Type Of Address
                </label>
                <div class="flex flex-wrap gap-4">
                    <input type="radio" name="addressType" id="home" value="home" class="hidden peer/home" checked>
                    <label for="home" class="cursor-pointer py-[6px] px-4 border border-[#D5D5D5] rounded flex items-center gap-[8px] text-base text-[#131615] peer-checked/home:bg-[#B4771E1A] peer-checked/home:border-[#B4771E1A] peer-checked/home:text-[#B4771E] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Home
                    </label>

                    <input type="radio" name="addressType" id="work" value="work" class="hidden peer/work">
                    <label for="work" class="cursor-pointer py-[6px] px-4 border border-[#D5D5D5] rounded flex items-center gap-[8px] text-base text-[#131615] peer-checked/work:bg-[#B4771E1A] peer-checked/work:border-[#B4771E1A] peer-checked/work:text-[#B4771E] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                        Work
                    </label>
                </div>
            </div>

            <div class="mb-5 flex items-center gap-[10px]">
                <div class="relative flex items-center justify-center w-[22px] h-[22px] shrink-0 rounded-[5px] border-2 border-[#B4771E] bg-white transition-colors duration-200">
                    <input type="checkbox" id="addr_is_default" name="is_default" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10 peer">
                    <svg class="w-[13px] h-[13px] text-[#B4771E] opacity-0 peer-checked:opacity-100 transition-opacity duration-200" viewBox="0 0 12 10" fill="none">
                        <path d="M1 5L4.5 8.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <label for="addr_is_default" class="text-base text-[#3D403F] cursor-pointer select-none">Set as default address</label>
            </div>
            
            <div id="addressSuccess" class="hidden mb-5 border border-green-200 bg-green-50 px-4 py-3 text-green-700"></div>
            <div id="addressFailure" class="hidden mb-5 border border-red-200 bg-red-50 px-4 py-3 text-red-700"></div>
            
            <button id="saveAddressBtn" onclick="saveCustomerAddress(event)" class="common-btn w-full h-[52px] rounded-sm">
                Save Address
            </button>
        </div>
    </div>
</div>

{{-- ══ BUY NOW — Payment Loader ══ --}}
<div id="buyNowLoader" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 backdrop-blur-sm !mt-0">
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
<div id="buyNowSuccessModal" class="fixed inset-0 z-[9999] hidden bg-black/50 overflow-y-auto p-4 !mt-0">
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
                text-base md:text-lg mt-7 common-btn">
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
<div id="buyNowFailureModal" class="fixed inset-0 z-[9999] hidden bg-black/50 overflow-y-auto p-4 !mt-0">
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

    var currentBuyNowCouponCode = null;
    var currentBuyNowCouponDiscount = 0;

    window.applyBuyNowCoupon = function() {
        var input = document.getElementById('buyNowCouponInput');
        var msg = document.getElementById('buyNowCouponMsg');
        var code = (input.value || '').trim();

        if (!code) {
            msg.textContent = 'Please enter a coupon code.';
            msg.className = 'mt-2 text-xs text-red-600';
            msg.classList.remove('hidden');
            return;
        }

        var price = getBuyNowUnitPrice();
        var qty = parseInt(document.getElementById('qty')?.innerText || 1) || 1;
        var subtotal = price * qty;

        var btn = document.getElementById('buyNowApplyCouponBtn');
        btn.disabled = true;
        btn.textContent = '...';

        fetch('{{ route('buynow.coupon.validate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                subtotal: subtotal
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Apply';

            if (data.status === 'success') {
                currentBuyNowCouponCode = data.coupon.code;
                currentBuyNowCouponDiscount = data.coupon.discount_amount;

                msg.classList.add('hidden');
                document.getElementById('buyNowCouponForm').classList.add('hidden');
                document.getElementById('buyNowCouponAppliedTag').classList.remove('hidden');
                document.getElementById('buyNowCouponAppliedText').textContent = 'Coupon (' + data.coupon.code + ') Applied: -₹' + data.coupon.discount_amount.toFixed(2);

                updateBuyNowCalculations();
            } else {
                msg.textContent = data.message || 'Invalid coupon code.';
                msg.className = 'mt-2 text-xs text-red-600';
                msg.classList.remove('hidden');
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.textContent = 'Apply';
            msg.textContent = err.message || 'Could not validate coupon.';
            msg.className = 'mt-2 text-xs text-red-600';
            msg.classList.remove('hidden');
        });
    };

    window.removeBuyNowCoupon = function() {
        currentBuyNowCouponCode = null;
        currentBuyNowCouponDiscount = 0;

        document.getElementById('buyNowCouponInput').value = '';
        document.getElementById('buyNowCouponMsg').classList.add('hidden');
        document.getElementById('buyNowCouponForm').classList.remove('hidden');
        document.getElementById('buyNowCouponAppliedTag').classList.add('hidden');

        updateBuyNowCalculations();
    };

    @php
        $shippingMap = [];
        foreach (\App\Models\State::where('status', \App\Models\State::STATUS_ACTIVE)->get() as $st) {
            $shippingMap[$st->name] = (float) $st->shipping_charge;
        }
    @endphp
    var stateShippingCharges = @json($shippingMap);

    function getSelectedAddressState() {
        var selectedAddrRadio = document.querySelector('input[name="buynow_address"]:checked');
        if (!selectedAddrRadio) return null;
        var card = selectedAddrRadio.closest('.address-card');
        return card ? (card.dataset.state || null) : null;
    }

    function getBuyNowUnitPrice() {
        var activeVariantBtn = document.querySelector('.variant-selector.active');
        if (activeVariantBtn && activeVariantBtn.dataset.salePrice) {
            return parseFloat(activeVariantBtn.dataset.salePrice) || 0;
        }
        var activeSizeBtn = document.querySelector('.custom-size-btn[style*="background:#B4771E"]');
        if (activeSizeBtn && activeSizeBtn.dataset.price) {
            return parseFloat(activeSizeBtn.dataset.price) || 0;
        }
        var priceEl = document.getElementById('productSalePrice');
        if (priceEl) {
            var rawText = priceEl.innerText.replace(/[^0-9.]/g, '');
            return parseFloat(rawText) || 0;
        }
        return 0;
    }

    function updateBuyNowCalculations() {
        var price = getBuyNowUnitPrice();
        var qty = parseInt(document.getElementById('qty')?.innerText || 1) || 1;
        var subtotal = price * qty;

        if (document.getElementById('buyNowSubtotalTxt')) document.getElementById('buyNowSubtotalTxt').textContent = '₹' + subtotal.toFixed(2);
        if (document.getElementById('buyNowUnitPrice')) document.getElementById('buyNowUnitPrice').textContent = '₹' + price.toFixed(2);
        if (document.getElementById('buyNowQtyBadge')) document.getElementById('buyNowQtyBadge').textContent = 'Qty: ' + qty;

        var variantDetailText = '';
        var activeVariantBtn = document.querySelector('.variant-selector.active');
        if (activeVariantBtn) {
            variantDetailText = activeVariantBtn.innerText.trim();
        }
        var pairType = document.getElementById('selectedPairType')?.value;
        var customSize = document.getElementById('selectedCustomSize')?.value;
        if (customSize) {
            variantDetailText += (variantDetailText ? ' | ' : '') + customSize + ' pcs set';
        } else if (pairType === 'pair') {
            variantDetailText += (variantDetailText ? ' | ' : '') + 'Pair';
        }
        if (document.getElementById('buyNowVariantDetail')) document.getElementById('buyNowVariantDetail').textContent = variantDetailText || 'Standard Item';

        if (currentBuyNowCouponDiscount > 0) {
            document.getElementById('buyNowDiscountRow').classList.remove('hidden');
            document.getElementById('buyNowDiscountTxt').textContent = '-₹' + currentBuyNowCouponDiscount.toFixed(2);
        } else {
            document.getElementById('buyNowDiscountRow').classList.add('hidden');
        }

        var shipping = 0;
        if (subtotal < 1999 && !(currentBuyNowCouponCode === 'FREESHIP' && subtotal >= 2000)) {
            var state = getSelectedAddressState();
            if (state && typeof stateShippingCharges !== 'undefined' && stateShippingCharges[state]) {
                shipping = parseFloat(stateShippingCharges[state]) || 0;
            }
        }

        if (shipping > 0) {
            document.getElementById('buyNowShippingTxt').textContent = '₹' + shipping.toFixed(2);
            document.getElementById('buyNowShippingTxt').className = 'font-medium text-[#131615]';
        } else if (subtotal > 0) {
            document.getElementById('buyNowShippingTxt').textContent = 'FREE Shipping';
            document.getElementById('buyNowShippingTxt').className = 'font-semibold text-green-600';
        } else {
            document.getElementById('buyNowShippingTxt').textContent = 'Calculated at checkout';
            document.getElementById('buyNowShippingTxt').className = 'font-medium text-[#131615]';
        }

        var total = Math.max(0, subtotal - currentBuyNowCouponDiscount + shipping);
        document.getElementById('buyNowTotalTxt').textContent = '₹' + total.toFixed(2);
        document.getElementById('buyNowProceedBtn').textContent = 'Proceed to Pay (₹' + total.toFixed(2) + ')';
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'buynow_address') {
            updateBuyNowCalculations();
        }
    });

    window.openBuyNowAddressModal = function () {
        removeBuyNowCoupon();
        updateBuyNowCalculations();
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
        var pairType  = document.getElementById('selectedPairType')?.value || 'single';
        var customSizeValue = document.getElementById('selectedCustomSize')?.value || null;

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
                qty: qty,
                pair_type: pairType,
                custom_size_value: customSizeValue,
                coupon_code: currentBuyNowCouponCode || null
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
                config: {
                    display: {
                        hide: [
                            { method: 'paylater' },
                            { method: 'wallet' }
                        ],
                        preferences: {
                            show_default_blocks: true
                        }
                    }
                },
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

<script>
(function() {
    const checkoutAddresses = [
        @if(auth('customer')->check())
        @foreach(\App\Models\CustomerAddress::where('customer_id', auth('customer')->id())->get() as $addr)
        {
            id: {{ $addr->id }},
            name: @json($addr->name),
            phone: @json($addr->phone),
            alternate_phone: @json($addr->alternate_phone ?? ''),
            email: @json($addr->email ?? ''),
            address: @json($addr->address),
            city: @json($addr->city),
            state: @json($addr->state),
            pincode: @json($addr->pincode ?? ''),
            type: @json($addr->type),
            is_default: {{ $addr->is_default ? 'true' : 'false' }}
        },
        @endforeach
        @endif
    ];

    const activeStates = @json($states->pluck('name'));
    let editingAddressId = null;

    window.toggleAddressDropdown = function(addressId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        const dropdown = document.getElementById('dropdown-' + addressId);
        const wasHidden = dropdown.classList.contains('hidden');
        document.querySelectorAll('.address-dropdown').forEach(d => d.classList.add('hidden'));
        if (wasHidden) {
            dropdown.classList.remove('hidden');
        }
    };

    // Close dropdowns on clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.address-dropdown').forEach(dropdown => {
            const btn = dropdown.previousElementSibling;
            if (!dropdown.contains(e.target) && (!btn || !btn.contains(e.target))) {
                dropdown.classList.add('hidden');
            }
        });
    });

    window.openModal = function() {
        document.getElementById('addressModal').classList.remove("hidden");
        document.documentElement.classList.add("modal-open");
        document.body.classList.add("modal-open");
    };

    window.closeModal = function() {
        document.getElementById('addressModal').classList.add("hidden");
        document.documentElement.classList.remove("modal-open");
        document.body.classList.remove("modal-open");
        editingAddressId = null;
        document.getElementById('addrModalTitle').textContent = 'Deliver To';
        document.getElementById('saveAddressBtn').innerText = 'Save Address';
    };

    window.openAddAddressModal = function() {
        editingAddressId = null;
        document.getElementById('addrModalTitle').textContent = 'Deliver To';
        resetAddressForm();
        document.getElementById('saveAddressBtn').innerText = 'Save Address';
        openModal();
    };

    function resetAddressForm() {
        document.getElementById('addr_name').value = '';
        document.getElementById('addr_phone').value = '';
        document.getElementById('addr_alternate_phone').value = '';
        document.getElementById('addr_email').value = '{{ auth('customer')->user()->email ?? '' }}';
        document.getElementById('addr_address').value = '';
        document.getElementById('addr_city').value = '';
        document.getElementById('addr_state').value = '';
        document.getElementById('addr_pincode').value = '';
        document.getElementById('home').checked = true;
        document.getElementById('addr_is_default').checked = false;
        clearAddrErrors();
    }

    window.editAddress = function(addressId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        document.querySelectorAll('.address-dropdown').forEach(dropdown => dropdown.classList.add('hidden'));

        const addr = checkoutAddresses.find(a => a.id === addressId);
        if (!addr) return;

        editingAddressId = addressId;
        document.getElementById('addrModalTitle').textContent = 'Edit Address';
        document.getElementById('addr_name').value = addr.name;
        document.getElementById('addr_phone').value = addr.phone;
        document.getElementById('addr_alternate_phone').value = addr.alternate_phone || '';
        document.getElementById('addr_email').value = addr.email || '';
        document.getElementById('addr_address').value = addr.address;
        document.getElementById('addr_city').value = addr.city;
        document.getElementById('addr_state').value = addr.state;
        document.getElementById('addr_pincode').value = addr.pincode || '';
        document.getElementById('addr_is_default').checked = !!addr.is_default;

        if (addr.type === 'work') {
            document.getElementById('work').checked = true;
        } else {
            document.getElementById('home').checked = true;
        }

        document.getElementById('saveAddressBtn').innerText = 'Update Address';
        clearAddrErrors();
        openModal();
    };

    window.deleteAddress = function(addressId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        document.querySelectorAll('.address-dropdown').forEach(dropdown => dropdown.classList.add('hidden'));

        if (typeof window.showDeleteConfirm === 'function') {
            window.showDeleteConfirm(() => {
                doDeleteAddress(addressId);
            });
        } else {
            if (confirm('Are you sure you want to delete this address?')) {
                doDeleteAddress(addressId);
            }
        }
    };

    function doDeleteAddress(addressId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('{{ route('checkout.address.delete') }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                sessionStorage.setItem('checkout_address_success', data.message || 'Address deleted.');
                sessionStorage.setItem('buynow_open_modal', 'true');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to delete address.');
            }
        })
        .catch(err => console.error(err));
    }

    window.setAddressAsDefault = function(addressId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        document.querySelectorAll('.address-dropdown').forEach(dropdown => dropdown.classList.add('hidden'));

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('{{ route('checkout.address.set-default') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                sessionStorage.setItem('checkout_address_success', data.message || 'Address set as default.');
                sessionStorage.setItem('buynow_open_modal', 'true');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to set default address.');
            }
        })
        .catch(err => console.error(err));
    };

    window.saveCustomerAddress = function(e) {
        e.preventDefault();
        clearAddrErrors();

        if (!validateAddressForm()) {
            return;
        }

        const btn = document.getElementById('saveAddressBtn');
        btn.disabled = true;
        btn.innerText = 'Saving...';

        const name = document.getElementById('addr_name').value.trim();
        const phone = document.getElementById('addr_phone').value.trim();
        const alternate_phone = document.getElementById('addr_alternate_phone').value.trim();
        const email = document.getElementById('addr_email').value.trim();
        const address = document.getElementById('addr_address').value.trim();
        const city = document.getElementById('addr_city').value.trim();
        const state = document.getElementById('addr_state').value;
        const pincode = document.getElementById('addr_pincode').value.trim();
        const type = document.querySelector('input[name="addressType"]:checked').value;
        const is_default = document.getElementById('addr_is_default').checked ? 1 : 0;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const payload = {
            name,
            phone,
            alternate_phone,
            email,
            address,
            city,
            state,
            pincode,
            type,
            is_default
        };

        let url = '{{ route('checkout.address.save') }}';
        let method = 'POST';

        if (editingAddressId) {
            payload.address_id = editingAddressId;
            url = '{{ route('checkout.address.update') }}';
            method = 'PATCH';
        }

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = editingAddressId ? 'Update Address' : 'Save Address';
            if (data.status === 'success') {
                sessionStorage.setItem('checkout_address_success', data.message || 'Address saved.');
                sessionStorage.setItem('buynow_open_modal', 'true');
                window.location.reload();
            } else if (data.status === 'error' && data.message) {
                if (typeof data.message === 'object') {
                    Object.keys(data.message).forEach(field => {
                        setAddrFieldError(field, data.message[field][0]);
                    });
                } else {
                    document.getElementById('addressFailure').textContent = data.message;
                    document.getElementById('addressFailure').classList.remove('hidden');
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = editingAddressId ? 'Update Address' : 'Save Address';
            console.error(err);
        });
    };

    function setAddrFieldError(field, message) {
        const input = document.querySelector(`.addr-input[name="${field}"]`);
        const error = document.querySelector(`.addr-error[data-error-for="${field}"]`);
        if (input) {
            if (message) {
                input.classList.add('border-red-500');
            } else {
                input.classList.remove('border-red-500');
            }
        }
        if (error) {
            error.textContent = message || '';
        }
    }

    function clearAddrErrors() {
        document.querySelectorAll('.addr-error').forEach(el => el.textContent = '');
        document.querySelectorAll('.addr-input').forEach(el => el.classList.remove('border-red-500'));
        const successBox = document.getElementById('addressSuccess');
        const failureBox = document.getElementById('addressFailure');
        if (successBox) successBox.classList.add('hidden');
        if (failureBox) failureBox.classList.add('hidden');
    }

    function validateAddressForm() {
        const errors = {};
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^[0-9]{10}$/;

        const name = document.getElementById('addr_name').value.trim();
        const phone = document.getElementById('addr_phone').value.trim();
        const alternatePhone = document.getElementById('addr_alternate_phone').value.trim();
        const email = document.getElementById('addr_email').value.trim();
        const address = document.getElementById('addr_address').value.trim();
        const city = document.getElementById('addr_city').value.trim();
        const state = document.getElementById('addr_state').value;

        if (!name) errors.name = 'Please enter your full name.';
        if (!phone) errors.phone = 'Please enter your mobile number.';
        else if (!phoneRegex.test(phone)) errors.phone = 'Please enter a valid 10 digit mobile number.';

        if (alternatePhone && !phoneRegex.test(alternatePhone)) {
            errors.alternate_phone = 'Please enter a valid 10 digit alternate mobile number.';
        }

        if (email && !emailRegex.test(email)) {
            errors.email = 'Please enter a valid email address.';
        }

        if (!address) errors.address = 'Please enter Flat/House/Building Name.';
        if (!city) errors.city = 'Please enter town / city.';
        if (!state) errors.state = 'Please select state.';

        const pincode = document.getElementById('addr_pincode').value.trim();
        if (!pincode) {
            errors.pincode = 'Please enter pincode.';
        } else if (!/^[0-9]{6}$/.test(pincode)) {
            errors.pincode = 'Please enter a valid 6 digit pincode.';
        }

        Object.keys(errors).forEach(field => {
            setAddrFieldError(field, errors[field]);
        });

        return Object.keys(errors).length === 0;
    }

    // Modal overlay click / keypress close
    const addressModalEl = document.getElementById("addressModal");
    addressModalEl.addEventListener("click", (e) => {
        if (e.target === addressModalEl) {
            closeModal();
        }
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !addressModalEl.classList.contains("hidden")) {
            closeModal();
        }
    });

    // Check for success alerts and modal reopen
    document.addEventListener('DOMContentLoaded', () => {
        const successMsg = sessionStorage.getItem('checkout_address_success');
        if (successMsg && window.showWishlistToast) {
            window.showWishlistToast(successMsg, true);
            sessionStorage.removeItem('checkout_address_success');
        }

        if (sessionStorage.getItem('buynow_open_modal') === 'true') {
            sessionStorage.removeItem('buynow_open_modal');
            setTimeout(() => {
                if (typeof window.openBuyNowAddressModal === 'function') {
                    window.openBuyNowAddressModal();
                }
            }, 300);
        }

        // Limit inputs to numbers
        const phoneInputs = ['addr_phone', 'addr_alternate_phone'];
        phoneInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 10);
                });
            }
        });
        const pincodeEl = document.getElementById('addr_pincode');
        if (pincodeEl) {
            pincodeEl.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    });
})();
</script>

<script>
function initProductImageZoom() {
    const slides = document.querySelectorAll('.mainSwiper .swiper-slide');
    slides.forEach((slide) => {
        const img = slide.querySelector('.zoom-main-img');
        if (!img) return;

        let isPinnedZoomed = false;
        let touchStartX = 0;
        let touchStartY = 0;
        let touchStartTime = 0;
        let isTouchDragging = false;

        function updateZoom(clientX, clientY, scale = 2.5) {
            const rect = slide.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100));
            img.style.transformOrigin = `${x}% ${y}%`;
            img.style.transform = `scale(${scale})`;
        }

        function resetZoom() {
            img.style.transform = 'scale(1)';
            img.style.transformOrigin = 'center center';
            isPinnedZoomed = false;
        }

        // Desktop Mouse Events
        slide.addEventListener('mousemove', (e) => {
            updateZoom(e.clientX, e.clientY);
        });

        slide.addEventListener('mouseleave', () => {
            resetZoom();
        });

        // Mobile Touch Events
        slide.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                touchStartTime = Date.now();
                isTouchDragging = false;
                updateZoom(touch.clientX, touch.clientY);
            }
        }, { passive: true });

        slide.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                const diffX = Math.abs(touch.clientX - touchStartX);
                const diffY = Math.abs(touch.clientY - touchStartY);
                if (diffX > 8 || diffY > 8) {
                    isTouchDragging = true;
                }
                updateZoom(touch.clientX, touch.clientY);
            }
        }, { passive: true });

        slide.addEventListener('touchend', (e) => {
            const touchDuration = Date.now() - touchStartTime;

            // If it's a quick tap without drag, toggle pinned zoom
            if (touchDuration < 250 && !isTouchDragging) {
                if (isPinnedZoomed) {
                    resetZoom();
                } else {
                    const changedTouch = e.changedTouches[0];
                    if (changedTouch) {
                        updateZoom(changedTouch.clientX, changedTouch.clientY);
                    }
                    isPinnedZoomed = true;
                }
            } else {
                // If it was a hold/drag, reset when finger lifts unless pinned
                if (!isPinnedZoomed) {
                    resetZoom();
                }
            }
        });

        slide.addEventListener('touchcancel', () => {
            if (!isPinnedZoomed) {
                resetZoom();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initProductImageZoom();
});
</script>
@endsection
