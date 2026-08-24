@extends('layouts.website')

@section('title', 'Shop - Chetan Imitation')

@section('content')

<section class="relative bg-center bg-no-repeat overflow-hidden"
    style="background-image: url('{{ asset('website/assets/images/about_banner.png') }}'); background-size: 100% 100%;">

    <div class="container-1440">

        <div class="grid grid-cols-1 lg:grid-cols-12 items-center">

            <div class="relative z-10 py-6 sm:py-10 lg:py-0 lg:col-span-7 text-center md:text-left">

                <h1 class="font-moglan hero-heading">
                    Explore Our
                    <br>
                    Jewellery Collections
                </h1>

                <p class="hero-para max-w-[750px]">
                    Discover handcrafted imitation jewellery designed for weddings, festive celebrations, parties, and elegant everyday wear.
                </p>

            </div>

            <div class="hidden lg:flex lg:justify-end lg:col-span-5">
                <img src="{{ asset('website/assets/images/shopby.png') }}" alt="" class="hidden lg:block">
            </div>

        </div>

    </div>

</section>

<div class="container-1440 mx-auto pt-4 md:pt-8 pb-[60px] md:pb-[80px] lg:pb-[100px]">

    <div class="grid grid-cols-12 lg:gap-5">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="fixed lg:static top-0 left-0 w-[320px] lg:w-auto h-screen lg:h-fit bg-white z-[999] overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 col-span-12 lg:col-span-4 xl:col-span-3 shrink-0 border border-[#D5D5D5] rounded-none lg:rounded-[8px]">
            <!-- Shop By Category -->
            <div class="sidebar-section overflow-hidden">

                <button onclick="toggleSection('cat-section','cat-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-3 2xl:px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Categories</span>
                    <svg id="cat-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="cat-section" class="px-3 2xl:px-5 py-[20px]">
                    @foreach($categories as $cat)
                    @php $catId = 'cat-' . $cat->id; @endphp
                    @php
                        $currentSlug = request()->segment(2);
                        $sessionFilters = session('shop_filters', []);
                        $selectedCats = !empty($sessionFilters['category']) ? explode(',', $sessionFilters['category']) : ($currentSlug ? [$currentSlug] : []);
                        $selectedSubs = !empty($sessionFilters['sub_category']) ? explode(',', $sessionFilters['sub_category']) : [];
                        $catSubSlugs = $cat->subCategories->pluck('slug')->all();
                        $selectedSubsInCat = array_values(array_intersect($selectedSubs, $catSubSlugs));
                        $allSubsSelected = empty($catSubSlugs) || empty($selectedSubs) || count($selectedSubsInCat) === count($catSubSlugs);
                        $isCatChecked = in_array($cat->slug, $selectedCats) && $allSubsSelected;
                        $shouldSelectAllSubs = $isCatChecked && empty($selectedSubs);
                        $isCatOpen = $isCatChecked || !empty($selectedSubsInCat);
                    @endphp
                    <div class="{{ $loop->last ? 'border-b-0 py-5' : 'border-b border-[#D5D5D5] py-3 2xl:py-4' }} {{ $loop->first ? 'pb-5' : '' }}">
                        <div class="w-full flex items-center justify-between gap-2">
                            <label class="flex items-center gap-[15px] min-w-0 flex-1 cursor-pointer select-none">
                                <span class="custom-checkbox shrink-0">
                                    <input type="checkbox" class="category-checkbox" value="{{ $cat->slug }}" data-category-id="{{ $cat->id }}" {{ $isCatChecked ? 'checked' : '' }} onchange="handleCategoryFilterChange(this)">
                                    <span></span>
                                </span>
                                <h3 class="text-base 2xl:text-[18px] text-[#3D403F]">
                                    {{ $cat->name }}
                                    @if($cat->products_count > 0)
                                        <span class="text-[#757575]">({{ $cat->products_count }})</span>
                                    @endif
                                </h3>
                            </label>
                            @if($cat->subCategories->isNotEmpty())
                            <button type="button" onclick="toggleCategory('{{ $catId }}')" class="shrink-0 p-1" aria-label="Toggle subcategories">
                            <svg id="{{ $catId }}-arrow" class="w-4 h-4 text-[#131615] transition-transform duration-300" style="transform: rotate({{ $isCatOpen ? '180deg' : '0deg' }});"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            </button>
                            @endif
                        </div>
                        @if($cat->subCategories->isNotEmpty())
                        <div id="{{ $catId }}-sub" class="{{ $isCatOpen ? '' : 'hidden' }} mt-5 space-y-4 pl-8 2xl:pl-10">
                            @foreach($cat->subCategories as $sub)
                            <label class="flex items-center gap-4 cursor-pointer select-none">
                                <span class="custom-checkbox shrink-0">
                                    <input type="checkbox" class="subcategory-checkbox" value="{{ $sub->slug }}" data-category-id="{{ $cat->id }}" {{ ($shouldSelectAllSubs || in_array($sub->slug, $selectedSubsInCat)) ? 'checked' : '' }} onchange="handleSubcategoryFilterChange(this)">
                                    <span></span>
                                </span>
                                <span class="text-base 2xl:text-[18px] text-[#757575]">{{ $sub->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Shop By Collection -->
            @if(isset($collections) && count($collections) > 0)
            <div class="sidebar-section overflow-hidden">
                <button onclick="toggleSection('col-section','col-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-3 2xl:px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Collections</span>
                    <svg id="col-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="col-section" class="px-3 2xl:px-5 py-[20px]">
                    @foreach($collections as $col)
                    @php
                        $sessionFilters = session('shop_filters', []);
                        $selectedCols = !empty($sessionFilters['collection']) ? explode(',', $sessionFilters['collection']) : (!empty(request('collection')) ? [request('collection')] : []);
                        $isColChecked = in_array((string)$col->id, $selectedCols, true) || in_array((string)$col->short_name, $selectedCols, true) || in_array((string)$col->name, $selectedCols, true);
                        $colValue = !empty($col->short_name) ? $col->short_name : (string) $col->id;
                    @endphp
                    <div class="{{ $loop->last ? 'border-b-0 py-3' : 'border-b border-[#D5D5D5] py-3' }}">
                        <label class="flex items-center gap-[15px] cursor-pointer select-none">
                            <span class="custom-checkbox shrink-0">
                                <input type="checkbox" class="collection-checkbox" value="{{ $colValue }}" {{ $isColChecked ? 'checked' : '' }} onchange="handleCollectionFilterChange(this)">
                                <span></span>
                            </span>
                            <h3 class="text-base 2xl:text-[18px] text-[#3D403F]">
                                {{ $col->display_name }}
                                @if(isset($col->products_count) && $col->products_count > 0)
                                    <span class="text-[#757575]">({{ $col->products_count }})</span>
                                @endif
                            </h3>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Shop By Price -->
            <div class="sidebar-section pb-0">
                <button onclick="toggleSection('price-section','price-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-3 2xl:px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Price Range</span>
                    <svg id="price-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="price-section" class="px-3 2xl:px-5 py-5 2xl:py-[25px]">
                    <div class="flex gap-4 mb-6">
                        <!-- Min Price -->
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-[34px] 2xl:top-[39px] -translate-y-1/2 text-sm 2xl:text-[20px] text-[#131615]">
                                ₹
                            </span>
                            <input id="minPriceInput" type="number" value="{{ $selectedMinPrice }}" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" class="w-full h-[48px] 2xl:h-[56px] border border-[#D5D5D5] rounded-[2px] text-sm 2xl:text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('min')" onblur="normalizePriceInput('min')" onkeydown="if(event.key==='Enter') this.blur()">
                            <div class="absolute right-4 top-[34px] 2xl:top-[38px] -translate-y-1/2 flex flex-col gap-2">
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
                            <span class="absolute left-4 top-[34px] 2xl:top-[39px] -translate-y-1/2 text-sm 2xl:text-[20px] text-[#131615]">
                                ₹
                            </span>
                            <input id="maxPriceInput" type="number" value="{{ $selectedMaxPrice }}" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" class="w-full h-[48px] 2xl:h-[56px] border border-[#D5D5D5] rounded-[2px] text-sm 2xl:text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('max')" onblur="normalizePriceInput('max')" onkeydown="if(event.key==='Enter') this.blur()">
                            <div class="absolute right-4 top-[34px] 2xl:top-[38px] -translate-y-1/2 flex flex-col gap-2">
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
                        <div id="rangeTrack" class="absolute h-[4px] bg-[#131615] rounded-full z-[2]" style="left:0%;right:90%;"></div>
                        <input id="minRange" type="range" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" value="{{ $selectedMinPrice }}" class="absolute w-full z-[3]" oninput="rangeSlide('min')" />
                        <input id="maxRange" type="range" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" value="{{ $selectedMaxPrice }}" class="absolute w-full z-[4]" oninput="rangeSlide('max')" />
                    </div>
                </div>
            </div>

            @if($sizes->isNotEmpty())
            <!-- Size -->
            <!-- <div class="sidebar-section pb-0 mb-1">
                <button onclick="toggleSection('size-section','size-arrow')"
                    class="flex items-center justify-between w-full pb-4 2xl:pb-[17px] pt-5 2xl:pt-[22px] px-3 2xl:px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Size</span>
                    <svg id="size-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="size-section" class="px-5 py-[25px]">
                    @php
                        $sessionFilters = session('shop_filters', []);
                        $selectedSizes = !empty($sessionFilters['size']) ? explode(',', $sessionFilters['size']) : [];
                    @endphp
                    <ul>
                        @foreach($sizes as $sizeVal)
                        @php $isLast = $loop->last; @endphp
                        <li class="{{ $isLast ? '' : 'border-b border-[#D5D5D5]' }}">
                            <label class="flex items-center gap-5 {{ $loop->first ? 'pb-[15px]' : 'pt-[10px] 2xl:pt-[15px] pb-[10px] 2xl:pb-[15px]' }} cursor-pointer">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="size-checkbox" value="{{ $sizeVal->value }}" {{ in_array($sizeVal->value, $selectedSizes) ? 'checked' : '' }} onchange="priceFilterTouched = false; applyFilters()">
                                    <span></span>
                                </label>
                                <span class="text-base 2xl:text-lg font-normal text-[#444444]">{{ $sizeVal->value }}</span>
                            </label>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div> -->
            @endif


        </aside>

        <!-- Overlay for mobile sidebar -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

        <!-- PRODUCT GRID -->
        <div class="col-span-12 lg:col-span-8 xl:col-span-9 min-w-0">

            <div class="flex flex-nowrap gap-3 items-center justify-between lg:justify-end mb-4 sm:mb-5">
                <button id="filterBtn" class="flex items-center justify-center px-2 sm:px-4 h-[42px] lg:hidden border border-[#B4771E] text-[#B4771E] rounded-[8px] transition duration-300 hover:bg-[#B4771E]/5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </button>
                <div class="flex gap-3">
                    <button id="resetFiltersBtn" type="button" onclick="resetAllFilters()" class="hidden items-center justify-center gap-2 px-3 h-[42px] border border-[#D5D5D5] rounded-[8px] text-base font-semibold text-[#3D403F] hover:bg-[#B4771E] transition duration-300 group hover:text-white group whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-[#B4771E]  group-hover:text-white transition-colors duration-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                       <span class="hidden sm:block"> Reset Filters</span>
                    </button>
    
                    <div class="relative w-full max-w-[200px] sm:max-w-[230px]">
                        @php $sessionSort = session('shop_filters.sort'); @endphp
                        <select id="sortSelect" onchange="applyFilters()"
                            class="appearance-none w-full px-2 sm:px-5 pr-8 sm:pr-12 h-[42px] border border-[#D5D5D5] rounded-[8px] text-base font-semibold text-[#3D403F] outline-none">
                            <option value="default" {{ $sessionSort == 'default' || !$sessionSort ? 'selected' : '' }}>Sorting</option>
                            <option value="price-low" {{ $sessionSort == 'price-low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price-high" {{ $sessionSort == 'price-high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="newest" {{ $sessionSort == 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="popular" {{ $sessionSort == 'popular' ? 'selected' : '' }}>Most Popular</option>
                        </select>
                         <div class="pointer-events-none absolute top-1/2 -translate-y-[50%] right-3 w-5 h-5">
                            <svg class="w-4 h-4 text-[#131615]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                         </div>
                         
                    </div>
                </div>
            </div>

            <div class="mb-3 flex justify-end sm:hidden" data-product-view-toggle>
                <div class="inline-flex border border-[#D5D5D5] rounded-md overflow-hidden bg-white shadow-sm">
                    <button type="button" data-grid-view-toggle="single" class="w-8 h-8 flex items-center justify-center border-r border-[#D5D5D5] bg-white text-[#131615]" aria-label="Single column view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="1.5"></rect>
                        </svg>
                    </button>
                    <button type="button" data-grid-view-toggle="dual" class="w-8 h-8 flex items-center justify-center bg-[#131615] text-white" aria-label="Two column view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                            <rect x="14" y="3" width="7" height="4" rx="1"></rect>
                            <rect x="14" y="11" width="7" height="10" rx="1"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                        </svg>
                    </button>
                </div>
            </div>
            <div id="productGrid" data-product-grid data-view="dual" class="grid grid-cols-2 sm:grid-cols-2 2xl:grid-cols-3 gap-2 sm:gap-5">
                @include('website.partials.product-grid-items')
            </div>

            <div id="infiniteScrollLoader" class="w-full text-center py-8 hidden col-span-full">
                <div class="inline-block w-8 h-8 border-4 border-[#B4771E] border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-2 text-sm text-[#757575] font-medium">Loading more products...</p>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page-js')
<script>
    const CATEGORY_BASE_URL = '{{ url('/shop') }}';
    let catalogMinPrice = {{ $catalogMinPrice }};
    let catalogMaxPrice = {{ $catalogMaxPrice }};
    let filterTimeout;
    let priceFilterTouched = {{ $hasPriceFilter ? 'true' : 'false' }};
    let currentPage = {{ $products->currentPage() }};
    let hasMorePages = {{ $products->hasMorePages() ? 'true' : 'false' }};
    let isLoadingMore = false;

    function getPriceStep() {
        const range = catalogMaxPrice - catalogMinPrice;
        return Math.max(1, Math.round(range / 20) || 1);
    }

    function clampPriceValue(value, fallback) {
        if (value === null || value === undefined) return fallback;
        const cleanVal = String(value).replace(/,/g, '').trim();
        const parsed = parseInt(cleanVal, 10);
        if (Number.isNaN(parsed)) return fallback;
        return Math.max(catalogMinPrice, Math.min(catalogMaxPrice, parsed));
    }

    function setPriceInputs(min, max) {
        document.getElementById('minPriceInput').value = min;
        document.getElementById('maxPriceInput').value = max;
        document.getElementById('minRange').value = min;
        document.getElementById('maxRange').value = max;
    }

    function syncPriceInputBounds() {
        ['minPriceInput', 'maxPriceInput', 'minRange', 'maxRange'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.min = catalogMinPrice;
            el.max = catalogMaxPrice;
        });
    }

    function isFullCatalogPriceRange(min, max) {
        return min <= catalogMinPrice && max >= catalogMaxPrice;
    }

    function updateCatalogPriceRange(min, max) {
        catalogMinPrice = min;
        catalogMaxPrice = max;
        syncPriceInputBounds();

        if (!priceFilterTouched) {
            setPriceInputs(catalogMinPrice, catalogMaxPrice);
        }

        updateRangeTrack();
    }

    function setChevronOpenState(arrow, open) {
        if (!arrow) return;

        arrow.classList.add('transition-transform', 'duration-300');
        arrow.classList.remove('rotate-180');
        arrow.style.rotate = '';
        arrow.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
        arrow.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function setCategoryDropdownState(catId, open) {
        const content = document.getElementById(catId + '-sub');
        const arrow = document.getElementById(catId + '-arrow');
        if (!content) return;

        content.classList.toggle('hidden', !open);
        content.dataset.open = open ? 'true' : 'false';
        setChevronOpenState(arrow, open);
    }

    function isCategoryDropdownOpen(catId) {
        const content = document.getElementById(catId + '-sub');
        return content ? !content.classList.contains('hidden') : false;
    }

    function categoryHasSelectedFilters(catId) {
        const categoryId = catId.replace('cat-', '');
        const category = document.querySelector('.category-checkbox[data-category-id="' + categoryId + '"]');
        const subcategory = document.querySelector('.subcategory-checkbox[data-category-id="' + categoryId + '"]:checked');
        return Boolean((category && category.checked) || subcategory);
    }

    function syncCategoryDropdown(catId, open) {
        const shouldOpen = typeof open === 'boolean' ? open : categoryHasSelectedFilters(catId);
        setCategoryDropdownState(catId, shouldOpen);
    }

    function toggleCategory(catId) {
        syncCategoryDropdown(catId, !isCategoryDropdownOpen(catId));
    }

    function syncCategoryDropdownFromInput(el) {
        syncCategoryDropdown('cat-' + el.dataset.categoryId);
    }

    function syncSubcategoriesWhenCategoryChecked(categoryCheckbox) {
        const targetState = categoryCheckbox.checked;
        document.querySelectorAll('.subcategory-checkbox[data-category-id="' + categoryCheckbox.dataset.categoryId + '"]').forEach(cb => {
            cb.checked = targetState;
        });
    }

    function handleCategoryFilterChange(categoryCheckbox) {
        syncSubcategoriesWhenCategoryChecked(categoryCheckbox);
        syncCategoryDropdown('cat-' + categoryCheckbox.dataset.categoryId);
        clearHeaderSearch();
        applyFilters();
    }

    function handleSubcategoryFilterChange(subcategoryCheckbox) {
        const parentCatId = subcategoryCheckbox.dataset.categoryId;
        const parentCheckbox = document.querySelector('.category-checkbox[data-category-id="' + parentCatId + '"]');

        if (!subcategoryCheckbox.checked) {
            if (parentCheckbox) {
                parentCheckbox.checked = false;
            }
        } else {
            if (parentCheckbox) {
                const allSubs = document.querySelectorAll('.subcategory-checkbox[data-category-id="' + parentCatId + '"]');
                const checkedSubs = document.querySelectorAll('.subcategory-checkbox[data-category-id="' + parentCatId + '"]:checked');
                if (allSubs.length === checkedSubs.length) {
                    parentCheckbox.checked = true;
                }
            }
        }

        syncCategoryDropdown('cat-' + parentCatId);
        clearHeaderSearch();
        applyFilters();
    }

    function clearHeaderSearch() {
        const headerSearchEl = document.getElementById('headerSearch');
        const clearIcon = document.getElementById('clearHeaderSearch');
        if (headerSearchEl) {
            headerSearchEl.value = '';
            if (clearIcon) {
                clearIcon.classList.add('hidden');
            }
        }
    }

    function setSectionDropdownState(contentId, arrowId, open) {
        const content = document.getElementById(contentId);
        const arrow = document.getElementById(arrowId);
        if (!content) return;

        content.classList.toggle("hidden", !open);
        setChevronOpenState(arrow, open);
    }

    function toggleSection(contentId, arrowId) {
        const content = document.getElementById(contentId);
        if (!content) return;

        setSectionDropdownState(contentId, arrowId, content.classList.contains("hidden"));
    }

    function syncSectionDropdownState(contentId, arrowId) {
        const content = document.getElementById(contentId);
        if (!content) return;

        setSectionDropdownState(contentId, arrowId, !content.classList.contains("hidden"));
    }

    function initSidebarSectionDropdowns() {
        syncSectionDropdownState('cat-section', 'cat-arrow');
        syncSectionDropdownState('price-section', 'price-arrow');
        syncSectionDropdownState('size-section', 'size-arrow');
    }

    function syncFromInput(type) {
        priceFilterTouched = true;
        const input = document.getElementById(type + 'PriceInput');
        const range = document.getElementById(type + 'Range');
        
        const cleanVal = String(input.value || '').replace(/,/g, '').trim();
        let val = parseInt(cleanVal, 10);
        if (!isNaN(val)) {
            range.value = val;
            updateRangeTrack();
            applyFilters();
        }
    }

    function normalizePriceInput(type) {
        const input = document.getElementById(type + 'PriceInput');
        const range = document.getElementById(type + 'Range');
        const step = getPriceStep();
        let min = clampPriceValue(document.getElementById('minPriceInput').value, catalogMinPrice);
        let max = clampPriceValue(document.getElementById('maxPriceInput').value, catalogMaxPrice);
        let val = clampPriceValue(input.value, type === 'min' ? catalogMinPrice : catalogMaxPrice);

        if (type === 'min' && val >= max) val = Math.max(catalogMinPrice, max - step);
        if (type === 'max' && val <= min) val = Math.min(catalogMaxPrice, min + step);

        input.value = val;
        range.value = val;
        priceFilterTouched = !isFullCatalogPriceRange(
            type === 'min' ? val : min,
            type === 'max' ? val : max
        );
        updateRangeTrack();
        applyFilters();
    }

    function increaseMin() {
        priceFilterTouched = true;
        const input = document.getElementById('minPriceInput');
        const range = document.getElementById('minRange');
        const step = getPriceStep();
        let val = clampPriceValue(clampPriceValue(input.value, catalogMinPrice) + step, catalogMinPrice);
        const max = clampPriceValue(document.getElementById('maxPriceInput').value, catalogMaxPrice);
        if (val >= max) val = Math.max(catalogMinPrice, max - step);
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function decreaseMin() {
        priceFilterTouched = true;
        const input = document.getElementById('minPriceInput');
        const range = document.getElementById('minRange');
        const step = getPriceStep();
        let val = clampPriceValue(clampPriceValue(input.value, catalogMinPrice) - step, catalogMinPrice);
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function increaseMax() {
        priceFilterTouched = true;
        const input = document.getElementById('maxPriceInput');
        const range = document.getElementById('maxRange');
        const step = getPriceStep();
        let val = clampPriceValue(clampPriceValue(input.value, catalogMaxPrice) + step, catalogMaxPrice);
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function decreaseMax() {
        priceFilterTouched = true;
        const input = document.getElementById('maxPriceInput');
        const range = document.getElementById('maxRange');
        const step = getPriceStep();
        let val = clampPriceValue(clampPriceValue(input.value, catalogMaxPrice) - step, catalogMaxPrice);
        const min = clampPriceValue(document.getElementById('minPriceInput').value, catalogMinPrice);
        if (val <= min) val = Math.min(catalogMaxPrice, min + step);
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function updateRangeTrack() {
        const minVal = String(document.getElementById('minRange').value || catalogMinPrice).replace(/,/g, '');
        const maxVal = String(document.getElementById('maxRange').value || catalogMaxPrice).replace(/,/g, '');
        const min = parseInt(minVal, 10) || catalogMinPrice;
        const max = parseInt(maxVal, 10) || catalogMaxPrice;
        const total = catalogMaxPrice - catalogMinPrice || 1;
        const leftPct = ((min - catalogMinPrice) / total) * 100;
        const rightPct = 100 - ((max - catalogMinPrice) / total) * 100;
        document.getElementById('rangeTrack').style.left = leftPct + '%';
        document.getElementById('rangeTrack').style.right = rightPct + '%';
    }

    function rangeSlide(type) {
        priceFilterTouched = true;
        const minRange = document.getElementById('minRange');
        const maxRange = document.getElementById('maxRange');
        const step = getPriceStep();
        let min = parseInt(minRange.value);
        let max = parseInt(maxRange.value);
        if (min >= max) {
            if (type === 'min') { min = Math.max(catalogMinPrice, max - step); minRange.value = min; }
            else { max = Math.min(catalogMaxPrice, min + step); maxRange.value = max; }
        }
        document.getElementById('minPriceInput').value = min;
        document.getElementById('maxPriceInput').value = max;

        if (isFullCatalogPriceRange(min, max)) {
            priceFilterTouched = false;
        }

        updateRangeTrack();
        applyFilters();
    }

    let stockState = 'show';

    function applyProductGridView(view) {
        const grid = document.getElementById('productGrid');
        if (!grid) return;

        const normalizedView = (view === 'dual') ? 'dual' : 'single';
        const singleBtn = document.querySelector('[data-grid-view-toggle="single"]');
        const dualBtn = document.querySelector('[data-grid-view-toggle="dual"]');

        grid.dataset.view = normalizedView;
        grid.classList.remove('grid-cols-1', 'grid-cols-2');
        grid.classList.add(normalizedView === 'single' ? 'grid-cols-1' : 'grid-cols-2');

        if (singleBtn) {
            singleBtn.classList.toggle('bg-[#131615]', normalizedView === 'single');
            singleBtn.classList.toggle('text-white', normalizedView === 'single');
            singleBtn.classList.toggle('bg-white', normalizedView !== 'single');
            singleBtn.classList.toggle('text-[#131615]', normalizedView !== 'single');
        }

        if (dualBtn) {
            dualBtn.classList.toggle('bg-[#131615]', normalizedView === 'dual');
            dualBtn.classList.toggle('text-white', normalizedView === 'dual');
            dualBtn.classList.toggle('bg-white', normalizedView !== 'dual');
            dualBtn.classList.toggle('text-[#131615]', normalizedView !== 'dual');
        }
    }

    function getFilterData() {
        const cats = [];
        document.querySelectorAll('.category-checkbox:checked').forEach(cb => cats.push(cb.value));
        const uniqueCats = [...new Set(cats)];

        const subs = [];
        document.querySelectorAll('.subcategory-checkbox:checked').forEach(cb => {
            const parentCatId = cb.dataset.categoryId;
            const parentIsChecked = document.querySelector(
                '.category-checkbox[data-category-id="' + parentCatId + '"]:checked'
            );
            if (!parentIsChecked) {
                subs.push(cb.value);
            }
        });
        const uniqueSubs = [...new Set(subs)];

        const cols = [];
        document.querySelectorAll('.collection-checkbox:checked').forEach(cb => cols.push(cb.value));
        const uniqueCols = [...new Set(cols)];

        const minPrice = document.getElementById('minPriceInput').value;
        const maxPrice = document.getElementById('maxPriceInput').value;
        const isPriceTouched = priceFilterTouched || (minPrice !== '' && parseInt(minPrice) > catalogMinPrice) || (maxPrice !== '' && parseInt(maxPrice) < catalogMaxPrice);

        const sizes = [];
        document.querySelectorAll('.size-checkbox:checked').forEach(cb => sizes.push(cb.value));

        const sort = document.getElementById('sortSelect').value;

        const headerSearchEl = document.getElementById('headerSearch');
        const search = headerSearchEl ? headerSearchEl.value.trim() : '';

        return {
            category: uniqueCats.join(','),
            sub_category: uniqueSubs.join(','),
            collection: uniqueCols.join(','),
            min_price: isPriceTouched ? minPrice : '',
            max_price: isPriceTouched ? maxPrice : '',
            size: sizes.join(','),
            sort: sort !== 'default' ? sort : '',
            search: search
        };
    }

    function handleCollectionFilterChange(el) {
        applyFilters();
    }

    function fetchProducts(page, isAppend = false) {
        if (isAppend && (isLoadingMore || !hasMorePages)) return;

        const filterData = getFilterData();
        const loaderEl = document.getElementById('infiniteScrollLoader');
        const gridEl = document.getElementById('productGrid');

        if (isAppend) {
            isLoadingMore = true;
            if (loaderEl) loaderEl.classList.remove('hidden');
        } else {
            if (loaderEl) loaderEl.classList.add('hidden');
            gridEl.innerHTML =
                '<div class="col-span-full text-center py-16"><div class="inline-block w-8 h-8 border-4 border-[#B4771E] border-t-transparent rounded-full animate-spin"></div><p class="mt-3 text-gray-500">Loading...</p></div>';
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        fetch('{{ route('shop.filter') }}?page=' + page, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(filterData)
        })
        .then(function (r) {
            if (r.status === 419) {
                window.location.reload();
                return Promise.reject(new Error('csrf_expired'));
            }
            return r.json().then(function (data) {
                if (!r.ok) {
                    return Promise.reject(new Error(data.message || 'Server error ' + r.status));
                }
                return data;
            });
        })
        .then(function (data) {
            if (!data || typeof data.html !== 'string') {
                throw new Error('Invalid filter response');
            }

            currentPage = data.current_page || page;
            hasMorePages = Boolean(data.has_more);

            if (!isAppend) {
                gridEl.innerHTML = data.html;
                if (gridEl) {
                    const yOffset = -150;
                    const y = gridEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            } else {
                gridEl.insertAdjacentHTML('beforeend', data.html);
            }

            applyProductGridView(gridEl?.dataset.view || 'single');

            if (data.price_range && typeof data.price_range.min === 'number' && typeof data.price_range.max === 'number') {
                updateCatalogPriceRange(data.price_range.min, data.price_range.max);
            }

            if (loaderEl) loaderEl.classList.add('hidden');
            isLoadingMore = false;
        })
        .catch(function (err) {
            isLoadingMore = false;
            if (loaderEl) loaderEl.classList.add('hidden');

            if (err && err.message === 'csrf_expired') return;

            console.error('Filtering failed:', err);
            if (!isAppend) {
                gridEl.innerHTML =
                    '<div class="col-span-full text-center py-16">' +
                        '<svg class="mx-auto w-10 h-10 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>' +
                        '<p class="text-gray-500 font-medium">Could not load products.</p>' +
                        '<button onclick="fetchProducts(1)" class="mt-3 px-4 py-2 text-sm border border-[#B4771E] text-[#B4771E] rounded hover:bg-[#B4771E] hover:text-white transition">Try Again</button>' +
                    '</div>';
            }
        });
    }

    function applyFilters() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function () {
            syncResetButton();
            fetchProducts(1);
        }, 250);
    }

    function goToPage(page) {
        fetchProducts(page);
    }

    function syncResetButton() {
        const hasCategory = document.querySelectorAll('.category-checkbox:checked').length > 0;
        const hasSubCategory = document.querySelectorAll('.subcategory-checkbox:checked').length > 0;
        const hasSize = document.querySelectorAll('.size-checkbox:checked').length > 0;
        const sortSelect = document.getElementById('sortSelect');
        const hasSort = sortSelect && sortSelect.value !== 'default';
        const hasPrice = priceFilterTouched;

        const btn = document.getElementById('resetFiltersBtn');
        if (btn) {
            if (hasCategory || hasSubCategory || hasSize || hasSort || hasPrice) {
                btn.classList.remove('hidden');
                btn.classList.add('flex');
            } else {
                btn.classList.add('hidden');
                btn.classList.remove('flex');
            }
        }
    }

    function resetAllFilters() {
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) sortSelect.value = 'default';

        document.querySelectorAll('.category-checkbox, .subcategory-checkbox, .collection-checkbox, .size-checkbox').forEach(cb => {
            cb.checked = false;
        });

        document.querySelectorAll('[id$="-sub"]').forEach(el => {
            el.classList.add('hidden');
            el.dataset.open = 'false';
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(el => {
            el.classList.add('transition-transform', 'duration-300');
            el.classList.remove('rotate-180');
            el.style.rotate = '';
            el.style.transform = 'rotate(0deg)';
            el.setAttribute('aria-expanded', 'false');
        });

        const headerSearchEl = document.getElementById('headerSearch');
        if (headerSearchEl) {
            headerSearchEl.value = '';
        }

        priceFilterTouched = false;
        setPriceInputs(catalogMinPrice, catalogMaxPrice);
        updateRangeTrack();

        syncResetButton();
        applyFilters();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSidebarSectionDropdowns();
        syncPriceInputBounds();
        updateRangeTrack();
        syncResetButton();
        applyProductGridView('dual');

        const singleToggle = document.querySelector('[data-grid-view-toggle="single"]');
        const dualToggle = document.querySelector('[data-grid-view-toggle="dual"]');

        if (singleToggle) {
            singleToggle.addEventListener('click', function () {
                applyProductGridView('single');
            });
        }

        if (dualToggle) {
            dualToggle.addEventListener('click', function () {
                applyProductGridView('dual');
            });
        }

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

        // Infinite Scroll on Page Scroll
        window.addEventListener('scroll', function () {
            if (!hasMorePages || isLoadingMore) return;
            const gridEl = document.getElementById('productGrid');
            if (!gridEl) return;

            const rect = gridEl.getBoundingClientRect();
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;

            if (rect.bottom - windowHeight < 450) {
                fetchProducts(currentPage + 1, true);
            }
        }, { passive: true });
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
