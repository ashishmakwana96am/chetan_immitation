@extends('layouts.website')

@section('title', 'Create Account | Chetan Imitation')

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
    .pw-strength-bar {
        height: 4px;
        border-radius: 2px;
        transition: width 0.3s ease, background-color 0.3s ease;
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
        <div class="grid lg:grid-cols-2 gap-5 items-stretch">

            {{-- ── Form Panel ── --}}
            <div class="bg-[#f7f7f7] border border-[#D5D5D5] rounded-[8px] px-5 py-6 lg:py-[50px] lg:px-[30px]">

                <div class="text-center">
                    <h1 class="font-moglan text-[#131615] text-[38px] md:text-[48px] lg:text-[54px] leading-none">
                        Create Your Account
                    </h1>
                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-7 max-w-[340px] mx-auto">
                        Join Chetan Imitation to save favorites, track orders, and shop with ease.
                    </p>
                </div>

                <form id="registerForm" action="{{ route('register.store') }}" method="POST" class="mt-8" novalidate>
                    @csrf

                    {{-- Full Name --}}
                    <div class="mb-5">
                        <label for="name" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Full Name <span class="text-[#dc2626]">*</span>
                        </label>
                        <input
                            id="name" name="name" type="text"
                            value="{{ old('name') }}"
                            placeholder="Enter Your Full Name"
                            class="reg-input w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200 {{ $errors->has('name') ? 'input-invalid' : '' }}">
                        @error('name')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="name-error"></p>
                    </div>

                    {{-- Mobile Number --}}
                    <div class="mb-5">
                        <label for="phone" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Mobile Number <span class="text-[#dc2626]">*</span>
                        </label>
                        <input
                            id="phone" name="phone" type="tel"
                            value="{{ old('phone') }}"
                            placeholder="Enter Your 10-digit Mobile Number"
                            maxlength="10"
                            class="reg-input w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200 {{ $errors->has('phone') ? 'input-invalid' : '' }}">
                        @error('phone')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="phone-error"></p>
                    </div>

                    {{-- Email --}}
                    <div class="mb-5">
                        <label for="email" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Email Address <span class="text-[#dc2626]">*</span>
                        </label>
                        <input
                            id="email" name="email" type="email"
                            value="{{ old('email') }}"
                            placeholder="Enter Your Email Address"
                            class="reg-input w-full h-[52px] border border-[#D5D5D5] px-4 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200 {{ $errors->has('email') ? 'input-invalid' : '' }}">
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
                                class="reg-input w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200 {{ $errors->has('password') ? 'input-invalid' : '' }}">
                            <button type="button" class="toggle-password" data-target="password" tabindex="-1" aria-label="Toggle password visibility">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        {{-- Strength bar --}}
                        <div class="mt-2 bg-[#e5e7eb] rounded-full overflow-hidden h-[4px]">
                            <div id="pwStrengthBar" class="pw-strength-bar w-0 bg-[#dc2626]"></div>
                        </div>
                        <p id="pwStrengthLabel" class="text-[12px] mt-1 text-[#9ca3af]"></p>
                        <p class="text-[12px] text-[#9ca3af] mt-1">Must contain: uppercase, lowercase, number &amp; special character (min 8 chars)</p>
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="password-error"></p>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Confirm Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input
                                id="password_confirmation" name="password_confirmation" type="password"
                                placeholder="Re-enter Your Password"
                                class="reg-input w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200">
                            <button type="button" class="toggle-password" data-target="password_confirmation" tabindex="-1" aria-label="Toggle confirm password visibility">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-error hidden" id="password_confirmation-error"></p>
                    </div>

                    <button
                        type="submit" id="registerBtn"
                        class="w-full h-[52px] bg-[#B4771E] text-white text-lg font-medium hover:bg-[#9d6719] transition flex items-center justify-center gap-2">
                        <span id="registerBtnText">Create Account</span>
                        <svg id="registerSpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                </form>

                <div class="text-center mt-4">
                    <span class="text-[#131615] text-lg">Already have an account?</span>
                    <a href="{{ route('login') }}" class="text-[#B4771E] ml-1 text-lg font-medium">Login</a>
                </div>

            </div>

            {{-- ── Image Panel ── --}}
            <div class="relative overflow-hidden rounded-[8px] min-h-[420px] lg:min-h-full">
                <img src="{{ asset('website/assets/images/account.png') }}" alt="Register" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/20 to-transparent"></div>
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

    /* ── Password strength ── */
    var strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#^()\-+=])[A-Za-z\d@$!%*?&_#^()\-+=]{8,}$/;

    function checkStrength(val) {
        var score = 0;
        if (val.length >= 8)                score++;
        if (/[a-z]/.test(val))              score++;
        if (/[A-Z]/.test(val))              score++;
        if (/\d/.test(val))                 score++;
        if (/[@$!%*?&_#^()\-+=]/.test(val)) score++;
        return score;
    }

    $('#password').on('input', function () {
        var val    = $(this).val();
        var score  = checkStrength(val);
        var pct    = (score / 5) * 100;
        var colors = ['#dc2626','#dc2626','#f59e0b','#f59e0b','#22c55e','#16a34a'];
        var labels = ['','Very Weak','Weak','Fair','Good','Strong'];

        $('#pwStrengthBar').css({ width: pct + '%', 'background-color': colors[score] });
        $('#pwStrengthLabel').text(val.length ? labels[score] : '').css('color', colors[score]);

        if (val.length) {
            if (strongRegex.test(val)) markValid('password');
            else { clearError('password'); $('#password').removeClass('input-valid'); }
        }
        if ($('#password_confirmation').val().length) validateConfirm();
    });

    function validateConfirm() {
        var pw  = $('#password').val();
        var cpw = $('#password_confirmation').val();
        if (!cpw)       { showError('password_confirmation', 'Please confirm your password.'); return false; }
        if (pw !== cpw) { showError('password_confirmation', 'Passwords do not match.');       return false; }
        markValid('password_confirmation');
        return true;
    }

    /* ── Blur validation ── */
    $('#name').on('blur', function () {
        var v = $.trim($(this).val());
        if (!v)          showError('name', 'Full name is required.');
        else if (v.length > 100) showError('name', 'Name may not be greater than 100 characters.');
        else             markValid('name');
    }).on('input', function () { if ($(this).hasClass('input-invalid')) clearError('name'); });

    $('#phone').on('blur', function () {
        var v = $.trim($(this).val());
        if (!v)                        showError('phone', 'Mobile number is required.');
        else if (!/^[0-9]{10}$/.test(v)) showError('phone', 'Please enter a valid 10-digit mobile number.');
        else                           markValid('phone');
    }).on('input', function () {
        $(this).val($(this).val().replace(/\D/g, ''));
        if ($(this).hasClass('input-invalid')) clearError('phone');
    });

    $('#email').on('blur', function () {
        var v = $.trim($(this).val());
        var rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!v)         showError('email', 'Email address is required.');
        else if (!rx.test(v)) showError('email', 'Please enter a valid email address.');
        else            markValid('email');
    }).on('input', function () { if ($(this).hasClass('input-invalid')) clearError('email'); });

    $('#password').on('blur', function () {
        var v = $(this).val();
        if (!v)                    showError('password', 'Password is required.');
        else if (!strongRegex.test(v)) showError('password', 'Must contain uppercase, lowercase, digit & special character (min 8 chars).');
        else                       markValid('password');
    });

    $('#password_confirmation').on('blur', validateConfirm)
        .on('input', function () { if ($(this).hasClass('input-invalid')) clearError('password_confirmation'); });

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
    $('#registerForm').on('submit', function (e) {
        var valid = true;

        var name = $.trim($('#name').val());
        if (!name)             { showError('name', 'Full name is required.'); valid = false; }
        else if (name.length > 100) { showError('name', 'Name may not be greater than 100 characters.'); valid = false; }

        var phone = $.trim($('#phone').val());
        if (!phone)                          { showError('phone', 'Mobile number is required.'); valid = false; }
        else if (!/^[0-9]{10}$/.test(phone)) { showError('phone', 'Please enter a valid 10-digit mobile number.'); valid = false; }

        var email = $.trim($('#email').val());
        var emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email)             { showError('email', 'Email address is required.'); valid = false; }
        else if (!emailRx.test(email)) { showError('email', 'Please enter a valid email address.'); valid = false; }

        var pw = $('#password').val();
        if (!pw)                    { showError('password', 'Password is required.'); valid = false; }
        else if (!strongRegex.test(pw)) { showError('password', 'Must contain uppercase, lowercase, digit & special character (min 8 chars).'); valid = false; }

        if (!validateConfirm()) valid = false;

        if (!valid) {
            e.preventDefault();
            var $first = $('.input-invalid').first();
            if ($first.length) {
                $('html,body').animate({ scrollTop: $first.offset().top - 100 }, 300);
                $first.focus();
            }
            return;
        }

        $('#registerBtn').prop('disabled', true);
        $('#registerBtnText').text('Creating Account...');
        $('#registerSpinner').removeClass('hidden');
    });

});
</script>
@endsection
