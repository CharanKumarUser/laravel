@extends('layouts.app')

@section('content')
    <main class="main">
        <section class="error-section d-flex align-items-center justify-content-center min-vh-100">
            <div class="container text-center">
                <img src="{{ asset('errors/400.svg') }}" alt="400 Error"
                    class="img-fluid mb-4 w-50">
                <h3 class="h-3 mb-2 fw-bold">400 - Bad Request</h3>
                <p class="sf-12">The request was invalid or cannot be served.</p>
                
                {{-- Button with loading text specific to bad request --}}
                <a href="{{ url('/login') }}"
                   class="btn btn-sm btn-primary mx-5 mt-3 px-4 rounded-pill"
                   data-loading-text="Redirecting to login...">
                   Return to Login
                </a>
            </div>
        </section>
    </main>
@endsection
