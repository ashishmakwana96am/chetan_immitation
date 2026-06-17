@extends('layouts.website')

@section('title', 'Shop By Category - Chetan Imitation')

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
                    @php $catId = 'cat-' . $cat->id; @endphp
                    <div class="{{ $loop->last ? 'border-b-0 py-5' : 'border-b border-[#D5D5D5] py-4' }} {{ $loop->first ? 'pb-5' : '' }}">
                        <button onclick="toggleCategory('{{ $catId }}-sub','{{ $catId }}-arrow')"
                            class="w-full flex items-center justify-between">
                            <div class="flex items-center gap-[15px]">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="category-checkbox" value="{{ $cat->slug }}" data-category-id="{{ $cat->id }}" {{ $loop->first ? 'checked' : '' }} onchange="applyFilters()">
                                    <span></span>
                                </label>
                                <h3 class="text-[18px] text-[#3D403F]">
                                    {{ $cat->name }}
                                    <span class="text-[#757575]">({{ $cat->products_count }})</span>
                                </h3>
                            </div>
                            @if($cat->subCategories->isNotEmpty())
                            <svg id="{{ $catId }}-arrow" class="w-4 h-4 text-[#131615] transition {{ $loop->first ? 'rotate-180' : '' }} duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            @endif
                        </button>
                        @if($cat->subCategories->isNotEmpty())
                        <div id="{{ $catId }}-sub" class="{{ $loop->first ? '' : 'hidden' }} mt-5 space-y-4 pl-10">
                            @foreach($cat->subCategories as $sub)
                            <label class="flex items-center gap-4 cursor-pointer">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="subcategory-checkbox" value="{{ $sub->slug }}" data-category-id="{{ $cat->id }}" onchange="applyFilters()">
                                    <span></span>
                                </label>
                                <span class="text-[18px] text-[#757575]">{{ $sub->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        @endif
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
                            <div class="absolute right-4 top-[38px] -translate-y-1/2 flex flex-col gap-2">
                                <button type="button" onclick="adjustPrice('min','inc')" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 5L5 1L9 5" stroke="#8A8A8A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button type="button" onclick="adjustPrice('min','dec')" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 1L5 5L9 1" stroke="#8A8A8A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[44px] -translate-y-1/2 text-[20px] text-[#131615]">₹</span>
                            <input id="maxPriceInput" type="number" value="5000" min="0" max="10000"
                                class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px] text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px] outline-none appearance-none">
                            <div class="absolute right-4 top-[38px] -translate-y-1/2 flex flex-col gap-2">
                                <button type="button" onclick="adjustPrice('max','inc')" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 5L5 1L9 5" stroke="#8A8A8A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button type="button" onclick="adjustPrice('max','dec')" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 1L5 5L9 1" stroke="#8A8A8A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-6 flex items-center">
                        <div class="absolute inset-x-0 h-[4px] bg-[#D5D5D5] rounded-full z-[1]"></div>
                        <div id="rangeTrack" class="absolute h-[4px] bg-[#131615] rounded-full z-[2]" style="left:0%;right:50%;"></div>
                        <input id="minRange" type="range" min="0" max="10000" value="0" class="absolute w-full z-[3]" oninput="rangeSlide('min')" />
                        <input id="maxRange" type="range" min="0" max="10000" value="5000" class="absolute w-full z-[4]" oninput="rangeSlide('max')" />
                    </div>
                </div>
            </div>

            <!-- Out of stock -->
            <div class="sidebar-section pb-0 mb-1">
                <button onclick="toggleSection('stock-section','stock-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Out of stock</span>
                    <svg id="stock-arrow" class="collapse-arrow open w-5 h-5 text-[#131615]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="stock-section" class="pb-4">
                    <div class="flex gap-5 pt-[25px] px-5">
                        <button onclick="stockFilter('show',this)"
                            class="flex-1 border border-[#B4771E] rounded-sm py-[13px] text-[#B4771E] text-base md:text-lg leading-[16px] md:leading-[18px] bg-[#B4771E05]">
                            Show
                        </button>
                        <button onclick="stockFilter('hide',this)"
                            class="flex-1 border border-[#757575] rounded-sm py-[13px] text-[#3D403F] text-base md:text-lg leading-[16px] md:leading-[18px]">
                            Hide
                        </button>
                    </div>
                </div>
            </div>

        </aside>

        <!-- PRODUCT GRID -->
        <div class="col-span-12 lg:col-span-8 xl:col-span-9 min-w-0">

            <div class="flex justify-end mb-5">
                <div class="relative w-full max-w-[340px]">
                    <select id="sortSelect" onchange="applyFilters()"
                        class="appearance-none w-full px-5 pr-14 py-4 leading-[20px] border border-[#D5D5D5] rounded-[8px] text-[20px] font-semibold text-[#3D403F] outline-none">
                        <option value="default" {{ request('sort') == 'default' || !request('sort') ? 'selected' : '' }}>Default Sorting</option>
                        <option value="price-low" {{ request('sort') == 'price-low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price-high" {{ request('sort') == 'price-high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
                    </select>
                    <svg class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="#3D403F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </div>

            <div id="productCount" class="text-[#757575] text-base mb-4">
                Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} results
            </div>

            <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @include('website.partials.product-grid-items')
            </div>

            <div id="paginationWrap">
                {{ $products->links('vendor.pagination.tailwind') }}
            </div>

        </div>
    </div>
</div>

@endsection

@section('page-js')
<script>
    let filterTimeout;

    function toggleCategory(contentId, arrowId) {
        const content = document.getElementById(contentId);
        const arrow = document.getElementById(arrowId);
        if (content && arrow) {
            content.classList.toggle("hidden");
            arrow.classList.toggle("rotate-180");
        }
    }

    function toggleSection(contentId, arrowId) {
        const content = document.getElementById(contentId);
        const arrow = document.getElementById(arrowId);
        if (content && arrow) {
            content.classList.toggle("hidden");
            arrow.classList.toggle("rotate-180");
        }
    }

    function rangeSlide(type) {
        const min = parseInt(document.getElementById('minRange').value);
        const max = parseInt(document.getElementById('maxRange').value);
        if (min >= max) {
            if (type === 'min') document.getElementById('minRange').value = max - 100;
            else document.getElementById('maxRange').value = min + 100;
        }
        document.getElementById('minPriceInput').value = document.getElementById('minRange').value;
        document.getElementById('maxPriceInput').value = document.getElementById('maxRange').value;
        const total = 10000;
        const leftPct = (document.getElementById('minRange').value / total) * 100;
        const rightPct = 100 - (document.getElementById('maxRange').value / total) * 100;
        document.getElementById('rangeTrack').style.left = leftPct + '%';
        document.getElementById('rangeTrack').style.right = rightPct + '%';
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 500);
    }

    function adjustPrice(type, dir) {
        const input = document.getElementById(type + 'PriceInput');
        const range = document.getElementById(type + 'Range');
        const step = 100;
        let val = parseInt(input.value) || 0;
        if (dir === 'inc') val = Math.min(val + step, 10000);
        else val = Math.max(val - step, 0);
        const min = parseInt(document.getElementById('minPriceInput').value);
        const max = parseInt(document.getElementById('maxPriceInput').value);
        if (type === 'min' && val >= max) val = max - step;
        if (type === 'max' && val <= min) val = min + step;
        input.value = val;
        range.value = val;
        rangeSlide(type);
    }

    function stockFilter(action, btn) {
        document.querySelectorAll('#stock-section button').forEach(b => {
            b.classList.remove('border-[#B4771E]', 'text-[#B4771E]', 'bg-[#B4771E05]');
            b.classList.add('border-[#757575]', 'text-[#3D403F]');
        });
        btn.classList.remove('border-[#757575]', 'text-[#3D403F]');
        btn.classList.add('border-[#B4771E]', 'text-[#B4771E]', 'bg-[#B4771E05]');
        applyFilters();
    }

    function applyFilters() {
        const params = new URLSearchParams();

        const sort = document.getElementById('sortSelect').value;
        if (sort && sort !== 'default') params.set('sort', sort);

        const cats = [];
        document.querySelectorAll('.category-checkbox:checked').forEach(cb => cats.push(cb.value));
        if (cats.length) params.set('category', cats.join(','));

        const subs = [];
        document.querySelectorAll('.subcategory-checkbox:checked').forEach(cb => subs.push(cb.value));
        if (subs.length) params.set('sub_category', subs.join(','));

        const minP = document.getElementById('minPriceInput').value;
        const maxP = document.getElementById('maxPriceInput').value;
        if (minP > 0) params.set('min_price', minP);
        if (maxP < 10000) params.set('max_price', maxP);

        const url = window.location.pathname + '?' + params.toString();
        window.history.replaceState({}, '', url);

        fetchProducts(1);
    }

    function goToPage(page) {
        fetchProducts(page);
    }

    function fetchProducts(page) {
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);

        document.getElementById('productGrid').innerHTML =
            '<div class="col-span-full text-center py-16"><div class="inline-block w-8 h-8 border-4 border-[#B4771E] border-t-transparent rounded-full animate-spin"></div><p class="mt-3 text-gray-500">Loading...</p></div>';

        fetch(window.location.pathname + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('productGrid').innerHTML = data.html;
            document.getElementById('paginationWrap').innerHTML = data.pagination;
            const countEl = document.getElementById('productCount');
            if (countEl) countEl.textContent = 'Showing ' + data.count + ' results';
        })
        .catch(() => {
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    // Ensure first category checkbox matches sub-category visibility
    document.addEventListener('DOMContentLoaded', function() {
        const firstCatCheck = document.querySelector('.category-checkbox');
        if (firstCatCheck) firstCatCheck.checked = true;
    });
</script>
@endsection
