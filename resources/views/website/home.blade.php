@extends('layouts.website')

@section('title', 'Home | Chetan Imitation')

@section('content')

    <!-- Hero Section -->
    <section
        class="bg-gradient-to-r from-[#F7F4EF] via-[#F7F4EF] to-[#F7F4EF] md:bg-[url('website/assets/images/hero_Banner.png')] sm:bg-no-repeat sm:bg-center sm:bg-[length:100%_100%] min-h-[calc(80vh-158px)] 2xl:!min-h-[calc(110vh-158px)] flex items-center relative overflow-hidden hero-section">

        <div class="container-1440 !px-0 lg:px-auto">
            <div class="grid grid-cols-12 text-center md:text-left items-center">
                <div class="col-span-12 md:col-span-8 xl:col-span-6 2xl:col-span-5 px-5 lg:px-0 py-10 relative z-10">

                    <h1 class="hero-heading font-moglan w-full md:max-w-[549px] mx-auto md:mx-0 text-center md:text-left">
                        Jewelry That Completes Your Celebration
                    </h1>

                    <p class="text-[#3D403F] text-base md:text-lg xl:text-xl xl:leading-8">
                        Discover handcrafted luxury imitation jewelry for weddings, festivals, and every precious moment in between.
                    </p>

                    <div class="flex flex-wrap gap-4 mt-5 md:mt-10 justify-center md:justify-start">
                        <a href="#" class="common-btn">
                            Explore Category
                        </a>
                        <a href="#" class="common-btn font-semibold transition duration-300">
                            View Bridal Jewelry
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

    <!-- Collection Carousel -->
    <section class="section-space">
        <div class="container-1440">
            <div class="text-center">
                <h2 class="hero-title">Shop By Collection</h2>
                <p class="hero-para">Discover curated jewelry for every occasion</p>
            </div>
            <div class="owl-carousel collection-slider mt-10">
                @foreach($categories as $category)
                <div class="group text-center cursor-pointer">
                    <div class="mx-auto rounded-full overflow-hidden border-2 border-transparent transition-all duration-500 ease-out group-hover:border-[#B4771E] group-hover:shadow-[0_0_25px_rgba(180,119,30,0.30)]">
                        <img src="{{ $category->image_url ?? asset('website/assets/images/collection_1.png') }}" class="w-full h-full object-cover transition-all duration-700 ease-out group-hover:scale-105">
                    </div>
                    <h3 class="mt-[30px] text-base md:text-lg lg:text-xl text-[#131615] transition-all duration-500 ease-out group-hover:text-[#B4771E] group-hover:tracking-wide">{{ $category->name }}</h3>
                    <div class="w-0 h-[2px] bg-[#B4771E] mx-auto mt-2 transition-all duration-500 ease-out group-hover:w-16"></div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Art Section -->
    <section class="bg-[#F7F4EF] py-16 lg:py-[80px]">
        <div class="container-1440">
            <div class="grid lg:grid-cols-[1.1fr_0.9fr] items-center lg:gap-[30px] xl:gap-[55px]">
                <div>
                    <h2 class="hero-title">
                        The Art and Passion Behind<br>Our Jewelry
                    </h2>
                    <p class="mt-5 text-[#131615] text-lg lg:text-[20px] leading-8 max-w-[700px]">
                        At Chetan Imitation, we create elegant imitation jewelry that blends timeless tradition with modern style. Every piece is carefully designed to enhance your look for weddings, celebrations, and everyday moments of beauty.
                    </p>
                    <div class="mt-10 bg-[#B4771E] text-white grid grid-cols-3 p-4 xl:p-[35px]">
                        <div class="pr-[10px] sm:pr-[22px] border-r border-[#d39d46]">
                            <h3 class="stat-number text-lg md:text-[26px] lg:text-[36px] leading-[26px] lg:leading-[36px] font-semibold" data-target="250" data-suffix="+">0+</h3>
                            <p class="text-base md:text-lg mt-2 md:mt-3">Jewelry Categories</p>
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
                        Chetan Imitation • Jewelry Brand
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

    <!-- Most Loved Jewelry -->
    <section class="section-space">
        <div class="container-1440">
            <div class="text-center mb-12">
                <h2 class="hero-title">Our Most Loved Jewelry</h2>
                <p class="hero-para">Elegant Creations for Every Occasion</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                @foreach($lovedProducts as $product)
                <div class="group border border-[#D5D5D5] relative cursor-pointer">
                    <div class="relative overflow-hidden">
                        @if($product->sale)
                        <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                            <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                        </div>
                        @endif
                        <img src="{{ $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}" alt="{{ $product->name }}" class="w-full h-[340px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                    </div>
                    <button class="group absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center outline-none focus:outline-none focus:ring-0">
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="#131615" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                       </svg>
                    </button>
                    <div class="p-4 md:p-[25px]">
                        <h3 class="product-title"><a href="#">{{ $product->name }}</a></h3>
                        <div class="flex items-center gap-1 mt-[9px]">
                            <div class="text-[#D5D5D5] text-base">★★★★★</div>
                            <span class="text-xs text-[#757575]">(0)</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1">
                             <span class="text-lg xl:text-[24px] text-[#131615]">₹{{ number_format($product->sale_price, 0) }}</span>
                             @if($product->mrp && $product->mrp > $product->sale_price)<span class="text-sm xl:text-lg text-[#757575] line-through">₹{{ number_format($product->mrp, 0) }}</span>@endif
                        </div>
                        <button class="w-full h-[45px] border border-[#131615] text-lg mt-[30px] hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white transition duration-300">
                            Add to Cart
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="text-center mt-8 sm:mt-10">
                <a href="#" class="common-btn">Explore More Jewelry</a>
            </div>
        </div>
    </section>

    <!-- Latest Jewelry Collection -->
    <section class="section-space-bottom">
        <div class="container-1440">
            <div class="text-center mb-10">
                <h2 class="hero-title">Discover Our Latest Jewelry Collection</h2>
                <p class="hero-para">Discover elegant new jewelry designs for every special occasion today.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                @foreach($latestProducts as $product)
                <div class="group border border-[#D5D5D5] relative cursor-pointer">
                    <div class="relative overflow-hidden">
                        @if($product->sale)
                        <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                            <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                        </div>
                        @endif
                        <img src="{{ $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}" alt="{{ $product->name }}" class="w-full h-[340px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105 transform transition-all duration-700 ease-in-out group-hover:scale-105">
                    </div>
                    <button class="group absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center text-[#131615] hover:text-[#B4771E] transition duration-300">
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                       </svg>
                    </button>
                    <div class="p-4 md:p-[25px]">
                        <h3 class="product-title"><a href="#">{{ $product->name }}</a></h3>
                        <div class="flex items-center gap-1 mt-[9px]">
                            <div class="text-[#D5D5D5] text-base">★★★★★</div>
                            <span class="text-xs text-[#757575]">(0)</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1">
                             <span class="text-lg xl:text-[24px] text-[#131615]">₹{{ number_format($product->sale_price, 0) }}</span>
                             @if($product->mrp && $product->mrp > $product->sale_price)<span class="text-sm xl:text-lg text-[#757575] line-through">₹{{ number_format($product->mrp, 0) }}</span>@endif
                        </div>
                        <button class="w-full h-[45px] border border-[#131615] text-lg mt-[30px] hover:border-[#B4771E] hover:bg-[#B4771E] hover:text-white transition duration-300">
                            Add to Cart
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Instagram Journey -->
    <section class="section-space-bottom">
        <div>
            <div class="text-center px-5">
                <h2 class="hero-title">Follow Our Jewelry Journey</h2>
            </div>
            <div class="mt-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle1.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle2.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle3.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle4.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle5.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
                <a href="#" class="group overflow-hidden">
                    <img src="{{ asset('website/assets/images/Rectangle6.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
                </a>
            </div>
            <div class="text-center mt-8 lg:mt-10">
                <a href="#" class="common-btn">Follow Us on Instagram</a>
            </div>
        </div>
    </section>

@endsection

@section('page-js')
<script>
$(document).ready(function(){
    $('.collection-slider').owlCarousel({
        loop:true,
        margin:30,
        nav:false,
        dots:true,
        autoplay:true,
        autoplayTimeout:3000,
        responsive:{
            0:{
                items:2
            },
            768:{
                items:3
            },
            1200:{
                items:6
            }
        }
    });

    // Stats Count Up Animation using IntersectionObserver
    const stats = document.querySelectorAll(".stat-number");
    
    const countUp = (el) => {
        const target = parseInt(el.getAttribute("data-target"), 10);
        const suffix = el.getAttribute("data-suffix") || "";
        const format = el.getAttribute("data-format") || "";
        let current = 0;
        const duration = 2000; // 2 seconds animation
        const stepTime = 16; // ~60fps
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

    stats.forEach(stat => observer.observe(stat));
});
</script>
@endsection
