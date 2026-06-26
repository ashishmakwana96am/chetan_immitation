@extends('layouts.website')

@section('title', 'Reset Password | Chetan Imitation')

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
    .pw-strength-bar { height: 4px; border-radius: 2px; transition: width 0.3s ease, background-color 0.3s ease; }
</style>
@endsection

@section('content')

<section class="section-space">
    <div class="container-1440">
        <div class="grid lg:grid-cols-2 gap-6 items-stretch mx-auto">

            <div class="border border-[#D5D5D5] px-5 py-5 lg:py-[50px] lg:px-[30px] rounded-[8px]">

                <div class="text-center">
                    <h1 class="font-moglan text-[#131615] text-[38px] md:text-[48px] lg:text-[54px] leading-none">Set New Password</h1>
                    <p class="mt-4 text-[#3D403F] text-[15px] sm:text-[18px] leading-7 max-w-[360px] mx-auto">
                        Create a strong new password for your Chetan Imitation account.
                    </p>
                </div>

                <form id="resetForm" class="mt-8" novalidate>
                    @csrf

                    <div class="mb-5">
                        <label for="password" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            New Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input id="password" name="password" type="password" placeholder="Enter New Password"
                                class="w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200">
                            <button type="button" class="toggle-password" data-target="password" tabindex="-1">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="mt-2 bg-[#e5e7eb] rounded-full overflow-hidden h-[4px]">
                            <div id="pwStrengthBar" class="pw-strength-bar w-0 bg-[#dc2626]"></div>
                        </div>
                        <p id="pwStrengthLabel" class="text-[12px] mt-1 text-[#9ca3af]"></p>
                        <p class="text-[12px] text-[#9ca3af] mt-1">Must contain: uppercase, lowercase, number &amp; special character (min 8 chars)</p>
                        <p class="field-error hidden" id="password-error"></p>
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-lg leading-[18px] font-medium text-[#131615] mb-[15px]">
                            Confirm Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter New Password"
                                class="w-full h-[52px] border border-[#D5D5D5] px-4 pr-11 text-lg text-[#757575] outline-none bg-white placeholder:text-lg transition-colors duration-200">
                            <button type="button" class="toggle-password" data-target="password_confirmation" tabindex="-1">
                                <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="field-error hidden" id="password_confirmation-error"></p>
                    </div>

                    <button type="submit" id="resetBtn"
                        class="w-full h-[52px] bg-[#B4771E] text-white text-lg font-medium hover:bg-[#131615] transition flex items-center justify-center gap-2">
                        <span id="resetBtnText">Reset Password</span>
                        <svg id="resetSpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                </form>
            </div>

            <div class="relative overflow-hidden rounded-[8px] min-h-[400px] lg:min-h-full">
                <img src="{{ asset('website/assets/images/login.png') }}" alt="Reset Password" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#B4771E]/95 via-[#B4771E]/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full p-5 lg:p-[25px] text-white">
                    <h3 class="text-[28px] lg:text-[32px] font-bold leading-tight">Almost There!</h3>
                    <p class="mt-3 text-lg leading-7">Create a strong password to keep your account secure and continue enjoying our exclusive jewellery collections.</p>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@section('page-js')
<script>
$(function () {

    var strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#^()\-+=])[A-Za-z\d@$!%*?&_#^()\-+=]{8,}$/;

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
        $('#resetBtn').prop('disabled', false);
        $('#resetBtnText').text('Reset Password');
        $('#resetSpinner').addClass('hidden');
    }

    function checkStrength(val) {
        var s = 0;
        if (val.length >= 8) s++; if (/[a-z]/.test(val)) s++;
        if (/[A-Z]/.test(val)) s++; if (/\d/.test(val)) s++;
        if (/[@$!%*?&_#^()\-+=]/.test(val)) s++;
        return s;
    }

    $('#password').on('input', function () {
        var val = $(this).val(), score = checkStrength(val), pct = (score / 5) * 100;
        var colors = ['#dc2626','#dc2626','#f59e0b','#f59e0b','#22c55e','#16a34a'];
        var labels = ['','Very Weak','Weak','Fair','Good','Strong'];
        $('#pwStrengthBar').css({ width: pct + '%', 'background-color': colors[score] });
        $('#pwStrengthLabel').text(val.length ? labels[score] : '').css('color', colors[score]);
        if (val.length) { if (strongRegex.test(val)) markValid('password'); else { clearError('password'); $('#password').removeClass('input-valid'); } }
        if ($('#password_confirmation').val().length) validateConfirm();
    });

    function validateConfirm() {
        var pw = $('#password').val(), cpw = $('#password_confirmation').val();
        if (!cpw) { showError('password_confirmation', 'Please confirm your password.'); return false; }
        if (pw !== cpw) { showError('password_confirmation', 'Passwords do not match.'); return false; }
        markValid('password_confirmation'); return true;
    }

    $('#password').on('blur', function () {
        var v = $(this).val();
        if (!v) showError('password', 'New password is required.');
        else if (!strongRegex.test(v)) showError('password', 'Must contain uppercase, lowercase, digit & special character (min 8 chars).');
        else markValid('password');
    });

    $('#password_confirmation').on('blur', validateConfirm)
        .on('input', function () { if ($(this).hasClass('input-invalid')) clearError('password_confirmation'); });

    $(document).on('click', '.toggle-password', function () {
        var $inp = $('#' + $(this).data('target')), hidden = $inp.attr('type') === 'password';
        $inp.attr('type', hidden ? 'text' : 'password');
        $(this).find('.eye-off').toggleClass('hidden', hidden);
        $(this).find('.eye').toggleClass('hidden', !hidden);
    });

    $('#resetForm').on('submit', function (e) {
        e.preventDefault();
        var valid = true;
        var pw = $('#password').val();
        if (!pw) { showError('password', 'New password is required.'); valid = false; }
        else if (!strongRegex.test(pw)) { showError('password', 'Must contain uppercase, lowercase, digit & special character (min 8 chars).'); valid = false; }
        if (!validateConfirm()) valid = false;

        if (!valid) { $('.input-invalid').first().focus().select(); return; }

        $('#resetBtn').prop('disabled', true);
        $('#resetBtnText').text('Resetting...');
        $('#resetSpinner').removeClass('hidden');

        $.ajax({
            url: '{{ route('customer.reset-password.update') }}',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                password: $('#password').val(),
                password_confirmation: $('#password_confirmation').val(),
            },
            success: function (res) {
                if (res.status === 'success' && res.redirect_url) {
                    window.location.href = res.redirect_url;
                } else { resetBtn(); }
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
