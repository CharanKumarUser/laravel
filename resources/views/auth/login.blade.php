@extends('layouts.app')
@section('title', 'Got It :: Login')
@section('content')
    <!-- Main authentication content container -->
    <div class="auth-content">
        <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
            <div class="col-md-7 mx-auto">
                <form method="POST" action="{{ route('login.post') }}" data-prevent="n">
                    @csrf
                    <div class="d-flex flex-column justify-content-center p-4 pb-0">
                        <!-- Logo Section -->
                        <div class="mx-auto mb-2 text-center">
                            <a href="{{ url('/') }}">
                                <img src="{{ asset('treasury/company/logo/logo.svg') }}" class="img-fluid authen-logo-img"
                                    alt="Got It Logo">
                            </a>
                        </div>
                        <!-- Login Form Section -->
                        <div>
                            <div class="text-center mt-4 mb-2">
                                <h3 class="mb-1 fw-bold">Welcome Back!</h3>
                                <p class="mb-0 sf-12">We're glad to see you again. Login to continue your journey with us.
                                </p>
                            </div>
                            <!-- Username Input -->
                            <div class="mt-3">
                                <div class="float-input-control">
                                    <span class="float-group-end"><i class="ti ti-user"></i></span>
                                    <input type="text" id="username" name="username" class="form-float-input"
                                        placeholder="Username" value="{{ old('username') }}" required
                                        autocomplete="username" autofocus>
                                    <label for="username" class="form-float-label">Username</label>
                                </div>
                                @error('username')
                                    <span class="invalid-feedback d-block sf-12" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            <!-- Password Input -->
                            <div class="mt-3">
                                <div class="float-input-control">
                                    <span class="float-group-end toggle-password"><i class="ti ti-eye-off"></i></span>
                                    <input type="password" id="password" name="password" class="form-float-input"
                                        placeholder="Password" required autocomplete="current-password">
                                    <label for="password" class="form-float-label">Password</label>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block sf-12" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            <!-- Remember Me Checkbox -->
                            <div class="mt-2 ms-2 d-flex justify-content-between">
                                <div class="form-check">
                                    <input type="checkbox" id="remember" name="remember" class="form-check-input">
                                    <label for="remember" class="form-check-label sf-12">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="text-primary sf-12">Forgot Password?</a>
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
                                <button type="submit" class="btn btn-md btn-primary px-5 mt-2 fw-bold rounded-pill" data-before-text="Logging in...">Login<i class="bi bi-box-arrow-in-right ms-2"></i></button>
                            </div>
                            <!-- Social Login Section -->
                            <div class="text-center mt-3 sf-12">
                                <span>or<br>Continue with</span>
                            </div>
                            <div class="mt-2">
                                <div class="d-flex align-items-center justify-content-center">
                                    @foreach (['google', 'x', 'facebook', 'github'] as $provider)
                                        <div class="text-center me-2">
                                            <a href="{{ route('social.login', $provider) }}" class="social-log-icon"
                                                aria-label="Login with {{ ucfirst($provider) }}">
                                                <img class="img-fluid m-1" src="{{ asset('social/' . $provider . '.svg') }}"
                                                    alt="{{ ucfirst($provider) }} Login">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            {{-- <!-- Register Link -->
                            <div class="mt-3 text-center">
                                <p class="sf-12">Don't have an account? <a href="{{ route('register') }}"
                                        class="text-primary">Register</a></p>
                            </div> --}}
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