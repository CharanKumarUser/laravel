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
    <style>
        .typewriter-container {
            text-align: center;
            color: #6c757d;
            font-weight:bold;
            white-space: nowrap;
            overflow: hidden;
            max-width: 100%;
        }
        .cursor {
            display: inline-block;
            animation: blink 0.5s steps(1) infinite;
            color: #00b4af;
            font-weight: bold;
        }
        @keyframes blink {
            0%,
            100% {
                opacity: 1;
            }
            50% {
                opacity: 0;
            }
        }
        @media (max-width: 1201px) {
            .typewriter-container {
                display: none !important;
            }
        }
    </style>
    <!-- Skeleton Pack CSS -->
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
                <div class="typewriter-container"><span id="typewriter-text"></span><span class="cursor">|</span></div>
            </nav>
            <a class="login-nav-btn" href="{{ url('/') }}"><i class="bi bi-house me-2"></i>Home</a>
        </div>
    </header>
    @yield('content')
    <footer id="footer" class="footer">
        <!-- Copyright Section -->
        <div class="container-fluid copyright text-center bg-light">
            <p>© <span>Copyright</span> 2025. All rights reserved. Maintained by<strong class="px-1 sitename"><a
                        href="https://digitalkuppam.com/">Digital Kuppam</a></strong> </p>
        </div>
    </footer>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script>
        const allPhrases = [
            "Effortless Attendance",
            "Simplify Workforce Management",
            "Automated Payroll Processing",
            "Smart Shift Scheduling",
            "Biometric Login Made Easy",
            "Advanced HR Analytics",
            "Real-time Attendance Monitoring",
            "Secure Employee Records",
            "Cloud-based HR Access",
            "GPS Attendance Tracking",
            "Customizable HR Workflows",
            "One Dashboard for All HR",
            "Seamless Leave Requests",
            "Face & Fingerprint Recognition",
            "Payroll Compliance Made Easy",
            "Instant Reports & Insights",
            "Multi-Location HR Management",
            "Employee Self-Service Portals",
            "Quick Onboarding Process",
            "Fully Integrated HR Suite"
        ];
        const getRandomUniquePhrases = (array, count) => {
            const shuffled = [...array].sort(() => 0.5 - Math.random());
            return shuffled.slice(0, count);
        };
        const phrases = getRandomUniquePhrases(allPhrases, 15);
        let i = 0;
        let j = 0;
        let currentPhrase = [];
        let isDeleting = false;
        let isEnd = false;
        const textElement = document.getElementById("typewriter-text");
        function loop() {
            isEnd = false;
            textElement.innerHTML = currentPhrase.join("");
            if (i < phrases.length) {
                if (!isDeleting && j <= phrases[i].length) {
                    currentPhrase.push(phrases[i][j]);
                    j++;
                    textElement.innerHTML = currentPhrase.join("");
                }
                if (isDeleting && j <= phrases[i].length) {
                    currentPhrase.pop();
                    j--;
                    textElement.innerHTML = currentPhrase.join("");
                }
                if (j == phrases[i].length) {
                    isEnd = true;
                    isDeleting = true;
                }
                if (isDeleting && j === 0) {
                    currentPhrase = [];
                    isDeleting = false;
                    i++;
                    if (i === phrases.length) i = 0;
                }
            }
            const speedUp = Math.random() * (40 - 20) + 20;
            const normalSpeed = Math.random() * (80 - 50) + 50;
            const time = isEnd ? 1000 : isDeleting ? speedUp : normalSpeed;
            setTimeout(loop, time);
        }
        loop();
    </script>
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
