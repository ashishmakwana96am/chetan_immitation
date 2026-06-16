@extends('layouts.website')

@section('title', 'Create Account | Chetan Imitation')

@section('content')

<section class="section-space">

    <div class="container-1440">

        <div class="grid lg:grid-cols-2 gap-5 items-stretch">

            <div class="bg-[#f7f7f7] border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px]">

                <div class="text-center">

                    <h1 class="font-moglan text-[#131615] text-[38px] md:text-[48px] lg:text-[54px] leading-none">
                        Create Your Account
                    </h1>

                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-7 max-w-[320px] mx-auto">
                        Join Chetan Imitation to save favorites,
                        track orders, and shop.
                    </p>

                </div>

                <form class="mt-8">

                    <div class="mb-4">

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Full Name
                        </label>

                        <input
                            type="text"
                            placeholder="Enter Your Full Name"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">

                    </div>

                    <div class="mb-4">

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Mobile Number
                        </label>

                        <input
                            type="tel"
                            placeholder="Enter Your Mobile Number"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">

                    </div>

                    <div class="mb-4">

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address
                        </label>

                        <input
                            type="email"
                            placeholder="Enter your email address"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">

                    </div>

                    <div class="mb-4">

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Password
                        </label>

                        <input
                            type="password"
                            placeholder="Enter Your Password"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">

                    </div>

                    <div>

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            placeholder="Enter Your Confirm Password"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">

                    </div>

                    <button
                        type="submit"
                        class="w-full h-[52px] bg-[#B4771E] text-white mt-6 text-lg font-medium hover:bg-[#b57a1f] transition">

                        Create Account

                    </button>

                </form>

                <div class="text-center mt-4 text-[14px]">

                    <span class="text-[#131615] text-lg">
                        Already have an account?
                    </span>

                    <a
                        href="{{ route('login') }}"
                        class="text-[#B4771E] ml-1 text-lg">

                        Login

                    </a>

                </div>

            </div>

            <div class="relative overflow-hidden rounded-[8px] min-h-[420px] lg:min-h-full">

                <img
                    src="{{ asset('website/assets/images/account.png') }}"
                    alt=""
                    class="absolute inset-0 ">

                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/20 to-transparent">
                </div>

                <div class="absolute bottom-0 left-0 w-full p-6 text-white">

                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">
                        Begin Your Jewelry Journey
                    </h3>

                    <p class="mt-3 text-lg leading-7">
                        Create your account to save favorites, track orders,
                        enjoy faster checkout, and discover exclusive jewelry collections.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection