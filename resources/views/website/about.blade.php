@extends('layouts.website')

@section('title', 'About Us | Chetan Imitation')

@section('content')

    <section class="relative bg-center bg-no-repeat overflow-hidden" style="background-image: url('{{ asset('website/assets/images/about_banner.png') }}'); background-size: 100% 100%;">

        <div class="container-1440">

            <div class="grid grid-cols-1 lg:grid-cols-12 items-center">

                <div class="relative z-10 py-10 sm:py-16 lg:py-0 lg:col-span-7 text-center md:text-left">

                    <span class="inline-flex items-center bg-white text-[#B4771E] text-base md:text-xl px-[15px] py-[10px] mb-2">
                        About Us
                    </span>

                    <h1 class="font-moglan hero-heading">
                        Crafting Elegance
                        <br>
                        for Every Celebration
                    </h1>

                    <p class="hero-para max-w-[750px]">
                        At Chetan Imitation, we believe jewellery is more than an
                        accessory—it's a reflection of tradition, beauty, and
                        personal style. Our collections are thoughtfully curated
                        to bring timeless elegance and modern sophistication
                        to every occasion.
                    </p>

                </div>

                <div class="relative flex justify-center lg:justify-end lg:col-span-5">
                    <img src="{{ asset('website/assets/images/about.png') }}" alt="" class="xl:w-full">
                </div>

            </div>

        </div>
    </section>

    <section class="section-space">

        <div class="container-1440">

            <div class="text-center">

                <h2 class="font-moglan hero-title">
                    Trusted Jewellery Destination
                </h2>

                <p class="hero-para">
                    Premium imitation jewellery crafted for every special occasion beautifully.
                </p>

            </div>

            <div class="grid md:grid-cols-2 gap-4 mt-10">

                <div class="border border-[#B4771E] rounded-[8px] px-3 sm:px-5 py-3 sm:py-[18px] flex items-center gap-3 bg-[#B4771E08]">
                    <img src="{{ asset('website/assets/images/Vector.png') }}" alt="">
                    <span class="text-base md:text-lg md:text-[20px] xl:leading-[20px] text-[#131615]">Premium Quality Imitation Jewellery</span>
                </div>

                <div class="border border-[#B4771E] rounded-[8px] px-3 sm:px-5 py-3 sm:py-[18px] flex items-center gap-3 bg-[#B4771E08]">
                    <img src="{{ asset('website/assets/images/Vector.png') }}" alt="">
                    <span class="text-base md:text-lg md:text-[20px] xl:leading-[20px] text-[#131615]">Inspired by Traditional & Modern Designs</span>
                </div>

                <div class="border border-[#B4771E] rounded-[8px] px-3 sm:px-5 py-3 sm:py-[18px] flex items-center gap-3 bg-[#B4771E08]">
                    <img src="{{ asset('website/assets/images/Vector.png') }}" alt="">
                    <span class="text-base md:text-lg md:text-[20px] xl:leading-[20px] text-[#131615]">Affordable Luxury for Every Occasion</span>
                </div>

                <div class="border border-[#B4771E] rounded-[8px] px-3 sm:px-5 py-3 sm:py-[18px] flex items-center gap-3 bg-[#B4771E08]">
                    <img src="{{ asset('website/assets/images/Vector.png') }}" alt="">
                    <span class="text-base md:text-lg md:text-[20px] xl:leading-[20px] text-[#131615]">Trusted by Thousands of Happy Customers</span>
                </div>

            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5 mt-10">

                <div class="border border-[#e3e3e3] bg-[#f8f8f8] p-5 lg:p-10 text-center">

                    <div class="w-[70px] md:w-[100px] h-[70px] md:h-[100px] border border-[#D5D5D5] rounded-full flex items-center justify-center mx-auto">
                        <img src="{{ asset('website/assets/images/desti1.png') }}" alt="" class="w-full max-w-[40px] md:max-w-[50px]">
                    </div>

                    <h3 class="mt-6 text-2xl sm:text-[28px] font-bold text-[#131615]">
                        What We Do
                    </h3>

                    <p class="mt-4 text-base sm:text-lg sm:leading-8 text-[#3D403F]">
                        We design and offer a wide range of imitation jewellery including necklaces,
                        earrings, rings, bangles, bridal collections, and festive wear. Our collections
                        are carefully selected to bring elegance, style, and confidence to every customer.
                    </p>

                </div>

                <div class="border border-[#e3e3e3] bg-[#f8f8f8] p-5 lg:p-10 text-center">

                    <div class="w-[70px] md:w-[100px] h-[70px] md:h-[100px] border border-[#D5D5D5] rounded-full flex items-center justify-center mx-auto">
                        <img src="{{ asset('website/assets/images/desti2.png') }}" alt="" class="w-full max-w-[40px] md:max-w-[50px]">
                    </div>

                    <h3 class="mt-6 text-2xl sm:text-[28px] font-bold text-[#131615]">
                        Our Vision
                    </h3>

                    <p class="mt-4 text-base sm:text-lg sm:leading-8 text-[#3D403F]">
                        Our vision is to become a leading jewellery destination known for exceptional designs, trusted
                        quality, and outstanding customer experiences. We continuously strive to bring the latest trends
                        while preserving the beauty of traditional craftsmanship.
                    </p>

                </div>

                <div class="border border-[#e3e3e3] bg-[#f8f8f8] p-5 lg:p-10 text-center">

                    <div class="w-[70px] md:w-[100px] h-[70px] md:h-[100px] border border-[#D5D5D5] rounded-full flex items-center justify-center mx-auto">
                        <img src="{{ asset('website/assets/images/desti3.png') }}" alt="" class="w-full max-w-[40px] md:max-w-[50px]">
                    </div>

                    <h3 class="mt-6 text-2xl sm:text-[28px] font-bold text-[#131615]">
                        Our Journey
                    </h3>

                    <p class="mt-4 text-base sm:text-lg sm:leading-8 text-[#3D403F]">
                        What started as a passion for beautiful jewellery has grown into a trusted brand serving customers
                        across India. Through dedication, innovation, and customer trust, Chetan Imitation continues to
                        expand its collections and inspire confidence through elegant designs.
                    </p>

                </div>

            </div>

        </div>

    </section>

    <section class="bg-[#F7F4EF] py-[49px] px-6 lg:px-16 mb-[60px] md:mb-[80px] lg:mb-[120px]">
        <div class="relative text-center w-full max-w-[1060px] mx-auto px-2 md:px-10">

            <span class="absolute left-0 top-0 text-[#B4771E] text-5xl leading-none">
                <img src="{{ asset('website/assets/images/inver_start.png') }}" alt="" class="w-full max-w-[10px] sm:max-w-[30px] md:max-w-[46px]">
            </span>

            <span class="absolute right-0 bottom-0 text-[#B4771E] text-5xl leading-none">
                <img src="{{ asset('website/assets/images/invert-end.png') }}" alt="" class="w-full max-w-[10px] sm:max-w-[30px] md:max-w-[46px]">
            </span>

            <p class="text-[#131615] text-base sm:text-lg lg:text-[24px] sm:leading-[34px] px-5 md:px-7 lg:px-10">
                Jewellery is more than an accessory-it is a reflection of personality,
                tradition, and celebration. At Chetan Imitation, every piece is crafted
                to make life's special moments even more memorable.
            </p>

        </div>
    </section>

@endsection
