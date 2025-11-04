@extends('layouts.empty-app')
@section('title', 'Got It :: Onboarding - User Details')
@section('content')
<main class="empty-main">
    <!-- User Details Section -->
    <section class="support-section" data-aos="fade-up">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2 class="white-line">Admin Account Setup</h2>
                        <p>Create a new account or sign in to link your existing Got It account for HR management in India.</p>
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible mt-4 rounded-pill fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                    </div>
                    <div class="features" data-aos="fade-up" data-aos-delay="100">
                        <div class="d-flex justify-content-center">
                            <ul class="nav nav-tabs" data-aos="fade-up" data-aos-delay="100">
                                <li class="nav-item">
                                    <a class="nav-link {{ !$onboarding || ($onboarding->save_type ?? '') !== 'existing' ? 'active show' : '' }}"
                                       data-bs-toggle="tab" data-bs-target="#new-user-tab">
                                        <h4>New to Got It</h4>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $onboarding && ($onboarding->save_type ?? '') === 'existing' ? 'active show' : '' }}"
                                       data-bs-toggle="tab" data-bs-target="#existing-user-tab">
                                        <h4>Existing User</h4>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content" data-aos="fade-up" data-aos-delay="200">
                            <!-- New User Tab -->
                            <div class="tab-pane fade {{ !$onboarding || ($onboarding->save_type ?? '') !== 'existing' ? 'active show' : '' }}"
                                 id="new-user-tab">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="card border-0 p-2 rounded-4 shadow-sm">
                                            <div class="card-body text-start">
                                                <h3 class="card-title mb-3">Create Your Account</h3>
                                                <p class="fst-italic mb-4">Set up a new admin account to manage your HR operations in India.</p>
                                                <form method="POST" action="{{ route('onboarding.forms') }}" class="landing-form" data-prevent='n'>
                                                    @csrf
                                                    <input type="hidden" name="save_type" value="new">
                                                    <input type="hidden" name="onboarding_id" value="{{ $onboarding->onboarding_id ?? \Illuminate\Support\Str::random(30) }}">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label for="new_admin_first_name" class="form-label">First Name</label>
                                                            <input type="text" name="admin_first_name" id="new_admin_first_name" class="form-control"
                                                                   placeholder="First Name" value="{{ old('admin_first_name', $onboarding->admin_first_name ?? '') }}"
                                                                   required pattern="^[A-Za-z\s]{2,100}$" title="Only letters and spaces, 2-100 characters.">
                                                            @error('admin_first_name')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="new_admin_last_name" class="form-label">Last Name</label>
                                                            <input type="text" name="admin_last_name" id="new_admin_last_name" class="form-control"
                                                                   placeholder="Last Name" value="{{ old('admin_last_name', $onboarding->admin_last_name ?? '') }}"
                                                                   required pattern="^[A-Za-z\s]{2,100}$" title="Only letters and spaces, 2-100 characters.">
                                                            @error('admin_last_name')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="new_admin_email" class="form-label">Email</label>
                                                            <input type="email" name="admin_email" id="new_admin_email" class="form-control"
                                                                   placeholder="Email" value="{{ old('admin_email', $onboarding->admin_email ?? '') }}"
                                                                   required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" maxlength="150"
                                                                   title="Enter a valid email address, up to 150 characters.">
                                                            @error('admin_email')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="new_admin_phone" class="form-label">Phone</label>
                                                            <input type="text" name="admin_phone" id="new_admin_phone" class="form-control"
                                                                   placeholder="Phone" value="{{ old('admin_phone', $onboarding->admin_phone ?? '') }}"
                                                                   required pattern="^\+?[1-9]\d{1,14}$" maxlength="20"
                                                                   title="Enter a valid phone number, up to 20 characters.">
                                                            @error('admin_phone')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="new_admin_password" class="form-label">Password</label>
                                                            <input type="password" name="admin_password" id="new_admin_password" class="form-control"
                                                                   placeholder="Password" required pattern=".{8,}" title="Password must be at least 8 characters.">
                                                            @error('admin_password')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="new_admin_confirm_password" class="form-label">Confirm Password</label>
                                                            <input type="password" name="admin_password_confirmation" id="new_admin_confirm_password"
                                                                   class="form-control" placeholder="Confirm Password" required pattern=".{8,}"
                                                                   title="Password must be at least 8 characters.">
                                                            @error('admin_password_confirmation')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-between mt-5">
                                                            <a href="{{ url('/g/plans') }}" data-loading-text="Redirecting..." class="btn btn-secondary rounded-pill px-3">All Plans</a>
                                                            <button type="submit" data-before-text="Processing data..." class="btn btn-primary rounded-pill px-3">Create & Continue</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Existing User Tab -->
                            <div class="tab-pane fade {{ $onboarding && ($onboarding->save_type ?? '') === 'existing' ? 'active show' : '' }}"
                                 id="existing-user-tab">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="card border-0 p-2 rounded-4 shadow-sm">
                                            <div class="card-body text-start">
                                                <h3 class="card-title mb-3">Link Existing Account</h3>
                                                <p class="fst-italic mb-4">Use your email or phone and password to continue onboarding.</p>
                                                <form method="POST" action="{{ route('onboarding.forms') }}" class="landing-form" data-prevent='n'>
                                                    @csrf
                                                    <input type="hidden" name="save_type" value="existing">
                                                    <input type="hidden" name="onboarding_id" value="{{ $onboarding->onboarding_id ?? \Illuminate\Support\Str::random(30) }}">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label for="existing_admin_email" class="form-label">Email (Optional)</label>
                                                            <input type="email" name="admin_email" id="existing_admin_email" class="form-control"
                                                                   placeholder="Email" value="{{ old('admin_email', $onboarding->admin_email ?? '') }}"
                                                                   pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" maxlength="150"
                                                                   title="Enter a valid email address, up to 150 characters.">
                                                            @error('admin_email')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="existing_admin_phone" class="form-label">Phone (Optional)</label>
                                                            <input type="text" name="admin_phone" id="existing_admin_phone" class="form-control"
                                                                   placeholder="Phone" value="{{ old('admin_phone', $onboarding->admin_phone ?? '') }}"
                                                                   pattern="^\+?[1-9]\d{1,14}$" maxlength="20"
                                                                   title="Enter a valid phone number, up to 20 characters.">
                                                            @error('admin_phone')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-12">
                                                            <label for="existing_admin_password" class="form-label">Password</label>
                                                            <input type="password" name="admin_password" id="existing_admin_password" class="form-control"
                                                                   placeholder="Password" required pattern=".{8,}" title="Password must be at least 8 characters.">
                                                            @error('admin_password')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                            <div class="invalid-feedback" id="email-phone-error" style="display: none;">
                                                                Please provide either an email or phone number.
                                                            </div>
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-between mt-5">
                                                            <a href="{{ route('onboarding.type', ['type' => 'plan-view']) }}" data-loading-text="Going Back..." class="btn btn-secondary rounded-pill px-3">Go Back</a>
                                                            <button type="submit" data-before-text="Processing data..." class="btn btn-primary rounded-pill px-3">Continue</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Support and Query Section -->
                <div class="row g-4 mt-5">
                    <div class="col-lg-6">
                        <h2 class="section-title text-white">Need Help with Onboarding?</h2>
                        <p class="text-white mb-3">Our support team is ready to assist you in setting up your admin account for HR management in India!</p>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone-fill me-3"></i>
                            <p class="mb-0"><strong>Call:</strong> <a href="tel:+919030990395" class="text-white">+91 90309 90395</a></p>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-envelope-fill me-3"></i>
                            <p class="mb-0"><strong>Email:</strong> <a href="mailto:info@gotit4all.com" class="text-white">info@gotit4all.com</a></p>
                        </div>
                        <p class="engage-text text-white">Let us help you get started with Got It’s HR solutions.</p>
                    </div>
                    <div class="col-lg-6">
                        <h2 class="section-title text-white">Ask Us Anything</h2>
                        <form method="POST" action="{{ route('landing.forms') }}" class="landing-form">
                            @csrf
                            <input type="hidden" name="save_type" value="faqs">
                            <input type="hidden" name="category" value="onboarding-user-query">
                            <input type="hidden" name="sub_category" value="-">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control" placeholder="Your Name" value="{{ old('name') }}"
                                           required pattern="^[A-Za-z\s]{3,50}$" title="Only letters and spaces, 3-50 characters.">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="Your Email" value="{{ old('email') }}"
                                           required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Enter a valid email address.">
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <textarea name="message" class="form-control" rows="4" placeholder="Your Question About Onboarding"
                                              required pattern=".{10,500}" title="Message should be 10 to 500 characters.">{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
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
            </div>
        </section>
    </main>
    <script>
        // Client-side validation for new user form
        document.getElementById('new-user-form')?.addEventListener('submit', function (e) {
            const password = document.getElementById('new_admin_password').value;
            const confirmPassword = document.getElementById('new_admin_confirm_password').value;
            const errorDiv = document.getElementById('password-mismatch-error');
            if (password !== confirmPassword) {
                e.preventDefault();
                errorDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'none';
            }
        });

        // Client-side validation for existing user form
        document.getElementById('existing-user-form')?.addEventListener('submit', function (e) {
            const email = document.getElementById('existing_admin_email').value;
            const phone = document.getElementById('existing_admin_phone').value;
            const errorDiv = document.getElementById('email-phone-error');
            if (!email && !phone) {
                e.preventDefault();
                errorDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'none';
            }
        });
    </script>
@endsection