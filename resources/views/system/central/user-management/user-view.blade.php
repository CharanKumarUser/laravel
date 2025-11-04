{{-- Template: Scope View Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', ($data['user']['first_name'] ?? 'Unkown') . ' ' . ($data['user']['last_name'] ?? 'User'))
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">{{ $data['user']['first_name'] ?? 'User' }} {{ $data['user']['last_name'] ?? '' }}</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/user-management/users') }}">User Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a
                                href="#">{{ $data['user']['first_name'] ?? 'User' }}
                                {{ $data['user']['last_name'] ?? '' }}</a></li>
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
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            @php
                $user = (object) ($data['user'] ?? []);
                $userInfo = (object) ($data['user_info'] ?? []);
                $userRole = $data['user_roles'] ?? [];
                $passwordWarning = '';
                if (!empty($user->last_password_changed_at)) {
                    $lastChanged = \Carbon\Carbon::parse($user->last_password_changed_at);
                    $daysSinceChange = $lastChanged->diffInDays(now());
                    $rotationDays = $settings->password_rotation_days ?? 90;
                    if ($daysSinceChange > $rotationDays) {
                        $passwordWarning = 'Your password has expired. Please update it to maintain account security.';
                    } elseif ($daysSinceChange > $rotationDays - 7) {
                        $passwordWarning =
                            'Your password will expire soon. Consider updating it to avoid interruptions.';
                    }
                }
            @endphp
            {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************ --}}

            @if (empty($user->user_id))
                <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                    style="height:calc(100vh - 200px) !important;">
                    <img src="{{ asset('errors/empty.svg') }}" alt="User Deleted" class="img-fluid mb-2 w-25">
                    <h1 class="h3 mb-2 fw-bold">User Account Deleted</h1>
                    <p class="text-muted mb-2" style="max-width: 600px;">
                        The user account you are trying to view has been deleted and is no longer available.<br>
                        Any associated data has been removed or archived.
                    </p>
                    <p class="text-muted" style="max-width: 600px;">
                        You can return to the previous page or explore other sections of the application.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded-pill">Go Back</a>
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary rounded-pill">Explore Dashboard</a>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-xl-4 theiaStickySidebar sf-12">
                        <div class="card border-0 shadow-sm overflow-hidden collapse show" id="profileCard">
                            <div class="position-relative">
                                <button class="cover-image-edit skeleton-popup text-white"
                                    data-token="@skeletonToken('open_um')_e_user_banner_{{ $user->user_id }}">
                                    <i class="ti ti-pencil me-1"></i>
                                </button>
                                <img src="{{ $user?->cover ? e(app(\App\Services\FileService::class)->getFile($user->cover)) : asset('default/preview-window.svg') }}"
                                    class="card-img-top" alt="Profile Banner"
                                    style="min-height:150px;height: 150px; object-fit: cover;">
                                <div class="position-absolute top-100 start-50 translate-middle" style="margin-top: 0px;">
                                    <img src="{{ $user?->profile ? e(app(\App\Services\FileService::class)->getFile($user->profile)) : asset('default/profile-avatar.svg') }}"
                                        class="rounded-circle border border-5 border-white shadow-lg" alt="Profile Image"
                                        style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                            </div>
                            <div class="card-body text-center" style="padding-top: 74px;">
                                <div class="text-center pb-3 border-bottom mb-2">
                                    <div class="mb-3">
                                        <h5 class="d-flex align-items-center justify-content-center mb-1 fw-bold">
                                            {{ strtoupper($user->first_name) ?? '' }}
                                            {{ strtoupper($user->last_name) ?? '' }}<i
                                                class="ti ti-discount-check-filled text-success ms-1"></i></h5>
                                        <span
                                            class="badge badge-soft-secondary fw-medium">{{ $userInfo->job_title ?? '' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-center gap-2 mb-3">
                                        <a href="#" class="btn px-3 btn-primary btn-sm px-4 skeleton-popup"
                                            data-token="@skeletonToken('open_um')_e_user_main_{{ $user->user_id }}"><i
                                                class="ti ti-pencil me-1"></i> Edit Profile</a>
                                        <a href="#" class="btn px-3 btn-outline-secondary btn-sm px-4 skeleton-popup"
                                            data-token="@skeletonToken('open_um')_e_user_profilechange_{{ $user->user_id }}"><i
                                                class="ti ti-camera me-1"></i> Change Photo</a>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="d-inline-flex align-items-center">
                                                <i class="ti ti-id me-2"></i>
                                                User ID
                                            </span>
                                            <p class="text-dark">{{ $user->user_id ?? '' }}</p>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="d-inline-flex align-items-center">
                                                <i class="ti ti-calendar-check me-2"></i>
                                                Added on
                                            </span>
                                            <p class="text-dark">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('jS F Y') ?? 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="text-start">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="fw-bold mt-4">About</h5>
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_bio_{{ $user->user_id }}"><i
                                                        class="ti ti-edit"></i></a>
                                            </div>
                                            <p class="text-muted">
                                                {{ $userInfo->bio ?? 'Passionate professional with extensive experience in their field, dedicated to excellence.' }}
                                            </p>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="fw-bold">Skills</h5>
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_skills_{{ $user->user_id }}"><i
                                                        class="ti ti-edit"></i></a>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                 @if (empty($userInfo->skills))
                                                    <p>No skills added yet</p>
                                                @else
                                                    @foreach (explode(',', $userInfo->skills ?? '') as $skill)
                                                        <span
                                                            class="badge badge-soft-dark rounded-pill fw-medium">{{ trim($skill) }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                 @php
                                            $addressParts = array_filter([
                                                $userInfo->address_line1 ?? null,
                                                $userInfo->address_line2 ?? null,
                                                $userInfo->city ?? null,
                                                $userInfo->state ?? null,
                                                $userInfo->postal_code ?? null,
                                            ]);
                                            $socialUrls = json_decode($userInfo->social_links ?? '[]', true);
                                            $educationDetails = json_decode($userInfo->education ?? '[]', true);
                                            $experience = json_decode($userInfo->experience ?? '[]', true);
                                            $emergencyInfo = json_decode($userInfo->emergency_info ?? '[]', true);
                                            $bankDetails = json_decode($userInfo->bank_info ?? '[]', true);
                                        @endphp
                                        @if (!empty($addressParts))
                                            <p class="text-dark text-end">
                                                {{ implode(', ', $addressParts) ?? 'Not Mentioned' }}
                                            </p>
                                        @endif
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6>Social Links</h6>
                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                        data-token="@skeletonToken('open_um')_e_user_sociallinks_{{ $user->user_id }}"><i
                                            class="ti ti-edit"></i></a>
                                </div>
                                @php
                                    $hasSocialLinks = collect($socialUrls)->filter()->isNotEmpty();
                                @endphp
                                <div class="d-flex align-items-center justify-content-center">
                                    @if ($hasSocialLinks)
                                        @foreach ($socialUrls as $platform => $url)
                                            @php
                                                $platform = strtolower($platform);
                                            @endphp
                                            @if (!empty($url))
                                                <a href="{{ $url }}"
                                                    class="avatar avatar-md me-2 avatar-rounded" target="_blank">
                                                    <img src="{{ asset('social/' . ($platform === 'portfolio' ? 'world' : $platform) . '.svg') }}"
                                                        alt="{{ $platform }}">
                                                </a>
                                            @endif
                                        @endforeach
                                    @else
                                        <p class="text-muted mb-0">No social links provided.</p>
                                    @endif
                                </div>
                                <div class="text-start">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold mt-4">Emergency Contact Number</h6>
                                        <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                            data-token="@skeletonToken('open_um')_e_user_emergency_{{ $user->user_id }}"><i
                                                class="ti ti-edit"></i></a>
                                    </div>
                                    @if (!empty($emergencyInfo) && is_array($emergencyInfo))
                                        @foreach ($emergencyInfo as $index => $contact)
                                            <div
                                                class="p-2 {{ $index !== array_key_last($emergencyInfo) ? 'border-bottom' : '' }}">
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
                    <div class="col-xl-8 sf-12">
                        <div class="row g-3">
                            <!-- Basic Information Card -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 transition h-100">
                                    <div class="card-body">
                                        <div class="row align-items-center mb-2">
                                            <div class="col">
                                                <h4 class="fw-bold mb-0">Basic Information</h4>
                                            </div>
                                            <div class="col-auto">
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_basicinfo_{{ $user->user_id }}">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <hr>
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
                                                <a href="mailto:{{ $user->alt_email ?? 'N/A' }}"
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
                                <div class="card border-0 shadow-sm rounded-3 transition h-100">
                                    <div class="card-body">
                                        <div class="row align-items-center mb-2">
                                            <div class="col">
                                                <h4 class="fw-bold mb-0">Address Information</h4>
                                            </div>
                                            <div class="col-auto">
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_address_{{ $user->user_id }}">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <hr>
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
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-3 transition h-100">
                                    <div class="card-body">
                                        <div class="row align-items-center mb-2">
                                            <div class="col">
                                                <h4 class="fw-bold mb-0">Bank Account Details</h4>
                                            </div>
                                            <div class="col-auto">
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_bankedit_{{ $user->user_id }}">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="text-start">
                                            @if (!empty($bankDetails) && is_array($bankDetails))
                                                {{-- Table Heading --}}
                                                <div class="row text-muted fw-bold border-bottom py-2 small">
                                                    <div class="col-md-3">Bank Name</div>
                                                    <div class="col-md-3">Account Number</div>
                                                    <div class="col-md-3">IFSC Code</div>
                                                    <div class="col-md-3">Branch</div>
                                                </div>
                                                {{-- Bank Data --}}
                                                @foreach ($bankDetails as $bank)
                                                    <div class="row border-bottom py-3 align-items-center">
                                                        <div class="col-md-3">
                                                            <h6 class="fw-medium mb-0">
                                                                {{ $bank['bank_name'] ?? 'N/A' }}</h6>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <h6 class="fw-medium mb-0">
                                                                {{ $bank['account_number'] ?? 'N/A' }}</h6>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <h6 class="fw-medium mb-0">
                                                                {{ $bank['ifsc_code'] ?? 'N/A' }}</h6>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <h6 class="fw-medium mb-0">
                                                                {{ $bank['branch'] ?? 'N/A' }},
                                                                {{ $bank['city'] ?? '' }}</h6>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                                    style="height:calc(100vh - 200px) !important;">
                                                    <img src="{{ asset('errors/empty.svg') }}" alt="No Bank Accounts"
                                                        class="img-fluid mb-2 w-25">
                                                    <h1 class="h4 mb-2 fw-bold">No Bank Accounts Added</h1>
                                                    <p class="text-muted mb-2" style="max-width: 300px;">
                                                        You haven't added any bank account information yet. To
                                                        receive payments or store
                                                        financial details, you need to add at least one account.
                                                    </p>
                                                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                                        <a href="#"
                                                            class="btn btn-primary btn-sm skeleton-popup transition"
                                                            data-token="@skeletonToken('open_um')_e_user_bankadd_{{ $user->user_id }}">
                                                            <i class="ti ti-plus me-1"></i> Add Bank Account
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 transition h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h4 class="fw-bold">Education</h4>
                                            <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                data-token="@skeletonToken('open_um')_e_user_educationadd_{{ $user->user_id }}">
                                                <i class="ti ti-copy-plus me-1"></i> Add
                                            </a>
                                            @if (!empty($educationDetails) && is_array($educationDetails))
                                                <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_educationedit_{{ $user->user_id }}">
                                                    <i class="ti ti-edit me-1"></i> Edit
                                                </a>
                                            @endif
                                        </div>
                                        <hr>
                                        @if (!empty($educationDetails) && is_array($educationDetails))
                                            @foreach ($educationDetails as $index => $education)
                                                <div class="mb-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <span class="d-inline-flex align-items-center fw-normal">
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
                                                <img src="{{ asset('errors/empty.svg') }}" alt="No Education Details"
                                                    class="img-fluid mb-2 w-25">
                                                <h6 class="mb-2 fw-bold">No education details</h6>
                                                <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                                    <a href="#"
                                                        class="btn btn-primary btn-sm skeleton-popup transition"
                                                        data-token="@skeletonToken('open_um')_e_user_educationadd_{{ $user->user_id }}">
                                                        <i class="ti ti-plus me-1"></i> Add Education
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 transition h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h4 class="fw-bold">Experience</h4>
                                            <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                data-token="@skeletonToken('open_um')_e_user_experienceadd_{{ $user->user_id }}">
                                                <i class="ti ti-copy-plus me-1"></i> Add
                                            </a>
                                            @if (!empty($experience) && is_array($experience))
                                                <a href="javascript:void(0);" class="btn btn-sm skeleton-popup"
                                                    data-token="@skeletonToken('open_um')_e_user_experienceedit_{{ $user->user_id }}">
                                                    <i class="ti ti-edit me-1"></i> Edit
                                                </a>
                                            @endif
                                        </div>
                                        <hr>
                                        <div>
                                            @if (!empty($experience) && is_array($experience))
                                                @foreach ($experience as $item)
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div>
                                                                <h6 class="d-inline-flex align-items-center fw-medium">
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
                                                    <img src="{{ asset('errors/empty.svg') }}" alt="No Work Experience"
                                                        class="img-fluid mb-2 w-25">
                                                    <h6 class="mb-2 fw-bold">No Work Experience Added</h6>
                                                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                                                        <a href="#"
                                                            class="btn btn-primary btn-sm skeleton-popup transition"
                                                            data-token="@skeletonToken('open_um')_e_user_experienceadd_{{ $user->user_id }}">
                                                            <i class="ti ti-plus me-1"></i> Add Work Experience
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card border-0 shadow-sm rounded-3 transition">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-4 border-bottom pb-3">Account Security</h5>
                                        <p class="text-muted small mb-4">
                                            <i class="ti ti-lock me-1"></i> Manage User password, and
                                            other security settings to protect account.
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
                                                                <i class="ti ti-alert-circle me-1"></i>
                                                                {{ $passwordWarning }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="col-12 col-md-auto mt-3 mt-md-0 text-end">
                                                        <a href="#"
                                                            class="btn btn-primary btn-sm transition shadow-sm skeleton-popup"
                                                            data-token="@skeletonToken('open_um')_e_user_changePassword_{{ $user->user_id }}">
                                                            <i class="ti ti-key me-1"></i> Change Password
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="fw-semibold text-dark mb-1">Delete Account</h6>
                                                        <p class="text-muted small mb-0">Permanently delete account and
                                                            data.
                                                        </p>
                                                        <p class="text-danger small mt-1"><i
                                                                class="ti ti-alert-circle me-1"></i>
                                                            This action is irreversible.</p>
                                                    </div>
                                                    <a href="#"
                                                        class="btn btn-danger btn-sm transition shadow-sm skeleton-popup"
                                                        data-token="@skeletonToken('open_um')_e_user_deleteaccount_{{ $user->user_id }}">
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
                    {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************ --}}
                </div>
            @endif
        </div>
    @endsection
