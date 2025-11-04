{{-- Template: Company Profile Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Company Profile')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts')
<script>
     window.addEventListener('load', function() {
        setTimeout(function() {
            document.querySelectorAll('[id^="scope-tree-container"]').forEach(function (el) {
                const scopes = JSON.parse(el.dataset.scopes || "[]"); 
                const rootName = el.dataset.rootName;                  
                const token = el.dataset.token;    
                window.skeleton.tree(el.id, scopes, token);
                window.general.tooltip();
            });
        }, 1000);
    });
</script>
@endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Company Profile</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/company-management') }}">Company Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Company Profile</a></li>
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
     <div class="col-xl-12">
        @php
            $companies = is_array($data['companies']) ? $data['companies'] : (is_array($data['companies']) ? [$data['companies']] : []);
        @endphp
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                        id="company-tabs" role="tablist">
                         @foreach ($companies as $index => $company)
                            @php
                                $id = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $company['name'] ?? 'company-' . $index));
                                $label = $company['name'] ?? 'Company ' . ($index + 1);
                            @endphp
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="{{ $id }}-tab" data-skl-action="b"
                                    data-bs-toggle="tab" href="#{{ $id }}-content" role="tab"
                                    aria-controls="{{ $id }}-content"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="action-area">
                        <button class="btn btn-primary skeleton-popup" id="companies-add-btn"
                            data-token="@skeletonToken('business_companies')_a" data-type="add">Add Company</button>
                    </div>
                </div>
                <!-- Rest of the tab content remains unchanged -->
                <div class="tab-content mt-3 h-100" id="companyTabsContent">
                    @if(empty($companies))
                    
                        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                            style="height:calc(80vh - 100px) !important;">
                            <img src="{{ asset('errors/empty.svg') }}"
                                alt="No Company Details" class="img-fluid mb-2 w-25">
                            <h6 class="mb-2 fw-bold">No company details available</h6>
                            <p class="text-muted">Select a company tab to view details or add a new company.</p>
                        </div>
                    @else
                    @foreach ($companies as $index => $company)
                        @php
                            $companyId = $company['company_id'] ?? null;
                            $id = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $company['name'] ?? 'company-' . $index));
                        @endphp
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $id }}-content"
                            role="tabpanel" aria-labelledby="{{ $id }}-tab">
                            <div class="row g-3">
                                <!-- Left Column: Company Profile Card -->
                                <div class="col-xl-4 col-lg-5 col-md-12 mx-auto fs-12">
                                    <div class="card border-0 shadow-sm overflow-hidden collapse show" id="companyCard-{{ $id }}">
                                        <!-- Company Banner -->
                                        <div class="position-relative">
                                            <button class="cover-image-edit skeleton-popup" data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="changebanner">
                                                <i class="ti ti-pencil me-1"></i>
                                            </button>
                                            <img src="{{ $company['banner'] ? e(app(\App\Services\FileService::class)->getFile($company['banner'])) : asset('default/profile-avatar.svg') }}" class="card-img-top" alt="Profile Banner" style="min-height:150px;height: 150px; object-fit: cover;">
                                            <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: 0px;">
                                                <img src="{{ $company['logo'] ? e(app(\App\Services\FileService::class)->getFile($company['logo'])) : asset('default/profile-avatar.svg') }}"
                                                    class="rounded-circle border border-5 border-white shadow-lg" alt="Profile Image"
                                                    style="width: 100px; height: 100px; object-fit: cover;">
                                            </div>
                                        </div>
                                        <div class="card-body text-center" style="padding-top: 74px;">
                                            <div class="mb-3">
                                                <h2 class="d-flex align-items-center justify-content-center mb-1 card-title">
                                                    {{ $company['name'] ?? 'Company Name' }}
                                                    @if (!empty($company['is_active']))
                                                        <i class="ti ti-discount-check-filled text-success ms-1"></i>
                                                    @endif
                                                </h2>
                                                <div class="text-center">
                                                    <span class="badge badge-soft-dark fw-medium me-2 rounded-pill">
                                                        {{ $company['industry'] ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-2">
                                                    <span class="sf-11">Business ID: <b>{{ $company['business_id'] ?? '-' }}</b></span>
                                                    <span class="mx-2">|</span>
                                                    <span class="sf-11">Created on: <b>{{ !empty($company['created_at'])
                                                    ? \Carbon\Carbon::parse($company['created_at'])->format('jS F Y')
                                                    : '-' }}</b></span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-center gap-2 mb-3">
                                                {{-- <a href="#" class="btn px-3 btn-primary btn-sm px-4 skeleton-popup"
                                                    data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="editicon">
                                                    <i class="ti ti-pencil me-1"></i> Edit Icon
                                                </a> --}}
                                                <a href="#" class="btn px-3 btn-outline-secondary btn-sm px-4 skeleton-popup"
                                                    data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="changelogo">
                                                    <i class="ti ti-camera me-1"></i> Change Logo
                                                </a>
                                            </div>     
                                            {{-- <div>  
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <h5 class="fw-bold mb-3 mt-4">About</h5>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                        data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="about"><i class="ti ti-edit"></i></a>
                                                </div>
                                                <p>About Company Will come here</p>
                                            </div> --}}
                                           
                                            <div>  
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <h5 class="fw-bold mb-3 mt-4">Social Network</h5>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                        data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="sociallinks"><i class="ti ti-edit"></i></a>
                                                </div>
                                                @php $socialUrls =json_decode( $company['social_links']?? '[]', true); @endphp

                                                 @if (!empty($socialUrls) && is_iterable($socialUrls))
                                                    @foreach ($socialUrls as $platform => $url)
                                                        @php $platform = strtolower($platform); @endphp
                                                        @if (!empty($url))
                                                            <a href="{{ $url }}" class="avatar avatar-md me-2 avatar-rounded" target="_blank">
                                                                <img src="{{ asset('social/' . ($platform === 'portfolio' ? 'world' : $platform) . '.svg') }}"
                                                                    alt="{{ $platform }}">
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <p>No social links available.</p>
                                                @endif
                                            </div>
                                        </div>  
                                    </div>
                                </div>
                                <!-- Right Column: Company Information Cards -->
                                <div class="col-xl-8 col-lg-7 col-md-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            {{-- Navigation Tabs with Proper Names --}}
                                            <ul class="nav nav-pills mb-4 data-skl-action" id="skeleton-config-{{ $companyId }}" role="tablist">
                                                <li class="nav-item bg-light" role="presentation">
                                                    <button class="nav-link active" id="info-tab-{{ $companyId }}" data-bs-toggle="tab" data-bs-target="#info-{{ $companyId }}" data-skl-action="b"
                                                            data-token="info" type="button" role="tab" aria-controls="info-{{ $companyId }}"
                                                            aria-selected="true">Info</button>
                                                </li>
                                                <li class="nav-item bg-light" role="presentation">
                                                    <button class="nav-link" id="scopes-tab-{{ $companyId }}" data-bs-toggle="tab" data-bs-target="#scopes-{{ $companyId }}" data-skl-action="b"
                                                            data-token="scopes" type="button" role="tab" aria-controls="scopes-{{ $companyId }}"
                                                            aria-selected="false">Scopes</button>
                                                </li>
                                                {{-- <li class="nav-item bg-light" role="presentation">
                                                    <button class="nav-link" id="settings-tab-{{ $companyId }}" data-bs-toggle="tab" data-bs-target="#settings-{{ $companyId }}"
                                                            data-skl-action="b" data-token="settings" type="button" role="tab" aria-controls="settings-{{ $companyId }}"
                                                            aria-selected="false">Settings</button>
                                                </li> --}}
                                                <li class="nav-item bg-light" role="presentation">
                                                    <button class="nav-link" id="policies-tab-{{ $companyId }}" data-bs-toggle="tab" data-bs-target="#policies-{{ $companyId }}"
                                                            data-skl-action="b" data-token="policies" type="button" role="tab" aria-controls="policies-{{ $companyId }}"
                                                            aria-selected="false">Policies</button>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <!-- Info Tab (Read-Only) -->
                                                <div class="tab-pane fade show active" id="info-{{ $companyId }}" role="tabpanel" aria-labelledby="info-tab-{{ $companyId }}">
                                                    <h4 class="fw-bold mb-4 border-bottom pb-3 fs-14">Company Information</h4>
                                                    <p class="text-muted small mb-4">
                                                    <i class="ti ti-info-circle me-1"></i> This section contains essential details about your organization, 
                                                    including company name, registration details, contact information, and other professional credentials 
                                                    that define your business identity.
                                                    </p>

                                                    @if ($companyId)
                                                        <div class="row g-3">
                                                            <!-- Basic Information Card -->
                                                            <div class="row mb-2 my-0 g-3">
                                                                <!-- Total Users -->
                                                                <div class="col-md col-6">
                                                                    <div class="card position-relative shadow-none mb-0 mt-0">
                                                                        <div class="card-body">
                                                                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                                                                                <div>
                                                                                    <p class="fw-medium mb-1 text-muted small">Total Users</p>
                                                                                    <h5 class="mb-0 fw-bold">{{ $company['users_count'] }}</h5>
                                                                                </div>
                                                                                <div class="avatar avatar-md br-10 icon-rotate bg-primary">
                                                                                    <span class="d-flex align-items-center">
                                                                                        <i class="fa-solid fa-users text-white fs-16"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Roles -->
                                                                <div class="col-md col-6">
                                                                    <div class="card position-relative shadow-none mb-0 mt-0">
                                                                        <div class="card-body">
                                                                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                                                                                <div>
                                                                                    <p class="fw-medium mb-1 text-muted small">No Of Roles</p>
                                                                                    <h5 class="mb-0 fw-bold">{{ $company['roles_count'] }}</h5>
                                                                                </div>
                                                                                <div class="avatar avatar-md br-10 icon-rotate bg-purple">
                                                                                    <span class="d-flex align-items-center">
                                                                                        <i class="fa-solid fa-badge-check text-white fs-16"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md col-6">
                                                                    <div class="card position-relative shadow-none mb-0 mt-0">
                                                                        <div class="card-body">
                                                                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                                                                                <div>
                                                                                    <p class="fw-medium mb-1 text-muted small">No of Plocies</p>
                                                                                    <h5 class="mb-0 fw-bold">{{ $company['policy_count'] }}</h5>
                                                                                </div>
                                                                                <div class="avatar avatar-md br-10 icon-rotate bg-warning">
                                                                                    <span class="d-flex align-items-center">
                                                                                        <i class="fa-solid fa-file-contract text-white fs-16"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-6 my-0">
                                                                <div class="card border-0 shadow-none rounded-3 transition">
                                                                    <div class="card-body sf-12">
                                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                                            <h4 class="fw-bold">Company Details</h4>
                                                                            <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                                            data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="editdetails">
                                                                                <i class="ti ti-edit"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-building me-2"></i>Company Name</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['name'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-building me-2"></i>Legal Name</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['legal_name'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-world me-2"></i>Website</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    @if (!empty($company['website']))
                                                                                        <a href="{{ $company['website'] }}" target="_blank" class="text-primary fw-semibold">
                                                                                            Visit Website <i class="ti ti-external-link ms-1"></i>
                                                                                        </a>
                                                                                    @else
                                                                                        <span class="text-muted">N/A</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-mail me-2"></i>Email</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <a href="mailto:{{ $company['email'] ?? 'N/A' }}"
                                                                                    class="text-primary">{{ $company['email'] ?? 'N/A' }}</a>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-phone me-2"></i>Phone</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['phone'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-square-check me-2"></i>Status</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    @if (!empty($company['is_active']))
                                                                                        <span class="badge bg-success-subtle text-success">Active</span>
                                                                                    @else
                                                                                        <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Address Information Card -->
                                                            <div class="col-md-6 my-0">
                                                                <div class="card border-0 shadow-none rounded-3 transition">
                                                                    <div class="card-body sf-12">
                                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                                            <h4 class="fw-bold">Address</h4>
                                                                            <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                                            data-token="@skeletonToken('business_companies')_e_{{ $companyId }}" data-id="editaddress">
                                                                                <i class="ti ti-edit"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="sf-12">
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-home-2 me-2"></i>Address Line 1</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['address_line1'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-road me-2"></i>Address Line 2</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['address_line2'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-building-community me-2"></i>City</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['city'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-map me-2"></i>State</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['state'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-flag me-2"></i>Country</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['country'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row align-items-center mb-2">
                                                                                <div class="col-auto">
                                                                                    <span><i class="ti ti-mailbox me-2"></i>Pincode</span>
                                                                                </div>
                                                                                <div class="col text-end">
                                                                                    <p class="text-dark mb-0">{{ $company['pincode'] ?? 'N/A' }}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                                            style="height:calc(50vh - 100px) !important;">
                                                            <img src="{{ asset('errors/empty.svg') }}"
                                                                alt="No Company Details" class="img-fluid mb-2 w-25">
                                                            <h6 class="mb-2 fw-bold">No company details available</h6>
                                                            <p class="text-muted">Select a company tab to view details or add a new company.</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <!-- Scopes Tab -->
                                                <div class="tab-pane fade" id="scopes-{{ $companyId }}" role="tabpanel" aria-labelledby="scopes-tab-{{ $companyId }}">
                                                   <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <div class="col">
                                                            <div class="d-flex justify-content-between mb-2 border-bottom pb-2 fs-14">
                                                                <h5 class="fw-bold">Scope</h5>
                                                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary skeleton-popup" data-token="@skeletonToken('open_scopes')_a" data-id={{ $companyId }}>
                                                                    <i class="ti ti-copy-plus me-1"></i> Add Scope
                                                                </a>
                                                            </div>
                                                            <p class="text-muted small mb-4">
                                                            <i class="ti ti-info-circle me-1"></i> This section allows you to manage and define the scope of your organization, including departments, designations, and roles. You can add or update the structure that outlines how your company operates, its internal hierarchy, and employee responsibilities.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <button class="btn btn-sm btn-outline-secondary" data-full-screen="#scope-tree-container-{{ $companyId }}">
                                                            Full Screen
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" onclick="location.reload()">
                                                            Refresh
                                                        </button>
                                                    </div>
                                                   <div id="scope-tree-container-{{ $company['company_id'] }}" style="width:100%; height:400px" data-root-name="{{ $company['name'] }}"
                                                    data-scopes='@json($company['scopes'])' data-token="@skeletonToken('open_scopes')">
                                                </div>
                                                    <!-- Add your scopes content here -->
                                                </div>
                                                <!-- Settings Tab -->
                                                <div class="tab-pane fade" id="settings-{{ $companyId }}" role="tabpanel" aria-labelledby="settings-tab-{{ $companyId }}">
                                                    <h5 class="fw-bold mb-4 border-bottom pb-3">Settings</h5>
                                                    <p class="text-muted small mb-4"><i class="ti ti-info-circle me-1"></i>Configure account settings.</p>
                                                    <!-- Add your settings content here -->
                                                </div>
                                                <!-- Policies Tab -->
                                                <div class="tab-pane fade" id="policies-{{ $companyId }}" role="tabpanel" aria-labelledby="policies-tab-{{ $companyId }}">
                                                    <div class="d-flex align-items-center justify-content-between mb-2">                                                        
                                                        <div class="col">
                                                            <div class="d-flex justify-content-between mb-2 border-bottom pb-2 fs-14">
                                                                <h5 class="fw-bold">Company Polocies</h5>
                                                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary skeleton-popup" data-token="@skeletonToken('business_company_policies')_a" data-id={{ $companyId }}>
                                                                    <i class="ti ti-copy-plus me-1"></i> Add policy
                                                                </a>
                                                            </div>
                                                            <p class="text-muted small mb-4">
                                                            <i class="ti ti-info-circle me-1"></i> This section allows you to manage and add policies for your organization and company, including guidelines, compliance requirements, and operational standards that govern your business practices and employee conduct.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div data-skeleton-card-set="@skeletonToken('business_company_policies')_c_{{ $companyId }}"  data-placeholder="card|9" data-type="scroll"
                                                        data-limit="10" data-container="row">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection