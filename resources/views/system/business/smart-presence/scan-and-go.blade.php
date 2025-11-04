{{-- Template: Scan And Go Page --}}
@extends('layouts.system-app')
@section('title', 'Scan And Go')
@push('styles')
<style>
    /* -------------------------------------------------
       QR “reveal” wrapper – keeps canvas in place
       ------------------------------------------------- */
    .qr-canvas-wrapper {
        position: relative;          /* Establishes stacking context for mask */
        width: 200px;
        height: 200px;
        overflow: hidden;            /* Clip anything outside the mask */
        background: #fff;            /* Fallback background */
        border-radius: 8px;          /* Optional – matches QR styling */
    }

    /* Mask that grows from the center outward – hides content initially */
    .qr-canvas-wrapper::before {
        content: "";
        position: absolute;
        inset: 0;
        clip-path: polygon(
            calc(50% - calc(var(--progress,0) * 50%)) calc(50% - calc(var(--progress,0) * 50%)),
            calc(50% + calc(var(--progress,0) * 50%)) calc(50% - calc(var(--progress,0) * 50%)),
            calc(50% + calc(var(--progress,0) * 50%)) calc(50% + calc(var(--progress,0) * 50%)),
            calc(50% - calc(var(--progress,0) * 50%)) calc(50% + calc(var(--progress,0) * 50%))
        );
        transition: clip-path 0s linear; /* Instant for JS control */
        z-index: 2;
        pointer-events: none;
    } 

    /* Optional waiting pulse overlay */
    .qr-canvas-wrapper.waiting::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0, 180, 175, 0.06);
        animation: pulse 1.5s ease-in-out infinite;
        z-index: 1;
        pointer-events: none;
    }
    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 0.2; }
    }

    /* Ensure canvas fills the wrapper */
    .qr-canvas-wrapper canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
        z-index: 1;
    }

    .qr-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 2rem;
    }
 
    .qr-title {
        font-weight: 600;
        font-size: 1.2rem;
    }
</style>
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
@vite(['resources/js/system/realtime/smart-qr.js'])
@endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Scan And Go</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/smart-presence') }}">Smart Presence</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Scan And Go</li>
                </ol>
            </nav>
        </div>
        <div></div> 
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="live-time-container head-icons">
                <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                <div class="live-time"></div>
            </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>

    {{-- QR Display Section --}}
    <div class="col-xl-12">
        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100" style="height:calc(100vh - 200px) !important;">
            <h1 class="h3 mb-3 fw-bold">Scan And Go</h1>
            <p class="text-muted mb-4" style="max-width: 600px;">
                Scan the QR code below using any compatible scanner to mark attendance automatically.
            </p>

            {{-- Example QR div(s) --}}
            <div class="qr-container">
                {{-- @foreach($companies as $company) --}}
                    <div data-render-qr data-company-id="COMW1PDN" style="display: flex; flex-direction: column; align-items: center;">
                        <div class="qr-title">DIGITAL</div>
                        <!-- QR wrapper will be injected here by JS -->
                    </div>
                     {{-- <div data-render-qr data-company-id="COMJ5N5G" style="display: flex; flex-direction: column; align-items: center;">
                        <div class="qr-title">DIGITAL</div>
                        <!-- QR wrapper will be injected here by JS -->
                    </div> --}}
                {{-- @endforeach --}}
            </div>
        </div>
    </div>
</div>
@endsection

