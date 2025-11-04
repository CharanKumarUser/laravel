@extends('layouts.empty-app')
@section('title', 'Got It :: Onboarding - Business Details')
@section('content')
<main class="empty-main">
    <!-- Business Details Section -->
    <section class="support-section" data-aos="fade-up">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2 class="white-line">Business Information</h2>
                        <p>Enter your business details to proceed with onboarding for HR management in India.</p>
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                    </div>
                    <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
                        <div class="col-lg-8">
                            <div class="card border-0 p-2 rounded-4 shadow-sm">
                                <div class="card-body text-start">
                                    <h3 class="card-title mb-3">Business Details</h3>
                                    <p class="fst-italic mb-4">Provide the following information to set up your business account.</p>
                                    <form method="POST" action="{{ route('onboarding.forms') }}" class="landing-form"  data-prevent="n">
                                        @csrf
                                        <input type="hidden" name="save_type" value="business">
                                        <input type="hidden" name="onboarding_id" value="{{ $onboarding->onboarding_id ?? \Illuminate\Support\Str::random(30) }}">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">Business Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" id="name" class="form-control"
                                                       placeholder="Business Name" value="{{ old('name', $onboarding->name ?? '') }}"
                                                       required>
                                                @error('name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="legal_name" class="form-label">Legal Name (Optional)</label>
                                                <input type="text" name="legal_name" id="legal_name" class="form-control"
                                                       placeholder="Legal Name" value="{{ old('legal_name', $onboarding->legal_name ?? '') }}">
                                                @error('legal_name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="industry" class="form-label">Industry <span class="text-danger">*</span></label>
                                                <input type="text" name="industry" id="industry" class="form-control"
                                                       placeholder="Industry" value="{{ old('industry', $onboarding->industry ?? '') }}"
                                                       required>
                                                @error('industry')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="business_size" class="form-label">Business Size <span class="text-danger">*</span></label>
                                                <select name="business_size" id="business_size" class="form-select" required>
                                                    <option value="" disabled {{ !old('business_size', $onboarding->business_size ?? '') ? 'selected' : '' }}>Select Size</option>
                                                    <option value="micro" {{ old('business_size', $onboarding->business_size ?? '') === 'micro' ? 'selected' : '' }}>Micro</option>
                                                    <option value="small" {{ old('business_size', $onboarding->business_size ?? '') === 'small' ? 'selected' : '' }}>Small</option>
                                                    <option value="medium" {{ old('business_size', $onboarding->business_size ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                                                    <option value="large" {{ old('business_size', $onboarding->business_size ?? '') === 'large' ? 'selected' : '' }}>Large</option>
                                                </select>
                                                @error('business_size')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="no_of_employees" class="form-label">Number of Employees <span class="text-danger">*</span></label>
                                                <input type="number" name="no_of_employees" id="no_of_employees" class="form-control"
                                                       placeholder="Number of Employees" value="{{ old('no_of_employees', $onboarding->no_of_employees ?? '') }}"
                                                       min="0" required>
                                                @error('no_of_employees')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="registration_no" class="form-label">Registration Number (Optional)</label>
                                                <input type="text" name="registration_no" id="registration_no" class="form-control"
                                                       placeholder="Registration Number" value="{{ old('registration_no', $onboarding->registration_no ?? '') }}"
                                                       pattern="^[A-Za-z0-9\-]{3,50}$" title="Letters, numbers, and hyphens, 3-50 characters.">
                                                @error('registration_no')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="tax_id" class="form-label">Tax ID (Optional)</label>
                                                <input type="text" name="tax_id" id="tax_id" class="form-control"
                                                       placeholder="Tax ID" value="{{ old('tax_id', $onboarding->tax_id ?? '') }}"
                                                       pattern="^[A-Za-z0-9\-]{3,50}$" title="Letters, numbers, and hyphens, 3-50 characters.">
                                                @error('tax_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="website" class="form-label">Website (Optional)</label>
                                                <input type="url" name="website" id="website" class="form-control"
                                                       placeholder="https://example.com" value="{{ old('website', $onboarding->website ?? '') }}"
                                                       pattern="^https?://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$" title="Enter a valid URL.">
                                                @error('website')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label for="address_line1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                                <input type="text" name="address_line1" id="address_line1" class="form-control"
                                                       placeholder="Address Line 1" value="{{ old('address_line1', $onboarding->address_line1 ?? '') }}"
                                                       required>
                                                @error('address_line1')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label for="address_line2" class="form-label">Address Line 2 (Optional)</label>
                                                <input type="text" name="address_line2" id="address_line2" class="form-control"
                                                       placeholder="Address Line 2" value="{{ old('address_line2', $onboarding->address_line2 ?? '') }}">
                                                @error('address_line2')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                                <input type="text" name="city" id="city" class="form-control"
                                                       placeholder="City" value="{{ old('city', $onboarding->city ?? '') }}"
                                                       required>
                                                @error('city')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                                                <input type="text" name="pincode" id="pincode" class="form-control"
                                                       placeholder="Pincode" value="{{ old('pincode', $onboarding->pincode ?? '') }}"
                                                       required pattern="^\d{5,10}$" title="Numbers only, 5-10 digits.">
                                                @error('pincode')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="state" class="form-label">State (Optional)</label>
                                                <input type="text" name="state" id="state" class="form-control"
                                                       placeholder="State" value="{{ old('state', $onboarding->state ?? '') }}">
                                                @error('state')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                                                <input type="text" name="country" id="country" class="form-control"
                                                       placeholder="Country" value="{{ old('country', $onboarding->country ?? 'India') }}"
                                                       required>
                                                @error('country')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Business Email <span class="text-danger">*</span></label>
                                                <input type="email" name="email" id="email" class="form-control"
                                                       placeholder="Business Email" value="{{ old('email', $onboarding->email ?? '') }}"
                                                       required>
                                                @error('email')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Business Phone (Optional)</label>
                                                <input type="text" name="phone" id="phone" class="form-control"
                                                       placeholder="Business Phone" value="{{ old('phone', $onboarding->phone ?? '') }}"
                                                       pattern="^\+?[1-9]\d{1,14}$" title="Enter a valid phone number.">
                                                @error('phone')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="hr_contact_email" class="form-label">HR Contact Email (Optional)</label>
                                                <input type="email" name="hr_contact_email" id="hr_contact_email" class="form-control"
                                                       placeholder="HR Email" value="{{ old('hr_contact_email', $onboarding->hr_contact_email ?? '') }}">
                                                @error('hr_contact_email')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="hr_contact_phone" class="form-label">HR Contact Phone (Optional)</label>
                                                <input type="text" name="hr_contact_phone" id="hr_contact_phone" class="form-control"
                                                       placeholder="HR Phone" value="{{ old('hr_contact_phone', $onboarding->hr_contact_phone ?? '') }}"
                                                       pattern="^\+?[1-9]\d{1,14}$" title="Enter a valid phone number.">
                                                @error('hr_contact_phone')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12 d-flex justify-content-between mt-5">
                                                <a href="{{ route('onboarding.type', ['type' => 'user']) }}" data-loading-text="Going Back..." class="btn btn-secondary rounded-pill px-3">Go Back</a>
                                                <button type="submit" data-before-text="Processing data..." class="btn btn-primary rounded-pill">Save & Continue</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Support and Query Section -->
                    <div class="row g-4 mt-5">
                        <div class="col-lg-6 text-center">
                            <h2 class="section-title text-white">Need Assistance?</h2>
                            <p class="text-white mb-3">Our support team is here to help you through the onboarding process!</p>
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="bi bi-telephone-fill me-3"></i>
                                <p class="mb-0"><strong>Call:</strong> <a href="tel:+919030990395" class="text-white">+91 90309 90395</a></p>
                            </div>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <i class="bi bi-envelope-fill me-3"></i>
                                <p class="mb-0"><strong>Email:</strong> <a href="mailto:info@gotit4all.com" class="text-white">info@gotit4all.com</a></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h2 class="section-title text-white">Ask Us Anything</h2>
                            <form method="POST" action="{{ route('landing.forms') }}" class="landing-form">
                                @csrf
                                <input type="hidden" name="save_type" value="faqs">
                                <input type="hidden" name="category" value="onboarding-business-query">
                                <input type="hidden" name="sub_category" value="-">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" name="name" class="form-control" placeholder="Your Name" value="{{ old('name') }}"
                                               required>
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" name="email" class="form-control" placeholder="Your Email" value="{{ old('email') }}"
                                               required>
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <textarea name="message" class="form-control" rows="4" placeholder="Your Question About Onboarding"
                                                  required>{{ old('message') }}</textarea>
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
@endsection