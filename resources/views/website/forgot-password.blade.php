@extends('layouts.website')

@section('title', 'Forgot Password | Chetan Imitation')

@section('content')

<section class="section-space">

    <div class="container-1440">

        <div class="mx-auto grid lg:grid-cols-2 gap-5 items-stretch">

            <div class="bg-[#f7f7f7] border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px] ">

                <div class="text-center">

                    <h1 class="font-moglan text-[#131615] text-[42px] md:text-[52px] leading-none">
                        Forgot Your Password?
                    </h1>

                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-7 max-w-[420px] mx-auto">
                        Enter your registered email address or mobile number
                        and we'll send you a password reset link.
                    </p>

                </div>

                <form class="mt-8">

                    <div>

                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address
                        </label>

                        <input
                            type="email"
                            placeholder="Enter Your Email Address"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-lg outline-none bg-white focus:border-[#B4771E] transition placeholder:text-lg">

                    </div>

                    <button
                        type="submit"
                        class="w-full h-[52px] bg-[#B4771E] text-white mt-6 text-lg font-medium hover:bg-[#b57a1f] transition">

                        Reset it

                    </button>

                </form>

            </div>

            <div class="relative overflow-hidden rounded-[8px] min-h-[320px]">

                <img
                    src="{{ asset('website/assets/images/forgot.png') }}"
                    alt=""
                    class="absolute inset-0 w-full h-full object-cover">

                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/25 to-transparent">
                </div>

                <div class="absolute bottom-0 left-0 p-6 lg:p-8 text-white">

                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">

                        Get Back To Shopping

                    </h3>

                    <p
                        class="mt-3 text-lg leading-7">

                        We'll help you regain access to your account so you can
                        continue discovering timeless jewelry pieces.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection