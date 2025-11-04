@extends('layouts.app')

@section('content')
    <main class="main">
        <section class="error-section d-flex align-items-center justify-content-center min-vh-100">
            <div class="container text-center">
                <img src="{{ asset('errors/401.svg') }}" alt="401 Error"
                    class="img-fluid mb-4 w-50">
                <h3 class="h-3 mb-2 fw-bold">401 - Unauthorized</h3>
                <p class="sf-12">You are not authorized to view this page.</p>

                {{-- Back button with custom loading text --}}
                <a href="{{ url()->previous() }}"
                   class="btn btn-sm btn-primary me-2 mt-3 px-4 rounded-pill"
                   data-loading-text="Taking you back...">
                   Go Back
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
