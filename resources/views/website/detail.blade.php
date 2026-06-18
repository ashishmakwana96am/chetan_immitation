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

<section class="pt-[50px] pb-[60px] md:pb-[80px] lg:pb-[100px]">

    <div class="container-1440 overflow-visible">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-[30px] items-start">

            <!-- LEFT SIDE -->
            <div class="lg:col-span-5 w-full  lg:sticky lg:top-[80px]">
                    <div class="swiper mainSwiper relative h-[60vh]">

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
                            data-variant-id=""
                            data-login-url="{{ route('login') }}"
                            data-toggle-url="{{ route('wishlist.toggle') }}"
                            data-current-url="{{ url()->current() }}"
                            @php
                                $inWishlist = auth('customer')->check()
                                    && \App\Models\Wishlist::where('customer_id', auth('customer')->id())
                                        ->where('product_id', $product->id)
                                        ->whereNull('product_variant_id')
                                        ->exists();
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

                <h1 class="text-[#131615] font-bold text-[22px] sm:text-[28px] sm:leading-[28px]">
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

                <div class="flex items-center gap-[10px] mt-4 sm:mt-6 ">
                    <span class="text-[#B4771E] text-[22px] leading-[24px] sm:text-[30px] font-bold">
                        ₹{{ number_format($product->sale_price, 0) }}
                    </span>
                    @if($product->mrp && $product->mrp > $product->sale_price)
                    <span class="line-through text-[#757575] text-[22px] md:text-2xl leading-[24px]">
                        ₹{{ number_format($product->mrp, 0) }}
                    </span>
                    @endif
                </div>

                <p class="text-[#3D403F] mt-4 md:mt-5 text-base sm:text-xl xl:text-[22px]">
                    Inclusive of all taxes
                </p>

                <div class="flex items-center gap-4 mt-5 lg:mt-[30px]">
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

                <div class="grid xl:grid-cols-2 gap-3 mt-6">
                    <button class="common-btn">Add To Cart</button>
                    <button class="border border-[#131615] common-btn !bg-transparent !text-[#131615]">Buy Now</button>
                </div>

                @php
                    $variantGroups = $product->variants
                        ->filter(fn ($variant) => $variant->attributeValue && $variant->attributeValue->attribute)
                        ->groupBy(fn ($variant) => $variant->attributeValue->attribute->id);
                    $hasAdditionalInformation = filled(trim(html_entity_decode(strip_tags($product->additional_information ?? ''))));
                    $hasProductHighlights = filled(trim(html_entity_decode(strip_tags($product->product_highlights ?? ''))));
                @endphp

                @if($variantGroups->isNotEmpty())
                    @foreach($variantGroups as $variants)
                    @php
                        $attribute = $variants->first()->attributeValue->attribute;
                        $attributeValues = $variants->unique('attribute_value_id');
                    @endphp
                    <div class="mt-[30px]">
                        <h4 class="text-xl md:text-[24px] font-medium mb-[15px] text-[#131615]">
                            {{ $attribute->name }}:
                        </h4>
                        <div class="flex flex-wrap gap-4 md:gap-[20px]">
                            @foreach($attributeValues as $variant)
                            <button class="min-w-[69px] min-h-10 px-4 py-2 border border-[#D5D5D5] text-base lg:text-xl leading-tight whitespace-normal text-center {{ $loop->first ? 'bg-[#B4771E] text-white' : '' }}">
                                {{ $variant->attributeValue->value }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @endif

                <div class="product-detail-accordion min-w-0 max-w-full ">
                    <details class="group" open>
                        <summary class="list-none flex items-center justify-between pt-[32px] pb-[15px] cursor-pointer border-b border-[#D9D9D9]">
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
                        <summary class="list-none flex items-center justify-between pt-[22px] pb-[15px] cursor-pointer border-b border-[#D9D9D9]">
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
                        <summary class="list-none flex items-center justify-between pt-[22px] pb-[15px] cursor-pointer border-b border-[#D9D9D9]">
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

<section>

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

</section>

<!-- You May Also Like -->
<section class="section-space">

    <div class="container-1440">

        <div class="text-center mb-10 lg:mb-10">
            <h2 class="font-moglan hero-title">You May Also Like</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

            @forelse($relatedProducts as $rp)
            <div class="group border border-[#D5D5D5] cursor-pointer">
                <div class="relative overflow-hidden">
                    @if($rp->sale)
                    <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                        <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                    </div>
                    @endif
                    <a href="{{ route('product.detail', $rp->slug) }}">
                        <img src="{{ $rp->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}" alt="" class="w-full h-[340px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                    </a>
                    <a href="{{ route('product.detail', $rp->slug) }}" class="group absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center text-[#131615] transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5 text-[#131615] fill-transparent hover:text-[#E01B1B] hover:fill-[#E01B1B] transition-all duration-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </a>
                </div>
                <div class="p-4 md:p-[25px]">
                    <h3 class="product-title"><a href="{{ route('product.detail', $rp->slug) }}">{{ $rp->name }}</a></h3>
                    <div class="flex items-center gap-1 mt-[9px]">
                        <div class="text-[#B4771E] text-base">★★★★★</div>
                        <span class="text-sm text-[#757575]">(0)</span>
                    </div>
                    <div class="mt-1 flex items-center gap-1">
                        <span class="text-lg xl:text-[24px] text-[#131615]">₹{{ number_format($rp->sale_price, 0) }}</span>
                        @if($rp->mrp && $rp->mrp > $rp->sale_price)
                        <span class="text-sm xl:text-lg text-[#757575] line-through">₹{{ number_format($rp->mrp, 0) }}</span>
                        @endif
                    </div>
                    <button class="w-full h-[45px] border border-[#131615] text-lg mt-[30px] hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white transition">Add to Cart</button>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-10 text-gray-400">No related products found.</div>
            @endforelse

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
        0: { slidesPerView: 4 },
        768: { slidesPerView: 5 }
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
let count = 1;

plusBtn.addEventListener("click", () => { count++; qty.innerText = count; });
minusBtn.addEventListener("click", () => { if (count > 1) { count--; qty.innerText = count; } });

</script>

<script>
// Wishlist toggle for detail page
(function () {
    var isLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};
    var csrfToken  = '{{ csrf_token() }}';

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.wishlist-btn[data-toggle-url]');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        if (!isLoggedIn) {
            var currentUrl = btn.dataset.currentUrl || window.location.href;
            window.location.href = btn.dataset.loginUrl + '?intended=' + encodeURIComponent(currentUrl);
            return;
        }

        var productId = btn.dataset.productId;
        var variantId = btn.dataset.variantId || null;
        var toggleUrl = btn.dataset.toggleUrl;
        var svg       = btn.querySelector('.wishlist-icon, svg');

        // Pick active variant button if user selected one
        var activeVariantBtn = document.querySelector('.variant-selector.active[data-variant-id]');
        if (activeVariantBtn && activeVariantBtn.dataset.variantId) {
            variantId = activeVariantBtn.dataset.variantId;
            btn.dataset.variantId = variantId;
        }

        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                product_id:         productId,
                product_variant_id: variantId || null,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'added') {
                btn.dataset.inWishlist = '1';
                svg.classList.remove('fill-transparent', 'text-[#131615]');
                svg.classList.add('fill-[#E01B1B]', 'text-[#E01B1B]');
            } else {
                btn.dataset.inWishlist = '0';
                svg.classList.remove('fill-[#E01B1B]', 'text-[#E01B1B]');
                svg.classList.add('fill-transparent', 'text-[#131615]');
            }
        })
        .catch(function () {});
    });
}());
</script>

@endsection
