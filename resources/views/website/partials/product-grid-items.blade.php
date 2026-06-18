@forelse($products as $product)
@php $stockQty = $product->inventories_sum_quantity ?? 0; @endphp
<div class="group border border-[#D5D5D5] relative cursor-pointer">
    <div class="relative overflow-hidden">
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
            <img src="{{ $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}" alt="{{ $product->name }}" class="w-full h-[340px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
        </a>
    </div>
    <button class="group absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center outline-none focus:outline-none focus:ring-0">
       <svg xmlns="http://www.w3.org/2000/svg"
fill="none"
viewBox="0 0 24 24"
stroke-width="1.6"
stroke="currentColor"
class="w-5 h-5 text-[#131615]
fill-transparent
hover:fill-[#E01B1B]
hover:text-[#E01B1B]
transition-all duration-300">

<path stroke-linecap="round"
stroke-linejoin="round"
d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
</svg>
    </button>
    <div class="p-4 md:p-[25px]">
        <h3 class="product-title"><a href="{{ route('product.detail', $product->slug) }}">{{ $product->name }}</a></h3>
        <div class="flex items-center gap-1 mt-[9px]">
            <div class="text-[#D5D5D5] text-base">★★★★★</div>
            <span class="text-xs text-[#757575]">(0)</span>
        </div>
        <div class="mt-1 flex items-center gap-1">
             <span class="text-lg xl:text-[24px] text-[#131615]">₹{{ number_format($product->sale_price, 0) }}</span>
             @if($product->mrp && $product->mrp > $product->sale_price)<span class="text-sm xl:text-lg text-[#757575] line-through">₹{{ number_format($product->mrp, 0) }}</span>@endif
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
            @if($variantValues->isNotEmpty())
            <div class="mt-5">
                <div class="flex flex-wrap gap-2">
                    @foreach($variantValues as $index => $value)
                    <button class="px-3 py-1 text-xs {{ $index === 0 ? 'bg-[#B4771E] text-white' : 'border border-[#D5D5D5]' }}">
                        {{ $value }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        <button class="w-full h-[45px] border border-[#131615] text-lg mt-[28px] hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white transition duration-300">
            Add to Cart
        </button>
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
