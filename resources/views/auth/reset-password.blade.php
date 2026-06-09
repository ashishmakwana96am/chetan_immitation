@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="authentication-wrapper authentication-cover authentication-bg">
  <div class="authentication-inner row">

    <!-- Left illustration -->
    <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-reset-password-illustration-light.png') }}"
          alt="auth-reset-password-cover" class="img-fluid my-5 auth-illustration"
          data-app-light-img="illustrations/auth-reset-password-illustration-light.png"
          data-app-dark-img="illustrations/auth-reset-password-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-light.png') }}"
          alt="auth-reset-password-cover" class="platform-bg"
          data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>

    <!-- Reset Password form -->
    <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
      <div class="w-px-400 mx-auto">

        <!-- Logo -->
        <div class="app-brand mb-4" style="overflow: visible;">
          <a href="{{ route('admin.login') }}" class="app-brand-link gap-2" style="width: 100%; overflow: visible;">
            <span class="app-brand-logo ecom" style="display: flex; justify-content: center; width: 100%; overflow: visible;">
              <img src="{{ asset('assets/img/logo.png') }}" alt="Chetan Imitation" style="width: 100%; max-width: 160px; height: auto; display: block;" />
            </span>
          </a>
        </div>

        <h3 class="mb-1 fw-bold" style="margin-top: 24px;">Reset Password 🔒</h3>
        <p class="mb-4">for <span class="fw-bold">{{ $email }}</span></p>

        <form id="formAuthentication" class="mb-3" action="{{ route('admin.password.update') }}" method="POST">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}" />
          <input type="hidden" name="email" value="{{ $email }}" />

          <div class="mb-3 form-password-toggle">
            <label class="form-label" for="password">New Password</label>
            <div class="input-group input-group-merge">
              <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                name="password" placeholder="············" />
              <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
            </div>
            @error('password')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3 form-password-toggle">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <div class="input-group input-group-merge">
              <input type="password" id="password_confirmation" class="form-control"
                name="password_confirmation" placeholder="············" />
              <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
            </div>
          </div>

          <button class="btn btn-primary d-grid w-100 mb-3">Set New Password</button>

          <div class="text-center">
            <a href="{{ route('admin.login') }}" class="d-flex align-items-center justify-content-center">
              <i class="ti ti-chevron-left"></i>
              Back to login
            </a>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>
@endsection
