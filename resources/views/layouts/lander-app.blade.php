@section('title', 'Gotit | Biometric HR Management Software | Attendance, Payroll, Leave Tracking')
<!--
  /$$$$$$   /$$$$$$  /$$$$$$$$       /$$$$$$ /$$$$$$$$
 /$$__  $$ /$$__  $$|__  $$__/      |_  $$_/|__  $$__/
| $$  \__/| $$  \ $$   | $$           | $$     | $$
| $$ /$$$$| $$  | $$   | $$           | $$     | $$
| $$|_  $$| $$  | $$   | $$           | $$     | $$
| $$  \ $$| $$  | $$   | $$           | $$     | $$
|  $$$$$$/|  $$$$$$/   | $$          /$$$$$$   | $$
 \______/  \______/    |__/         |______/   |__/
-->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default"
    data-template="gotit">
<head>
    <!-- Meta Essentials -->
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="modal" content="lander">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Got-It HR Solutions">
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
    <meta name="twitter:image" content="{{ asset('treasury/company/favicon/favicon.png') }}">
    <!-- Fonts and Favicon -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('treasury/company/favicon/favicon.svg') }}" type="image/x-icon">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/lander.css'])
    @endif
    <!-- Dynamic Top Scripts -->
    @yield('top-script')
    <!-- Dynamic Top Styles -->
    @yield('top-style')
</head>
<body class="index-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div
            class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="logo d-flex align-items-center me-auto me-xl-0">
                <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="">
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="{{ url('/') }}#Got-It">Home</a></li>
                    <li><a href="{{ url('/') }}#about">About Us</a></li>
                    <li><a href="{{ url('/') }}#features">Features</a></li>
                    <li><a href="{{ url('/') }}#services">Services</a></li>
                    <li><a href="{{ url('/') }}#pricing">Pricing</a></li>
                    <li><a href="{{ url('/') }}#contact">Contact</a></li>
                    <li><a href="{{ url('/help') }}" class="@yield('help_section')">Help</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-three-dots-vertical"></i>
            </nav>
            <a class="login-nav-btn" href="{{ url('/login') }}"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
        </div>
    </header>
    @yield('content')
    <footer id="footer" class="footer">
        <div class="container footer-top">
            <div class="row gy-4">
                <!-- Logo Section -->
                <div class="col-lg-4 col-md-6 footer-about">
                    <a href="{{ url('/') }}" class="logo d-flex align-items-center">
                        <img src="{{ asset('treasury/company/logo/logo.svg') }}" alt="Got-It Logo" class="img-fluid">
                    </a>
                    <p>Manage employee data, track attendance, performance, payroll, and leave efficiently with our
                        comprehensive employee management system.</p>
                </div>
                <!-- Useful Links Section -->
                <div class="col-lg-2 col-md-6 footer-links ps-4">
                    <h4>Useful Links</h4>
                    <ul>
                        <li><a href="{{ url('/') }}#"><i class="fa-solid fa-angle-right mx-2"></i>Home</a></li>
                        <li><a href="{{ url('/') }}#about"><i class="fa-solid fa-angle-right mx-2"></i>About
                                Us</a></li>
                        <li><a href="{{ url('/') }}#features"><i
                                    class="fa-solid fa-angle-right mx-2"></i>Features</a></li>
                        <li><a href="{{ url('/') }}#services"><i
                                    class="fa-solid fa-angle-right mx-2"></i>Services</a></li>
                        <li><a href="{{ url('/') }}#pricing"><i
                                    class="fa-solid fa-angle-right mx-2"></i>Pricing</a></li>
                    </ul>
                </div>
                <!-- Legal Section -->
                <div class="col-lg-3 col-md-6 footer-links ps-4">
                    <h4>Legal</h4>
                    <ul>
                        @php
                            $legalResponse = \App\Http\Helpers\SupremeHelper::fetch('PPG', [
                                'where' => ['type' => 'OPTNJVYD'],
                                'where' => ['dkp_id' => env('SUPREME_PRODUCT_ID')],
                            ]);
                            // Check if response is a JsonResponse and convert it to an array
                            if ($legalResponse instanceof \Illuminate\Http\JsonResponse) {
                                $legal = $legalResponse->getData(true); // Convert to array
                            } else {
                                $legal = $legalResponse; // Assume it's already an array
                            }
                        @endphp
                        @if (isset($legal['data']) && is_array($legal['data']))
                            @foreach ($legal['data'] as $data)
                                <li>
                                    <a href="{{ route('dyn_legal_page.' . $data['page_id']) }}">
                                        <i class="fa-solid fa-angle-right mx-2"></i>
                                        {{ ucfirst($data['title']) ?? 'NULL' }}
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><i class="fa-solid fa-angle-right mx-2"></i> No legal pages available</li>
                        @endif
                    </ul>
                </div>
                <!-- Social Links Section -->
                <div class="col-lg-3 col-md-6 footer-social">
                    <h4 class="mb-0">Follow Us</h4>
                    <div class="social-links d-flex">
                        <a href="https://www.linkedin.com/company/digital-kuppam/posts/?feedView=all"
                            target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://x.com/i/flow/login?redirect_after_login=%2Fdigitalkuppam" target="_blank"><i
                                class="fab fa-x-twitter"></i></a>
                        <a href="https://www.facebook.com/digitalkpn.kuppam.7/" target="_blank"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/digital_kuppam/" target="_blank"><i
                                class="fab fa-instagram"></i></a>
                    </div>
                    <div class="footer-data">
                        <h4>Open Hours</h4>
                        <ul>
                            <li>Monday - Friday: 9:00 AM - 05:30 PM</li>
                            <li>Saturday: 9:00 AM - 12:30 PM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright Section -->
        <div class="container-fluid copyright text-center bg-light">
            <p>© <span>Copyright</span> 2025. All rights reserved. Maintained by<strong class="px-1 sitename"><a
                        href="https://digitalkuppam.com/">Digital Kuppam</a></strong> </p>
        </div>
    </footer>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Skeleton Pack JS -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/lander.js'])
    @endif
    <!-- Dynamic Bottom Scripts -->
    @yield('bottom-script')
    <!-- Dynamic Bottom Styles -->
    @yield('bottom-style')
</body>
</html>
