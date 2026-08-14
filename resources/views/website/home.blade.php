@extends('layouts.website')

@section('title', 'Home | Chetan Imitation')

@section('content')

    <!-- Hero Section -->
    @if(isset($banners) && $banners->count() > 0)
    <style>
        .hero-banner-slider:not(.owl-loaded) {
            display: block !important;
        }
        .hero-banner-slider:not(.owl-loaded) > :not(:first-child) {
            display: none !important;
        }
        .hero-banner-slider,
        .hero-banner-slider .owl-stage-outer,
        .hero-banner-slider .owl-stage,
        .hero-banner-slider .owl-item,
        .hero-banner-slider .item {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 0 !important;
            font-size: 0 !important;
        }
        .hero-banner-slider img {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            vertical-align: bottom !important;
        }
    </style>
    <section class="relative overflow-hidden hero-section p-0 m-0 leading-none">
        <div class="owl-carousel hero-banner-slider w-full">
            @foreach($banners as $index => $banner)
            <div class="item">
                <img src="{{ $banner->image_url }}" alt="Hero Banner" class="w-full h-auto object-cover max-h-[750px] block" @if($index === 0) fetchpriority="high" loading="eager" @else loading="lazy" @endif>
            </div>
            @endforeach
        </div>
    </section>
    @else
    <section
        class="bg-gradient-to-r from-[#F7F4EF] via-[#F7F4EF] to-[#F7F4EF] md:bg-[url('website/assets/images/hero_Banner.png')] sm:bg-no-repeat sm:bg-center sm:bg-[length:100%_100%] min-h-[calc(80vh-158px)] 2xl:!min-h-[calc(100vh-158px)] flex items-center relative overflow-hidden hero-section">

        <div class="container-1440 !px-0 lg:px-auto">
            <div class="grid grid-cols-12 text-center md:text-left items-center">
                <div class="col-span-12 md:col-span-8 xl:col-span-6 2xl:col-span-5 px-5 xl:px-0 py-10 relative z-10">

                    <h1 class="hero-heading font-moglan w-full md:max-w-[549px] mx-auto md:mx-0 text-center md:text-left">
                        Jewellery That Completes Your Celebration
                    </h1>

                    <p class="text-[#3D403F] text-base md:text-lg xl:text-xl xl:leading-8">
                        Discover handcrafted luxury imitation jewellery for weddings, festivals, and every precious moment in between.
                    </p>

                    <div class="flex flex-wrap gap-4 mt-5 md:mt-10 justify-center md:justify-start">
                        <a href="{{ route('shop-by-category') }}" class="common-btn">
                            Explore Category
                        </a>
                        <a href="#most-loved-jewellery"
                            class="border-2 border-[#131615] common-btn font-semibold bg-transparent text-[#131615] hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                            View Bridal Jewellery
                        </a>
                    </div>
                </div>
                <div class="col-span-12 md:hidden mt-2">
                   <img
                        src="website/assets/images/hero-bg.png"
                        class="w-full
                    ">
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Collection Carousel -->
    @php
        $categoriesWithImage = isset($categories) ? $categories->filter(fn($c) => !empty($c->image)) : collect();
    @endphp
    @if($categoriesWithImage->count() > 0)
    <section class="section-space">
        <div class="container-1440">
            <div class="text-center">
                <h2 class="hero-title">Shop By Collection</h2>
                <p class="hero-para">Discover curated jewellery for every occasion</p>
            </div>
            <div class="owl-carousel collection-slider mt-10">
                @foreach($categoriesWithImage as $category)
                <a href="{{ route('shop-by-category', $category->slug) }}" class="group text-center cursor-pointer block">
                    <div class="mx-auto rounded-[999px] overflow-hidden border-2 border-transparent transition-all duration-500 ease-out group-hover:border-[#B4771E] w-[207px] h-[270px]">
                        <img src="{{ $category->image_url }}" class="w-full h-full object-cover transition-all duration-700 ease-out group-hover:scale-105">
                    </div>
                    <h3 class="mt-[30px] text-base md:text-lg lg:text-xl text-[#131615] transition-all duration-500 ease-out group-hover:text-[#B4771E] group-hover:tracking-wide">{{ $category->name }}</h3>
                    <div class="w-0 h-[2px] bg-[#B4771E] mx-auto mt-2 transition-all duration-500 ease-out group-hover:w-16"></div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Art Section -->
    <section class="bg-[#F7F4EF] py-16 lg:py-[80px]">
        <div class="container-1440">
            <div class="grid lg:grid-cols-[1.1fr_0.9fr] items-center lg:gap-[30px] xl:gap-[55px]">
                <div>
                    <h2 class="hero-title">
                        The Art and Passion Behind<br>Our Jewellery
                    </h2>
                    <p class="mt-5 text-[#131615] text-lg lg:text-[20px] leading-8 max-w-[700px]">
                        At Chetan Imitation, we create elegant imitation jewellery that blends timeless tradition with modern style. Every piece is carefully designed to enhance your look for weddings, celebrations, and everyday moments of beauty.
                    </p>
                    <div class="mt-10 bg-[#B4771E] text-white grid grid-cols-3 p-4 xl:p-[35px]">
                        <div class="pr-[10px] sm:pr-[22px] border-r border-[#d39d46]">
                            <h3 class="stat-number text-lg md:text-[26px] lg:text-[36px] leading-[26px] lg:leading-[36px] font-semibold" data-target="250" data-suffix="+">0+</h3>
                            <p class="text-base md:text-lg mt-2 md:mt-3">Jewellery Categories</p>
                        </div>
                        <div class="px-[10px] sm:px-[22px] border-r border-[#d39d46]">
                            <h3 class="stat-number text-lg md:text-[26px] lg:text-[36px] leading-[26px] lg:leading-[36px] font-semibold" data-target="10000" data-suffix="+" data-format="comma">0+</h3>
                            <p class="text-base md:text-lg mt-2 md:mt-3">Products Available</p>
                        </div>
                        <div class="pl-[10px] sm:pl-[22px]">
                            <h3 class="stat-number text-lg md:text-[26px] lg:text-[36px] leading-[26px] lg:leading-[36px] font-semibold" data-target="98" data-suffix="%">0%</h3>
                            <p class="text-base md:text-lg mt-2 md:mt-3">Satisfied Customers</p>
                        </div>
                    </div>
                    <p class="pb-5 md:pb-0 mt-5 text-[#B4771E] text-lg md:text-xl">
                        Chetan Imitation • Jewellery Brand
                    </p>
                </div>
                <div class="flex justify-center lg:justify-end">
                    <div class="relative">
                        <img src="{{ asset('website/assets/images/art.png') }}" alt="" class="w-full">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Most Loved Jewellery -->
    @if(isset($lovedProducts) && count($lovedProducts) > 0)
    <section id="most-loved-jewellery" class="section-space">
        <div class="container-1440">
            <div class="text-center mb-12">
                <h2 class="hero-title">Our Most Loved Jewellery</h2>
                <p class="hero-para">Elegant Creations for Every Occasion</p>
            </div>
            <div class="product-list-toggle-wrapper" data-product-view-toggle>
                <div class="mb-3 flex justify-end sm:hidden">
                    <div class="inline-flex border border-[#D5D5D5] rounded-md overflow-hidden bg-white shadow-sm">
                        <button type="button" data-grid-view="single" class="w-8 h-8 flex items-center justify-center border-r border-[#D5D5D5] bg-[#131615] text-white" aria-label="Single column view">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1.5"></rect></svg>
                        </button>
                        <button type="button" data-grid-view="dual" class="w-8 h-8 flex items-center justify-center bg-white text-[#131615]" aria-label="Two column view">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="4" rx="1"></rect><rect x="14" y="11" width="7" height="10" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                        </button>
                    </div>
                </div>
                <div data-product-grid class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                    @include('website.partials.product-grid-items', ['products' => $lovedProducts])
                </div>
            </div>
            <div class="text-center mt-8 sm:mt-10">
                <a href="{{ route('shop-by-category') }}" class="common-btn">Explore More Jewellery</a>
            </div>
        </div>
    </section>
    @endif

    <!-- Latest Jewellery Collection -->
    @if(isset($latestProducts) && count($latestProducts) > 0)
    <section class="section-space-bottom">
        <div class="container-1440">
            <div class="text-center mb-10">
                <h2 class="hero-title">Discover Our Latest Jewellery Collection</h2>
                <p class="hero-para">Discover elegant new jewellery designs for every special occasion today.</p>
            </div>
            <div class="product-list-toggle-wrapper" data-product-view-toggle>
                <div class="mb-3 flex justify-end sm:hidden">
                    <div class="inline-flex border border-[#D5D5D5] rounded-md overflow-hidden bg-white shadow-sm">
                        <button type="button" data-grid-view="single" class="w-8 h-8 flex items-center justify-center border-r border-[#D5D5D5] bg-[#131615] text-white" aria-label="Single column view">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1.5"></rect></svg>
                        </button>
                        <button type="button" data-grid-view="dual" class="w-8 h-8 flex items-center justify-center bg-white text-[#131615]" aria-label="Two column view">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="4" rx="1"></rect><rect x="14" y="11" width="7" height="10" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                        </button>
                    </div>
                </div>
                <div data-product-grid class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                    @include('website.partials.product-grid-items', ['products' => $latestProducts])
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Instagram Journey -->
    @php
        $hasPrecedingProducts = (isset($lovedProducts) && count($lovedProducts) > 0) || (isset($latestProducts) && count($latestProducts) > 0);
    @endphp
    @include('website.partials.instagram-journey', ['sectionClass' => !$hasPrecedingProducts ? 'section-space' : 'section-space-bottom'])

