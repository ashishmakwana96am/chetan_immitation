@extends('layouts.website')

@section('title', 'Login | Chetan Imitation')

@section('page-css')
<style>
    .field-error { color: #dc2626; font-size: 13px; margin-top: 6px; }
    .input-invalid { border-color: #dc2626 !important; background-color: #fff5f5 !important; }
    .input-valid { border-color: #16a34a !important; }
    .toggle-password {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        cursor: pointer; color: #9ca3af; transition: color 0.2s;
        background: none; border: none; padding: 0; display: flex; align-items: center;
    }
    .toggle-password:hover { color: #B4771E; }
    .pw-wrap { position: relative; }
    #remember:checked + svg { opacity: 1; }
</style>
@endsection

@section('content')

<section class="section-space">
    <div class="container-1440">
        <div class="grid lg:grid-cols-2 gap-6 items-stretch mx-auto">

            <div class="border border-[#D5D5D5] px-5 py-6 lg:py-10 lg:px-[30px] rounded-[4px]">

                <h1 class="font-moglan text-[#131615] text-[42px] md:text-[54px] leading-none">
                    Login To Your Account
                </h1>
                <p class="mt-5 text-[#3D403F] text-lg">
                    Access your account, track orders, and save favorite jewelry.
                </p>

                {{-- Flash / query-param success --}}
                <div id="successAlert" class="{{ session('success') || request('registered') || request('reset') ? '' : 'hidden' }} mt-5 flex items-start gap-3 bg-[#f0faf4] border border-[#22c55e] rounded-[6px] px-4 py-3">
                    <svg class="shrink-0 mt-[2px] w-5 h-5 text-[#16a34a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[#15803d] text-base leading-snug" id="successMsg">
                        @if(session('success'))
                            {{ session('success') }}
                        @elseif(request('registered'))
                            Account created successfully! Welcome to Chetan Imitation. Please log in.
                        @elseif(request('reset'))
                            Password reset successfully! Please log in with your new password.
                        @endif
                    </p>
                </div>

                <form id="loginForm" class="mt-10" novalidate>
                    @csrf

                    <div class="mb-5">
                        <label for="email" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address <span class="text-[#dc2626]">*</span>
                        </label>
                        <input id="email" name="email" type="email"
                            placeholder="Enter Your Email Address"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors rounded-sm duration-200">
                        <p class="field-error hidden" id="email-error"></p>
                    </div>

                    <div class="mb-5">
                        <label for="password" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input id="password" name="password" type="password"
                                placeholder="Enter Your Password"
                                class="w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors rounded-sm duration-200">
                            <button type="button" class="toggle-password" data-target="password" tabindex="-1">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="field-error hidden" id="password-error"></p>
                    </div>

                    <div class="flex items-center justify-between mt-5 flex-wrap gap-3">
                        <label class="flex items-center gap-[10px] cursor-pointer select-none group" for="remember">
                            <span class="relative flex items-center justify-center w-[22px] h-[22px] shrink-0 rounded-[5px] border-2 border-[#B4771E] bg-white transition-colors duration-200">
                                <input type="checkbox" name="remember" id="remember" class="absolute opacity-0 w-0 h-0 peer">
                                <svg class="w-[13px] h-[13px] text-[#B4771E] opacity-0 peer-checked:opacity-100 transition-opacity duration-200" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5L4.5 8.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="text-base text-[#3D403F]">Remember Me</span>
                        </label>
                        <a href="{{ route('forgot-password') }}" class="text-lg text-[#131615] hover:text-[#B4771E] transition">Forgot Password?</a>
                    </div>

                    <button type="submit" id="loginBtn"
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

            <div class="relative overflow-hidden rounded-[8px] min-h-[400px] lg:min-h-full">
                <img src="{{ asset('website/assets/images/login.png') }}" alt="Login" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full p-5 lg:p-[25px] text-white">
                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">Timeless Elegance Awaits</h3>
                    <p class="mt-3 text-lg leading-7">Sign in to discover exquisite jewelry collections crafted for every celebration and special moment.</p>
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
        $('#' + field).removeClass('input-invalid input-valid');
        $('#' + field + '-error').addClass('hidden').text('');
    }
    function markValid(field) {
        $('#' + field).addClass('input-valid').removeClass('input-invalid');
        $('#' + field + '-error').addClass('hidden').text('');
    }
    function resetBtn() {
        $('#loginBtn').prop('disabled', false);
        $('#loginBtnText').text('Log In');
        $('#loginSpinner').addClass('hidden');
    }

    $('#email').on('blur', function () {
        var v = $.trim($(this).val()), rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!v) showError('email', 'Email address is required.');
        else if (!rx.test(v)) showError('email', 'Please enter a valid email address.');
        else markValid('email');
    }).on('input', function () { if ($(this).hasClass('input-invalid')) clearError('email'); });

    $('#password').on('blur', function () {
        if (!$(this).val()) showError('password', 'Password is required.');
        else markValid('password');
    }).on('input', function () { if ($(this).hasClass('input-invalid')) clearError('password'); });

    $(document).on('click', '.toggle-password', function () {
        var $inp = $('#' + $(this).data('target')), hidden = $inp.attr('type') === 'password';
        $inp.attr('type', hidden ? 'text' : 'password');
        $(this).find('.eye-off').toggleClass('hidden', hidden);
        $(this).find('.eye').toggleClass('hidden', !hidden);
    });

    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        var valid = true;
        var email = $.trim($('#email').val()), rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email)          { showError('email', 'Email address is required.'); valid = false; }
        else if (!rx.test(email)) { showError('email', 'Please enter a valid email address.'); valid = false; }

        if (!$('#password').val()) { showError('password', 'Password is required.'); valid = false; }

        if (!valid) {
            $('html,body').animate({ scrollTop: $('.input-invalid').first().offset().top - 100 }, 300);
            return;
        }

        $('#loginBtn').prop('disabled', true);
        $('#loginBtnText').text('Logging In...');
        $('#loginSpinner').removeClass('hidden');
        $('#successAlert').addClass('hidden');

        var pendingWishlist = sessionStorage.getItem('pendingWishlist');

        $.ajax({
            url: '{{ route('login.store') }}',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                email: $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked') ? 1 : 0,
                intended: new URLSearchParams(window.location.search).get('intended') || '',
                pending_wishlist: pendingWishlist || '',
            },
            success: function (res) {
                if (res.status === 'success' && res.redirect_url) {
                    if (pendingWishlist) {
                        sessionStorage.setItem('wishlistToastPending', 'Product added to your wishlist! ❤️');
                        sessionStorage.removeItem('pendingWishlist');
                    }
                    window.location.href = res.redirect_url;
                } else {
                    resetBtn();
                }
            },
            error: function (xhr) {
                resetBtn();
                var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                $.each(errors, function (field, msgs) {
                    showError(field, msgs[0]);
                });
                $('html,body').animate({ scrollTop: $('.input-invalid').first().offset().top - 100 }, 300);
            }
        });
    });

});
</script>
@endsection
