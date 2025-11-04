@section('title', 'Gotit | Biometric HR Management Software | Attendance, Payroll, Leave Tracking')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default"
    data-template="gotit">
<head>
    <!-- Meta Essentials -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Got-It HR Solutions">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="modal" content="system">
    <!-- SEO Meta Tags -->
    <title>@yield('title')</title>
    <meta name="description"
        content="Got-It HR software is your go-to solution for biometric attendance, payroll management, leave tracking, and workforce management. Simplify HR processes and boost efficiency today!">
    <meta name="keywords"
        content="HR management software, biometric attendance system, payroll management software, leave management, HR solutions, workforce management, employee management system, HR automation, attendance tracking, payroll automation, biometric HR, Got-It HR software, HR analytics, employee performance tracking, time tracking software, leave tracking software, workforce efficiency, HR technology, HR payroll, employee attendance system">
    <!-- Geo Location -->
    <meta name="geo.placename" content="Hyderabad, Bengaluru, Chennai">
    <meta name="geo.position" content="17.385044, 78.486671">
    <link rel="canonical" href="{{ url('/') }}">
    <!-- Open Graph for Social Media -->
    <meta property="og:title" content="Biometric HR Management Software | Attendance, Payroll, Leave Tracking | Got-It">
    <meta property="og:description"
        content="Discover Got-It HR software: biometric attendance, payroll management, leave tracking, and workforce optimization. Simplify HR processes for your business.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('treasury/company/favicon/favicon.png') }}">
    <meta property="og:type" content="website">
    <!-- Twitter Meta -->
    <meta name="twitter:title" content="Biometric HR Software | Attendance, Payroll, Leave Tracking | Got-It">
    <meta name="twitter:description"
        content="Streamline HR management with Got-It HR software. Manage attendance, payroll, leave tracking, and workforce performance seamlessly.">
    <meta name="user-name" content="{{ $authUser ? (strlen($authUser->first_name) < 3 ? $authUser->last_name : $authUser->first_name) : '' }}">
    <meta name="user-id" content="{{ $authUser ? $authUser->user_id : '' }}">
    <meta name="business-id" content="{{ $authUser ? $authUser->business_id : '' }}">
    <meta name="company-id" content="{{ $authUser ? $authUser->company_id : '' }}">
    <meta name="scope-id" content="{{ $authUser ? $authUser->scope_id : '' }}">
    <meta name="twitter:image" content="{{ asset('treasury/company/favicon/favicon.png') }}">
    <!-- Fonts and Favicon -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('treasury/company/favicon/favicon.svg') }}" type="image/x-icon">
    <!-- Skeleton Pack CSS -->
    @if ((auth()->check() && file_exists(public_path('build/manifest.json'))) || file_exists(public_path('hot')))
        @vite(['resources/css/system.css'])
    @else
        @vite(['resources/css/lander.css'])
    @endif
    <!-- Dynamic Top Styles -->
    @stack('styles')
    <!-- Dynamic Pre-Scripts (scripts that must be in <head>) -->
    @stack('pre-scripts')
