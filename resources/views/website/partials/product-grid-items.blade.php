@php
    if (isset($products)) {
        if ($products instanceof \Illuminate\Pagination\AbstractPaginator || $products instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $products->getCollection()->loadMissing('variants.attributeValue.attribute');
            $products->getCollection()->loadCount('reviews')->loadAvg('reviews', 'rating');
        } elseif (method_exists($products, 'loadMissing')) {
            $products->loadMissing('variants.attributeValue.attribute');
            if (method_exists($products, 'loadCount')) {
                $products->loadCount('reviews')->loadAvg('reviews', 'rating');
            }
        }
    }
@endphp
@forelse($products as $product)
@php
    $stockQty   = $product->type === 'variable' ? $product->totalAvailableStock() : ($product->inventories_sum_quantity ?? 0);
    $wishlistItem = auth('customer')->check()
        ? auth('customer')->user()->wishlists->where('product_id', $product->id)->first()
        : null;
    $inWishlist = !empty($wishlistItem);
    $wishlistVariantId = $wishlistItem ? $wishlistItem->product_variant_id : null;
    $detailUrl = route('product.detail', $product->slug);
    if ($wishlistVariantId) {
        $detailUrl .= '?variant=' . $wishlistVariantId;
    }
    $gridSalePrice = (float) $product->display_sale_price;
    $gridMrp = (float) $product->display_mrp;
    $discountPercent = 0;
    if ($gridMrp > 0 && $gridMrp > $gridSalePrice && $gridSalePrice > 0) {
        $discountPercent = (int) round((($gridMrp - $gridSalePrice) / $gridMrp) * 100);
    }
