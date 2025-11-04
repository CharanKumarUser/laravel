@extends('layouts.app')

@section('content')
    <main class="main">
        <section class="error-section d-flex align-items-center justify-content-center min-vh-100">
            <div class="container text-center">
                <img src="{{ asset('errors/503.svg') }}" alt="503 Error"
                    class="img-fluid mb-4 w-50">
                <h3 class="h-3 mb-2 fw-bold">503 - Service Unavailable</h3>
                <p class="sf-12">The service is temporarily unavailable. Please try again later.</p>

                {{-- Button with custom loading text for temporary downtime --}}
                <a href="{{ url('/dashboard') }}"
                   class="btn btn-sm btn-primary me-2 mt-3 px-4 rounded-pill"
                   data-loading-text="Redirecting to homepage...">
                   Go to Homepage
                </a>

                {{-- Logout button with custom loading text --}}
                <a href="{{ route('logout') }}"
                   class="btn btn-sm btn-danger mt-3 px-4 rounded-pill"
                   data-loading-text="Logging you out...">
                   Logout
                </a>
            </div>
        </section>
    </main>
@endsection
