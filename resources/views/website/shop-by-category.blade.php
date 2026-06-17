@extends('layouts.website')

@section('title', 'Shop By Category - Chetan Imitation')

@section('content')

<section class="relative bg-center bg-no-repeat overflow-hidden"
    style="background-image: url('{{ asset('website/assets/images/about_banner.png') }}'); background-size: 100% 100%;">

    <div class="container-1440">

        <div class="grid grid-cols-1 lg:grid-cols-12 items-center">

            <div class="relative z-10 py-16 lg:py-0 lg:col-span-7 text-center md:text-left">

                <h1 class="font-moglan hero-heading mt-5">
                    Explore Our
                    <br>
                    Jewelry Collections
                </h1>

                <p class="hero-para max-w-[750px]">
                    Discover handcrafted imitation jewelry designed for weddings, festive celebrations, parties, and elegant everyday wear.
                </p>

            </div>

            <div class="relative flex justify-center lg:justify-end lg:col-span-5">
                <img src="{{ asset('website/assets/images/shopby.png') }}" alt="">
            </div>

        </div>

    </div>

</section>

<div class="container-1440 mx-auto section-space">

    <div class="grid grid-cols-12 lg:gap-5">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="fixed lg:static top-0 left-0 w-[320px] lg:w-auto h-screen lg:h-auto bg-white z-[999] overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 col-span-12 lg:col-span-4 xl:col-span-3 shrink-0 border border-[#D5D5D5] rounded-none lg:rounded-[8px]">
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
                    @php
                        $currentSlug = request()->segment(2);
                        $selectedCats = request('category') ? explode(',', request('category')) : ($currentSlug ? [$currentSlug] : []);
                        $isCatChecked = in_array($cat->slug, $selectedCats);
                    @endphp
                    <div class="{{ $loop->last ? 'border-b-0 py-5' : 'border-b border-[#D5D5D5] py-4' }} {{ $loop->first ? 'pb-5' : '' }}">
                        <button onclick="toggleCategory('{{ $catId }}-sub','{{ $catId }}-arrow')"
                            class="w-full flex items-center justify-between">
                            <div class="flex items-center gap-[15px]">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="category-checkbox" value="{{ $cat->slug }}" data-category-id="{{ $cat->id }}" {{ $isCatChecked ? 'checked' : '' }} onchange="applyFilters()">
                                    <span></span>
                                </label>
                                <h3 class="text-[18px] text-[#3D403F]">
                                    {{ $cat->name }}
                                    <span class="text-[#757575]">({{ $cat->products_count }})</span>
                                </h3>
                            </div>
                            @if($cat->subCategories->isNotEmpty())
                            <svg id="{{ $catId }}-arrow" class="w-4 h-4 text-[#131615] transition {{ $isCatChecked ? 'rotate-180' : '' }} duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            @endif
                        </button>
                        @if($cat->subCategories->isNotEmpty())
                        @php $selectedSubs = request('sub_category') ? explode(',', request('sub_category')) : []; @endphp
                        <div id="{{ $catId }}-sub" class="{{ $isCatChecked ? '' : 'hidden' }} mt-5 space-y-4 pl-10">
                            @foreach($cat->subCategories as $sub)
                            <label class="flex items-center gap-4 cursor-pointer">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="subcategory-checkbox" value="{{ $sub->slug }}" data-category-id="{{ $cat->id }}" {{ in_array($sub->slug, $selectedSubs) ? 'checked' : '' }} onchange="applyFilters()">
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
                        <!-- Min Price -->
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[44px] -translate-y-1/2 text-[20px] text-[#131615]">
                                ₹
                            </span>
                            <input id="minPriceInput" type="number" value="0" min="0" max="10000" class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px]
                                    text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('min')">
                            <div class="absolute right-4 top-[38px] -translate-y-1/2 flex flex-col gap-2">
                                <button type="button" onclick="increaseMin()" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 5L5 1L9 5" stroke="#8A8A8A" stroke-width="1.2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button type="button" onclick="decreaseMin()" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 1L5 5L9 1" stroke="#8A8A8A" stroke-width="1.2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- Max Price -->
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[44px] -translate-y-1/2 text-[20px] text-[#131615]">
                                ₹
                            </span>
                            <input id="maxPriceInput" type="number" value="2000" min="0" max="10000" class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px]
                                    text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('max')">
                            <div class="absolute right-4 top-[38px] -translate-y-1/2 flex flex-col gap-2">
                                <button type="button" onclick="increaseMax()" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 5L5 1L9 5" stroke="#8A8A8A" stroke-width="1.2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <button type="button" onclick="decreaseMax()" class="leading-none">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                        <path d="M1 1L5 5L9 1" stroke="#8A8A8A" stroke-width="1.2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Dual range slider (simulated with two overlapping ranges) -->
                    <div class="relative h-6 flex items-center">
                        <!-- Gray Track -->
                        <div class="absolute inset-x-0 h-[4px] bg-[#D5D5D5] rounded-full z-[1]"></div>
                        <!-- Active Track -->
                        <div id="rangeTrack" class="absolute h-[4px] bg-[#131615] rounded-full z-[2]" style="left:0%;right:80%;"></div>
                        <input id="minRange" type="range" min="0" max="10000" value="0" class="absolute w-full z-[3]" oninput="rangeSlide('min')" />
                        <input id="maxRange" type="range" min="0" max="10000" value="2000" class="absolute w-full z-[4]" oninput="rangeSlide('max')" />
                    </div>
                </div>
            </div>

            @if($sizes->isNotEmpty())
            <!-- Size -->
            <div class="sidebar-section pb-0 mb-1">
                <button onclick="toggleSection('size-section','size-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Size</span>
                    <svg id="size-arrow" class="collapse-arrow open w-5 h-5 text-[#131615]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="size-section" class="px-5 py-[25px]">
                    @php $selectedSizes = request('size') ? explode(',', request('size')) : []; @endphp
                    <ul>
                        @foreach($sizes as $sizeVal)
                        @php $isLast = $loop->last; @endphp
                        <li class="{{ $isLast ? '' : 'border-b border-[#D5D5D5]' }}">
                            <label class="flex items-center gap-5 {{ $loop->first ? 'pb-[15px]' : 'pt-[15px] pb-[15px]' }} cursor-pointer">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="size-checkbox" value="{{ $sizeVal->value }}" {{ in_array($sizeVal->value, $selectedSizes) ? 'checked' : '' }} onchange="applyFilters()">
                                    <span></span>
                                </label>
                                <span class="text-[20px] font-normal text-[#444444]">{{ $sizeVal->value }}</span>
                            </label>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

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
                            class="stock-btn flex-1 rounded-sm py-[13px] text-base md:text-lg leading-[16px] md:leading-[18px] bg-[#B4771E] text-white">
                            Show
                        </button>
                        <button onclick="stockFilter('hide',this)"
                            class="stock-btn flex-1 rounded-sm py-[13px] text-base md:text-lg leading-[16px] md:leading-[18px] border border-[#757575] text-[#3D403F]">
                            Hide
                        </button>
                    </div>
                </div>
            </div>

        </aside>

        <!-- Overlay for mobile sidebar -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

        <!-- PRODUCT GRID -->
        <div class="col-span-12 lg:col-span-8 xl:col-span-9 min-w-0">

            <div class="flex gap-4 justify-between lg:justify-end mb-5">
                <button id="filterBtn" class="px-3 py-3 lg:hidden col-span-full mb-4 border border-[#B4771E] text-[#B4771E] py-3 rounded-md text-lg font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </button>
                <div class="relative w-full max-w-[340px]">
                    <select id="sortSelect" onchange="applyFilters()"
                        class="appearance-none w-full px-5 pr-14 py-3 sm:leading-[20px] border border-[#D5D5D5] rounded-[8px] text-base font-semibold text-[#3D403F] outline-none">
                        <option value="default" {{ request('sort') == 'default' || !request('sort') ? 'selected' : '' }}>Default Sorting</option>
                        <option value="price-low" {{ request('sort') == 'price-low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price-high" {{ request('sort') == 'price-high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
                    </select>
                    <svg class="absolute -translate-y-1/2 top-[56%] lg:inset-y-0 lg:top-[23%] lg:-translate-y-0 right-5 flex items-center pointer-events-none h-auto" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="#3D403F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
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

    function syncFromInput(type) {
        const input = document.getElementById(type + 'PriceInput');
        const range = document.getElementById(type + 'Range');
        let val = parseInt(input.value) || 0;
        val = Math.max(0, Math.min(10000, val));
        const min = parseInt(document.getElementById('minPriceInput').value) || 0;
        const max = parseInt(document.getElementById('maxPriceInput').value) || 10000;
        if (type === 'min' && val >= max) val = max - 100;
        if (type === 'max' && val <= min) val = min + 100;
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function increaseMin() {
        const input = document.getElementById('minPriceInput');
        const range = document.getElementById('minRange');
        let val = (parseInt(input.value) || 0) + 100;
        const max = parseInt(document.getElementById('maxPriceInput').value) || 10000;
        if (val >= max) val = max - 100;
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function decreaseMin() {
        const input = document.getElementById('minPriceInput');
        const range = document.getElementById('minRange');
        let val = (parseInt(input.value) || 0) - 100;
        if (val < 0) val = 0;
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function increaseMax() {
        const input = document.getElementById('maxPriceInput');
        const range = document.getElementById('maxRange');
        let val = (parseInt(input.value) || 0) + 100;
        if (val > 10000) val = 10000;
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function decreaseMax() {
        const input = document.getElementById('maxPriceInput');
        const range = document.getElementById('maxRange');
        let val = (parseInt(input.value) || 0) - 100;
        const min = parseInt(document.getElementById('minPriceInput').value) || 0;
        if (val <= min) val = min + 100;
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function updateRangeTrack() {
        const min = parseInt(document.getElementById('minRange').value);
        const max = parseInt(document.getElementById('maxRange').value);
        const total = 10000;
        const leftPct = (min / total) * 100;
        const rightPct = 100 - (max / total) * 100;
        document.getElementById('rangeTrack').style.left = leftPct + '%';
        document.getElementById('rangeTrack').style.right = rightPct + '%';
    }

    function rangeSlide(type) {
        const minRange = document.getElementById('minRange');
        const maxRange = document.getElementById('maxRange');
        let min = parseInt(minRange.value);
        let max = parseInt(maxRange.value);
        if (min >= max) {
            if (type === 'min') { min = max - 100; minRange.value = min; }
            else { max = min + 100; maxRange.value = max; }
        }
        document.getElementById('minPriceInput').value = min;
        document.getElementById('maxPriceInput').value = max;
        updateRangeTrack();
        applyFilters();
    }

    let stockState = 'show';

    function stockFilter(action, btn) {
        stockState = action;
        document.querySelectorAll('.stock-btn').forEach(b => {
            b.classList.remove('bg-[#B4771E]', 'text-white', 'border', 'border-[#B4771E]', 'border-[#757575]', 'text-[#3D403F]');
            if (b === btn) {
                b.classList.add('bg-[#B4771E]', 'text-white');
            } else {
                b.classList.add('border', 'border-[#757575]', 'text-[#3D403F]');
            }
        });
        applyFilters();
    }

    function buildQueryString() {
        const parts = [];

        const sort = document.getElementById('sortSelect').value;
        if (sort && sort !== 'default') parts.push('sort=' + sort);

        const cats = [];
        document.querySelectorAll('.category-checkbox:checked').forEach(cb => cats.push(cb.value));
        const uniqueCats = [...new Set(cats)];
        if (uniqueCats.length) parts.push('category=' + uniqueCats.join(','));

        const subs = [];
        document.querySelectorAll('.subcategory-checkbox:checked').forEach(cb => subs.push(cb.value));
        const uniqueSubs = [...new Set(subs)];
        if (uniqueSubs.length) parts.push('sub_category=' + uniqueSubs.join(','));

        const minP = document.getElementById('minPriceInput').value;
        const maxP = document.getElementById('maxPriceInput').value;
        if (parseInt(minP) > 0) parts.push('min_price=' + minP);
        if (parseInt(maxP) < 10000) parts.push('max_price=' + maxP);

        const sizes = [];
        document.querySelectorAll('.size-checkbox:checked').forEach(cb => sizes.push(cb.value));
        if (sizes.length) parts.push('size=' + sizes.join(','));

        if (stockState === 'hide') parts.push('stock=hide');

        return parts.join('&');
    }

    function applyFilters() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function () {
            const qs = buildQueryString();
            const url = '/category' + (qs ? '?' + qs : '');
            window.history.replaceState({}, '', url);
            fetchProducts(1);
        }, 250);
    }

    function goToPage(page) {
        fetchProducts(page);
    }

    function fetchProducts(page) {
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);

        document.getElementById('productGrid').innerHTML =
            '<div class="col-span-full text-center py-16"><div class="inline-block w-8 h-8 border-4 border-[#B4771E] border-t-transparent rounded-full animate-spin"></div><p class="mt-3 text-gray-500">Loading...</p></div>';

        const qs = params.toString();
        const url = '/category' + (qs ? '?' + qs : '');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('productGrid').innerHTML = data.html;
            document.getElementById('paginationWrap').innerHTML = data.pagination;
            syncCheckboxesFromUrl();
        })
        .catch(() => {
            window.location.href = url;
        });
    }

    function getCategoryFromPath() {
        const parts = window.location.pathname.replace(/\/+$/, '').split('/');
        return parts.length > 0 ? parts[parts.length - 1] : null;
    }

    function syncCheckboxesFromUrl() {
        const params = new URLSearchParams(window.location.search);

        let catList = [];
        const catParam = params.get('category');
        if (catParam) {
            catList = catParam.split(',');
        } else {
            const pathCat = getCategoryFromPath();
            if (pathCat && pathCat !== 'category') {
                catList = [pathCat];
            }
        }

        document.querySelectorAll('.category-checkbox').forEach(cb => {
            cb.checked = catList.includes(cb.value);
            const catId = cb.dataset.categoryId;
            const subDiv = document.getElementById('cat-' + catId + '-sub');
            const arrow = document.getElementById('cat-' + catId + '-arrow');
            if (subDiv) {
                if (cb.checked) {
                    subDiv.classList.remove('hidden');
                    if (arrow) arrow.classList.add('rotate-180');
                } else {
                    subDiv.classList.add('hidden');
                    if (arrow) arrow.classList.remove('rotate-180');
                }
            }
        });

        const subs = params.get('sub_category');
        const subList = subs ? subs.split(',') : [];
        document.querySelectorAll('.subcategory-checkbox').forEach(cb => {
            cb.checked = subList.includes(cb.value);
        });

        const sizeParam = params.get('size');
        const sizeList = sizeParam ? sizeParam.split(',') : [];
        document.querySelectorAll('.size-checkbox').forEach(cb => {
            cb.checked = sizeList.includes(cb.value);
        });
    }

    function initStockState() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('stock') === 'hide') {
            stockState = 'hide';
            document.querySelectorAll('.stock-btn').forEach(b => {
                b.classList.remove('bg-[#B4771E]', 'text-white', 'border', 'border-[#B4771E]', 'border-[#757575]', 'text-[#3D403F]');
            });
            const hideBtn = document.querySelectorAll('.stock-btn')[1];
            if (hideBtn) {
                hideBtn.classList.add('bg-[#B4771E]', 'text-white');
                document.querySelectorAll('.stock-btn')[0].classList.add('border', 'border-[#757575]', 'text-[#3D403F]');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        syncCheckboxesFromUrl();
        initStockState();

        const sidebar = document.querySelector('aside');
        const filterBtn = document.getElementById('filterBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        if (filterBtn && sidebar && sidebarOverlay) {
            filterBtn.addEventListener('click', function () {
                sidebar.classList.remove('hidden');
                sidebarOverlay.classList.remove('hidden');
            });
            sidebarOverlay.addEventListener('click', function () {
                sidebar.classList.add('hidden');
                sidebarOverlay.classList.add('hidden');
            });
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        const sidebar = document.getElementById("sidebar");
        const filterBtn = document.getElementById("filterBtn");
        const sidebarOverlay = document.getElementById("sidebarOverlay");
        filterBtn.addEventListener("click", function () {
            sidebar.classList.remove("-translate-x-full");
            sidebarOverlay.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        });
        sidebarOverlay.addEventListener("click", function () {
            sidebar.classList.add("-translate-x-full");
            sidebarOverlay.classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        });
    
        window.addEventListener("resize", function () {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove("-translate-x-full");
                sidebarOverlay.classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
            }
        });
});
</script>
@endsection
