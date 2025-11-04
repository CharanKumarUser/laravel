@extends('layouts.empty-app')
@section('title', 'Got It :: All Plans')
@section('content')
<main class="empty-main">
    <!-- Hero Section -->
    <section class="plan-hero" data-aos="fade-up" data-aos-delay="100">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Explore Our Plans</h1>
            <p class="lead mb-4">Choose the right plan for your business growth in India. All plans include Advanced Attendance Tracking (with Biometric Integration) and device configuration.</p>
            <a href="#plan-grid" class="btn action-btn">See Plans Below</a>
            <!-- Abstract Background Elements -->
            <div class="shape shape-1">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M47.1,-57.1C59.9,-45.6,68.5,-28.9,71.4,-10.9C74.2,7.1,71.3,26.3,61.5,41.1C51.7,55.9,35,66.2,16.9,69.2C-1.3,72.2,-21,67.8,-36.9,57.9C-52.8,48,-64.9,32.6,-69.1,15.1C-73.3,-2.4,-69.5,-22,-59.4,-37.1C-49.3,-52.2,-32.8,-62.9,-15.7,-64.9C1.5,-67,34.3,-68.5,47.1,-57.1Z"
                        transform="translate(100 100)" fill="#ffffff"></path>
                </svg>
            </div>
        </div>
    </section>

    <!-- Plans Grid Section -->
    <section id="plan-grid" class="pricing section light-background">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            @if($data['status'] && $data['plans'] && $data['plans']->isNotEmpty())
                <div class="d-flex flex-wrap justify-content-center gap-4">
                    @foreach($data['plans'] as $index => $plan)
                        @php
                            $durationLabel = 'N/A';
                            $durationJson = json_decode($plan->duration, true);
                            if (is_array($durationJson)) {
                                $unit = array_key_first($durationJson);
                                $durationLabel = $durationJson[$unit] . ' ' . ucfirst($unit);
                            }
                            $descHtml = json_decode($plan->description, true)['content'] ?? 'No description available';
                            $featureHtml = json_decode($plan->features, true)['content'] ?? 'No features listed';
                            $tax = json_decode($plan->tax, true) ?? ['gst' => '18'];
                            $taxDetails = [];
                            $totalTaxRate = 0;
                            $totalTaxAmount = 0;

                            // Handle different tax structures
                            if (isset($tax['gst'])) {
                                $totalTaxRate = (float)$tax['gst'];
                                $totalTaxAmount = ($plan->amount * $totalTaxRate) / 100;
                                $taxDetails[] = ['name' => 'GST', 'rate' => $totalTaxRate];
                            } elseif (isset($tax['cgst']) && isset($tax['sgst'])) {
                                $cgstRate = (float)$tax['cgst'];
                                $sgstRate = (float)$tax['sgst'];
                                $totalTaxRate = $cgstRate + $sgstRate;
                                $totalTaxAmount = ($plan->amount * $totalTaxRate) / 100;
                                $taxDetails[] = ['name' => 'CGST', 'rate' => $cgstRate];
                                $taxDetails[] = ['name' => 'SGST', 'rate' => $sgstRate];
                            } elseif (isset($tax['igst'])) {
                                $totalTaxRate = (float)$tax['igst'];
                                $totalTaxAmount = ($plan->amount * $totalTaxRate) / 100;
                                $taxDetails[] = ['name' => 'IGST', 'rate' => $totalTaxRate];
                            }

                            $totalAmount = $plan->amount + $totalTaxAmount - ($plan->discount > 0 ? ($plan->amount * $plan->discount / 100) : 0);
                            $onboarding_id = $data['onboarding_id'] ?? \Illuminate\Support\Str::random(30);
                        @endphp
                        <div class="pricing-card {{ $index === 1 ? 'popular active' : '' }}" data-aos="fade-up" data-aos-delay="{{ 100 * ($index + 1) }}">
                            <!-- Icon -->
                            <div class="d-flex justify-content-center">
                                <div class="pricing-icon">
                                    <i class="fa-solid {{ $plan->icon ?? '' }}"></i>
                                </div>
                            </div>
                            <!-- Badge -->
                            @if($index === 1)
                                <div class="popular-badge">Most Popular</div>
                            @endif
                            <!-- Plan Info -->
                            <h3>{{ ucfirst($plan->name) }}</h3>
                            <div class="price">
                                <span class="currency">₹</span>
                                <span class="amount">{{ number_format($totalAmount) }}</span>
                                <span class="period">/ {{ $durationLabel }}</span>
                            </div>
                            <div class="tax-note small text-dark mb-4">
                                Inclusive of
                                @foreach($taxDetails as $tax)
                                    {{ $tax['name'] }} ({{ $tax['rate'] }}%)@if(!$loop->last), @endif
                                @endforeach
                            </div>
                            <!-- Description & Features -->
                            <div class="sf-10 mb-2 text-container">{!! $descHtml !!}</div>
                            <div class="sf-10 text-container">{!! $featureHtml !!}</div>
                            <a href="{{ url('/') }}/g/plans/{{ $plan->plan_id }}/{{ \Illuminate\Support\Str::kebab($plan->name) }}" data-loading-text="Processing..." type="submit" class="btn btn-light mt-3">Buy Now <i class="bi bi-arrow-right"></i></a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center rounded p-4" data-aos="fade-up" data-aos-delay="200">
                    <h4 class="alert-heading fw-bold mb-3">No Plans Available</h4>
                    <p class="mb-4">We currently have no fixed plans to display. Contact our support team for assistance.</p>
                    <a href="#support-section" class="btn btn-primary btn-lg">Contact Support</a>
                </div>
            @endif
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-9 faq section light-background" id="faq">
        <div class="container">
            <div class="row">
                <div class="col-lg-5" data-aos="fade-up">
                    <h2 class="faq-title">Have a Question? Check out the FAQ</h2>
                    <p class="faq-description">Got questions about our plans? Find answers below or reach out to us for more information on how to revolutionize your HR processes in India with Got-It!</p>
                    <div class="faq-arrow d-none d-lg-block" data-aos="fade-up" data-aos-delay="200">
                        <svg class="faq-arrow" width="200" height="211" viewBox="0 0 200 211" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M198.804 194.488C189.279 189.596 179.529 185.52 169.407 182.07L169.384 182.049C169.227 181.994 169.07 181.939 168.912 181.884C166.669 181.139 165.906 184.546 167.669 185.615C174.053 189.473 182.761 191.837 189.146 195.695C156.603 195.912 119.781 196.591 91.266 179.049C62.5221 161.368 48.1094 130.695 56.934 98.891C84.5539 98.7247 112.556 84.0176 129.508 62.667C136.396 53.9724 146.193 35.1448 129.773 30.2717C114.292 25.6624 93.7109 41.8875 83.1971 51.3147C70.1109 63.039 59.63 78.433 54.2039 95.0087C52.1221 94.9842 50.0776 94.8683 48.0703 94.6608C30.1803 92.8027 11.2197 83.6338 5.44902 65.1074C-1.88449 41.5699 14.4994 19.0183 27.9202 1.56641C28.6411 0.625793 27.2862 -0.561638 26.5419 0.358501C13.4588 16.4098 -0.221091 34.5242 0.896608 56.5659C1.8218 74.6941 14.221 87.9401 30.4121 94.2058C37.7076 97.0203 45.3454 98.5003 53.0334 98.8449C47.8679 117.532 49.2961 137.487 60.7729 155.283C87.7615 197.081 139.616 201.147 184.786 201.155L174.332 206.827C172.119 208.033 174.345 211.287 176.537 210.105C182.06 207.125 187.582 204.122 193.084 201.144C193.346 201.147 195.161 199.887 195.423 199.868C197.08 198.548 193.084 201.144 195.528 199.81C196.688 199.192 197.846 198.552 199.006 197.935C200.397 197.167 200.007 195.087 198.804 194.488ZM60.8213 88.0427C67.6894 72.648 78.8538 59.1566 92.1207 49.0388C98.8475 43.9065 106.334 39.2953 114.188 36.1439C117.295 34.8947 120.798 33.6609 124.168 33.635C134.365 33.5511 136.354 42.9911 132.638 51.031C120.47 77.4222 86.8639 93.9837 58.0983 94.9666C58.8971 92.6666 59.783 90.3603 60.8213 88.0427Z"
                                fill="currentColor"></path>
                        </svg>
                    </div>
                </div>
                <div class="col-lg-7" data-aos="fade-up" data-aos-delay="300">
                    <div class="faq-container">
                        @if(!empty($data['faqs']))
                            @foreach($data['faqs'] as $index => $faq)
                                <div class="faq-item {{ $index === 0 ? 'faq-active' : '' }}">
                                    <h3>{{ $faq->question }}</h3>
                                    <div class="faq-content">
                                        <p>{!! $faq->answer !!}</p>
                                    </div>
                                    <i class="faq-toggle bi bi-chevron-right"></i>
                                </div>
                            @endforeach
                        @else
                            <div class="faq-item faq-active">
                                <h3>What types of plans does Got-It offer?</h3>
                                <div class="faq-content">
                                    <p>Got-It offers fixed plans tailored for Indian businesses, including features like biometric attendance, payroll processing, leave management, and compliance with GST, CGST, SGST, or IGST.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                            <div class="faq-item">
                                <h3>How do Got-It plans handle Indian tax compliance?</h3>
                                <div class="faq-content">
                                    <p>Our plans automatically calculate applicable taxes (GST, CGST/SGST, or IGST) based on your business location, ensuring compliance with Indian tax regulations.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                            <div class="faq-item">
                                <h3>Can I switch between Got-It plans?</h3>
                                <div class="faq-content">
                                    <p>Yes, you can upgrade or downgrade your plan at any time by contacting our support team, with billing adjustments reflecting Indian tax laws.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                            <div class="faq-item">
                                <h3>Are Got-It plans suitable for multi-state businesses?</h3>
                                <div class="faq-content">
                                    <p>Absolutely, our plans support multi-state operations with centralized payroll, geo-fencing, and compliance with state-specific tax rates in India.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                            <div class="faq-item">
                                <h3>How secure are Got-It plans for my business data?</h3>
                                <div class="faq-content">
                                    <p>All plans use AES-256 encryption, two-factor authentication, and comply with Indian data protection regulations to ensure your data is secure.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                            <div class="faq-item">
                                <h3>Can employees access HR data with Got-It plans?</h3>
                                <div class="faq-content">
                                    <p>Yes, employees can access attendance, payslips, and leave details through a secure employee portal, enhancing transparency and compliance.</p>
                                </div>
                                <i class="faq-toggle bi bi-chevron-right"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support and Query Section -->
    <section id="support-section" class="support-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h2 class="section-title text-white">Need Help Choosing?</h2>
                    <p class="text-white mb-3">Our support team is ready to guide you to the perfect HR plan for your business in India!</p>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone-fill me-3"></i>
                        <p class="mb-0"><strong>Call:</strong> <a href="tel:+919030990395" class="text-white">+91 90309 90395</a></p>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-envelope-fill me-3"></i>
                        <p class="mb-0"><strong>Email:</strong> <a href="mailto:info@gotit4all.com" class="text-white">info@gotit4all.com</a></p>
                    </div>
                    <p class="engage-text text-white">Let us help you find the ideal plan to streamline your HR processes with Got-It.</p>
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title text-white">Ask Us Anything</h2>
                    @php
                        $formSkeletonToken = \App\Facades\Skeleton::skeletonToken('lander_landing_requests') . '_f_plans_query';
                        $formAction = url('/lander-action') . '/' . $formSkeletonToken;
                    @endphp
                    <form method="POST" action="{{ $formAction }}" class="landing-form">
                        @csrf
                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required pattern="^[A-Za-z\s]{3,50}$" title="Only letters and spaces, 3-50 characters.">
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Your Email" required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Enter a valid email address.">
                            </div>
                            <div class="col-12">
                                <textarea name="message" class="form-control" rows="4" placeholder="Your Question About Our Plans" required pattern=".{10,500}" title="Message should be 10 to 500 characters."></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="action-btn text-muted">Send Query</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Abstract Background Elements -->
            <div class="shape shape-1">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M41.3,-49.1C54.4,-39.3,66.6,-27.2,71.1,-12.1C75.6,3,72.4,20.9,63.3,34.4C54.2,47.9,39.2,56.9,23.2,62.3C7.1,67.7,-10,69.4,-24.8,64.1C-39.7,58.8,-52.3,46.5,-60.1,31.5C-67.9,16.4,-70.9,-1.4,-66.3,-16.6C-61.8,-31.8,-49.7,-44.3,-36.3,-54C-22.9,-63.7,-8.2,-70.6,3.6,-75.1C15.4,-79.6,28.2,-58.9,41.3,-49.1Z"
                        transform="translate(100 100)" fill="#ffffff"></path>
                </svg>
            </div>
        </div>
    </section>
</main>
@endsection