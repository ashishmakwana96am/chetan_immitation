@extends('layouts.website')

@section('title', $category->name . ' - Chetan Imitation')

@section('content')

<div class="container-1440 mx-auto section-space">

    <div class="grid grid-cols-12 lg:gap-5">

        <!-- SIDEBAR -->
        <aside class="col-span-12 lg:col-span-4 xl:col-span-3 shrink-0 hidden lg:block border border-[#D5D5D5] rounded-[8px]">

            <!-- Shop By Category -->
            <div class="sidebar-section rounded-lg overflow-hidden">

                <button onclick="toggleSection('cat-section','cat-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Shop By Category</span>
                    <svg id="cat-arrow" class="collapse-arrow open w-5 h-5 text-[#131615]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="cat-section" class="px-5 py-[20px]">
                    @foreach($categories as $cat)
                    <div class="border-b border-[#D5D5D5] py-4">
                        <div class="flex items-center gap-[15px]">
                            <span class="text-[18px] text-[#3D403F]">{{ $cat->name }}</span>
                            <span class="text-[#757575]">({{ $cat->products_count ?? 0 }})</span>
                        </div>
                    </div>
                    @endforeach
                </div>

            </div>

            <!-- Shop By Price -->
            <div class="sidebar-section pb-0 mb-1">
                <button onclick="toggleSection('price-section','price-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Shop By Price</span>
                    <svg id="price-arrow" class="collapse-arrow open w-5 h-5 text-[#131615]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="price-section" class="p-5 py-[25px]">
                    <div class="flex gap-4 mb-6">
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[44px] -translate-y-1/2 text-[20px] text-[#131615]">₹</span>
                            <input id="minPriceInput" type="number" value="0" min="0" max="10000"
                                class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px] text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px] outline-none appearance-none">
                        </div>
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[44px] -translate-y-1/2 text-[20px] text-[#131615]">₹</span>
                            <input id="maxPriceInput" type="number" value="2000" min="0" max="10000"
                                class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px] text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px] outline-none appearance-none">
                        </div>
                    </div>
                </div>
            </div>

        </aside>

        <!-- PRODUCT GRID -->
        <div class="col-span-12 lg:col-span-8 xl:col-span-9 min-w-0">

            <div class="flex justify-end mb-5">
                <div class="relative w-full max-w-[340px]">
                    <select id="sortSelect"
                        class="appearance-none w-full px-5 pr-14 py-4 leading-[20px] border border-[#D5D5D5] rounded-[8px] text-[20px] font-semibold text-[#3D403F] outline-none">
                        <option value="default">Default Sorting</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="newest">Newest First</option>
                        <option value="popular">Most Popular</option>
                    </select>
                    <svg class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="#3D403F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </div>

            <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @forelse($products as $product)
                <div class="group border border-[#D5D5D5] relative cursor-pointer">
                    <div class="relative overflow-hidden">
                        @if($product->sale)
                        <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                            <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                        </div>
                        @endif
                        <img src="{{ $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}"
                            alt="{{ $product->name }}"
                            class="w-full h-[340px] object-cover transition-all duration-700 ease-in-out group-hover:scale-105">
                    </div>
                    <div class="p-4 md:p-[25px]">
                        <h3 class="product-title">{{ $product->name }}</h3>
                        <div class="mt-1 flex items-center gap-1">
                            <span class="text-lg xl:text-[24px] text-[#131615]">₹{{ number_format($product->sale_price, 0) }}</span>
                            @if($product->mrp && $product->mrp > $product->sale_price)
                            <span class="text-sm xl:text-lg text-[#757575] line-through">₹{{ number_format($product->mrp, 0) }}</span>
                            @endif
                        </div>
                        <button class="w-full h-[45px] border border-[#131615] text-lg mt-[30px] hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white transition duration-300">
                            Add to Cart
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-16 text-gray-400">
                    <p class="font-medium">No products found</p>
                    <p class="text-sm mt-1">Try adjusting your filters</p>
                </div>
                @endforelse
            </div>

            @if($products->hasPages())
            <div class="flex items-center justify-center gap-2 sm:gap-3 md:gap-4 mt-8 md:mt-10 flex-wrap">
                {{ $products->links() }}
            </div>
            @endif

        </div>
    </div>
</div>

<script>
    function toggleSection(contentId, arrowId) {
        var content = document.getElementById(contentId);
        var arrow = document.getElementById(arrowId);
        if (content && arrow) {
            content.classList.toggle("hidden");
            arrow.classList.toggle("rotate-180");
        }
    }
</script>

@endsection
