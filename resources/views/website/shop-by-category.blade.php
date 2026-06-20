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
        <aside id="sidebar" class="fixed lg:static top-0 left-0 w-[320px] lg:w-auto h-screen lg:h-fit bg-white z-[999] overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 col-span-12 lg:col-span-4 xl:col-span-3 shrink-0 border border-[#D5D5D5] rounded-none lg:rounded-[8px]">
            <!-- Shop By Category -->
            <div class="sidebar-section overflow-hidden">

                <button onclick="toggleSection('cat-section','cat-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Shop By Category</span>
                    <svg id="cat-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
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
                        $selectedSubs = request('sub_category') ? explode(',', request('sub_category')) : [];
                        $isCatChecked = in_array($cat->slug, $selectedCats);
                        $shouldSelectAllSubs = $isCatChecked && empty($selectedSubs);
                        $isCatOpen = $isCatChecked || $cat->subCategories->pluck('slug')->intersect($selectedSubs)->isNotEmpty();
                    @endphp
                    <div class="{{ $loop->last ? 'border-b-0 py-5' : 'border-b border-[#D5D5D5] py-4' }} {{ $loop->first ? 'pb-5' : '' }}">
                        <div class="w-full flex items-center justify-between gap-2">
                            <label class="flex items-center gap-[15px] min-w-0 flex-1 cursor-pointer select-none">
                                <span class="custom-checkbox shrink-0">
                                    <input type="checkbox" class="category-checkbox" value="{{ $cat->slug }}" data-category-id="{{ $cat->id }}" {{ $isCatChecked ? 'checked' : '' }} onchange="handleCategoryFilterChange(this)">
                                    <span></span>
                                </span>
                                <h3 class="text-[18px] text-[#3D403F]">
                                    {{ $cat->name }}
                                    <span class="text-[#757575]">({{ $cat->products_count }})</span>
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
                        <div id="{{ $catId }}-sub" class="{{ $isCatOpen ? '' : 'hidden' }} mt-5 space-y-4 pl-10">
                            @foreach($cat->subCategories as $sub)
                            <label class="flex items-center gap-4 cursor-pointer select-none">
                                <span class="custom-checkbox shrink-0">
                                    <input type="checkbox" class="subcategory-checkbox" value="{{ $sub->slug }}" data-category-id="{{ $cat->id }}" {{ ($shouldSelectAllSubs || in_array($sub->slug, $selectedSubs)) ? 'checked' : '' }} onchange="handleSubcategoryFilterChange(this)">
                                    <span></span>
                                </span>
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
                    <svg id="price-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
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
                            <input id="minPriceInput" type="number" value="{{ $selectedMinPrice }}" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px]
                                    text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('min')" onblur="normalizePriceInput('min')" onkeydown="if(event.key==='Enter') this.blur()">
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
                            <input id="maxPriceInput" type="number" value="{{ $selectedMaxPrice }}" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" class="w-full h-[56px] border border-[#D5D5D5] rounded-[2px]
                                    text-[20px] font-normal text-[#3D403F] pl-8 pr-5 py-[14px]
                                    outline-none appearance-none" oninput="syncFromInput('max')" onblur="normalizePriceInput('max')" onkeydown="if(event.key==='Enter') this.blur()">
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
                        <div id="rangeTrack" class="absolute h-[4px] bg-[#131615] rounded-full z-[2]" style="left:0%;right:90%;"></div>
                        <input id="minRange" type="range" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" value="{{ $selectedMinPrice }}" class="absolute w-full z-[3]" oninput="rangeSlide('min')" />
                        <input id="maxRange" type="range" min="{{ $catalogMinPrice }}" max="{{ $catalogMaxPrice }}" value="{{ $selectedMaxPrice }}" class="absolute w-full z-[4]" oninput="rangeSlide('max')" />
                    </div>
                </div>
            </div>

            @if($sizes->isNotEmpty())
            <!-- Size -->
            <div class="sidebar-section pb-0 mb-1">
                <button onclick="toggleSection('size-section','size-arrow')"
                    class="flex items-center justify-between w-full pb-[17px] pt-[22px] px-5 font-semibold text-lg leading-[18px] text-[#131615] border-b border-[#D5D5D5]">
                    <span>Size</span>
                    <svg id="size-arrow" class="collapse-arrow w-5 h-5 text-[#131615]" style="transform: rotate(180deg);" fill="none"
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
                                    <input type="checkbox" class="size-checkbox" value="{{ $sizeVal->value }}" {{ in_array($sizeVal->value, $selectedSizes) ? 'checked' : '' }} onchange="priceFilterTouched = false; applyFilters()">
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


        </aside>

        <!-- Overlay for mobile sidebar -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

        <!-- PRODUCT GRID -->
        <div class="col-span-12 lg:col-span-8 xl:col-span-9 min-w-0">

            <div class="flex flex-nowrap gap-3 items-center justify-between lg:justify-end mb-5">
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
                        <select id="sortSelect" onchange="applyFilters()"
                            class="appearance-none w-full px-2 sm:px-5 pr-8 sm:pr-12 h-[42px] border border-[#D5D5D5] rounded-[8px] text-base font-semibold text-[#3D403F] outline-none">
                            <option value="default" {{ request('sort') == 'default' || !request('sort') ? 'selected' : '' }}>Sorting</option>
                            <option value="price-low" {{ request('sort') == 'price-low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price-high" {{ request('sort') == 'price-high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
                        </select>
                        <svg class="absolute top-1/2 -translate-y-[50%] right-3 pointer-events-none w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <path d="M6 9L12 15L18 9" stroke="#3D403F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
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
    const CATEGORY_BASE_URL = '{{ url('/shop') }}';
    let catalogMinPrice = {{ $catalogMinPrice }};
    let catalogMaxPrice = {{ $catalogMaxPrice }};
    let filterTimeout;
    let priceFilterTouched = {{ $hasPriceFilter ? 'true' : 'false' }};

    function getPriceStep() {
        const range = catalogMaxPrice - catalogMinPrice;
        return Math.max(1, Math.round(range / 20) || 1);
    }

    function clampPriceValue(value, fallback) {
        const parsed = parseInt(value);
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
        } else {
            let currentMin = parseInt(document.getElementById('minPriceInput').value);
            let currentMax = parseInt(document.getElementById('maxPriceInput').value);
            if (isNaN(currentMin)) currentMin = catalogMinPrice;
            if (isNaN(currentMax)) currentMax = catalogMaxPrice;

            // Check if there is no overlap between the user's selection and the new catalog range
            if (currentMax < catalogMinPrice || currentMin > catalogMaxPrice) {
                // Reset price filter since it doesn't apply to the new catalog range at all
                priceFilterTouched = false;
                setPriceInputs(catalogMinPrice, catalogMaxPrice);
            } else {
                // There is some overlap, clamp the user's selection within the new bounds
                let selectedMin = Math.max(catalogMinPrice, Math.min(catalogMaxPrice, currentMin));
                let selectedMax = Math.max(catalogMinPrice, Math.min(catalogMaxPrice, currentMax));
                const step = getPriceStep();

                if (selectedMin >= selectedMax) {
                    if (selectedMax === catalogMinPrice) {
                        selectedMax = Math.min(catalogMaxPrice, selectedMin + step);
                    } else {
                        selectedMin = Math.max(catalogMinPrice, selectedMax - step);
                    }
                }

                setPriceInputs(selectedMin, selectedMax);

                if (isFullCatalogPriceRange(selectedMin, selectedMax)) {
                    priceFilterTouched = false;
                }
            }
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
        priceFilterTouched = false;
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
        priceFilterTouched = false;
        applyFilters();
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
        
        let val = parseInt(input.value);
        if (!isNaN(val)) {
            if (val >= catalogMinPrice && val <= catalogMaxPrice) {
                range.value = val;
                updateRangeTrack();
                applyFilters();
            }
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
        let val = clampPriceValue((parseInt(input.value) || catalogMinPrice) + step, catalogMinPrice);
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
        let val = clampPriceValue((parseInt(input.value) || catalogMinPrice) - step, catalogMinPrice);
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
        let val = clampPriceValue((parseInt(input.value) || catalogMaxPrice) + step, catalogMaxPrice);
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
        let val = clampPriceValue((parseInt(input.value) || catalogMaxPrice) - step, catalogMaxPrice);
        const min = clampPriceValue(document.getElementById('minPriceInput').value, catalogMinPrice);
        if (val <= min) val = Math.min(catalogMaxPrice, min + step);
        input.value = val;
        range.value = val;
        updateRangeTrack();
        applyFilters();
    }

    function updateRangeTrack() {
        const min = parseInt(document.getElementById('minRange').value);
        const max = parseInt(document.getElementById('maxRange').value);
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
        const minPrice = clampPriceValue(minP, catalogMinPrice);
        const maxPrice = clampPriceValue(maxP, catalogMaxPrice);

        if (priceFilterTouched) {
            if (minPrice > catalogMinPrice) parts.push('min_price=' + minPrice);
            if (maxPrice < catalogMaxPrice) parts.push('max_price=' + maxPrice);
        }

        const sizes = [];
        document.querySelectorAll('.size-checkbox:checked').forEach(cb => sizes.push(cb.value));
        if (sizes.length) parts.push('size=' + sizes.join(','));

        return parts.join('&');
    }

    function getCategoryBase() {
        const idx = window.location.pathname.indexOf('/shop');
        if (idx !== -1) return window.location.pathname.substring(0, idx + 5);
        return CATEGORY_BASE_URL;
    }

    function fetchProducts(page, qs) {
        if (qs === undefined) {
            qs = buildQueryString();
        }
        const params = new URLSearchParams(qs);
        params.set('page', page);

        document.getElementById('productGrid').innerHTML =
            '<div class="col-span-full text-center py-16"><div class="inline-block w-8 h-8 border-4 border-[#B4771E] border-t-transparent rounded-full animate-spin"></div><p class="mt-3 text-gray-500">Loading...</p></div>';

        const base = getCategoryBase();
        const url = base + (qs ? '?' + params.toString() : '?page=' + page);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('productGrid').innerHTML = data.html;
            document.getElementById('paginationWrap').innerHTML = data.pagination;
            if (data.price_range) {
                updateCatalogPriceRange(data.price_range.min, data.price_range.max);
            }

            const gridEl = document.getElementById('productGrid');
            if (gridEl) {
                const yOffset = -150;
                const y = gridEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        })
        .catch(() => {
            window.location.href = url;
        });
    }

    function applyFilters() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function () {
            syncResetButton();
            const qs = buildQueryString();
            const base = getCategoryBase();
            const url = base + (qs ? '?' + qs : '');
            window.history.replaceState({}, '', url);
            fetchProducts(1, qs);
        }, 250);
    }

    function goToPage(page) {
        const qs = buildQueryString();
        const base = getCategoryBase();
        const url = base + (qs ? '?' + qs + '&page=' + page : '?page=' + page);
        window.history.replaceState({}, '', url);
        fetchProducts(page);
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

        const hasSubCategoryParam = params.has('sub_category');
        const subList = hasSubCategoryParam
            ? (params.get('sub_category') || '').split(',').filter(Boolean)
            : [];

        document.querySelectorAll('.category-checkbox').forEach(category => {
            category.checked = catList.includes(category.value);
        });

        document.querySelectorAll('.subcategory-checkbox').forEach(cb => {
            const parentCatId = cb.dataset.categoryId;
            const parentCheckbox = document.querySelector('.category-checkbox[data-category-id="' + parentCatId + '"]');
            
            if (hasSubCategoryParam) {
                cb.checked = subList.includes(cb.value);
            } else if (parentCheckbox && parentCheckbox.checked) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });

        document.querySelectorAll('.category-checkbox').forEach(cb => {
            const catId = cb.dataset.categoryId;
            syncCategoryDropdown('cat-' + catId);
        });

        const sizeParam = params.get('size');
        const sizeList = sizeParam ? sizeParam.split(',') : [];
        document.querySelectorAll('.size-checkbox').forEach(cb => {
            cb.checked = sizeList.includes(cb.value);
        });

        const minPrice = params.get('min_price');
        const maxPrice = params.get('max_price');
        priceFilterTouched = params.has('min_price') || params.has('max_price');
        const minVal = minPrice !== null ? parseInt(minPrice) : catalogMinPrice;
        const maxVal = maxPrice !== null ? parseInt(maxPrice) : catalogMaxPrice;
        const activeId = document.activeElement ? document.activeElement.id : null;
        const isEditingPrice = activeId === 'minPriceInput' || activeId === 'maxPriceInput';
        if (!isEditingPrice && priceFilterTouched) {
            setPriceInputs(
                clampPriceValue(minVal, catalogMinPrice),
                clampPriceValue(maxVal, catalogMaxPrice)
            );
            updateRangeTrack();
        }
    }

    function syncResetButton() {
        const hasCategory = document.querySelectorAll('.category-checkbox:checked').length > 0;
        const hasSubCategory = document.querySelectorAll('.subcategory-checkbox:checked').length > 0;
        const hasSize = document.querySelectorAll('.size-checkbox:checked').length > 0;
        const hasSort = document.getElementById('sortSelect').value !== 'default';
        const hasPrice = priceFilterTouched;

        const btn = document.getElementById('resetFiltersBtn');
        if (hasCategory || hasSubCategory || hasSize || hasSort || hasPrice) {
            btn.classList.remove('hidden');
            btn.classList.add('flex');
        } else {
            btn.classList.add('hidden');
            btn.classList.remove('flex');
        }
    }

    function resetAllFilters() {
        document.getElementById('sortSelect').value = 'default';

        document.querySelectorAll('.category-checkbox, .subcategory-checkbox, .size-checkbox').forEach(cb => {
            cb.checked = false;
        });

        priceFilterTouched = false;
        setPriceInputs(catalogMinPrice, catalogMaxPrice);
        updateRangeTrack();

        syncResetButton();
        applyFilters();
    }

    function syncCategoryQueryWithSubcategories() {
        const params = new URLSearchParams(window.location.search);
        const pathCat = getCategoryFromPath();
        const hasCategoryFilter = params.has('category') || (pathCat && pathCat !== 'category');

        if (!hasCategoryFilter || params.has('sub_category')) {
            return;
        }

        const qs = buildQueryString();
        if (!qs.includes('sub_category=')) {
            return;
        }

        const base = getCategoryBase();
        const url = base + '?' + qs;
        window.history.replaceState({}, '', url);
        fetchProducts(1, qs);
    }

    function initStockState() {
        // stock filter removed
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSidebarSectionDropdowns();
        syncPriceInputBounds();
        updateRangeTrack();
        syncCheckboxesFromUrl();
        initStockState();
        syncCategoryQueryWithSubcategories();
        syncResetButton();

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