@endsection

@section('page-js')
<script>
$(document).ready(function(){
    const bannerCount = {{ isset($banners) ? $banners->count() : 0 }};
    if (bannerCount > 0) {
        $('.hero-banner-slider').owlCarousel({
            items: 1,
            loop: bannerCount > 1,
            margin: 0,
            nav: false,
            dots: bannerCount > 1,
            autoplay: bannerCount > 1,
            autoplayTimeout: 3500,
            autoplayHoverPause: false,
            smartSpeed: 800
        });
    }

    const colCount = {{ $categoriesWithImage->count() }};
    $('.collection-slider').owlCarousel({
        loop: colCount > 6,
        margin:30,
        nav:false,
        dots: colCount > 1,
        autoplay: colCount > 1,
        autoplayTimeout:3000,
        responsive:{
            0:{
                items: Math.min(colCount, 2)
            },
            768:{
                items: Math.min(colCount, 3)
            },
            1200:{
                items: Math.min(colCount, 6)
            }
        }
    });

    const stats = document.querySelectorAll(".stat-number");
    
    const countUp = (el) => {
        const target = parseInt(el.getAttribute("data-target"), 10);
        const suffix = el.getAttribute("data-suffix") || "";
        const format = el.getAttribute("data-format") || "";
        let current = 0;
        const duration = 2000;
        const stepTime = 16;
        const totalSteps = duration / stepTime;
        const stepValue = target / totalSteps;
        
        let step = 0;
        const timer = setInterval(() => {
            step++;
            current += stepValue;
            if (step >= totalSteps) {
                current = target;
                clearInterval(timer);
            }
            
            let displayVal = Math.floor(current);
            if (format === "comma") {
                displayVal = displayVal.toLocaleString('en-US');
            }
            el.textContent = displayVal + suffix;
        }, stepTime);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                countUp(entry.target);
                observer.unobserve(entry.target); // Trigger only once
            }
        });
    }, { threshold: 0.1 });

    $('a[href="#most-loved-jewellery"]').on('click', function(e) {
        e.preventDefault();
        const target = $('#most-loved-jewellery');
        if (target.length) {
            const headerHeight = $('header').outerHeight() || 80;
            $('html, body').animate({
                scrollTop: target.offset().top - headerHeight
            }, 600);
        }
    });

    stats.forEach(stat => observer.observe(stat));
});
</script>
@endsection
