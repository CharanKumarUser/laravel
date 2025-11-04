@extends('layouts.app')

@section('content')
    <main class="main">
        <section class="error-section d-flex align-items-center justify-content-center min-vh-100">
            <div class="container text-center">
                <img src="{{ asset('errors/419.svg') }}" alt="419 Error"
                    class="img-fluid mb-4 w-50">
                <h3 class="h-3 mb-2 fw-bold">419 - Page Expired</h3>
                <p class="sf-12">Your session has expired. Please refresh and try again.</p>

                {{-- Back button with custom loading text for expired session --}}
                <a href="{{ url()->previous() }}"
                   class="btn btn-sm btn-primary mx-5 mt-3 px-4 rounded-pill"
                   data-loading-text="Refreshing page...">
                   Refresh & Go Back
                </a>
            </div>
        </section>
    </main>
@endsection
