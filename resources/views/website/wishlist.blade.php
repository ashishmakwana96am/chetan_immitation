@extends('layouts.website')

@section('title', 'My Wishlist | Chetan Imitation')

@section('content')

<section class="section-space">
    <div class="container-1440">

        <div class="text-center mb-10">
            <h2 class="font-moglan hero-title">My Wishlist</h2>
            <p class="hero-para">Save your favorite jewelry pieces and shop them anytime.</p>
        </div>

        @if($wishlists->isEmpty())
        {{-- Empty State --}}
        <div class="text-center py-20">
            <svg class="mx-auto w-16 h-16 text-[#D5D5D5] mb-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
            <h3 class="text-[#131615] text-2xl font-semibold mb-3">Your wishlist is empty</h3>
            <p class="text-[#757575] text-lg mb-8">Browse our collections and save the pieces you love.</p>
            <a href="{{ route('shop-by-category') }}"
                class="inline-block bg-[#B4771E] text-white px-8 py-3 text-lg font-medium hover:bg-[#9d6719] transition">
                Start Shopping
            </a>
        </div>

        @else
        <div class="grid xl:grid-cols-[1fr_320px] gap-6 items-start">

            {{-- Wishlist Items --}}
            <div class="space-y-[30px]" id="wishlistItems">
                @foreach($wishlists as $wishlist)
                @php
                    $prod    = $wishlist->product;
                    $variant = $wishlist->productVariant;
                    $attrLabel = null;
                    if ($variant && $variant->attributeValue) {
                        $attrName  = optional($variant->attributeValue->attribute)->name;
                        $attrValue = $variant->attributeValue->value;
                        $attrLabel = $attrName ? $attrName . ': ' . $attrValue : $attrValue;
                    }
                    $stockQty = $prod->inventories_sum_quantity ?? 0;
                @endphp
                <div class="wishlist-item border border-[#D5D5D5] p-3 lg:p-[25px]" data-wishlist-id="{{ $wishlist->id }}">
                    <div class="flex flex-col md:flex-row gap-4 group">
                        {{-- Image --}}
                        <div class="relative shrink-0 w-[200px] h-[200px] overflow-hidden cursor-pointer">
                            @if($stockQty < 1)
                            <div class="absolute top-[25px] left-[-42px] z-10 rotate-[-20deg]">
                                <span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">OUT OF STOCK</span>
                            </div>
                            @elseif($prod->sale)
                            <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                                <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                            </div>
                            @endif
                            <a href="{{ route('product.detail', $prod->slug) }}">
                                <img src="{{ $prod->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}"
                                    alt="{{ $prod->name }}"
                                    class="w-[200px] h-[200px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                            </a>
                            
                            <button class="wishlist-btn absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center transition"
                                data-product-id="{{ $prod->id }}"
                                data-variant-id="{{ $variant?->id ?? '' }}"
                                data-login-url="{{ route('login') }}"
                                data-toggle-url="{{ route('wishlist.toggle') }}"
                                data-current-url="{{ url()->current() }}"
                                data-in-wishlist="1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
                                    class="wishlist-icon w-5 h-5 fill-[#E01B1B] text-[#E01B1B] transition-all duration-300">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>
                            </button>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('product.detail', $prod->slug) }}"
                                class="block product-title text-base md:text-[22px] lg:text-[26px] leading-[28px] lg:leading-[36px] font-semibold text-[#131615] hover:text-[#B4771E] transition">
                                {{ $prod->name }}
                            </a>

                            <div class="flex items-center gap-2 mt-4">
                                <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                    ₹{{ number_format($prod->sale_price, 0) }}
                                </span>
                                @if($prod->mrp && $prod->mrp > $prod->sale_price)
                                <span class="text-[#999] line-through text-base md:text-lg">
                                    ₹{{ number_format($prod->mrp, 0) }}
                                </span>
                                @endif
                            </div>

                            <div class="mt-4 space-y-[10px]">
                                @if($prod->category)
                                <p class="text-base sm:text-lg flex flex-wrap">
                                    <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                    <span class="text-[#757575] ml-2">{{ $prod->category->name }}</span>
                                </p>
                                @endif
                                @if($attrLabel)
                                <p class="text-base sm:text-lg flex flex-wrap">
                                    <span class="font-medium text-[#131615] w-[120px]">Variant:</span>
                                    <span class="text-[#757575] ml-2">{{ $attrLabel }}</span>
                                </p>
                                @endif

                            </div>

                            <div class="border-t border-[#D5D5D5] mt-4 pt-2">
                                <div class="flex items-center flex-wrap gap-2 text-[#3D403F]">
                                    <button class="after:border-r after:border-[#3D403F] after:h-5 after:mx-4 after:content-[''] after:inline-block hover:text-[#B4771E] flex items-center gap-[10px] text-base md:text-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        Add To Cart
                                    </button>
                                    <button class="remove-wishlist-btn hover:text-red-500 flex items-center gap-[10px] text-base md:text-lg transition"
                                        data-product-id="{{ $prod->id }}"
                                        data-variant-id="{{ $variant?->id ?? '' }}"
                                        data-toggle-url="{{ route('wishlist.toggle') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        Remove From Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Wishlist Summary --}}
            <div class="border border-[#D5D5D5] p-5 sticky top-5">
                <h3 class="text-lg md:text-[22px] font-semibold text-[#131615]">Wishlist Summary</h3>
                <div class="border-t border-[#D5D5D5] mt-5 pt-5">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5 text-[#131615]">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                            <span class="text-base md:text-xl">Saved Items</span>
                        </div>
                        <span class="text-base md:text-xl font-semibold" id="wishlistCount">{{ $wishlists->count() }}</span>
                    </div>
                </div>
                <button class="w-full bg-[#B4771E] text-white text-lg font-medium h-[52px] mt-[30px] hover:bg-[#9d6719] transition">
                    Move All To Cart
                </button>
                <a href="{{ route('shop-by-category') }}"
                    class="flex items-center justify-center w-full h-[52px] mt-3 border-2 border-[#131615] text-[#131615] text-lg font-medium hover:bg-[#131615] hover:text-white transition">
                    Continue Shopping
                </a>
            </div>

        </div>
        @endif

        {{-- You May Also Like Section --}}
        @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <div class="mt-20 border-t border-[#D5D5D5] pt-16">
            <div class="text-center mb-10">
                <h2 class="font-moglan hero-title">You May Also Like</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('website.partials.product-grid-items', ['products' => $relatedProducts])
            </div>
        </div>
        @endif

    </div>
</section>

@endsection

@section('page-js')
<script>
$(function () {
    var csrfToken = '{{ csrf_token() }}';

    $(document).on('click', '#wishlistItems .remove-wishlist-btn', function (e) {
        var btn = $(this);

        var productId = btn.data('product-id');
        var variantId = btn.data('variant-id') || null;
        var toggleUrl = btn.data('toggle-url');
        var $item     = btn.closest('.wishlist-item');

        btn.prop('disabled', true).addClass('opacity-50');

        $.ajax({
            url: toggleUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId, product_variant_id: variantId }),
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                $item.fadeOut(300, function () {
                    $(this).remove();
                    $('#wishlistCount').text(res.count);
                    if (window.updateWishlistBadge) window.updateWishlistBadge(res.count);
                    if (window.showWishlistToast) window.showWishlistToast('Product removed from your wishlist.');
                    if (res.count === 0 || $('#wishlistItems .wishlist-item').length === 0) {
                        location.reload();
                    }
                });
            },
            error: function () { btn.prop('disabled', false).removeClass('opacity-50'); }
        });
    });

    document.addEventListener('wishlistToggled', function (e) {
        var data = e.detail;

        // Heart click on item inside #wishlistItems → remove that card from DOM
        if (data.status === 'removed' && data.product_id) {
            var $item = $('#wishlistItems .wishlist-item').filter(function () {
                return $(this).find('.wishlist-btn[data-product-id="' + data.product_id + '"]').length > 0;
            });
            if ($item.length) {
                $item.fadeOut(300, function () {
                    $(this).remove();
                    $('#wishlistCount').text(data.count);
                    if (window.updateWishlistBadge) window.updateWishlistBadge(data.count);
                    if ($('#wishlistItems .wishlist-item').length === 0) {
                        location.reload();
                    }
                });
                return;
            }
        }

        if (data.status !== 'added' || !data.product_data) return;

        var p = data.product_data;

        if ($('#wishlistItems .wishlist-item[data-wishlist-id="' + p.wishlist_id + '"]').length) return;

        var badgeHtml = '';
        if (p.out_of_stock) {
            badgeHtml = '<div class="absolute top-[25px] left-[-42px] z-10 rotate-[-20deg]"><span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">OUT OF STOCK</span></div>';
        } else if (p.sale) {
            badgeHtml = '<div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]"><span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span></div>';
        }

        var priceHtml = '<span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">₹' + p.sale_price + '</span>';
        if (p.mrp) {
            priceHtml += '<span class="text-[#999] line-through text-base md:text-lg">₹' + p.mrp + '</span>';
        }

        var metaHtml = '';
        if (p.category) {
            metaHtml += '<p class="text-base sm:text-lg flex flex-wrap"><span class="font-medium text-[#131615] w-[120px]">Category:</span><span class="text-[#757575] ml-2">' + $('<span>').text(p.category).html() + '</span></p>';
        }
        if (p.attr_label) {
            metaHtml += '<p class="text-base sm:text-lg flex flex-wrap"><span class="font-medium text-[#131615] w-[120px]">Variant:</span><span class="text-[#757575] ml-2">' + $('<span>').text(p.attr_label).html() + '</span></p>';
        }

        var itemHtml =
            '<div class="wishlist-item border border-[#D5D5D5] p-3 lg:p-[25px]" data-wishlist-id="' + p.wishlist_id + '" style="display:none">' +
                '<div class="flex flex-col md:flex-row gap-4 group">' +
                    '<div class="relative shrink-0 w-[200px] h-[200px] overflow-hidden cursor-pointer">' +
                        badgeHtml +
                        '<a href="' + p.detail_url + '"><img src="' + p.image + '" alt="' + $('<span>').text(p.name).html() + '" class="w-[200px] h-[200px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105"></a>' +
                        '<button class="wishlist-btn absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center transition"' +
                            ' data-product-id="' + p.product_id + '" data-variant-id="' + (p.variant_id || '') + '"' +
                            ' data-login-url="' + p.login_url + '" data-toggle-url="' + p.toggle_url + '"' +
                            ' data-current-url="' + p.current_url + '" data-in-wishlist="1">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="wishlist-icon w-5 h-5 fill-[#E01B1B] text-[#E01B1B] transition-all duration-300"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<a href="' + p.detail_url + '" class="block product-title text-base md:text-[22px] lg:text-[26px] leading-[28px] lg:leading-[36px] font-semibold text-[#131615] hover:text-[#B4771E] transition">' + $('<span>').text(p.name).html() + '</a>' +
                        '<div class="flex items-center gap-2 mt-4">' + priceHtml + '</div>' +
                        '<div class="mt-4 space-y-[10px]">' + metaHtml + '</div>' +
                        '<div class="border-t border-[#D5D5D5] mt-5 pt-[15px]">' +
                            '<div class="flex items-center flex-wrap gap-2 text-[#3D403F]">' +
                                '<button class="after:border-r after:border-[#3D403F] after:h-5 after:mx-4 after:content-[\'\'] after:inline-block hover:text-[#B4771E] flex items-center gap-[10px] text-base md:text-lg transition">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>Add To Cart' +
                                '</button>' +
                                '<button class="remove-wishlist-btn hover:text-red-500 flex items-center gap-[10px] text-base md:text-lg transition"' +
                                    ' data-product-id="' + p.product_id + '" data-variant-id="' + (p.variant_id || '') + '" data-toggle-url="' + p.toggle_url + '">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>Remove From Wishlist' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('#wishlistItems').prepend($(itemHtml).fadeIn(300));
        $('#wishlistCount').text(data.count);
    });
});
</script>
@endsection
