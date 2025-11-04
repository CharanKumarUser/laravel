@extends('layouts.empty-app')
@section('title', 'Got It :: Onboarding - Payment Failed')
@section('content')
<link href="{{ asset('css/onboarding.css') }}" rel="stylesheet">
<style>
    .highlight-url { background-color: #e9ecef; padding: 8px 12px; border-radius: 6px; font-family: monospace; font-weight: bold; }
    .device-list { margin-top: 20px; }
    .device-item { border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
    .device-item h5 { color: #343a40; }
    .invalid-feedback { display: block; color: #dc3545; font-size: 0.9rem; }
</style>
<main class="empty-main">
    <!-- Hero Section -->
    <section class="onboarding-hero" data-aos="fade-up" data-aos-delay="100">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Payment Failed</h1>
            <p class="lead">There was an issue processing your payment for your Avind ADMS-compatible biometric device setup. Please review your details, try again, or contact support.</p>
        </div>
        <!-- Abstract Background Elements -->
        <div class="shape shape-1">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <path d="M47.1,-57.1C59.9,-45.6,68.5,-28.9,71.4,-10.9C74.2,7.1,71.3,26.3,61.5,41.1C51.7,55.9,35,66.2,16.9,69.2C-1.3,72.2,-21,67.8,-36.9,57.9C-52.8,48,-64.9,32.6,-69.1,15.1C-73.3,-2.4,-69.5,-22,-59.4,-37.1C-49.3,-52.2,-32.8,-62.9,-15.7,-64.9C1.5,-67,34.3,-68.5,47.1,-57.1Z" transform="translate(100 100)" fill="#ffffff"></path>
            </svg>
        </div>
        <div class="dots dots-1">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <pattern id="dot-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="2" fill="#ffffff"></circle>
                </pattern>
                <rect width="100" height="100" fill="url(#dot-pattern)"></rect>
            </svg>
        </div>
    </section>

    <!-- Failed Section -->
    <section class="onboarding-section">
        <div class="container">
            <div class="onboarding-card text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="card-body p-4">
                    <i class="bi bi-x-circle-fill fa-5x text-danger mb-4"></i>
                    <h2 class="section-title">Payment Failed</h2>
                    <p class="engage-text mb-4">Something went wrong with your payment for the Got It platform and Avind ADMS-compatible biometric device setup. Please review your configured devices below, try again, or contact our support team for assistance.</p>
                    <div class="device-list">
                        <h4>Biometric Devices</h4>
                        @if($onboarding->biometricDevices && $onboarding->biometricDevices->count() > 0)
                            @foreach($onboarding->biometricDevices as $index => $device)
                                <div class="device-item">
                                    <h5>Device {{ $index + 1 }}</h5>
                                    <p><strong>Name:</strong> {{ $device->name }}</p>
                                    @if($device->serial_number)
                                        <p><strong>Serial Number:</strong> {{ $device->serial_number }}</p>
                                    @endif
                                    @if($device->location)
                                        <p><strong>Location:</strong> {{ $device->location }}</p>
                                    @endif
                                    @if($device->ip)
                                        <p><strong>IP Address:</strong> {{ $device->ip }}</p>
                                    @endif
                                    @if($device->port)
                                        <p><strong>Port:</strong> {{ $device->port }}</p>
                                    @endif
                                    @if($device->mac_address)
                                        <p><strong>MAC Address:</strong> {{ $device->mac_address }}</p>
                                    @endif
                                    @if($device->info_json)
                                        <p><strong>Additional Info:</strong> {{ $device->info_json }}</p>
                                    @endif
                                    @if($device->last_sync)
                                        <p><strong>Last Sync:</strong> {{ $device->last_sync->format('d M Y, H:i:s') }}</p>
                                    @endif
                                    @if($onboarding->device_code)
                                        <p><strong>Device Code:</strong> <span class="highlight-url">{{ $onboarding->device_code }}</span></p>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p>No biometric devices configured.</p>
                        @endif
                        @error('biometric_devices')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                    <a href="{{ url('/g/onboarding/' . ($onboarding->onboarding_id ?? '') . '/summary') }}" class="btn action-btn mt-4">Try Again</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Section -->
    <section class="support-section" data-aos="fade-up" data-aos-delay="300">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-12 text-center">
                    <h2 class="section-title text-white">Need Assistance?</h2>
                    <p class="text-white mb-3">Our support team is here to resolve any payment issues or assist with your Avind ADMS-compatible biometric device setup!</p>
                    <div class="d-flex justify-content-center align-items-center mb-2">
                        <i class="bi bi-telephone-fill me-3"></i>
                        <p class="mb-0"><strong>Call:</strong> <a href="tel:+919030990395" class="text-white">+91 90309 90395</a></p>
                    </div>
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <i class="bi bi-envelope-fill me-3"></i>
                        <p class="mb-0"><strong>Email:</strong> <a href="mailto:info@gotit4all.com" class="text-white">info@gotit4all.com</a></p>
                    </div>
                    @php
                        $formSkeletonToken = \App\Facades\Skeleton::skeletonToken('lander_landing_requests') . '_f_onboarding_query';
                        $formAction = url('/lander-action') . '/' . $formSkeletonToken;
                    @endphp
                    <form method="POST" action="{{ $formAction }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" value="{{ old('name') }}" required pattern="^[A-Za-z\s]{3,50}$" title="Only letters and spaces, 3-50 characters.">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Your Email" value="{{ old('email') }}" required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Enter a valid email address.">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endif
                            </div>
                            <div class="col-12">
                                <textarea name="message" class="form-control" rows="4" placeholder="Your Question About Payment Issues or Biometric Device Setup" required pattern=".{10,500}" title="Message should be 10 to 500 characters.">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endif
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn action-btn">Send Query</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Abstract Background Elements -->
            <div class="shape shape-1">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path d="M41.3,-49.1C54.4,-39.3,66.6,-27.2,71.1,-12.1C75.6,3,72.4,20.9,63.3,34.4C54.2,47.9,39.2,56.9,23.2,62.3C7.1,67.7,-10,69.4,-24.8,64.1C-39.7,58.8,-52.3,46.5,-60.1,31.5C-67.9,16.4,-70.9,-1.4,-66.3,-16.6C-61.8,-31.8,-49.7,-44.3,-36.3,-54C-22.9,-63.7,-8.2,-70.6,3.6,-75.1C15.4,-79.6,28.2,-58.9,41.3,-49.1Z" transform="translate(100 100)" fill="#ffffff"></path>
                </svg>
            </div>
            <div class="dots dots-1">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <pattern id="dot-pattern-2" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="2" fill="#ffffff"></circle>
                    </pattern>
                    <rect width="100" height="100" fill="url(#dot-pattern-2)"></rect>
                </svg>
            </div>
        </div>
    </section>
</main>
@endsection