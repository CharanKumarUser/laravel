@extends('layouts.app')

@section('title', 'Got It :: Register')

@section('content')
<div class="auth-content">
    <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
        <div class="col-md-7 mx-auto">
            <form method="POST" action="{{ route('register.post') }}" data-prevent='n'>
                @csrf
                <div class="d-flex flex-column justify-content-center p-4 pb-0">
                    <div class="mx-auto mb-2 text-center">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('treasury/company/logo/logo.svg') }}" class="img-fluid authen-logo-img"
                                alt="Got It Logo">
                        </a>
                    </div>

                    <div class="text-center mt-4 mb-2">
                        <h3 class="mb-1 fw-bold">Create an Account</h3>
                        <p class="mb-0 sf-12">Join us today! Fill in the details below to get started. You will need to verify your email.</p>
                    </div>

                    {{-- Form Fields --}}
                    <div class="row g-3 mt-3">
                        {{-- First Name --}}
                        <div class="col-md-6">
                            <div class="float-input-control">
                                <span class="float-group-end"><i class="ti ti-user"></i></span>
                                <input type="text" id="first_name" name="first_name" class="form-float-input"
                                    placeholder="First Name" value="{{ old('first_name') }}" required>
                                <label for="first_name" class="form-float-label">First Name</label>
                            </div>
                            @error('first_name')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Last Name --}}
                        <div class="col-md-6">
                            <div class="float-input-control">
                                <span class="float-group-end"><i class="ti ti-user"></i></span>
                                <input type="text" id="last_name" name="last_name" class="form-float-input"
                                    placeholder="Last Name" value="{{ old('last_name') }}" required>
                                <label for="last_name" class="form-float-label">Last Name</label>
                            </div>
                            @error('last_name')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-12">
                            <div class="float-input-control">
                                <span class="float-group-end"><i class="ti ti-user"></i></span>
                                <input type="text" id="username" name="username" class="form-float-input"
                                    placeholder="Username" value="{{ old('username') }}" required
                                    autocomplete="username" autofocus>
                                <label for="username" class="form-float-label">Username</label>
                            </div>
                            @error('username')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-12">
                            <div class="float-input-control">
                                <span class="float-group-end"><i class="ti ti-mail"></i></span>
                                <input type="email" id="email" name="email" class="form-float-input"
                                    placeholder="Email Address" value="{{ old('email') }}" required
                                    autocomplete="email">
                                <label for="email" class="form-float-label">Email Address</label>
                            </div>
                            @error('email')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="col-12">
                            <div class="float-input-control">
                                <span class="float-group-end toggle-password"><i class="ti ti-eye-off"></i></span>
                                <input type="password" id="password" name="password" class="form-float-input"
                                    placeholder="Password" required autocomplete="new-password">
                                <label for="password" class="form-float-label">Password</label>
                            </div>
                            @error('password')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-12">
                            <div class="float-input-control">
                                <span class="float-group-end toggle-password"><i class="ti ti-eye-off"></i></span>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-float-input" placeholder="Confirm Password" required
                                    autocomplete="new-password">
                                <label for="password_confirmation" class="form-float-label">Confirm Password</label>
                            </div>
                            @error('password_confirmation')
                                <span class="invalid-feedback d-block sf-12">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Session Messages --}}
                    @if (session('status'))
                        <div class="alert alert-success py-1 sf-12 fw-bold mb-2 mt-3">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger py-1 sf-12 fw-bold mb-2 mt-3">
                            @foreach ($errors->all() as $error)
                                <p class="m-0">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    {{-- Submit --}}
                    <div class="mt-3 text-center">
                        <button type="submit" class="btn btn-md btn-primary px-5 mt-2 fw-bold rounded-pill"
                            data-before-text="Registering...">Register <i class="bi bi-person-plus ms-2"></i>
                        </button>
                    </div>

                    {{-- Social Login --}}
                    <div class="text-center mt-3 sf-12">
                        <span>or<br>Continue with</span>
                    </div>
                    <div class="row justify-content-center mt-2 g-2">
                        @foreach (['google', 'x', 'facebook', 'github'] as $provider)
                            <div class="col-auto">
                                <a href="{{ route('social.login', $provider) }}" class="social-log-icon"
                                    aria-label="Register with {{ ucfirst($provider) }}">
                                    <img class="img-fluid m-1" src="{{ asset('social/' . $provider . '.svg') }}"
                                        alt="{{ ucfirst($provider) }} Register">
                                </a>
                            </div>
                        @endforeach
                    </div>

                    {{-- Back to Login --}}
                    <div class="mt-3 text-center">
                        <a href="{{ route('login') }}" class="text-primary sf-12">Already have an account? Login</a>
                    </div>

                    {{-- Footer --}}
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