@endphp
<div class="product-card group sm:border border-[#D5D5D5] relative cursor-pointer flex flex-col h-full rounded-[6px] overflow-hidden" data-product-id="{{ $product->id }}">
    <div class="relative overflow-hidden">
        @if($stockQty < 1)
        <div class="absolute top-[17px] left-[-39px] z-10 rotate-[-20deg]">
            <span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SOLD OUT</span>
        </div>
        @elseif($discountPercent > 0)
        <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
            <span class="bg-[#16a34a] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">{{ $discountPercent }}% OFF</span>
        </div>
        @elseif($product->sale)
        <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
            <span class="bg-[#16a34a] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
        </div>
        @endif
        <a class="product-detail-link" href="{{ $detailUrl }}">
            <img src="{{ $product->primaryImage?->image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $product->name }}" class="w-full aspect-[3/4] sm:aspect-auto sm:h-[240px] md:h-[260px] xl:h-[300px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
             {{-- Rating Badge --}}
            @php
                $avgRating = round((float) ($product->reviews_avg_rating ?? 0), 1);
                $reviewCount = (int) ($product->reviews_count ?? 0);
            @endphp
            @if($reviewCount > 0)
            <div
                class="absolute bottom-3 left-3 z-20 backdrop-blur-md bg-white/70 border border-white/60 rounded-lg shadow-sm px-2 py-1">
                <div class="flex items-center font-semibold text-[#131615]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 512 512" fill="#B4771E" aria-hidden="true" style="flex-shrink:0; display:inline-block; vertical-align:middle; margin-top:-1px;">
                        <path d="m512 197.816-186.039-12.231L255.898 9.569l-70.063 176.016L0 197.816l142.534 121.026-46.772 183.589L255.898 401.21l160.137 101.221-46.772-183.589z"/>
                    </svg>
                  <span class="text-[13px] leading-[13px] ml-1.5 mt-0.5">{{ number_format($avgRating, 1) }} | {{ $reviewCount }}</span>
                </div>
            </div>
            @endif
        </a>
    </div>
    <button class="wishlist-btn absolute top-2 right-2 w-6 sm:w-[36px] h-6 sm:h-[36px] bg-white rounded-md sm:rounded-lg flex items-center justify-center outline-none focus:outline-none focus:ring-0"
        data-product-id="{{ $product->id }}"
        data-variant-id="{{ $wishlistVariantId ?? '' }}"
        data-login-url="{{ route('login') }}"
        data-toggle-url="{{ route('wishlist.toggle') }}"
        data-current-url="{{ url()->current() }}"
        data-in-wishlist="{{ $inWishlist ? '1' : '0' }}">
       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
            class="wishlist-icon w-4 sm:w-5 h-4 sm:h-5 transition-all duration-300 {{ $inWishlist ? 'fill-[#E01B1B] text-[#E01B1B]' : 'fill-transparent text-[#131615]' }}">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
       </svg>
    </button>
 
    <div class="p-1 sm:p-4 2xl:p-[18px] flex-1 flex flex-col">
        <div class="flex justify-between flex-col h-full">
            <div>
                <h3 class="product-title text-xs sm:text-base lg:text-[17px] leading-[1.35] font-semibold text-[#131615]">
                    <a class="product-detail-link" href="{{ $detailUrl }}">{{ $product->name }}</a>
                </h3>
                <div class="flex justify-between flex-wrap gap-2 mt-1 sm:mt-2">
                        @php
                            $gridSalePrice = $product->display_sale_price;
                            $gridMrp = $product->display_mrp;
                        @endphp
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="text-base sm:text-lg xl:text-[22px] font-semibold text-[#131615]">{{ website_price($gridSalePrice) }}</span>
                            @if($gridMrp && $gridMrp > $gridSalePrice)<span class="text-xs sm:text-sm xl:text-base text-[#757575] line-through">{{ website_price($gridMrp) }}</span>@endif
                        </div>
                        @php
                            $variantValues = $product->relationLoaded('variants')
                                ? $product->variants
                                    ->filter(fn($v) => $v->relationLoaded('attributeValue') && $v->attributeValue)
                                    ->map(fn($v) => $v->attributeValue->value)
                                    ->unique()
                                    ->values()
                                : collect();
                        @endphp
        
                        @php
                            $variantGroups = $product->variants
                                ->filter(fn ($variant) => $variant->attributeValue && $variant->attributeValue->attribute)
                                ->groupBy(fn ($variant) => $variant->attributeValue->attribute->id);
                            $firstGroup = $variantGroups->first();
                        @endphp
                        
                        @if($firstGroup)
                        @php
                            $attribute = $firstGroup->first()->attributeValue->attribute;
                            $attributeValues = $firstGroup->unique('attribute_value_id');
                        @endphp
                        <div class="relative w-full max-w-[88px] sm:max-w-[92px]">
                            <select
                              class="grid-variant-select appearance-none w-full border border-[#D5D5D5] px-2 pr-6 py-1 text-[11px] sm:text-xs focus:outline-none focus:border-[#B4771E]"
                              data-product-id="{{ $product->id }}">
                                <option value="">{{ $attribute->name }}</option>
                                @foreach($attributeValues as $variant)
                                    <option value="{{ $variant->id }}" {{ $wishlistVariantId == $variant->id ? 'selected' : '' }}>
                                        {{ $variant->attributeValue->value }}
                                    </option>
                                @endforeach
                            </select>
                            <svg
                                class="absolute right-2 top-1/2 -translate-y-[50%] w-4 h-4 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        @endif
                </div>
            </div>
            
            <button class="add-to-cart-btn w-full h-8 sm:h-[40px] border border-[#131615] text-sm sm:text-base mt-2 sm:mt-4 transition duration-300 rounded-sm {{ $stockQty < 1 ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white' }}"
                data-product-id="{{ $product->id }}"
                data-login-url="{{ route('login') }}?intended={{ urlencode(route('cart')) }}"
                {{ $stockQty < 1 ? 'disabled' : '' }}>
                {{ $stockQty < 1 ? 'Sold Out' : 'Add to Cart' }}
            </button>
        </div>
    </div>
</div>
@empty
<div class="col-span-full text-center py-16 text-gray-400">
    <svg class="mx-auto w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <p class="font-medium">No products found</p>
    <p class="text-sm mt-1">Try adjusting your filters</p>
</div>
@endforelse