</head>
<body>
    {{-- <div id="global-loader">
        <div class="page-loader"></div>
    </div> --}}
    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="main-header">
                <div class="header-left">
                    <a href="{{ url('/dashboard') }}" class="logo">
                        <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="Logo">
                    </a>
                    <a href="{{ url('/dashboard') }}" class="dark-logo">
                        <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="Logo">
                    </a>
                </div>
                <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                    <span class="bar-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </a>
                <div class="header-user">
                    <div class="nav user-menu nav-list">
                        <div class="me-auto d-flex align-items-center" id="header-search">
                            <a id="toggle_btn" href="javascript:void(0);" class="btn btn-menubar me-1">
                                <i class="ti ti-arrow-bar-to-left"></i>
                            </a>
                            <!-- Search -->
                            <div class="skeleton-search-container position-relative">
                                <i class="ti ti-search"></i>
                                <input type="text" id="skeleton-search-input"
                                    placeholder="Search news, users, departments, companies..." autocomplete="off">
                                <kbd>CTRL + /</kbd>
                                <div class="skeleton-search-dropdown" id="skeleton-search-dropdown">
                                    <!-- Recent Searches -->
                                    <div class="skeleton-search-dropdown-section" id="skeleton-search-recent-section">
                                        <div class="skeleton-search-dropdown-section-title">
                                            Recent Searches <span class="skeleton-search-clear-btn">Clear</span>
                                        </div>
                                        <div class="skeleton-search-recent-searches"
                                            id="skeleton-search-recent-searches"></div>
                                    </div>
                                    <!-- Suggestions -->
                                    <div class="skeleton-search-dropdown-section" id="skeleton-search-suggestions">
                                    </div>
                                    <!-- Quick Filters -->
                                    <div class="skeleton-search-dropdown-section">
                                        <div class="skeleton-search-dropdown-section-title">Quick Filters</div>
                                        <div class="skeleton-search-recent-searches">
                                            <span class="skeleton-search-recent-pill" data-type="users"
                                                data-bs-toggle="tooltip" title="Employees">Employees</span>
                                            <span class="skeleton-search-recent-pill" data-type="scopes"
                                                data-bs-toggle="tooltip" title="Departments">Departments</span>
                                            <span class="skeleton-search-recent-pill" data-type="news"
                                                data-bs-toggle="tooltip" title="News">News</span>
                                            <span class="skeleton-search-recent-pill" data-type="companies"
                                                data-bs-toggle="tooltip" title="Companies">Companies</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Search -->
                            <a href="{{ url('/profile') }}" class="btn btn-menubar">
                                <i class="ti ti-settings-cog"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center">
                            <!-- Reload Skeleton -->
                            <a href="#" class="btn btn-menubar me-1 reload-skeleton" data-bs-toggle="tooltip"
                                title="Reload">
                                <i class="fa ti ti-reload"></i>
                            </a>
                            @php
                                $system = App\Facades\Skeleton::getUserSystem();
                            @endphp
                            @if ($system == 'business')
                                <!-- Geo Face Attendance -->
                                <a href="#" class="btn btn-menubar me-1 skeleton-popup"
                                    data-token="@skeletonToken('business_smart_attendance')_a" data-id='geo-face' data-bs-toggle="tooltip"
                                    title="Mark Attendance">
                                    <i class="fa ti ti-camera-selfie"></i>
                                </a>
                            @endif
                            <!-- Fullscreen -->
                            <a href="#" class="btn btn-menubar btnFullscreen me-1" data-bs-toggle="tooltip"
                                title="Fullscreen">
                                <i class="ti ti-maximize"></i>
                            </a>
                            <!-- Applications Dropdown -->
                            <div class="dropdown me-1">
                                <a href="#" class="btn btn-menubar" data-bs-toggle="dropdown">
                                    <i class="ti ti-layout-grid-remove"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="card mb-0 border-0 shadow-none">
                                        <div class="card-header">
                                            <h4>Applications</h4>
                                        </div>
                                        <div class="card-body">
                                            <a href="#" class="d-block pb-2">
                                                <span class="avatar avatar-md bg-transparent-dark me-2"><i
                                                        class="ti ti-calendar text-gray-9"></i></span>Calendar
                                            </a>
                                            <a href="#" class="d-block py-2">
                                                <span class="avatar avatar-md bg-transparent-dark me-2"><i
                                                        class="ti ti-news text-gray-9"></i></span>News
                                            </a>
                                            <a href="#" class="d-block py-2">
                                                <span class="avatar avatar-md bg-transparent-dark me-2"><i
                                                        class="ti ti-subtask text-gray-9"></i></span>To-Do
                                            </a>
                                            <a href="#" class="d-block pt-2">
                                                <span class="avatar avatar-md bg-transparent-dark me-2"><i
                                                        class="ti ti-notes text-gray-9"></i></span>Notes
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Chat Dropdown -->
                            <div class="dropdown me-1">
                                <a href="#" class="btn btn-menubar position-relative" id="chat_feature_popup"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-brand-hipchat"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-3 text-center"
                                    aria-labelledby="chat_feature_popup" style="min-width: 220px;">
                                    <div class="fs-1 mb-2">🚧</div>
                                    <div class="fw-semibold mb-2">Coming Soon</div>
                                    <small class="text-muted">We're working on something awesome!</small>
                                </div>
                            </div>
                            <!-- Notifications Dropdown -->
                            <div class="dropdown me-1 notification_item">
                                <a href="#" class="btn btn-menubar position-relative notification-toggle"
                                    id="notification_popup" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-bell"></i>
                                    <span
                                        class="notification-count badge bg-danger rounded-pill d-flex align-items-center justify-content-center header-badge animate__animated">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-0 noti-content"
                                    aria-labelledby="notification_popup" style="min-width: 380px;"
                                    data-bs-auto-close="false">
                                    <div
                                        class="d-flex align-items-center justify-content-between border-bottom px-3 py-2">
                                        <h6 class="m-0 notification-title">Notifications (0)</h6>
                                        <button type="button"
                                            class="btn btn-link btn-sm text-decoration-none mark-all-read animate-btn"
                                            disabled>
                                            <i class="ti ti-checks"></i> Mark all as read
                                        </button>
                                    </div>
                                    <div class="notification-list" style="max-height: 360px; overflow-y: auto;">
                                        <div class="notifications-container d-flex flex-column p-1"></div>
                                        <div class="empty-state text-center px-4 py-5 text-muted">
                                            <div class="fs-1 mb-2">📭</div>
                                            <div>Nothing currently available</div>
                                        </div>
                                    </div>
                                    <div class="d-flex p-3 gap-2">
                                        <a href="#"
                                            class="btn btn-light w-100 btn-cancel animate-btn">Cancel</a>
                                        <a href="{{ url('/profile') }}"
                                            class="btn btn-primary w-100 animate-btn">View All</a>
                                    </div>
                                </div>
                            </div>
                            <!-- Profile Dropdown -->
                            <div class="dropdown profile-dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="avatar avatar-sm online">
                                        <img src="{{ $authUser?->profile ? e(app(\App\Services\FileService::class)->getFile($authUser->profile)) : asset('default/profile-avatar.svg') }}"
                                            alt="User Avatar" class="img-fluid rounded-circle">
                                    </span>
                                </a>
                                <div class="dropdown-menu shadow">
                                    <div class="card mb-0">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-lg me-2 avatar-rounded">
                                                    <img src="{{ $authUser?->profile ? e(app(\App\Services\FileService::class)->getFile($authUser->profile)) : asset('default/profile-avatar.svg') }}"
                                                        alt="User Avatar">
                                                </span>
                                                <div>
                                                    <h5 class="mb-0">
                                                        {{ $authUser ? $authUser->first_name . ' ' . ($authUser->last_name ?? '') : 'Guest' }}
                                                    </h5>
                                                    <p class="fs-12 fw-medium mb-0">
                                                        {{ $authUser ? $authUser->email : '' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body p-0 ms-2">
                                            @can('view:Dashboard')
                                                <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                    href="{{ url('/profile') }}"><i class="ti ti-user-circle me-1"></i>My
                                                    Account</a>
                                            @endcan
                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="{{ url('/profile') }}"><i
                                                    class="ti ti-settings me-1"></i>Settings</a>
                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="{{ url('/profile') }}"><i
                                                    class="ti ti-bell me-1"></i>Notifications</a>
                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="{{ url('/profile') }}"><i
                                                    class="ti ti-headset me-1"></i>Contact Support</a>
                                            <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                                href="{{ url('/profile') }}"><i
                                                    class="ti ti-question-mark me-1"></i>Knowledge Base</a>
                                        </div>
                                        <div class="card-footer text-center">
                                            <a class="btn btn-danger w-100 text-white" href="{{ url('logout') }}"><i
                                                    class="ti ti-login me-2"></i>Logout</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Mobile Menu -->
                <div class="dropdown profile-dropdown mobile-user-menu">
                    <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Profile Menu">
                        <span class="avatar avatar-sm online">
                            <img src="{{ $authUser?->profile ? e(app(\App\Services\FileService::class)->getFile($authUser->profile)) : asset('default/profile-avatar.svg') }}"
                                alt="User Avatar" class="img-fluid rounded-circle">
                        </span>
                    </a>
                    <div class="dropdown-menu shadow">
                        <div class="card mb-0">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-lg me-2 avatar-rounded">
                                        <img src="{{ $authUser?->profile ? e(app(\App\Services\FileService::class)->getFile($authUser->profile)) : asset('default/profile-avatar.svg') }}"
                                            alt="User Avatar">
                                    </span>
                                    <div>
                                        <h5 class="mb-0">
                                            {{ $authUser ? $authUser->first_name . ' ' . ($authUser->last_name ?? '') : 'Guest' }}
                                        </h5>
                                        <p class="fs-12 fw-medium mb-0">{{ $authUser ? $authUser->email : '' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body py-0 ms-2">
                                @can('view:Dashboard')
                                    <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                        href="{{ url('/profile') }}">
                                        <i class="ti ti-user-circle me-1"></i>My Account
                                    </a>
                                @endcan
                                <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                    href="{{ url('/profile') }}">
                                    <i class="ti ti-settings me-1"></i>Settings
                                </a>
                                <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                    href="{{ url('/profile') }}">
                                    <i class="ti ti-bell me-1"></i>Notifications
                                </a>
                                <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                    href="{{ url('/profile') }}">
                                    <i class="ti ti-headset me-1"></i>Contact Support
                                </a>
                                <a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
                                    href="{{ url('/profile') }}">
                                    <i class="ti ti-question-mark me-1"></i>Knowledge Base
                                </a>
                            </div>
                            <div class="card-footer text-center">
                                <a class="btn btn-danger w-100 text-white" href="{{ url('logout') }}">
                                    <i class="ti ti-login me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Mobile Menu -->
            </div>
        </div>
        <!-- /Header -->
        <!-- Sidebar -->
        @php
            $sidebar = app(\App\Services\SkeletonService::class)->authUser('navigation');
            $currentUrl = url()->current();
        @endphp
        <div class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="sidebar-logo">
                <a href="{{ url('/dashboard') }}" class="logo logo-normal">
                    <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="Logo">
                </a>
                <a href="{{ url('/dashboard') }}" class="logo-small">
                    <img src="{{ asset('treasury/company/favicon/favicon.svg') }}" alt="Logo">
                </a>
                <a href="{{ url('/dashboard') }}" class="dark-logo">
                    <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="Logo">
                </a>
            </div>
            <!-- /Logo -->
            <div class="sidebar-inner">
                <div id="sidebar-menu" class="sidebar-menu">
                    @if (empty($sidebar))
                        <div class="text-center p-4">
                            <p>You don't have permissions to view the navigations.</p>
                            <a href="{{ route('logout') }}" class="btn btn-primary">Logout</a>
                        </div>
                    @else
                        <ul>
                            @foreach ($sidebar as $module)
                                <li class="menu-title"><span>{{ $module['name'] }}</span></li>
                                <li>
                                    <ul>
                                        @foreach ($module['sections'] as $section)
                                            @php
                                                // Check if section has children
                                                $hasItems = !empty($section['items']);
                                                // Check if any item under this section is active
                                                $hasActiveItem = false;
                                                if ($hasItems) {
                                                    foreach ($section['items'] as $item) {
                                                        if ($currentUrl === url($item['route'])) {
                                                            $hasActiveItem = true;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $hasActiveItem = $currentUrl === url($section['route']);
                                                }
                                            @endphp
                                            @if ($hasItems)
                                                <li class="submenu {{ $hasActiveItem ? 'active' : '' }}">
                                                    <a href="javascript:void(0);"
                                                        class="{{ $hasActiveItem ? 'active subdrop' : '' }}">
                                                        <i class="{{ $section['icon'] ?? 'ti ti-folder' }}"></i>
                                                        <span>{{ $section['name'] }}</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul style="{{ $hasActiveItem ? 'display: block;' : '' }}">
                                                        @foreach ($section['items'] as $item)
                                                            @php
                                                                $isActiveItem = $currentUrl === url($item['route']);
                                                            @endphp
                                                            <li>
                                                                <a href="{{ url($item['route']) }}"
                                                                    class="{{ $isActiveItem ? 'active' : '' }}">
                                                                    {{ $item['name'] }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @else
                                                <li>
                                                    <a href="{{ url($section['route']) }}"
                                                        class="{{ $hasActiveItem ? 'active' : '' }}">
                                                        <i class="{{ $section['icon'] ?? 'ti ti-folder' }}"></i>
                                                        <span>{{ $section['name'] }}</span>
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <!-- /Sidebar -->
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            @yield('content')
            <button class="scope-menu-toggle-btn skeleton-popup"
                data-token="@skeletonToken('open_scopes_toggle_btn')_v_scope_{{ collect(request()->segments())->last() }}"><i
                    class="ti ti-sitemap"></i></button>
            <div class="footer d-sm-flex align-items-center justify-content-between border-top bg-white p-3">
                <p class="mb-0">2020 - {{ date('Y') }} &copy; Got It.</p>
                <p>Designed &amp; Developed By <a href="javascript:void(0);" class="text-primary">Digital Kuppam</a>
                </p>
            </div>
        </div>
        <!-- /Page Wrapper -->
        <!-- QR Scanner Modal -->
        <div class="modal fade p-3" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="qrScannerModalLabel">Scan & Go</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div
                        class="modal-body text-center d-flex justify-content-center flex-column align-items-center pt-0">
                        <!-- Scanner wrapper -->
                        <div id="qr-scanner-wrapper" class="border rounded-3 bg-light position-relative"
                            style="width:300px; height:300px; overflow:hidden;"></div>
                        <!-- Countdown and messages -->
                        <div id="qr-message" class="mt-2 text-muted sf-9"><b class="text-danger">Note : </b>Position the QR code within the frame</div>
                        <!-- Camera toggle -->
                        <button id="toggleCameraBtn" class="btn btn-outline-secondary mt-3 d-none">
                            <i class="ti ti-camera-rotate"></i> Switch Camera
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="smart-bar-bottom">
            <ul class="smart-bar-listWrap">
                <!-- Attendance Log / Clock In-Out -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-clock"></i></i>
                        <span class="smart-bar-text">Logs</span>
                    </a>
                </li>
                <!-- Leave Requests -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-calendar-event"></i></i>
                        <span class="smart-bar-text">Leave</span>
                    </a>
                </li>
                <!-- Geo Face -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-face-id"></i></i>
                        <span class="smart-bar-text">Geo Face</span>
                    </a>
                </li>
                <!-- QR Attendance -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-qrcode"></i></i>
                        <span class="smart-bar-text">Scan & Go</span>
                    </a>
                </li>
                <!-- To Do -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-list-check"></i></i>
                        <span class="smart-bar-text">To Do</span>
                    </a>
                </li>
                <!-- News -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-news"></i></i>
                        <span class="smart-bar-text">News</span>
                    </a>
                </li>
                <!-- Notes -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-notebook"></i></i>
                        <span class="smart-bar-text">Notes</span>
                    </a>
                </li>
                <!-- User / Profile -->
                <li class="smart-bar-list">
                    <a href="javascript:void(0);" data-type="btn">
                        <i class="smart-bar-icon"><i class="ti ti-user-circle"></i></i>
                        <span class="smart-bar-text">Profile</span>
                    </a>
                </li>
                <!-- Indicator -->
                <li class="smart-bar-indicator"></li>
            </ul>
            <!-- Toggle arrow for smart bar -->
            <div class="smart-bar-toggle-arrow"><i class="fa fa-chevron-up"></i></div>
        </div>
    </div>
    <!-- /Main Wrapper -->
    <!-- Skeleton Pack JS -->
    @if ((auth()->check() && file_exists(public_path('build/manifest.json'))) || file_exists(public_path('hot')))
        @vite(['resources/js/system.js', 'resources/js/system/realtime/notifications.js', 'resources/js/system/realtime/search.js', 'resources/js/system/realtime/attendance.js', 'resources/js/system/realtime/smart-bar.js'])
    @else
        @vite(['resources/js/lander.js'])
    @endif
    <!-- Dynamic Bottom Scripts -->
    @stack('scripts')
</body>
</html>
