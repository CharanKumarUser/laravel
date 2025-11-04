@extends('layouts.empty-app')
@section('title', 'Got It :: Onboarding - Finalize Setup')
@section('top-style')
    <style>
        .loading-div {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            position: relative;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-message {
            font-size: 0.9rem;
            color: #333;
            margin-top: 10px;
        }
        .highlight-url {
            background-color: #e9f7ef;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .payment-table table, .payment-table table {
            font-size: 0.9rem;
        }
        .payment-table th, .payment-table td, .payment-table th, .payment-table td {
            padding: 8px;
            text-align: left;
        }
        .payment-table .table-striped tbody tr:nth-of-type(odd), .payment-table .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        .notice-section {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
@endsection
@section('bottom-script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const payNowBtn = document.querySelector('.pay-now-btn');
            const finalizeContent = document.querySelector('.finalize-content');
            const loadingDiv = document.querySelector('.loading-div');

            payNowBtn.addEventListener('click', (e) => {
                // Replace content with payment processing message
                finalizeContent.innerHTML = `
                    <div class="text-center p-4">
                        <h2 class="text-success fw-bold">
                            <i class="fa-solid fa-refresh me-2"></i> Payment Processing...
                        </h2>
                        <p class="text-muted">You are being securely redirected to the payment gateway.</p>
                        <div class="loading-spinner"></div>
                        <p class="text-muted mt-3">
                            Please do not refresh or close this window.
                        </p>
                    </div>
                `;

                // Show loading animation
                loadingDiv.style.display = 'block';

                // Simulate redirect after 3 seconds (replace with actual payment gateway URL)
                // setTimeout(() => {
                //     window.location.href = 'https://gotit4all.com/payment-gateway';
                // }, 3000);
            });
        });
    </script>
@endsection
@section('content')
    <main class="empty-main">
        <section class="support-section py-5" data-aos="fade-up">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="section-title">
                            <h2 class="white-line">Finalize Your Setup</h2>
                            <p>Complete your onboarding by reviewing your details and making the payment to activate your Got It platform, powering HR management, biometric attendance, geolocation-based tracking, and panel-based features.</p>
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                        </div>
                        <div class="row justify-content-center finalize-content" data-aos="fade-up" data-aos-delay="200">
                            <div class="col-lg-10">
                                <div class="row g-4">
                                    <!-- payment Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 p-2 rounded-4 shadow-sm">
                                            <div class="card-body text-start">
                                                <h3 class="card-title mb-4">Onboarding Details</h3>
                                                <div class="notice-section">
                                                    <p class="fw-bold text-dark mb-2">Important Notice</p>
                                                    <p>Ensure all details are accurate before proceeding. These details will be used for HR management, biometric attendance, geolocation tracking, and panel-based configurations.</p>
                                                </div>
                                                <div class="payment-table">
                                                    <h4>Admin Details</h4>
                                                    <p><strong>Name</strong> {{ $onboarding->admin_first_name ?? 'N/A' }} {{ $onboarding->admin_last_name ?? '' }}</p>
                                                    <p><strong>Email</strong> {{ $onboarding->admin_email ?? 'N/A' }}</p>
                                                    <p><strong>Phone</strong> {{ $onboarding->admin_phone ?? 'N/A' }}</p>
                                                    <h4 class="mt-4">Business Details</h4>
                                                    <p><strong>Name</strong> {{ $onboarding->name ?? 'N/A' }}</p>
                                                    <p><strong>Industry</strong> {{ $onboarding->industry ?? 'N/A' }}</p>
                                                    <p><strong>Size</strong> {{ ucfirst($onboarding->business_size ?? 'N/A') }}</p>
                                                    <p><strong>Employees</strong> {{ $onboarding->no_of_employees ?? '0' }}</p>
                                                    <p><strong>Address</strong> {{ $onboarding->address_line1 ?? 'N/A' }}, {{ $onboarding->city ?? 'N/A' }}, {{ $onboarding->pincode ?? 'N/A' }}</p>
                                                    <h4 class="mt-4">Configured Devices</h4>
                                                    @if($onboarding->biometricDevices && $onboarding->biometricDevices->count() > 0)
                                                        <table class="table table-bordered table-striped mt-3">
                                                            <thead>
                                                                <tr>
                                                                    <th>Device Name</th>
                                                                    <th>Serial Number</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($onboarding->biometricDevices as $device)
                                                                    <tr>
                                                                        <td>{{ $device->DeviceName ?? 'N/A' }}</td>
                                                                        <td>{{ $device->SerialNumber ?? $device->slno ?? 'N/A' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @else
                                                        <p>No devices configured for biometric attendance or geolocation tracking.</p>
                                                    @endif
                                                    @error('biometric_devices')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Payment Info Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 p-2 rounded-4 shadow-sm">
                                            <div class="card-body text-start">
                                                <h3 class="card-title mb-4">Payment Information</h3>
                                                <div class="notice-section">
                                                    <p class="fw-bold text-dark mb-2">Payment Notice</p>
                                                    <p>Payment will activate your Got It subscription, enabling HR management tools, biometric attendance, geolocation-based tracking, and panel-based dashboards.</p>
                                                </div>
                                                <div class="payment-table">
                                                    @php
                                                        $plan = $onboarding->plan ?? null;
                                                        $duration = $plan ? ($plan->duration['year'] ?? 'N/A') : 'N/A';
                                                        $tax = $plan ? ($plan->tax['gst'] ?? '0') : '0';
                                                    @endphp
                                                    <p><strong>Plan</strong> {{ $plan ? ucfirst($plan->name) : 'Not Selected' }}</p>
                                                    <p><strong>Duration</strong> {{ $duration }} Year(s)</p>
                                                    <p><strong>Total</strong> ${{ $plan ? number_format($plan->total_amount, 2) : '0.00' }} (incl. {{ $tax }}% GST)</p>
                                                    @error('plan_id')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @endif
                                                    <div class="alert alert-success mt-4">
                                                        <p class="fw-bold text-dark mb-2">Compatibility Confirmed</p>
                                                        <p>Your setup is fully compatible with our HR management, biometric attendance, geolocation, and panel-based features.</p>
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <p class="fw-bold text-dark mb-2">Payment Processing Note</p>
                                                        <p>This is a product of <strong>Digital Kuppam</strong>. You will be redirected to their secure payment gateway for processing.</p>
                                                    </div>
                                                    <div class="alert alert-warning">
                                                        <p class="fw-bold text-dark mb-2">Important</p>
                                                        <p>Ensure a stable internet connection during payment to avoid interruptions. Contact support if you encounter issues.</p>
                                                    </div>
                                                    <p class="text-muted">
                                                        By proceeding, you agree to our 
                                                        <a href="https://gotit4all.com/terms-and-conditions" class="text-primary" target="_blank">Terms and Conditions</a> and 
                                                        <a href="https://gotit4all.com/privacy-policy" class="text-primary" target="_blank">Privacy Policy</a>.
                                                    </p>
                                                    <div class="d-flex justify-content-between mt-5">
                                                        <a href="{{ route('onboarding.type', ['type' => 'device']) }}" data-loading-text="Going Back..." class="btn btn-secondary rounded-pill px-3">Go Back</a>
                                                        <a href="{{ $payLink }}" class="btn btn-primary rounded-pill pay-now-btn">Pay Now</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Loading Animation Div -->
                                <div class="loading-div mt-4">
                                    <div class="loading-spinner"></div>
                                    <p class="loading-message">Initializing payment process...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Abstract Background Elements -->
                <div class="shape shape-1">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="M41.3,-49.1C54.4,-39.3,66.6,-27.2,71.1,-12.1C75.6,3,72.4,20.9,63.3,34.4C54.2,47.9,39.2,56.9,23.2,62.3C7.1,67.7,-10,69.4,-24.8,64.1C-39.7,58.8,-52.3,46.5,-60.1,31.5C-67.9,16.4,-70.9,-1.4,-66.3,-16.6C-61.8,-31.8,-49.7,-44.3,-36.3,-54C-22.9,-63.7,-8.2,-70.6,3.6,-75.1C15.4,-79.6,28.2,-58.9,41.3,-49.1Z" transform="translate(100 100)" fill="#ffffff"></path>
                    </svg>
                </div>
            </section>
        </main>
    @endsection