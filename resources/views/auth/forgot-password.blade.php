@extends('layouts.app')
@section('title', 'Got It :: Forgot Password')
@section('content')
    <!-- Main authentication content container -->
    <div class="auth-content">
        <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
            <div class="col-md-7 mx-auto">
                <form method="POST" action="{{ route('password.email') }}" data-prevent='n'>
                    @csrf
                    <div class="d-flex flex-column justify-content-center p-4 pb-0">
                        <!-- Logo Section -->
                        <div class="mx-auto mb-2 text-center">
                            <a href="{{ url('/') }}">
                                <img src="{{ asset('treasury/company/logo/logo.svg') }}" class="img-fluid authen-logo-img"
                                    alt="Got It Logo">
                            </a>
                        </div>
                        <!-- Forgot Password Form Section -->
                        <div>
                            <div class="text-center mt-4 mb-2">
                                <h3 class="mb-1 fw-bold">Forgot Your Password?</h3>
                                <p class="mb-0 sf-12">Enter your email address to receive a password reset link.</p>
                            </div>
                            <input type="hidden" name="type" value="forgot-password">
                            <!-- Email Input -->
                            <div class="mt-3">
                                <div class="float-input-control">
                                    <span class="float-group-end"><i class="ti ti-mail"></i></span>
                                    <input type="email" id="email" name="email" class="form-float-input"
                                        placeholder="Email Address" value="{{ old('email') }}" required
                                        autocomplete="email" autofocus>
                                    <label for="email" class="form-float-label">Email Address</label>
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block sf-12" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            <!-- Session Status and Validation Errors -->
                            @if (session('status'))
                                <div class="alert alert-success py-1 sf-12 fw-bold mb-2">
                                    {{ session('status') }}
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger py-1 sf-12 fw-bold mb-2">
                                    @foreach ($errors->all() as $error)
                                        <p class="m-0">{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <!-- Submit Button -->
                            <div class="mt-2 text-center">
                                <button type="submit" class="btn btn-md btn-primary px-5 mt-2 fw-bold rounded-pill" data-before-text="Sending..." >Send Reset OTP<i class="bi bi-envelope ms-2"></i></button>
                            </div>
                            <!-- Back to Login -->
                            <div class="mt-3 text-center">
                                <a href="{{ route('login') }}" class="text-primary sf-12">Back to Login</a>
                            </div>
                        </div>
                        <!-- Footer Section -->
                        <div class="mt-3 pb-4 text-center">
                            <p class="mb-0 text-gray-9 sf-13">© {{ date('Y') }} - <b>Got It</b> - A <a
                                    href="https://digitalkuppam.com" target="_blank">Digital Kuppam</a> Company</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection