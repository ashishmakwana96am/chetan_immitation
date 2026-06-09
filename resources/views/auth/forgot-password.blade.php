@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="authentication-wrapper authentication-cover authentication-bg">
  <div class="authentication-inner row">

    <!-- Left illustration -->
    <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-forgot-password-illustration-light.png') }}"
          alt="auth-forgot-password-cover" class="img-fluid my-5 auth-illustration"
          data-app-light-img="illustrations/auth-forgot-password-illustration-light.png"
          data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-light.png') }}"
          alt="auth-forgot-password-cover" class="platform-bg"
          data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>

    <!-- Forgot Password form -->
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

        <h3 class="mb-1 fw-bold" style="margin-top: 24px;">Forgot Password? 🔒</h3>
        <p class="mb-4">Enter your email and we'll send you instructions to reset your password</p>

        @if(session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form id="formAuthentication" class="mb-3" action="{{ route('admin.password.email') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
              id="email" name="email" value="{{ old('email') }}"
              placeholder="Enter your email" autofocus />
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <button class="btn btn-primary d-grid w-100">Send Reset Link</button>
        </form>

        <div class="text-center">
          <a href="{{ route('admin.login') }}" class="d-flex align-items-center justify-content-center">
            <i class="ti ti-chevron-left"></i>
            Back to login
          </a>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection
