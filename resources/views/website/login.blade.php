@extends('layouts.website')

@section('title', 'Login | Chetan Imitation')

@section('page-css')
<style>
    .field-error {
        color: #dc2626;
        font-size: 13px;
        margin-top: 6px;
    }
    .input-invalid {
        border-color: #dc2626 !important;
        background-color: #fff5f5 !important;
    }
    .input-valid {
        border-color: #16a34a !important;
    }
    .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #9ca3af;
        transition: color 0.2s;
        background: none;
        border: none;
        padding: 0;
        display: flex;
        align-items: center;
    }
    .toggle-password:hover { color: #B4771E; }
    .pw-wrap { position: relative; }
</style>
@endsection

@section('content')

<section class="section-space">
    <div class="container-1440">
        <div class="grid lg:grid-cols-2 gap-6 items-stretch mx-auto">

            {{-- ── Form Panel ── --}}
            <div class="border border-[#D5D5D5]  px-5 py-6 lg:py-[50px] lg:px-[30px] rounded-[4px]">

                <h1 class="font-moglan text-[#131615] text-[42px] md:text-[54px] leading-none">
                    Login To Your Account
                </h1>

                <p class="mt-5 text-[#3D403F] text-lg">
                    Access your account, track orders, and save favorite jewelry.
                </p>

                @if(session('success'))
                <div class="mt-5 flex items-start gap-3 bg-[#f0faf4] border border-[#22c55e] rounded-[6px] px-4 py-3">
                    <svg class="shrink-0 mt-[2px] w-5 h-5 text-[#16a34a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[#15803d] text-base leading-snug">{{ session('success') }}</p>
                </div>
                @endif

                <form id="loginForm" action="{{ route('login.store') }}" method="POST" class="mt-10" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div class="mb-5">
                        <label for="email" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address <span class="text-[#dc2626]">*</span>
                        </label>
                        <input
                            id="email" name="email" type="email"
                            value="{{ old('email') }}"
                            placeholder="Enter Your Email Address"
                            class="login-input w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors rounded-sm duration-200 {{ $errors->has('email') ? 'input-invalid' : '' }}">
                        @error('email')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="email-error"></p>
                    </div>

                    {{-- Password --}}
                    <div class="mb-5">
                        <label for="password" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input
                                id="password" name="password" type="password"
                                placeholder="Enter Your Password"
                                class="login-input w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors rounded-sm duration-200 {{ $errors->has('password') ? 'input-invalid' : '' }}">
                            <button type="button" class="toggle-password" data-target="password" tabindex="-1" aria-label="Toggle password visibility">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="password-error"></p>
                    </div>

                    {{-- Remember + Forgot --}}
                    <div class="flex items-center justify-between mt-5 flex-wrap gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                class="w-4 h-4 border border-[#d6d6d6] accent-[#B4771E]"
                                {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-base text-[#3D403F]">Remember Me</span>
                        </label>
                        <a href="{{ route('forgot-password') }}" class="text-lg text-[#131615] hover:text-[#B4771E] transition">
                            Forgot Password?
                        </a>
                    </div>

                    <button
                        type="submit" id="loginBtn"
                        class="w-full h-[52px] bg-[#B4771E] text-white mt-6 text-lg font-medium hover:bg-[#9d6719] transition flex items-center justify-center gap-2 rounded-sm">
                        <span id="loginBtnText">Log In</span>
                        <svg id="loginSpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                </form>

                <div class="text-center mt-[30px] text-lg font-semibold">
                    <span class="text-[#131615]">Don't have an account?</span>
                    <a href="{{ route('register') }}" class="text-[#B4771E] ml-1">Create account</a>
                </div>

            </div>

            {{-- ── Image Panel ── --}}
            <div class="relative overflow-hidden rounded-[8px] min-h-[400px] lg:min-h-full">
                <img src="{{ asset('website/assets/images/login.png') }}" alt="Login" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full p-5 lg:p-[25px] text-white">
                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">
                        Timeless Elegance Awaits
                    </h3>
                    <p class="mt-3 text-lg leading-7">
                        Sign in to discover exquisite jewelry collections
                        crafted for every celebration and special moment.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@section('page-js')
<script>
$(function () {

    function showError(field, msg) {
        $('#' + field).addClass('input-invalid').removeClass('input-valid');
        $('#' + field + '-error').text(msg).removeClass('hidden');
    }

    function clearError(field) {
        $('#' + field).removeClass('input-invalid');
        $('#' + field + '-error').addClass('hidden').text('');
    }

    function markValid(field) {
        $('#' + field).addClass('input-valid').removeClass('input-invalid');
        $('#' + field + '-error').addClass('hidden').text('');
    }

    /* ── Blur validation ── */
    $('#email').on('blur', function () {
        var v  = $.trim($(this).val());
        var rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!v)              showError('email', 'Email address is required.');
        else if (!rx.test(v)) showError('email', 'Please enter a valid email address.');
        else                 markValid('email');
    }).on('input', function () {
        if ($(this).hasClass('input-invalid')) clearError('email');
    });

    $('#password').on('blur', function () {
        var v = $(this).val();
        if (!v) showError('password', 'Password is required.');
        else    markValid('password');
    }).on('input', function () {
        if ($(this).hasClass('input-invalid')) clearError('password');
    });

    /* ── Password show / hide toggle ── */
    $(document).on('click', '.toggle-password', function () {
        var targetId = $(this).data('target');
        var $input   = $('#' + targetId);
        var isHidden = $input.attr('type') === 'password';
        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('.eye-off').toggleClass('hidden', isHidden);
        $(this).find('.eye').toggleClass('hidden', !isHidden);
    });

    /* ── Submit validation ── */
    $('#loginForm').on('submit', function (e) {
        var valid = true;

        var email = $.trim($('#email').val());
        var emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email)              { showError('email', 'Email address is required.'); valid = false; }
        else if (!emailRx.test(email)) { showError('email', 'Please enter a valid email address.'); valid = false; }

        var pw = $('#password').val();
        if (!pw) { showError('password', 'Password is required.'); valid = false; }

        if (!valid) {
            e.preventDefault();
            var $first = $('.input-invalid').first();
            if ($first.length) {
                $('html,body').animate({ scrollTop: $first.offset().top - 100 }, 300);
                $first.focus();
            }
            return;
        }

        $('#loginBtn').prop('disabled', true);
        $('#loginBtnText').text('Logging In...');
        $('#loginSpinner').removeClass('hidden');
    });

});
</script>
@endsection
