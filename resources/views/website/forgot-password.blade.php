@extends('layouts.website')

@section('title', 'Forgot Password | Chetan Imitation')

@section('page-css')
<style>
    .field-error { color: #dc2626; font-size: 13px; margin-top: 6px; }
    .input-invalid { border-color: #dc2626 !important; background-color: #fff5f5 !important; }
    .input-valid { border-color: #16a34a !important; }
</style>
@endsection

@section('content')

<section class="section-space">
    <div class="container-1440">
        <div class="mx-auto grid lg:grid-cols-2 gap-5 items-stretch">

            <div class=" border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px]">

                <div class="text-center">
                    <h1 class="font-moglan text-[#131615] text-[42px] md:text-[52px] leading-none">Forgot Your Password?</h1>
                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-7 max-w-[420px] mx-auto">
                        Enter your registered email address and we'll send you a 6-digit OTP to reset your password.
                    </p>
                </div>

                <div id="otpSentAlert" class="hidden mt-6 flex items-start gap-3 bg-[#f0faf4] border border-[#22c55e] rounded-[6px] px-4 py-3">
                    <svg class="shrink-0 mt-[2px] w-5 h-5 text-[#16a34a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[#15803d] text-base leading-snug" id="otpSentMsg"></p>
                </div>

                @if(session('error'))
                <div class="mt-6 flex items-start gap-3 bg-[#fff5f5] border border-[#dc2626] rounded-[6px] px-4 py-3">
                    <p class="text-[#dc2626] text-base">{{ session('error') }}</p>
                </div>
                @endif

                <form id="forgotForm" class="mt-5 xl:mt-8" novalidate>
                    @csrf
                    <div class="mb-6">
                        <label for="email" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address <span class="text-[#dc2626]">*</span>
                        </label>
                        <input id="email" name="email" type="email" placeholder="Enter Your Email Address"
                            class="w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white focus:border-[#B4771E] transition placeholder:text-lg rounded-sm">
                        <p class="field-error hidden" id="email-error"></p>
                    </div>

                    <button type="submit" id="sendBtn"
                        class="w-full h-[52px] bg-[#B4771E] text-white text-lg font-medium hover:bg-[#131615] transition flex items-center justify-center gap-2">
                        <span id="sendBtnText">Send OTP</span>
                        <svg id="sendSpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                </form>

                <div class="text-center mt-5">
                    <a href="{{ route('login') }}" class="text-[#B4771E] text-lg">← Back to Login</a>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-[8px] min-h-[320px]">
                <img src="{{ asset('website/assets/images/forgot.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/25 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6 lg:p-8 text-white">
                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">Get Back To Shopping</h3>
                    <p class="mt-3 text-lg leading-7">We'll help you regain access to your account so you can continue discovering timeless jewellery pieces.</p>
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
        $('#sendBtn').prop('disabled', false);
        $('#sendBtnText').text('Send OTP');
        $('#sendSpinner').addClass('hidden');
    }

    $('#email').on('blur', function () {
        var v = $.trim($(this).val()), rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!v) showError('email', 'Email address is required.');
        else if (!rx.test(v)) showError('email', 'Please enter a valid email address.');
        else markValid('email');
    }).on('input', function () { if ($(this).hasClass('input-invalid')) clearError('email'); });

    $('#forgotForm').on('submit', function (e) {
        e.preventDefault();
        var email = $.trim($('#email').val()), rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) { showError('email', 'Email address is required.'); $('#email').focus().select(); return; }
        if (!rx.test(email)) { showError('email', 'Please enter a valid email address.'); $('#email').focus().select(); return; }

        $('#sendBtn').prop('disabled', true);
        $('#sendBtnText').text('Sending OTP...');
        $('#sendSpinner').removeClass('hidden');
        $('#otpSentAlert').addClass('hidden');

        $.ajax({
            url: '{{ route('forgot-password.send-otp') }}',
            method: 'POST',
            data: { _token: $('input[name="_token"]').val(), email: email },
            success: function (res) {
                if (res.status === 'success' && res.redirect_url) {
                    window.location.href = res.redirect_url;
                } else {
                    resetBtn();
                }
            },
            error: function (xhr) {
                resetBtn();
                var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                $.each(errors, function (field, msgs) { showError(field, msgs[0]); });
                $('.input-invalid').first().focus().select();
            }
        });
    });

});
</script>
@endsection
