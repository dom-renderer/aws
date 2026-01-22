@extends('front.layout')

@push('css')
<style>
    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 50px;
        margin-bottom: 50px;
    }

    .login-card {
        width: 100%;
        max-width: 420px;
        border-radius: 12px;
        border: none;
    }

    .login-header img {
        height: 50px;
    }

    .form-control {
        height: 46px;
        border-radius: 8px;
    }

    .btn-primary {
        background-color: #1f3b73;
        border-color: #1f3b73;
        height: 46px;
        border-radius: 8px;
        font-weight: 500;
    }

    .btn-primary:hover {
        background-color: #162b56;
    }

    .login-footer a {
        color: #1f3b73;
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer a:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('content')

<div class="login-container">
    <div class="card login-card">

        <div class="login-header text-center mb-4">
            <img src="{{ asset('front-theme/images/aw-log.svg') }}" alt="ANJO Wholesale">
        </div>

        <h5 class="text-center mb-1">Login to your account</h5>
        <p class="text-center text-muted mb-4">

        </p>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}"> @csrf
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="john.doe@gmail.com" value="{{ old('email') }}">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <a href="#" class="small">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>
            @if(count(session()->get('saved_accounts', [])) > 1)
            <a href="{{ route('switch-account') }}" class="btn btn-primary w-100">
                Switch Account
            </a>
            @endif
        </form>

        <!-- Footer -->
        <div class="login-footer text-center mt-4">
            @if($errors->has('email') && $errors?->first('email') == 'Please verify your email address before logging in.')
            <small class="text-muted">
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email') }}">
                    To get new verification link in mail,
                    <button type="submit" style="border: none;background: transparent;color: #1f3d71;">Click here</button>
                </form>
            </small> <br>
            @endif

            <small class="text-muted">
                Don’t have an account?
                <a href="{{ route('register') }}">Register</a>
            </small>
        </div>

    </div>
</div>

<form id="resendVerificationForm" method="POST" action="{{ route('verification.resend') }}">
    @csrf
    <input type="hidden" name="email" value="{{ request()->filled('e') ? base64_decode(request('e')) : '' }}">
</form>
@endsection

@push('js')
<script>
    @if(session()->has('success'))
        Swal.fire('Success', "{{ session()->get('success') }}", 'success');

    @elseif(session()->has('error'))
        Swal.fire('Error', "{{ session()->get('error') }}", 'error');

    @elseif(session()->has('verification_token_send'))
        Swal.fire({
            title: 'Verification Expired',
            text: "{{ session()->get('verification_token_send') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Re-send',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('resendVerificationForm').submit();
            }
        });
    @endif
</script>
@endpush
