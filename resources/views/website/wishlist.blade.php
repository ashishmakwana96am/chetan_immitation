@extends('layouts.website')

@section('title', 'My Wishlist | Chetan Imitation')

@section('content')

<section class="section-space">
    <div class="container-1440">

        <div class="text-center mb-7">
            <h2 class="font-moglan hero-title">My Wishlist</h2>
            <p class="hero-para">Save your favorite jewelry pieces and shop them anytime.</p>
        </div>

        {{-- Empty State --}}
        <div id="wishlistEmptyState" class="text-center py-20 {{ $wishlists->isEmpty() ? '' : 'hidden' }}">
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

        {{-- Grid Wrapper --}}
        <div id="wishlistGridWrapper" class="grid grid-cols-1 lg:grid-cols-[minmax(0,70%)_1fr] 2xl:grid-cols-[953px_1fr] gap-6 items-start  {{ $wishlists->isEmpty() ? 'hidden' : '' }}">

            {{-- Wishlist Items --}}
            <div class=" min-w-0 space-y-[30px]" id="wishlistItems">
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
                <div class="wishlist-item border border-[#D5D5D5] p-3 lg:p-4" data-wishlist-id="{{ $wishlist->id }}">
                    <div class="flex flex-col sm:flex-row gap-4 group">
                        {{-- Image --}}
                        <div class="relative shrink-0 sm:w-[190px] sm:h-[190px] overflow-hidden cursor-pointer">
                            @if($stockQty < 1)
                            <div class="absolute top-[25px] left-[-42px] z-10 rotate-[-20deg]">
                                <span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SOLD OUT</span>
                            </div>
                            @elseif($prod->sale)
                            <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                                <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                            </div>
                            @endif
                            <a href="{{ route('product.detail', $prod->slug) }}">
                                <img src="{{ $prod->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}"
                                    alt="{{ $prod->name }}"
                                    class="sm:w-[190px] sm:h-[190px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
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
                        <div class="flex-1 min-w-0 flex flex-col justify-between">
                            <div>
                                <a href="{{ route('product.detail', $prod->slug) }}"
                                    class="block product-title text-base md:text-[22px] font-semibold text-[#131615] hover:text-[#B4771E] transition w-full min-w-0 overflow-hidden text-ellipsis whitespace-nowrap">
                                    {{ $prod->name }}
                                </a>
    
                                <div class="flex items-center gap-2 mt-3">
                                    <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                        ₹{{ number_format($prod->sale_price, 0) }}
                                    </span>
                                    @if($prod->mrp && $prod->mrp > $prod->sale_price)
                                    <span class="text-[#999] line-through text-base md:text-lg">
                                        ₹{{ number_format($prod->mrp, 0) }}
                                    </span>
                                    @endif
                                </div>
    
                                <div class="mt-2 sm:mt-4 space-y-2">
                                    @if($prod->category)
                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                        <span class="text-[#757575] ml-2">{{ $prod->category->name }}</span>
                                    </p>
                                    @endif
                                    @if($attrLabel)
                                    @php
                                        $parts = explode(':', $attrLabel, 2);
                                        $labelName = count($parts) > 1 ? trim($parts[0]) : 'Variant';
                                        $labelVal = count($parts) > 1 ? trim($parts[1]) : trim($parts[0]);
                                    @endphp
                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">{{ $labelName }}:</span>
                                        <span class="text-[#757575] ml-2">{{ $labelVal }}</span>
                                    </p>
                                    @endif
    
                                </div>
                            </div>

                            <div class="border-t border-[#D5D5D5] mt-2 pt-2">
                                <div class="flex items-center flex-wrap gap-2 text-[#3D403F]">
                                    <button class="add-to-cart-from-wishlist after:border-r after:border-[#3D403F] after:h-5 after:sm:mx-4 after:content-[''] after:inline-block hover:text-[#B4771E] flex items-center gap-[10px] text-sm sm:text-base transition"
                                        data-product-id="{{ $prod->id }}"
                                        data-variant-id="{{ $variant?->id ?? '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 sm:size-5 -mt-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        Add To Cart
                                    </button>
                                    <button class="remove-wishlist-btn hover:text-red-500 flex items-center gap-[10px] text-sm sm:text-base transition"
                                        data-product-id="{{ $prod->id }}"
                                        data-variant-id="{{ $variant?->id ?? '' }}"
                                        data-toggle-url="{{ route('wishlist.toggle') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 sm:size-5 -mt-1">
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
            <div class=" min-w-0 border border-[#D5D5D5] p-3 lg:p-4 sticky top-5">
                <h3 class="text-lg md:text-[22px] font-semibold text-[#131615]">Wishlist Summary</h3>
                <div class="border-t border-[#D5D5D5] mt-3 sm:mt-5 pt-3 sm:pt-5">
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
                <div class="flex mt-5 sm:mt-6 gap-2 flex-col sm:flex-row lg:flex-col">
                    <button id="moveAllToCartBtn" class="w-full bg-[#B4771E] text-white text-lg font-medium h-[52px] transition common-btn">
                        Move All To Cart
                    </button>
                    <a href="{{ route('shop-by-category') }}"
                        class="flex items-center justify-center w-full h-[52px] border-2 border-[#131615] text-[#131615] text-lg font-medium transition  common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                        Continue Shopping
                    </a>
                </div>
            </div>

        </div>

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
    var csrfToken   = '{{ csrf_token() }}';
    var cartAddUrl  = '{{ route('cart.add') }}';

    /* ── helper: add one item to cart ── */
    function addItemToCart(productId, variantId, btn, onSuccess) {
        var originalText = '';
        if (btn) {
            originalText = $(btn).html();
            $(btn).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        }

        $.ajax({
            url: cartAddUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                product_id: productId,
                variant_id: variantId || null,
                qty: 1,
            }),
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (btn) { $(btn).prop('disabled', false).html(originalText); }
                if (res.status === 'success') {
                    if (window.updateCartBadge) window.updateCartBadge(res.count);
                    if (onSuccess) onSuccess(res);
                }
            },
            error: function () {
                if (btn) { $(btn).prop('disabled', false).html(originalText); }
            }
        });
    }

    /* ── Per-item "Add To Cart" (Moves item to Cart) ── */
    $(document).on('click', '.add-to-cart-from-wishlist', function () {
        var btn       = this;
        var productId = $(btn).data('product-id');
        var variantId = $(btn).data('variant-id') || null;
        var toggleUrl = '{{ route('wishlist.toggle') }}';
        var $item     = $(btn).closest('.wishlist-item');

        addItemToCart(productId, variantId, btn, function (res) {
            // Remove from wishlist database since it is moved to cart
            $.ajax({
                url: toggleUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ product_id: productId, product_variant_id: variantId }),
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (wishlistRes) {
                    if (window.showWishlistToast) window.showWishlistToast('Item moved to cart! 🛒');
                    $item.fadeOut(300, function () {
                        $(this).remove();
                        $('#wishlistCount').text(wishlistRes.count);
                        if (window.updateWishlistBadge) window.updateWishlistBadge(wishlistRes.count);
                        if (wishlistRes.count === 0 || $('#wishlistItems .wishlist-item').length === 0) {
                            fetchAndSwapWishlist();
                        }
                    });
                }
            });
        });
    });

    /* ── "Move All To Cart" ── */
    $(document).on('click', '#moveAllToCartBtn', function () {
        var btn   = this;
        var items = [];
        $('#wishlistItems .wishlist-item').each(function () {
            var addBtn = $(this).find('.add-to-cart-from-wishlist');
            items.push({
                product_id: addBtn.data('product-id'),
                variant_id: addBtn.data('variant-id') || null,
            });
        });

        if (!items.length) return;

        $(btn).prop('disabled', true).text('Moving...');

        var done = 0;
        var toggleUrl = '{{ route('wishlist.toggle') }}';

        items.forEach(function (item) {
            addItemToCart(item.product_id, item.variant_id, null, function () {
                // Remove from wishlist database since it is moved to cart
                $.ajax({
                    url: toggleUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ product_id: item.product_id, product_variant_id: item.variant_id }),
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    success: function () {
                        done++;
                        if (done === items.length) {
                            $(btn).prop('disabled', false).text('Move All To Cart');
                            if (window.showWishlistToast) window.showWishlistToast('All items moved to cart! 🛒');
                            fetchAndSwapWishlist();
                        }
                    }
                });
            });
        });
    });

    /* ── dynamically added "Add To Cart" (wishlistToggled adds new items) ── */
    $(document).on('click', '#wishlistItems .add-to-cart-from-wishlist', function () {
        // already handled above via event delegation
    });

    function fetchAndSwapWishlist() {
        setTimeout(function () {
            fetch('{{ route('wishlist') }}?_t=' + Date.now(), { 
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    
                    var newWrapper = doc.getElementById('wishlistGridWrapper');
                    var oldWrapper = document.getElementById('wishlistGridWrapper');
                    if (newWrapper && oldWrapper) {
                        oldWrapper.innerHTML = newWrapper.innerHTML;
                        oldWrapper.className = newWrapper.className;
                    }
                    
                    var newEmpty = doc.getElementById('wishlistEmptyState');
                    var oldEmpty = document.getElementById('wishlistEmptyState');
                    if (newEmpty && oldEmpty) {
                        oldEmpty.innerHTML = newEmpty.innerHTML;
                        oldEmpty.className = newEmpty.className;
                    }

                    // Update counts
                    var countVal = doc.getElementById('wishlistCount') ? doc.getElementById('wishlistCount').textContent : '0';
                    if (window.updateWishlistBadge) window.updateWishlistBadge(parseInt(countVal));
                    var countEl = document.getElementById('wishlistCount');
                    if (countEl) countEl.textContent = countVal;
                });
        }, 300);
    }

    /* ── Remove from Wishlist ── */
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
                        fetchAndSwapWishlist();
                    }
                });
            },
            error: function () { btn.prop('disabled', false).removeClass('opacity-50'); }
        });
    });

    /* ── wishlistToggled event (heart clicks) ── */
    document.addEventListener('wishlistToggled', function (e) {
        var data = e.detail;

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
                        fetchAndSwapWishlist();
                    }
                });
                return;
            }
        }

        if (data.status === 'added') {
            fetchAndSwapWishlist();
            return;
        }

        var priceHtml = '<span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">₹' + p.sale_price + '</span>';
        if (p.mrp) {
            priceHtml += '<span class="text-[#999] line-through text-base md:text-lg">₹' + p.mrp + '</span>';
        }

        var metaHtml = '';
        if (p.category) {
            metaHtml += '<p class="text-base flex flex-wrap"><span class="font-medium text-[#131615] w-[120px]">Category:</span><span class="text-[#757575] ml-2">' + $('<span>').text(p.category).html() + '</span></p>';
        }
        if (p.attr_label) {
            metaHtml += '<p class="text-base flex flex-wrap"><span class="font-medium text-[#131615] w-[120px]">Variant:</span><span class="text-[#757575] ml-2">' + $('<span>').text(p.attr_label).html() + '</span></p>';
        }

        var itemHtml =
            '<div class="wishlist-item border border-[#D5D5D5] p-3 lg:p-4" data-wishlist-id="' + p.wishlist_id + '" style="display:none">' +
                '<div class="flex flex-col sm:flex-row gap-4 group">' +
                    '<div class="relative shrink-0 sm:w-[190px] sm:h-[190px] overflow-hidden cursor-pointer">' +
                        badgeHtml +
                        '<a href="' + p.detail_url + '"><img src="' + p.image + '" alt="' + $('<span>').text(p.name).html() + '" class="sm:w-[190px] sm:h-[190px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105"></a>' +
                        '<button class="wishlist-btn absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center transition"' +
                            ' data-product-id="' + p.product_id + '" data-variant-id="' + (p.variant_id || '') + '"' +
                            ' data-login-url="' + p.login_url + '" data-toggle-url="' + p.toggle_url + '"' +
                            ' data-current-url="' + p.current_url + '" data-in-wishlist="1">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="wishlist-icon w-5 h-5 fill-[#E01B1B] text-[#E01B1B] transition-all duration-300"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0 flex flex-col justify-between">' +
                       '<div>' +
                        '<a href="' + p.detail_url + '" class="block product-title text-base md:text-[22px] lg:leading-[36px] font-semibold text-[#131615] hover:text-[#B4771E] transition w-full min-w-0 overflow-hidden text-ellipsis whitespace-nowrap">' +
                            $('<span>').text(p.name).html() +
                        '</a>' +

                        '<div class="flex items-center gap-2 mt-3">' +
                            priceHtml +
                        '</div>' +

                        '<div class="mt-2 sm:mt-4 space-y-2">' +
                            metaHtml +
                        '</div>' +
                    '</div>' +
                        '<div class="border-t border-[#D5D5D5] mt-2 pt-2">' +
                            '<div class="flex items-center flex-wrap gap-2 text-[#3D403F]">' +
                                '<button class="after:border-r after:border-[#3D403F] after:h-5 after:sm:mx-4 after:content-[\'\'] after:inline-block hover:text-[#B4771E] flex items-center gap-[10px] text-sm sm:text-base transition">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 sm:size-4 sm:size-5 -mt-1 -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>Add To Cart' +
                                '</button>' +
                                '<button class="remove-wishlist-btn hover:text-red-500 flex items-center gap-[10px] text-sm sm:text-base transition"' +
                                    ' data-product-id="' + p.product_id + '" data-variant-id="' + (p.variant_id || '') + '" data-toggle-url="' + p.toggle_url + '">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 sm:size-4 sm:size-5 -mt-1 -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>Remove From Wishlist' +
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
