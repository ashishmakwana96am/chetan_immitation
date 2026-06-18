@extends('layouts.website')

@section('title', 'OTP Verification | Chetan Imitation')

@section('page-css')
<style>
    .otp-input {
        width: 52px; height: 56px; border: 1px solid #D5D5D5;
        text-align: center; font-size: 22px; font-weight: 600; color: #131615;
        outline: none; background: #fff; border-radius: 4px; transition: border-color .2s;
    }
    .otp-input:focus { border-color: #B4771E; }
    .otp-input.input-invalid { border-color: #dc2626 !important; background: #fff5f5; }
    .otp-input.input-valid   { border-color: #16a34a !important; }
    .field-error { color: #dc2626; font-size: 13px; margin-top: 8px; }
    @media (min-width: 480px) { .otp-input { width: 64px; height: 64px; } }
    @media (min-width: 1024px) { .otp-input { width: 72px; height: 72px; } }
</style>
@endsection

@section('content')

<section class="section-space">
    <div class="container-1440">
        <div class="grid lg:grid-cols-2 gap-5">

            <div class="border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px]">

                <div class="text-center">
                    <h1 class="font-moglan text-[#131615] text-[34px] sm:text-[42px] lg:text-[54px] leading-none">Verify Your OTP</h1>
                    @php $otpEmail = request('email') ?: ''; @endphp
                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-6 max-w-[420px] mx-auto">
                        Enter the 6-digit code sent to
                        @if($otpEmail) <strong>{{ $otpEmail }}</strong>. @else your registered email address. @endif
                        The code expires in <strong>10 minutes</strong>.
                    </p>
                </div>

                <div id="resentAlert" class="hidden mt-5 flex items-start gap-3 bg-[#f0faf4] border border-[#22c55e] rounded-[6px] px-4 py-3">
                    <svg class="shrink-0 mt-[2px] w-5 h-5 text-[#16a34a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[#15803d] text-base">A new OTP has been sent to your email.</p>
                </div>

                <div class="mt-8">
                    <input type="hidden" id="hiddenEmail" value="{{ $otpEmail }}">

                    <div class="flex justify-center gap-2 sm:gap-3">
                        @for($i = 1; $i <= 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-input otp-digit" autocomplete="off">
                        @endfor
                    </div>
                    <input type="hidden" id="otpHidden">
                    <p class="field-error text-center hidden" id="otp-error"></p>

                    <button id="verifyBtn"
                        class="w-full h-[52px] bg-[#B4771E] text-white mt-7 text-lg font-medium hover:bg-[#9d6719] transition flex items-center justify-center gap-2">
                        <span id="verifyBtnText">Verify OTP</span>
                        <svg id="verifySpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                </div>

                <div class="mt-5 text-center">
                    <span class="text-[#757575] text-base">Didn't receive the code?</span>
                    <button type="button" id="resendBtn" class="text-[#B4771E] text-base font-medium ml-1 hover:underline">Resend OTP</button>
                    <span id="resendCountdown" class="text-[#757575] text-sm ml-1 hidden"></span>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('forgot-password') }}" class="text-[#B4771E] text-base hover:underline">← Change Email</a>
                </div>
            </div>

            <div class="relative rounded-[4px] overflow-hidden min-h-[320px]">
                <img src="{{ asset('website/assets/images/otpverification.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/25 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6 lg:p-8 text-white">
                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">Secure Your Journey</h3>
                    <p class="mt-3 text-lg leading-7">Verify your account to access exclusive collections, track orders, and enjoy a seamless jewelry shopping experience.</p>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@section('page-js')
<script>
$(function () {

    var $digits = $('.otp-digit');

    $digits.on('input', function () {
        var val = $(this).val().replace(/\D/g, '');
        $(this).val(val ? val[0] : '');
        if (val) { var next = $digits.index(this) + 1; if (next < $digits.length) $digits.eq(next).focus(); }
        syncHidden(); clearOtpError();
    });

    $digits.on('keydown', function (e) {
        if (e.key === 'Backspace' && !$(this).val()) {
            var prev = $digits.index(this) - 1;
            if (prev >= 0) { $digits.eq(prev).val('').focus(); }
            syncHidden();
        }
        if (e.key === 'ArrowLeft') { var p = $digits.index(this)-1; if(p>=0) $digits.eq(p).focus(); }
        if (e.key === 'ArrowRight') { var n = $digits.index(this)+1; if(n<$digits.length) $digits.eq(n).focus(); }
    });

    $digits.on('paste', function (e) {
        e.preventDefault();
        var pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        $digits.each(function (i) { $(this).val(pasted[i] || ''); });
        syncHidden(); clearOtpError();
        $digits.eq(Math.min(pasted.length, 5)).focus();
    });

    function syncHidden() {
        var otp = ''; $digits.each(function () { otp += $(this).val(); }); $('#otpHidden').val(otp);
    }
    function clearOtpError() {
        $digits.removeClass('input-invalid'); $('#otp-error').addClass('hidden').text('');
    }
    function resetVerifyBtn() {
        $('#verifyBtn').prop('disabled', false);
        $('#verifyBtnText').text('Verify OTP');
        $('#verifySpinner').addClass('hidden');
    }

    $('#verifyBtn').on('click', function () {
        syncHidden();
        var otp = $('#otpHidden').val();
        var email = $('#hiddenEmail').val();

        if (!otp || otp.length < 6) {
            $digits.addClass('input-invalid');
            $('#otp-error').text('Please enter the complete 6-digit OTP.').removeClass('hidden');
            $digits.eq(0).focus(); return;
        }
        if (!email) { alert('Email is missing. Please go back.'); return; }

        $(this).prop('disabled', true);
        $('#verifyBtnText').text('Verifying...');
        $('#verifySpinner').removeClass('hidden');

        $.ajax({
            url: '{{ route('otp-verification.verify') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', email: email, otp: otp },
            success: function (res) {
                if (res.status === 'success' && res.redirect_url) {
                    window.location.href = res.redirect_url;
                } else { resetVerifyBtn(); }
            },
            error: function (xhr) {
                resetVerifyBtn();
                var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                if (errors.otp) {
                    $digits.addClass('input-invalid');
                    $('#otp-error').text(errors.otp[0]).removeClass('hidden');
                }
            }
        });
    });

    // Resend OTP
    function startResendCooldown() {
        var secs = 30;
        $('#resendBtn').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        $('#resendCountdown').text('(' + secs + 's)').removeClass('hidden');
        var timer = setInterval(function () {
            secs--;
            if (secs <= 0) {
                clearInterval(timer);
                $('#resendBtn').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                $('#resendCountdown').addClass('hidden').text('');
            } else { $('#resendCountdown').text('(' + secs + 's)'); }
        }, 1000);
    }

    $('#resendBtn').on('click', function () {
        var email = $('#hiddenEmail').val();
        if (!email) { alert('Email is missing. Please go back.'); return; }

        $(this).prop('disabled', true);
        $('#resentAlert').addClass('hidden');

        $.ajax({
            url: '{{ route('otp-verification.resend') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', email: email },
            success: function (res) {
                if (res.status === 'success') {
                    $('#resentAlert').removeClass('hidden');
                    startResendCooldown();
                }
            },
            error: function () { $('#resendBtn').prop('disabled', false); }
        });
    });

});
</script>
@endsection
