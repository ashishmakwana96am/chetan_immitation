@extends('layouts.website')

@section('title', 'OTP Verification | Chetan Imitation')

@section('content')

<section class="section-space">

    <div class="container-1440">

        <div class="grid lg:grid-cols-2 gap-5">

            <div class="border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px] ">

                <div class="text-center">

                    <h1 class="font-moglan text-[#131615] text-[34px] sm:text-[42px] lg:text-[54px] leading-none">
                        Verify Your Account
                    </h1>

                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-6 max-w-[420px] mx-auto">
                        Enter the 6-digit verification code sent to your
                        registered mobile number to continue.
                    </p>

                </div>

                <div class="flex justify-center gap-2 sm:gap-3 mt-8">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                    <input
                        type="text"
                        maxlength="1"
                        class="w-[42px] h-[42px] sm:w-[55px] sm:h-[55px] lg:w-[70px] lg:h-[70px] border border-[#D5D5D5] text-center text-[18px] lg:text-[22px] outline-none bg-white rounded-[4px]">

                </div>

                <button
                    type="submit"
                    class="w-full h-[50px] sm:h-[52px] bg-[#B4771E] text-white mt-6 text-[16px] sm:text-[18px] font-medium hover:bg-[#a86f1b] transition">

                    Verify OTP

                </button>

                <div class="text-center mt-5">

                    <a
                        href="#"
                        class="text-[#B4771E] text-[15px] sm:text-[16px] font-medium">

                        Resend OTP

                    </a>

                </div>

            </div>

            <div class="relative rounded-[4px] overflow-hidden min-h-[320px]">

                <img
                    src="{{ asset('website/assets/images/otpverification.png') }}"
                    alt=""
                    class="absolute inset-0">

                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/25 to-transparent">
                </div>

                <div class="absolute bottom-0 left-0 p-6 lg:p-8 text-white">

                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">
                        Secure Your Journey
                    </h3>

                    <p class="mt-3 text-lg leading-7">
                        Verify your account to access exclusive collections,
                        track orders, and enjoy a seamless jewelry shopping experience.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection