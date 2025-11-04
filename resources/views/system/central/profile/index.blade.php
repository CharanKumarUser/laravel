@extends('layouts.system-app')
@section('title', 'Profile')
@section('content')
    <div class="content p-3 p-md-4">
        {{-- Breadcrumb and Header --}}
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
            <div class="my-auto mb-2">
                <h3 class="mb-1 fw-bold">Profile Dashboard</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i>
                                Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profile</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons me-3">
                    <span class="live-time-icon me-2"><i class="ti ti-clock"></i></span>
                    <div class="live-time"></div>
                </div>
                <div class="head-icons">
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Collapse Header" id="collapse-header"
                        class="btn btn-sm btn-outline-light"><i class="ti ti-chevrons-up"></i></a>
                </div>
            </div>
        </div>
        @php
            $user = (object) ($data['user'] ?? []);

       
            $userInfo = (object) ($data['info'] ?? []);
            $tfSet = (object) ($data['tf_set'] ?? ['qr' => '', 'code' => '', 'codes' => []]);
            $settings = (object) ($data['settings'] ?? [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'social_logins' => ['google' => false, 'facebook' => false, 'github' => false, 'x' => false],
                'allow_fcm' => true,
                'auto_logout_on_password_change' => true,
                'allow_logout_all_devices' => true,
                'max_login_limit' => 5,
                'session_timeout_minutes' => 30,
                'failed_login_attempts_limit' => 3,
                'lockout_duration_minutes' => 5,
                'password_rotation_days' => 90,
                'rate_limit_attempts' => 10,
                'rate_limit_window_seconds' => 60,
                'email_verification_enabled' => true,
                'allow_marketing' => false,
                'profile_visibility' => 'public',
            ]);
            $bankInfo = $data['bank_info'] ?? '[]';
            $socialUrls = $data['social_urls'] ?? [];
            $educationDetails = $data['education_details'] ?? [];
            $experience = $data['experience'] ?? [];
            $emergencyInfo = $data['emergency_info'] ?? [];
            $authLogs = (array) ($data['auth_logs'] ?? []);
            $documents = (array) ($data['documents'] ?? []);
            $providers = (array) ($data['providers'] ?? [
                'google' => 'connected',
                'facebook' => 'connected',
                'x' => 'disconnected',
                'github' => 'connected',
            ]);
            // Password rotation warning
            $passwordWarning = '';
            if (!empty($user->last_password_changed_at)) {
                $lastChanged = \Carbon\Carbon::parse($user->last_password_changed_at);
                $daysSinceChange = $lastChanged->diffInDays(now());
                $rotationDays = $settings->password_rotation_days ?? 90;
                if ($daysSinceChange > $rotationDays) {
                    $passwordWarning = 'Your password has expired. Please update it to maintain account security.';
                } elseif ($daysSinceChange > $rotationDays - 7) {
                    $passwordWarning = 'Your password will expire soon. Consider updating it to avoid interruptions.';
                }
            }

        @endphp
        <div class="row g-3">
            <!-- Left Column: Profile Card (4/12) -->
            <div class="col-xl-4 col-lg-5 col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden collapse show" id="profileCard">
                    <!-- Profile Banner -->
                    <div class="position-relative">
                        <button class="cover-image-edit skeleton-popup" data-token="@skeletonToken('open_cover_photo_change')_a">
                            <i class="ti ti-pencil me-1"></i>
                        </button>
                        <img src="{{ $authUser?->cover ? e(app(\App\Services\FileService::class)->getFile($authUser->cover)) : asset('default/preview-window.svg') }}"
                            class="card-img-top" alt="Profile Banner"
                            style="min-height:150px;height: 150px; object-fit: cover;">
                        <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: 0px;">
                            <img src="{{ $authUser?->profile ? e(app(\App\Services\FileService::class)->getFile($authUser->profile)) : asset('default/profile-avatar.svg') }}"
                                class="rounded-circle border border-5 border-white shadow-lg" alt="Profile Image"
                                style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="card-body text-center" style="padding-top: 74px;">
                        <div class="mb-3">
                            <h2 class="d-flex align-items-center justify-content-center mb-1 card-title">{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}<i class="ti ti-discount-check-filled text-success ms-1"></i></h2>
                            <div class="text-center"><span class="badge badge-soft-dark fw-medium me-2 rounded-pill"> {{ $userInfo->job_title ?? '' }} </span></div>
                            <div class="d-flex justify-content-center align-items-center mt-2">
                                <span class="sf-11">Code: <b>{{ $userInfo->unique_code ?? '-' }}</b></span>
                                <span class="mx-2">|</span>
                                <span class="sf-11">Joined on: <b>{{ $userInfo->hire_date ?? '-' }}</b></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <a href="#" class="btn px-3 btn-primary btn-sm px-4 skeleton-popup"
                                data-token="@skeletonToken('open_profile_edit')_a_main"><i class="ti ti-pencil me-1"></i> Edit Profile</a>
                            <a href="#" class="btn px-3 btn-outline-secondary btn-sm px-4 skeleton-popup"
                                data-token="@skeletonToken('open_profile_photo_change')_a"><i class="ti ti-camera me-1"></i> Change Photo</a>
                        </div>
                        <div class="text-start">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="fw-bold">About</h5>
                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                    data-token="@skeletonToken('open_profile_edit')_a_bio"><i class="ti ti-edit"></i></a>
                            </div>
                            <p class="text-muted sf-12">
                                {{ $userInfo->bio ?? 'Passionate professional with extensive experience in their field, dedicated to excellence.' }}
                            </p>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="fw-bold">Skills</h5>
                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                    data-token="@skeletonToken('open_profile_edit')_a_skills"><i class="ti ti-edit"></i></a>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach (explode(',', $userInfo->skills ?? '') as $skill)
                                    <span class="badge badge-soft-dark rounded-pill fw-medium">{{ trim($skill) }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="fw-bold mb-3 mt-4">Social Network</h5>
                            <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                data-token="@skeletonToken('open_profile_edit')_a_sociallinks"><i class="ti ti-edit"></i></a>
                        </div>
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
                        <div class="text-start">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="fw-bold">Emergency Contact Number</h5>
                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                    data-token="@skeletonToken('open_profile_edit')_a_emergency"><i class="ti ti-edit"></i></a>
                            </div>
                            @if (!empty($emergencyInfo) && is_array($emergencyInfo))
                                @foreach ($emergencyInfo as $index => $contact)
                                    <div class="p-2 sf-12 {{ $index !== array_key_last($emergencyInfo) ? 'border-bottom' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="d-inline-flex align-items-center">
                                                    {{ $contact['type'] ?? 'N/A' }}
                                                </span>
                                                <h6 class="d-flex align-items-center fw-medium mt-1">
                                                    {{ $contact['name'] ?? 'N/A' }}
                                                    <span class="d-inline-flex mx-1">
                                                        <i class="ti ti-point-filled text-danger"></i>
                                                    </span>
                                                    {{ $contact['relation'] ?? '' }}
                                                </h6>
                                            </div>
                                            <p class="text-dark">
                                                {{ $contact['phone'] ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No emergency contacts found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Right Column: Tabs (8/12) -->
            <div class="col-xl-8 col-lg-7 col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        {{-- Navigation Tabs with Proper Names --}}
                        <ul class="nav nav-pills mb-4 data-skl-action" id="skeleton-configs" role="tablist">
                            <li class="nav-item bg-light" role="presentation"><button class="nav-link active"
                                    id="account-tab" data-bs-toggle="tab" data-bs-target="#account" data-skl-action="b"
                                    data-token="account" type="button" role="tab" aria-controls="account"
                                    aria-selected="true">Account</button></li>
                            <li class="nav-item bg-light" role="presentation"><button class="nav-link" id="banking-tab"
                                    data-bs-toggle="tab" data-bs-target="#banking" data-skl-action="b"
                                    data-token="banking" type="button" role="tab" aria-controls="banking"
                                    aria-selected="false">Banking</button></li>
                            {{-- <li class="nav-item bg-light" role="presentation"><button class="nav-link" id="documents-tab"
                                    data-bs-toggle="tab" data-bs-target="#documents" data-skl-action="b"
                                    data-token="documents" type="button" role="tab" aria-controls="documents"
                                    aria-selected="false">Documents</button></li> --}}
                            <li class="nav-item bg-light" role="presentation"><button class="nav-link"
                                    id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications"
                                    data-skl-action="b" data-token="notifications" type="button" role="tab"
                                    aria-controls="notifications" aria-selected="false">Notifications</button></li>
                            <li class="nav-item bg-light" role="presentation"><button class="nav-link" id="security-tab"
                                    data-bs-toggle="tab" data-bs-target="#security" data-skl-action="b"
                                    data-token="security" type="button" role="tab" aria-controls="security"
                                    aria-selected="false">Security</button></li>
                        </ul>
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Account Tab (Read-Only) -->
                            <div class="tab-pane fade show active" id="account" role="tabpanel"
                                aria-labelledby="account-tab">
                                <h5 class="fw-bold mb-4 border-bottom pb-3">Account Information</h5>
                                <p class="text-muted small mb-4"><i class="ti ti-info-circle me-1"></i> Your personal and
                                    professional details.</p>
                                <div class="row g-3">
                                    <!-- Basic Information Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-none rounded-3 transition">
                                            <div class="card-body sf-12">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col">
                                                        <h4 class="fw-bold mb-0">Basic Information</h4>
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="javascript:void(0);"
                                                            class="btn btn-icon btn-sm skeleton-popup"
                                                            data-token="@skeletonToken('open_profile_edit')_a_basicinfo">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-phone me-2"></i>Phone</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->phone ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-phone-plus me-2"></i>Alt Phone</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->alt_phone ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-mail-check me-2"></i>Email</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <a href="mailto:{{ $user->email ?? 'N/A' }}"
                                                            class="text-primary">{{ $user->email ?? 'N/A' }}</a>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-mail-plus me-2"></i>Alt Email</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <a href="mailto:{{ $userInfo->alt_email ?? 'N/A' }}"
                                                            class="text-primary">{{ $userInfo->alt_email ?? 'N/A' }}</a>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-gender-male me-2"></i>Gender</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->gender ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-cake me-2"></i>Birthday</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">
                                                            {{ \Carbon\Carbon::parse($userInfo->date_of_birth ?? '')->format('jS F Y') ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-map-pin-check me-2"></i>Nationality</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->nationality ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Contact Information Card -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-none rounded-3 transition">
                                            <div class="card-body sf-12">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col">
                                                        <h4 class="fw-bold mb-0">Address Information</h4>
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="javascript:void(0);"
                                                            class="btn btn-icon btn-sm skeleton-popup"
                                                            data-token="@skeletonToken('open_profile_edit')_a_address">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-home-2 me-2"></i>Address Line 1</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->address_line1 ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-road me-2"></i>Address Line 2</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->address_line2 ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-building-community me-2"></i>City</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->city ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-map me-2"></i>State</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->state ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-mailbox me-2"></i>Postal Code</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->postal_code ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-auto">
                                                        <span><i class="ti ti-flag me-2"></i>Country</span>
                                                    </div>
                                                    <div class="col text-end">
                                                        <p class="text-dark mb-0">{{ $userInfo->country ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-none rounded-3 transition">
                                            <div class="card-body sf-12">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <h4 class="fw-bold">Education</h4>
                                                    <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                        data-token="@skeletonToken('open_profile_edit')_a_educationadd">
                                                        <i class="ti ti-copy-plus me-1"></i> Add
                                                    </a>
                                                    @if (!empty($educationDetails) && is_array($educationDetails))
                                                        <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                            data-token="@skeletonToken('open_profile_edit')_a_educationedit">
                                                            <i class="ti ti-edit me-1"></i> Edit
                                                        </a>
                                                    @endif
                                                </div>
                                                @if (!empty($educationDetails) && is_array($educationDetails))
                                                    @foreach ($educationDetails as $index => $education)
                                                        <div class="mb-3">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <div>
                                                                    <span
                                                                        class="d-inline-flex align-items-center fw-normal">
                                                                        {{ $education['university'] ?? 'N/A' }}
                                                                    </span>
                                                                    <h6 class="d-flex align-items-center mt-1">
                                                                        {{ $education['degree'] ?? 'N/A' }}
                                                                    </h6>
                                                                </div>
                                                                <div class="text-end">
                                                                    <p class="text-dark mb-1">
                                                                        {{ \Illuminate\Support\Carbon::parse($education['start_year'] ?? '')->format('M Y') ?? 'N/A' }}
                                                                        -
                                                                        {{ \Illuminate\Support\Carbon::parse($education['end_year'] ?? '')->format('M Y') ?? 'N/A' }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                                        style="height:calc(30vh - 30px) !important;">
                                                        <img src="{{ asset('errors/empty.svg') }}"
                                                            alt="No Education Details" class="img-fluid mb-2 w-25">
                                                        <h6 class="mb-2 fw-bold">No education details</h6>
                                                        <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                                            <a href="#"
                                                                class="btn btn-primary btn-sm skeleton-popup transition"
                                                                data-token="@skeletonToken('open_profile_edit')_a_educationadd">
                                                                <i class="ti ti-plus me-1"></i> Add Education
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-none rounded-3 transition">
                                            <div class="card-body sf-12">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <h4 class="fw-bold">Experience</h4>
                                                    <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                        data-token="@skeletonToken('open_profile_edit')_a_experienceadd">
                                                        <i class="ti ti-copy-plus me-1"></i> Add
                                                    </a>
                                                    @if (!empty($experience) && is_array($experience))
                                                        <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                            data-token="@skeletonToken('open_profile_edit')_a_experienceedit">
                                                            <i class="ti ti-edit me-1"></i> Edit
                                                        </a>
                                                    @endif
                                                </div>
                                                <div>
                                                    @if (!empty($experience) && is_array($experience))
                                                        @foreach ($experience as $item)
                                                            <div class="mb-3">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <h6
                                                                            class="d-inline-flex align-items-center fw-medium">
                                                                            {{ $item['company'] ?? 'N/A' }}
                                                                        </h6>
                                                                        <span
                                                                            class="d-flex align-items-center badge bg-secondary-transparent mt-1">
                                                                            <i class="ti ti-point-filled me-1"></i>
                                                                            {{ $item['position'] ?? 'N/A' }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <p class="text-dark mb-1">
                                                                            {{ \Illuminate\Support\Carbon::parse($item['start_date'] ?? '')->format('M Y') ?? 'N/A' }}
                                                                            -
                                                                            {{ \Illuminate\Support\Carbon::parse($item['end_date'] ?? '')->format('M Y') ?? 'N/A' }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                                            style="height:calc(30vh - 30px) !important;">
                                                            <img src="{{ asset('errors/empty.svg') }}"
                                                                alt="No Work Experience" class="img-fluid mb-2 w-25">
                                                            <h6 class="mb-2 fw-bold">No Work Experience Added</h6>
                                                            <div
                                                                class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                                                <a href="#"
                                                                    class="btn btn-primary btn-sm skeleton-popup transition"
                                                                    data-token="@skeletonToken('open_profile_edit')_a_experienceadd">
                                                                    <i class="ti ti-plus me-1"></i> Add Work Experience
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Banking Tab -->
                            <div class="tab-pane fade" id="banking" role="tabpanel" aria-labelledby="banking-tab">
                                <h5 class="fw-bold mb-4 border-bottom pb-3">Banking Information</h5>
                                <p class="text-muted small mb-4"><i class="ti ti-info-circle me-1"></i> Manage your bank
                                    accounts for financial transactions.</p>
                                @if (!empty($bankInfo))
                                    @foreach ($bankInfo as $index => $bank)
                                        <div class="card border-0 shadow-none mb-3 rounded-3 transition">
                                            <div class="card-body sf-12">
                                                @php
                                                    $formSkeletonToken =
                                                        \App\Facades\Skeleton::skeletonToken('open_profile_edit') .
                                                            '_f_' .
                                                            $bank['account_number'] ??
                                                        ('' ?? '');
                                                    $formAction = url('/skeleton-action') . '/' . $formSkeletonToken;
                                                @endphp
                                                <form method="POST" action="{{ $formAction }}">
                                                    @csrf
                                                    <input type="hidden" name="save_token"
                                                        value="{{ $formSkeletonToken }}">
                                                    <input type="hidden" name="existing_json"
                                                        value="{{ json_encode($bankInfo) }}">
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-building-bank"></i></span>
                                                                <input type="text" id="bankName_{{ $index }}"
                                                                    name="bank_name" class="form-float-input"
                                                                    placeholder="Bank Name"
                                                                    value="{{ $bank['bank_name'] ?? '' }}">
                                                                <label for="bankName_{{ $index }}"
                                                                    class="form-float-label">Bank Name</label>
                                                            </div>
                                                            <small class="text-muted">e.g., State Bank</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-credit-card"></i></span>
                                                                <input type="text"
                                                                    id="accountNumber_{{ $index }}"
                                                                    name="account_number" class="form-float-input"
                                                                    placeholder="Account Number"
                                                                    value="{{ $bank['account_number'] ?? '' }}">
                                                                <label for="accountNumber_{{ $index }}"
                                                                    class="form-float-label">Account Number</label>
                                                            </div>
                                                            <small class="text-muted">e.g., 1234567890</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-barcode"></i></span>
                                                                <input type="text" id="ifscCode_{{ $index }}"
                                                                    name="ifsc_code" class="form-float-input"
                                                                    placeholder="IFSC Code"
                                                                    value="{{ $bank['ifsc_code'] ?? '' }}">
                                                                <label for="ifscCode_{{ $index }}"
                                                                    class="form-float-label">IFSC Code</label>
                                                            </div>
                                                            <small class="text-muted">e.g., SBIN0001234</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-wallet"></i></span>
                                                                <select id="accountType_{{ $index }}"
                                                                    name="account_type" class="form-float-input"
                                                                    data-select="dropdown"
                                                                    data-value="{{ $bank['account_type'] }}">
                                                                    <option value="Checking">
                                                                        Checking
                                                                    </option>
                                                                    <option value="Savings">
                                                                        Savings
                                                                    </option>
                                                                </select>
                                                                <label for="accountType_{{ $index }}"
                                                                    class="form-float-label">Account Type</label>
                                                            </div>
                                                            <small class="text-muted">Select account type</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-map-pin"></i></span>
                                                                <input type="text" id="branch_{{ $index }}"
                                                                    name="branch" class="form-float-input"
                                                                    placeholder="Branch"
                                                                    value="{{ $bank['branch'] ?? '' }}">
                                                                <label for="branch_{{ $index }}"
                                                                    class="form-float-label">Branch</label>
                                                            </div>
                                                            <small class="text-muted">e.g., Main Branch</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-map"></i></span>
                                                                <input type="text" id="city_{{ $index }}"
                                                                    name="city" class="form-float-input"
                                                                    placeholder="City" value="{{ $bank['city'] ?? '' }}">
                                                                <label for="city_{{ $index }}"
                                                                    class="form-float-label">City</label>
                                                            </div>
                                                            <small class="text-muted">e.g., Mumbai</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-3 d-flex justify-content-end">
                                                        <button type="submit"
                                                            class="btn btn-primary btn-sm me-2 transition">Save Bank
                                                            Account</button>
                                                        <a href="#"
                                                            class="btn btn-outline-danger btn-sm skeleton-popup transition"
                                                            data-token="@skeletonToken('open_profile_edit')_a_bankdelete_{{ $bank['account_number'] ?? '' }}">
                                                            <i class="ti ti-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                    <a href="#" class="btn btn-primary btn-sm skeleton-popup transition"
                                        data-token="@skeletonToken('open_profile_edit')_a_bankadd">
                                        <i class="ti ti-plus me-1"></i> Add New Bank Account
                                    </a>
                                @else
                                    <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                        style="height:calc(100vh - 200px) !important;">
                                        <img src="{{ asset('errors/empty.svg') }}" alt="No Bank Accounts"
                                            class="img-fluid mb-2 w-25">
                                        <h1 class="h4 mb-2 fw-bold">No Bank Accounts Added</h1>
                                        <p class="text-muted mb-2" style="max-width: 600px;">
                                            You haven't added any bank account information yet. To receive payments or store
                                            financial details, you need to add at least one account.
                                        </p>
                                        <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                            <a href="#" class="btn btn-primary btn-sm skeleton-popup transition"
                                                data-token="@skeletonToken('open_profile_edit')_a_bankadd">
                                                <i class="ti ti-plus me-1"></i> Add Bank Account
                                            </a>
                                        </div>
                                    </div>
                                @endif
                                <p class="text-muted small mt-3"><i class="ti ti-info-circle me-1"></i> Ensure bank
                                    details are accurate to avoid transaction issues.</p>
                            </div>
                            <!-- Documents Tab -->
                            <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                                <h5 class="fw-bold mb-4 border-bottom pb-3">Documents</h5>
                                <p class="text-muted small mb-4"><i class="ti ti-info-circle me-1"></i> Upload and manage
                                    documents such as IDs, certificates, or contracts.</p>
                                <div class="mb-4">
                                    <form method="POST" action="{{ url('/profile/form') }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="token" value="@skeletonToken('upload_document')_f">
                                        <div class="input-group">
                                            <input type="file" id="documentUpload" name="document_upload"
                                                class="form-control" multiple>
                                            <label class="input-group-text" for="documentUpload"><i
                                                    class="ti ti-upload"></i></label>
                                        </div>
                                        <p class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i> Supported
                                            formats: PDF, DOCX, JPG, PNG (Max 5MB)</p>
                                        <button type="submit" class="btn btn-primary btn-sm transition">Upload
                                            Documents</button>
                                    </form>
                                </div>
                                @if (!empty($documents))
                                    <table class="table table-hover table-bordered rounded-3">
                                        <thead>
                                            <tr>
                                                <th>Document Name</th>
                                                <th>Uploaded Date</th>
                                                <th>Type</th>
                                                <th>Size</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documents as $index => $doc)
                                                <tr class="transition">
                                                    <td>{{ $doc['name'] ?? 'N/A' }}</td>
                                                    <td>{{ $doc['date'] ?? 'N/A' }}</td>
                                                    <td>{{ $doc['type'] ?? 'N/A' }}</td>
                                                    <td>{{ $doc['size'] ?? 'N/A' }}</td>
                                                    <td>
                                                        <a href="#"
                                                            class="btn btn-outline-primary btn-sm me-1 skeleton-popup transition"
                                                            data-token="@skeletonToken('open_document_view_{{ $index }}')_a"><i class="ti ti-eye"></i>
                                                            View</a>
                                                        <a href="#"
                                                            class="btn btn-outline-danger btn-sm skeleton-popup transition"
                                                            data-token="@skeletonToken('open_document_delete_{{ $index }}')_a"><i class="ti ti-trash"></i>
                                                            Delete</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted">No documents uploaded.</p>
                                @endif
                            </div>
                            <!-- Notifications Tab -->
                            <div class="tab-pane fade" id="notifications" role="tabpanel"
                                aria-labelledby="notifications-tab">
                                <h5 class="fw-bold mb-4 border-bottom pb-3">Manage Alerts</h5>
                                <p class="text-muted small mb-4">
                                    <i class="ti ti-info-circle me-1"></i> Control your notification preferences for
                                    emails, SMS, and in-app messages.
                                </p>
                                <!-- Notification Settings -->
                                    <div class="mb-3 border-bottom mb-3 pb-3">
                                        <h6 class="fw-bold mb-2 pb-2">Notification Settings</h6>
                                        <p class="text-muted small mb-2"><i class="ti ti-info-circle me-1"></i> Manage
                                            notification preferences.</p>
                                        <h6 class="fw-semibold mb-2 text-dark">Notification Preferences</h6>
                                        @php
                                            $formSkeletonToken =
                                                \App\Facades\Skeleton::skeletonToken('open_profile_settings') .
                                                '_f_notificationpreference';
                                            $formAction = url('/skeleton-action') . '/' . $formSkeletonToken;
                                        @endphp
                                        <form method="POST" action="{{ $formAction }}">
                                            @csrf
                                            <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                            <div class="row g-2">
                                                <div class="col-lg-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                        <div>
                                                            <h6 class="fw-medium mb-1">Email Notifications</h6>
                                                            <p class="text-muted small mb-0">Receive updates via email</p>
                                                        </div>
                                                        <div class="form-check form-check-md form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="email_notifications" role="switch"
                                                                id="email_notifications"
                                                                {{ $settings->email_notifications ?? false ? 'checked' : '' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                        <div>
                                                            <h6 class="fw-medium mb-1">SMS Notifications</h6>
                                                            <p class="text-muted small mb-0">Receive alerts via SMS</p>
                                                        </div>
                                                        <div class="form-check form-check-md form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="sms_notifications" role="switch"
                                                                id="sms_notifications"
                                                                {{ $settings->sms_notifications ?? false ? 'checked' : '' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                        <div>
                                                            <h6 class="fw-medium mb-1">Push Notifications</h6>
                                                            <p class="text-muted small mb-0">Receive real-time push
                                                                notifications</p>
                                                        </div>
                                                        <div class="form-check form-check-md form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="push_notifications" role="switch"
                                                                id="push_notifications"
                                                                {{ $settings->push_notifications ?? false ? 'checked' : '' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-2 text-end">
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm transition shadow-sm">Save
                                                        Preference</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <div class="container-fluid py-3">
                                    <!-- Recent Notifications -->
                                    <div class="card border-0 shadow-sm mb-3 rounded-3">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-2">Recent Notifications</h6>
                                            {{-- @if (!empty($notifications))
                                                @php
                                                    $categoryIcons = [
                                                        'system' => 'ti ti-settings',
                                                        'feature' => 'ti ti-star',
                                                        'account' => 'ti ti-user',
                                                        'meeting' => 'ti ti-calendar-event',
                                                        'finance' => 'ti ti-currency-dollar',
                                                        'app' => 'ti ti-device-mobile',
                                                        'event' => 'ti ti-ticket',
                                                    ];
                                                @endphp
                                                @foreach ($notifications as $notification)
                                                    @php
                                                        $isUnread = empty($notification['read_at']);
                                                        $category = strtolower($notification['category'] ?? 'default');
                                                        $iconClass = $categoryIcons[$category] ?? 'ti ti-bell';
                                                    @endphp
                                                    <a href="#" class="text-decoration-none">
                                                        <div
                                                            class="d-flex p-2 border rounded mb-2 align-items-start {{ $isUnread ? 'bg-light bg-opacity-10' : 'bg-light' }} hover-shadow transition">
                                                            <span
                                                                class="avatar avatar-sm me-2 flex-shrink-0 d-flex align-items-center justify-content-center bg-white border rounded-circle">
                                                                <i class="{{ $iconClass }} text-primary"></i>
                                                            </span>
                                                            <div class="flex-grow-1">
                                                                <p class="mb-1 text-dark fw-semibold">
                                                                    {{ $notification['message'] ?? 'N/A' }}</p>
                                                                <small
                                                                    class="text-muted d-block">{{ \Carbon\Carbon::parse($notification['created_at'])->format('M d, Y H:i') }}</small>
                                                            </div>
                                                        </div>
                                                    </a>
                                                @endforeach
                                            @else
                                                <p class="text-muted">No recent notifications.</p>
                                            @endif --}}
                                            <p class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i> Unread
                                                notifications highlighted.</p>
                                        </div>
                                    </div>
                                    <!-- Authentication Logs -->
                                    <div class="card border-0 shadow-sm rounded-3">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-2">Authentication Logs</h6>
                                            @if (!empty($authLogs))
                                                <table class="table table-hover table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Status</th>
                                                            <th>IP Address</th>
                                                            <th>Date</th>
                                                            <th>Device</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($authLogs as $log)
                                                            @php
                                                                $browser = 'Unknown';
                                                                $icon = 'ti ti-device-desktop';
                                                                $ua = strtolower($log['device_info'] ?? '');
                                                                if (str_contains($ua, 'chrome')) {
                                                                    $browser = 'Chrome';
                                                                    $icon = 'ti ti-brand-chrome';
                                                                } elseif (str_contains($ua, 'firefox')) {
                                                                    $browser = 'Firefox';
                                                                    $icon = 'ti ti-brand-firefox';
                                                                } elseif (
                                                                    str_contains($ua, 'safari') &&
                                                                    !str_contains($ua, 'chrome')
                                                                ) {
                                                                    $browser = 'Safari';
                                                                    $icon = 'ti ti-brand-safari';
                                                                } elseif (str_contains($ua, 'edge')) {
                                                                    $browser = 'Edge';
                                                                    $icon = 'ti ti-brand-edge';
                                                                } elseif (
                                                                    str_contains($ua, 'opera') ||
                                                                    str_contains($ua, 'opr')
                                                                ) {
                                                                    $browser = 'Opera';
                                                                    $icon = 'ti ti-brand-opera';
                                                                }
                                                            @endphp
                                                            <tr class="transition">
                                                                <td>
                                                                    @if (!empty($log['logout_at']))
                                                                        <span
                                                                            class="badge bg-danger-subtle text-danger">Logout</span>
                                                                    @else
                                                                        <span
                                                                            class="badge bg-success-subtle text-success">Login</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $log['ip_address'] ?? 'N/A' }}</td>
                                                                <td>
                                                                    <div>
                                                                        {{ \Carbon\Carbon::parse($log['login_at'])->format('d M Y, h:i A') }}
                                                                    </div>
                                                                    <small class="text-muted">Last active:
                                                                        {{ \Carbon\Carbon::parse($log['last_activity_at'])->diffForHumans() }}</small>
                                                                </td>
                                                                <td>
                                                                    <i class="{{ $icon }} me-1 text-muted"></i>
                                                                    {{ $browser }}
                                                                    <div class="text-muted small">
                                                                        {{ Str::limit($log['device_info'], 40) }}</div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-muted">No authentication logs available.</p>
                                            @endif
                                            <p class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i>
                                                Monitor login activity for security.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <h5 class="fw-bold mb-4 border-bottom pb-3">Account Security</h5>
                                <p class="text-muted small mb-4">
                                    <i class="ti ti-lock me-1"></i> Manage your password, two-factor authentication, and
                                    other security settings to protect your account.
                                </p>
                                <div class="container-fluid py-3">
                                    <!-- Password Section -->
                                    <div class="mb-3">
                                        <div class="row align-items-center border-bottom mb-3 pb-3">
                                            <div class="col-12 col-md">
                                                <h6 class="fw-semibold text-dark mb-1">Password</h6>
                                               <p class="text-muted small mb-0">
    Last Changed:
    <b class="sf-15">
        {{ $user->last_password_changed_at ? \Carbon\Carbon::parse($user->last_password_changed_at)->format('M d, Y h:i A') : 'Never' }}
    </b>
</p>
                                                @if ($passwordWarning)
                                                    <p class="text-danger small mt-1">
                                                        <i class="ti ti-alert-circle me-1"></i> {{ $passwordWarning }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="col-12 col-md-auto mt-3 mt-md-0 text-end">
                                                <a href="#"
                                                    class="btn btn-primary btn-sm transition shadow-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_profile_change_password')_a">
                                                    <i class="ti ti-key me-1"></i> Change Password
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Logout from All Devices Section -->
                                    <div class="mb-3">
                                        <div class="row align-items-center border-bottom mb-3 pb-3">
                                            <div class="col-12 col-md">
                                                <h6 class="fw-semibold text-dark mb-1">Logout from All Devices</h6>
                                                <p class="text-muted small mb-0">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    This will log you out from all devices and terminate all active sessions
                                                    except the current one.
                                                </p>
                                            </div>
                                            <div class="col-12 col-md-auto mt-3 mt-md-0 text-end">
                                                <a href="#"
                                                    class="btn btn-outline-danger btn-sm transition shadow-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_logout_all_devices')_a">
                                                    <i class="ti ti-logout me-1"></i> Logout from All
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Two-Factor Authentication Section -->
                                    <div class="mb-3">
                                        <div
                                            class="d-flex justify-content-between align-items-start border-bottom mb-3 pb-3">
                                            <div>
                                                <h6 class="fw-semibold text-dark mb-1">Two-Factor Authentication</h6>
                                                @if ($data['tf'] === 'enabled')
                                                    <span class="badge bg-success my-2">Enabled</span>
                                                    <p class="text-muted small mb-0">
                                                        @if ($data['tf_via'] === 'app')
                                                            Using <strong>Authenticator App</strong>.
                                                        @elseif($data['tf_via'] === 'email')
                                                            Receiving codes via <strong>Email</strong>.
                                                        @endif
                                                    </p>
                                                @elseif($data['tf'] === 'pending')
                                                    <span class="badge bg-warning my-2">Pending Verification</span>
                                                    <p class="text-muted small mb-0">Complete verification to enable
                                                        two-factor authentication.</p>
                                                @else
                                                    <span class="badge bg-secondary my-2">Disabled</span>
                                                    <p class="text-muted small mb-0">Two-factor authentication is disabled.
                                                    </p>
                                                @endif
                                            </div>
                                            <a href="#"
                                                class="btn btn-sm transition shadow-sm 
                                                @if ($data['tf'] === 'enabled') btn-dark 
                                                @elseif($data['tf'] === 'pending') btn-warning 
                                                @else btn-primary @endif skeleton-popup"
                                                data-token="@skeletonToken('open_manage_two_factor')_a">
                                                @if ($data['tf'] === 'enabled')
                                                    Disable
                                                @elseif($data['tf'] === 'pending')
                                                    Cancel
                                                @else
                                                    Enable
                                                @endif
                                            </a>
                                        </div>
                                        @if ($data['tf'] === 'pending' && $data['tf_via'] === 'app')
                                            <div class="mt-2 border-bottom mb-3 pb-3">
                                                <h6 class="fw-semibold text-dark mb-1">Set Up Authenticator App</h6>
                                                <p class="text-muted small mb-2">Scan QR code or enter key manually.</p>
                                                <div class="text-center mb-2">
                                                    <img src="data:image/svg+xml;base64,{{ $tfSet->qr }}"
                                                        alt="QR Code" class="img-thumbnail" style="max-width: 150px;">
                                                </div>
                                                <p class="text-muted text-center small mb-2">Manual code:
                                                    <code>{{ $tfSet->code }}</code>
                                                </p>
                                                <form method="POST"
                                                    action="{{ url('/skeleton-action') }}/@skeletonToken('open_two_factor_enable')_f">
                                                    @csrf
                                                    <input type="hidden" name="save_token" value="@skeletonToken('open_two_factor_enable')_f">
                                                    <input type="hidden" name="form_type" value="database">
                                                    <div class="row justify-content-center mb-2">
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end toggle-password"><i
                                                                        class="ti ti-eye-off"></i></span>
                                                                <input type="password" id="code" name="code"
                                                                    class="form-float-input"
                                                                    placeholder="Enter code from app" required>
                                                                <label for="code" class="form-float-label">Enter code
                                                                    from app</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center">
                                                        <button type="submit"
                                                            class="btn btn-primary btn-sm transition shadow-sm"
                                                            data-before-text="Enabling..." data-after-text="Enable">Verify
                                                            & Enable</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @elseif($data['tf'] === 'pending' && $data['tf_via'] === 'email')
                                            <div class="mt-2 border-bottom mb-3 pb-3">
                                                <h6 class="fw-semibold text-dark mb-1">Verify Your Email</h6>
                                                <p class="text-muted small mb-2">Enter the verification code sent to your
                                                    email.</p>
                                                <form method="POST"
                                                    action="{{ url('/skeleton-action') }}/@skeletonToken('open_two_factor_enable')_f">
                                                    @csrf
                                                    <input type="hidden" name="save_token" value="@skeletonToken('open_two_factor_enable')_f">
                                                    <input type="hidden" name="form_type" value="database">
                                                    <div class="row justify-content-center mb-2">
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control">
                                                                <span class="float-group-end toggle-password"><i
                                                                        class="ti ti-eye-off"></i></span>
                                                                <input type="password" id="code" name="code"
                                                                    class="form-float-input"
                                                                    placeholder="Check your email for the code" required>
                                                                <label for="code" class="form-float-label">Check your
                                                                    email for the code</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center">
                                                        <button type="submit"
                                                            class="btn btn-primary btn-sm transition shadow-sm"
                                                            data-before-text="Enabling..." data-after-text="Enable">Verify
                                                            & Enable</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                        @if ($data['tf'] === 'enabled' && $data['tf_via'] === 'app')
                                            <div class="mt-2 border-bottom mb-3 pb-3">
                                                <h6 class="fw-semibold text-dark mb-1">Backup Codes</h6>
                                                <p class="text-muted small mb-2">Save these codes securely for one-time
                                                    use.</p>
                                                <div class="bg-light p-2 rounded">
                                                    <div class="row g-2">
                                                        @foreach ($tfSet->codes as $code => $used)
                                                            <div class="col-lg-6 text-center">
                                                                @if ($used)
                                                                    <span>----------------</span>
                                                                @else
                                                                    <code>{{ $code }}</code>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($data['tf'] === 'enabled' && $data['tf_via'] === 'email')
                                            <div class="mt-2 border-bottom mb-3 pb-3">
                                                <h6 class="fw-semibold text-dark mb-1">Email Verification Enabled</h6>
                                                <p class="text-muted small mb-2">Verification code sent via email on
                                                    sign-in.</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mb-3 border-bottom mb-3 pb-3">
                                        <h6 class="fw-semibold mb-2 text-dark">Social Logins</h6>
                                        <p class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i> Toggle
                                            to enable or disable social login options.</p>
                                        @php
                                            $formSkeletonToken =
                                                \App\Facades\Skeleton::skeletonToken('open_profile_settings') .
                                                '_f_sociallogins';
                                            $formAction = url('/skeleton-action') . '/' . $formSkeletonToken;
                                        @endphp
                                        <form method="POST" action="{{ $formAction }}">
                                            @csrf
                                            <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                            <input type="hidden" name="type" value="sociallogins">
                                            <div class="row g-2">
                                                @if (!empty($settings->social_logins) && is_iterable($settings->social_logins))
                                                    @foreach ($settings->social_logins as $provider => $status)
                                                        <div class="col-lg-12">
                                                            <div class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <img src="{{ asset('social/' . strtolower($provider) . '.svg') }}"
                                                                        alt="{{ $provider }} icon"
                                                                        class="rounded-circle" width="30">
                                                                    <div>
                                                                        <span>Sign in with {{ ucfirst($provider) }}</span>
                                                                        <div class="text-muted small mt-1 sf-10 {{ $status ? 'text-success' : 'text-secondary' }} mb-1">
                                                                            {{ $status ? 'Connected' : 'Disconnected' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-check form-check-md form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="social_logins[{{ $provider }}]"
                                                                        role="switch" id="{{ $provider }}_login"
                                                                        {{ $status ? 'checked' : '' }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="col-lg-12">
                                                        <div class="alert alert-secondary text-center">
                                                            No social login settings found.
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="col-lg-12 mt-2 text-end">
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm transition shadow-sm">Save
                                                        Preferences</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- Email Verification Section -->
                                    <div class="mb-3 border-bottom mb-3 pb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="fw-semibold text-dark mb-1">Email Verification</h6>
                                                <p class="text-muted small mb-0">Verified email:
                                                    {{ $user->email ?? 'N/A' }}</p>
                                                <span>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                @if (!empty($user->email_verified_at))
                                                    <i class="ti ti-discount-check-filled text-success ms-2"></i> Verified
                                                    at
                                                    <b class="ms-1">
                                                        {{ \Carbon\Carbon::parse($user->email_verified_at)->format('M d, Y h:i A') }}
                                                    </b>
                                                @else
                                                    <i class="ti ti-alert-circle-filled text-danger ms-2"></i>
                                                    <b class="text-danger ms-1">Not Verified</b>
                                                    <p class="text-muted small mt-2"><i
                                                            class="ti ti-info-circle me-1"></i> Verify
                                                        email for security.</p>
                                                @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Security Settings -->
                                    <div class="mb-3 border-bottom mb-3 pb-3">
                                        <h5 class="fw-bold mb-2 pb-2 text-primary">Security Settings</h5>
                                        <p class="text-muted small mb-2"><i class="ti ti-info-circle me-1"></i> Customize
                                            account security.</p>
                                        @php
                                            $formSkeletonToken =
                                                \App\Facades\Skeleton::skeletonToken('open_profile_settings') .
                                                '_f_securitysettings';
                                            $formAction = url('/skeleton-action') . '/' . $formSkeletonToken;
                                        @endphp
                                        <form method="POST" action="{{ $formAction }}">
                                            @csrf
                                            <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                            <div class="row g-3">
                                                <!-- General Preferences -->
                                                <div class="col-lg-12">
                                                    <h6 class="fw-semibold mb-2 text-dark">General Preferences</h6>
                                                    <div class="row g-2">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                                <div>
                                                                    <h6 class="fw-medium mb-1">Receive Marketing Emails
                                                                    </h6>
                                                                    <p class="text-muted small mb-0">Opt in for promotional
                                                                        content</p>
                                                                </div>
                                                                <div class="form-check form-check-md form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="allow_marketing" role="switch"
                                                                        id="allow_marketing"
                                                                        {{ $settings->allow_marketing ?? false ? 'checked' : '' }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Authentication Settings -->
                                                <div class="col-lg-12">
                                                    <h6 class="fw-semibold mb-2 text-dark">Authentication Settings</h6>
                                                    <div class="row g-2">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                                <div>
                                                                    <h6 class="fw-medium mb-1">Enable Push Notifications
                                                                        (FCM)</h6>
                                                                    <p class="text-muted small mb-0">Allow Firebase Cloud
                                                                        Messaging</p>
                                                                </div>
                                                                <div class="form-check form-check-md form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="allow_fcm" role="switch" id="allow_fcm"
                                                                        {{ $settings->allow_fcm ?? false ? 'checked' : '' }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                                <div>
                                                                    <h6 class="fw-medium mb-1">Auto Logout on Password
                                                                        Change</h6>
                                                                    <p class="text-muted small mb-0">Log out from all
                                                                        devices on password change</p>
                                                                </div>
                                                                <div class="form-check form-check-md form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="auto_logout_on_password_change"
                                                                        role="switch" id="auto_logout_on_password_change"
                                                                        {{ $settings->auto_logout_on_password_change ?? false ? 'checked' : '' }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light transition">
                                                                <div>
                                                                    <h6 class="fw-medium mb-1">Allow Logout from All
                                                                        Devices</h6>
                                                                    <p class="text-muted small mb-0">Enable manual logout
                                                                        from all sessions</p>
                                                                </div>
                                                                <div class="form-check form-check-md form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="allow_logout_all_devices" role="switch"
                                                                        id="allow_logout_all_devices"
                                                                        {{ $settings->allow_logout_all_devices ?? false ? 'checked' : '' }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Security Inputs -->
                                                <div class="col-lg-12">
                                                    <h6 class="fw-semibold mb-2 text-dark">Security Configurations</h6>
                                                    <div class="row g-2">
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <select id="profile_visibility" name="profile_visibility"
                                                                    class="form-float-input" data-select="dropdown"
                                                                    data-value="{{ $settings->profile_visibility ?? '' }}">
                                                                    <option value="public">Public</option>
                                                                    <option value="organization">Organization Only</option>
                                                                    <option value="private">Private</option>
                                                                </select>
                                                                <label for="profile_visibility"
                                                                    class="form-float-label">Profile Visibility</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Control who can view
                                                                your profile</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-users text-primary"></i></span>
                                                                <input type="number" id="max_login_limit"
                                                                    name="max_login_limit"
                                                                    value="{{ $settings->max_login_limit ?? '' }}"
                                                                    class="form-float-input" placeholder="Max Login Limit"
                                                                    min="1" required>
                                                                <label for="max_login_limit"
                                                                    class="form-float-label">Maximum Login Limit</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Maximum concurrent login
                                                                sessions</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-clock text-primary"></i></span>
                                                                <input type="number" id="session_timeout_minutes"
                                                                    name="session_timeout_minutes"
                                                                    value="{{ $settings->session_timeout_minutes ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Session Timeout (Minutes)" min="1"
                                                                    required>
                                                                <label for="session_timeout_minutes"
                                                                    class="form-float-label">Session Timeout
                                                                    (Minutes)</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Duration before inactive
                                                                session expires</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-lock text-primary"></i></span>
                                                                <input type="number" id="failed_login_attempts_limit"
                                                                    name="failed_login_attempts_limit"
                                                                    value="{{ $settings->failed_login_attempts_limit ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Failed Login Attempts Limit"
                                                                    min="1" required>
                                                                <label for="failed_login_attempts_limit"
                                                                    class="form-float-label">Failed Login Attempts
                                                                    Limit</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Number of failed
                                                                attempts before lockout</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-lock text-primary"></i></span>
                                                                <input type="number" id="lockout_duration_minutes"
                                                                    name="lockout_duration_minutes"
                                                                    value="{{ $settings->lockout_duration_minutes ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Lockout Duration (Minutes)"
                                                                    min="1" required>
                                                                <label for="lockout_duration_minutes"
                                                                    class="form-float-label">Lockout Duration
                                                                    (Minutes)</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Duration of account
                                                                lockout</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-key text-primary"></i></span>
                                                                <input type="number" id="password_rotation_days"
                                                                    name="password_rotation_days"
                                                                    value="{{ $settings->password_rotation_days ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Password Rotation (Days)" min="1"
                                                                    required>
                                                                <label for="password_rotation_days"
                                                                    class="form-float-label">Password Rotation
                                                                    (Days)</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Days before password
                                                                expires</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-shield text-primary"></i></span>
                                                                <input type="number" id="rate_limit_attempts"
                                                                    name="rate_limit_attempts"
                                                                    value="{{ $settings->rate_limit_attempts ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Rate Limit Attempts" min="1"
                                                                    required>
                                                                <label for="rate_limit_attempts"
                                                                    class="form-float-label">Rate Limit Attempts</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Maximum login attempts
                                                                in rate limit window</small>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="float-input-control mb-2">
                                                                <span class="float-group-end"><i
                                                                        class="ti ti-clock text-primary"></i></span>
                                                                <input type="number" id="rate_limit_window_seconds"
                                                                    name="rate_limit_window_seconds"
                                                                    value="{{ $settings->rate_limit_window_seconds ?? '' }}"
                                                                    class="form-float-input"
                                                                    placeholder="Rate Limit Window (Seconds)"
                                                                    min="1" required>
                                                                <label for="rate_limit_window_seconds"
                                                                    class="form-float-label">Rate Limit Window
                                                                    (Seconds)</label>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">Time window for rate
                                                                limiting</small>
                                                        </div>
                                                        <div class="col-lg-12 mt-2 text-end">
                                                            <button type="submit"
                                                                class="btn btn-primary btn-sm transition shadow-sm">Save
                                                                Preference</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- Delete Account Section -->
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="fw-semibold text-dark mb-1">Delete Account</h6>
                                                <p class="text-muted small mb-0">Permanently delete account and data.</p>
                                                <p class="text-danger small mt-1"><i class="ti ti-alert-circle me-1"></i>
                                                    This action is irreversible.</p>
                                            </div>
                                            <a href="#" class="btn btn-danger btn-sm transition shadow-sm"
                                                data-token="@skeletonToken('open_profile_edit')_a_deleteaccount">
                                                <i class="ti ti-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
