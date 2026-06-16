@extends('layouts.website')

@section('title', 'Login | Chetan Imitation')

@section('content')

<section class="section-space">

    <div class="container-1440">

        <div class="grid lg:grid-cols-2 gap-6 items-stretch mx-auto">

            <div class="border border-[#D5D5D5] bg-[#f7f7f7] px-5 py-6 lg:py-[50px] lg:px-[30px] rounded-[4px]">

                <h1 class="font-moglan text-[#131615] text-[42px] md:text-[54px] leading-none">
                    Login To Your Account
                </h1>

                <p class="mt-5 text-[#3D403F] text-lg">
                    Access your account, track orders, and save favorite jewelry.
                </p>

                <form class="mt-10">

                    <div>
                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address
                        </label>

                        <input
                            type="email"
                            placeholder="Enter Your Email Address"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-text-lg text-[#757575] outline-none bg-white placeholder:text-lg">
                    </div>

                    <div class="mt-6">
                        <label class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Password
                        </label>

                        <input
                            type="password"
                            placeholder="Enter Your Password"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg">
                    </div>

                    <div class="flex items-center justify-between mt-5 flex-wrap gap-3">

                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                class="w-4 h-4 border border-[#d6d6d6]">

                            <span class="text-[14px] text-[#3D403F]">
                                Remember Me
                            </span>

                        </label>

                        <a
                            href="{{ route('forgot-password') }}"
                            class="text-lg text-[#131615] hover:text-[#B4771E] transition">

                            Forgot Password?

                        </a>

                    </div>

                    <button
                        type="submit"
                        class="w-full h-[52px] bg-[#B4771E] text-white mt-6 text-lg font-medium hover:bg-[#b57a1f] transition">

                        Log In

                    </button>

                </form>

                <div class="text-center mt-[30px] text-lg">

                    <span class="text-[#131615]">
                        Don't have an account?
                    </span>

                    <a
                        href="{{ route('register') }}"
                        class="text-[#B4771E] ml-1">

                        Create account

                    </a>

                </div>

            </div>

            <div class="relative overflow-hidden rounded-[4px] min-h-[400px] lg:min-h-full">

                <img
                    src="{{ asset('website/assets/images/login.png') }}"
                    alt=""
                    class="absolute inset-0 w-full h-full">

                <div
                    class="absolute bottom-0 left-0 w-full p-5 lg:p-[25px] text-white">

                    <h3
                        class="text-[28px] lg:text-[32px] font-bold leading-tight">

                        Timeless Elegance Awaits

                    </h3>

                    <p
                        class="mt-3 text-lg leading-7">

                        Sign in to discover exquisite jewelry collections
                        crafted for every celebration and special moment.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection