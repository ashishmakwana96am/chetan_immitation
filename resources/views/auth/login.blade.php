@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="authentication-wrapper authentication-cover authentication-bg">
  <div class="authentication-inner row">

    <!-- Left illustration -->
    <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-login-illustration-light.png') }}"
          alt="auth-login-cover" class="img-fluid my-5 auth-illustration"
          data-app-light-img="illustrations/auth-login-illustration-light.png"
          data-app-dark-img="illustrations/auth-login-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-light.png') }}"
          alt="auth-login-cover" class="platform-bg"
          data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>

    <!-- Login form -->
    <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
      <div class="w-px-400 mx-auto">

        <!-- Logo -->
        <div class="app-brand mb-4" style="overflow: visible;">
          <a href="{{ url('/') }}" class="app-brand-link gap-2" style="width: 100%; overflow: visible;">
            <span class="app-brand-logo ecom" style="display: flex; justify-content: flex-start; width: 100%; overflow: visible;">
              <img src="{{ asset('assets/img/logo.png') }}" alt="Chetan Imitation" style="width: 100%; max-width: 160px; height: auto; display: block;" />
            </span>
          </a>
        </div>

        <h3 class="mb-1 fw-bold" style="margin-top: 45px;">Welcome back! 👋</h3>
        <p class="mb-4">Please sign-in to your account</p>

        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form id="loginForm" class="mb-3" action="{{ route('admin.login') }}" method="POST">
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

          <div class="mb-3 custom-password-toggle">
            <label class="form-label" for="password">Password</label>
            <div class="input-group input-group-merge">
              <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                name="password" placeholder="············" />
              <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
            </div>
            @error('password')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
        </form>

      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Password toggle
    const toggleBtn = document.querySelector('.custom-password-toggle .input-group-text');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('ti-eye');
                icon.classList.add('ti-eye-off');
            }
        });
    }

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="d-flex align-items-center justify-content-center"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...</div>';
            }

            // Remove any existing errors before sending
            const activeErrors = this.querySelectorAll('.invalid-feedback');
            activeErrors.forEach(err => err.remove());
            const invalidInputs = this.querySelectorAll('.is-invalid');
            invalidInputs.forEach(inp => inp.classList.remove('is-invalid'));
            
            const existingAlert = this.querySelector('.alert');
            if (existingAlert) existingAlert.remove();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    return response.json().then(errData => {
                        throw errData;
                    });
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = data.redirect_url;
                }
            })
            .catch(error => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign in';
                }

                if (error && error.errors) {
                    Object.keys(error.errors).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.classList.add('is-invalid');
                            const parent = input.closest('.mb-3');
                            if (parent) {
                                const feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback d-block';
                                feedback.textContent = error.errors[key][0];
                                parent.appendChild(feedback);
                            }
                        }
                    });
                } else {
                    const fallbackAlert = document.createElement('div');
                    fallbackAlert.className = 'alert alert-danger mt-3';
                    fallbackAlert.textContent = 'An unexpected error occurred. Please try again.';
                    loginForm.prepend(fallbackAlert);
                }
            });
        });

        const inputs = loginForm.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('input', function () {
                this.classList.remove('is-invalid');
                const parent = this.closest('.mb-3');
                if (parent) {
                    const feedback = parent.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.remove();
                    }
                }
            });
        });
    }
});
</script>
@endsection
