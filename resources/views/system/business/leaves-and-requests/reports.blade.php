{{-- Template: Reports Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Reports')
@push('styles')
@endpush
@push('scripts')
    <script>
        window.addEventListener('load', () => {
            if (window.skeleton && typeof window.skeleton.charts === 'function') {
                window.skeleton.charts();
            }
            
            // Download handled via link ?export=pdf
        });
    </script>
@endpush

@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Reports</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/leave-management') }}">Leave Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Reports</a></li>
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
            {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************ --}}
                <div class="card-body">
                    
                    <div class="tab-content mt-2 pt-2 border-top">
                        @if(isset($data['companies']) && count($data['companies']) > 0)
                            @foreach($data['companies'] as $index => $company)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                                     id="company-{{ $company['company_id'] }}" 
                                     role="tabpanel" 
                                     aria-labelledby="company-{{ $company['company_id'] }}-tab">
                                    <div class="container-fluid py-3">
                                        <!-- KPI Row -->
                                        <div class="row g-3">
                                            <div class="col-md col-6">
                                                <div class="card shadow-sm border-0 text-black text-center">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1 text-muted small">Total Leave Requests</h6>
                                                                <h4 class="mb-0 fw-bold" id="lmTotalRequests-{{ $company['company_id'] }}"
                                                                    style="color: #1a9bb6; font-size: 2rem;">
                                                                    {{ $company['total_requests'] ?? 0 }}</h4>
                                                            </div>
                                                            <div class="bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-database" style="color: #06f23c; font-size: 2rem;"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md col-6">
                                                <div class="card shadow-sm border-0 text-white text-center">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="mb-1 text-muted small">Approved</div>
                                                                <h3 class="mb-0 fw-bold" id="lmApproved-{{ $company['company_id'] }}" style="color: #1a9bb6; font-size: 2rem;">
                                                                    {{ $company['status_counts']['approved'] ?? 0 }}</h3>
                                                            </div>
                                                            <div class="bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-user-check" style="color: #198754; font-size: 2rem;"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md col-6">
                                                <div class="card shadow-sm border-0 text-white text-center">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="mb-1 text-muted small">Pending</div>
                                                                <h3 class="mb-0 fw-bold" id="lmPending-{{ $company['company_id'] }}" style="color: #1a9bb6; font-size: 2rem;">
                                                                    {{ $company['status_counts']['pending'] ?? 0 }}</h3>
                                                            </div>
                                                            <div class="bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-user-clock" style="color: #ffc107; font-size: 2rem;"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md col-6">
                                                <div class="card shadow-sm border-0 text-white text-center">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="mb-1 text-muted small">Rejected</div>
                                                                <h3 class="mb-0 fw-bold" id="lmRejected-{{ $company['company_id'] }}" style="color: #1a9bb6; font-size: 2rem;">
                                                                    {{ $company['status_counts']['rejected'] ?? 0 }}</h3>
                                                            </div>
                                                            <div class="bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-user-times" style="color: #dc3545; font-size: 2rem;"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Charts Row -->
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-md-6">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Leave Distribution by Type</div>
                                                    <div class="card-body">
                                                        <div data-chart="pie" data-title="Leave Types"
                                                            data-labels="{{ isset($company['type_counts']) ? trim(implode(',', array_keys($company['type_counts']))) : '' }}"
                                                            data-set1="{{ isset($company['type_counts']) ? trim(implode(',', array_values($company['type_counts']))) : '' }}"
                                                            data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                                                            data-label-settings="offset:10,truncate:12" data-rich="size:10"
                                                            data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5" data-size="100%x320px">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Leave Status Breakdown</div>
                                                    <div class="card-body">
                                                        <div data-chart="bar" data-title="Status" data-labels="Approved,Pending,Rejected"
                                                            data-set1="{{ isset($company['status_counts']) ? implode(',', [$company['status_counts']['approved'] ?? 0, $company['status_counts']['pending'] ?? 0, $company['status_counts']['rejected'] ?? 0]) : '' }}"
                                                            data-settings="labels,tooltip,legend,animation" data-size="100%x240px"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-12">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Monthly Leave Requests</div>
                                                    <div class="card-body">
                                                        <div data-chart="line" data-title="Requests Over Time"
                                                            data-labels="{{ isset($company['monthly']['labels']) ? implode(',', $company['monthly']['labels']) : '' }}"
                                                            data-set1="{{ isset($company['monthly']['counts']) ? implode(',', $company['monthly']['counts']) : '' }}"
                                                            data-settings="smooth,area,tooltip,legend,animation" data-size="100%x240px"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Default view when no companies -->
                            <div class="tab-pane fade show active" id="default" role="tabpanel" aria-labelledby="default-tab">
                                <div class="container-fluid py-3">
                                    <!-- KPI Row -->
                                    <div class="row g-3">
                                        <div class="col-md col-6">
                                            <div class="card shadow-sm border-0 text-black text-center">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1 text-muted small">Total Leave Requests</h6>
                                                            <h4 class="mb-0 fw-bold" id="lmTotalRequests"
                                                                style="color: #1a9bb6; font-size: 2rem;">
                                                                {{ $data['total_requests'] ?? 0 }}</h4>
                                                        </div>
                                                        <div class="bg-opacity-10 p-3 rounded">
                                                            <i class="fas fa-database" style="color: #06f23c; font-size: 2rem;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md col-6">
                                            <div class="card shadow-sm border-0 text-white text-center">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="mb-1 text-muted small">Approved</div>
                                                            <h3 class="mb-0 fw-bold" id="lmApproved" style="color: #1a9bb6; font-size: 2rem;">
                                                                {{ $data['status_counts']['approved'] ?? 0 }}</h3>
                                                        </div>
                                                        <div class="bg-opacity-10 p-3 rounded">
                                                            <i class="fas fa-user-check" style="color: #198754; font-size: 2rem;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md col-6">
                                            <div class="card shadow-sm border-0 text-white text-center">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="mb-1 text-muted small">Pending</div>
                                                            <h3 class="mb-0 fw-bold" id="lmPending" style="color: #1a9bb6; font-size: 2rem;">
                                                                {{ $data['status_counts']['pending'] ?? 0 }}</h3>
                                                        </div>
                                                        <div class="bg-opacity-10 p-3 rounded">
                                                            <i class="fas fa-user-clock" style="color: #ffc107; font-size: 2rem;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md col-6">
                                            <div class="card shadow-sm border-0 text-white text-center">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="mb-1 text-muted small">Rejected</div>
                                                            <h3 class="mb-0 fw-bold" id="lmRejected" style="color: #1a9bb6; font-size: 2rem;">
                                                                {{ $data['status_counts']['rejected'] ?? 0 }}</h3>
                                                        </div>
                                                        <div class="bg-opacity-10 p-3 rounded">
                                                            <i class="fas fa-user-times" style="color: #dc3545; font-size: 2rem;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Charts Row -->
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Leave Distribution by Type</div>
                                                <div class="card-body">
                                                    <div data-chart="pie" data-title="Leave Types"
                                                        data-labels="{{ isset($data['type_counts']) ? trim(implode(',', array_keys($data['type_counts']))) : '' }}"
                                                        data-set1="{{ isset($data['type_counts']) ? trim(implode(',', array_values($data['type_counts']))) : '' }}"
                                                        data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                                                        data-label-settings="offset:10,truncate:12" data-rich="size:10"
                                                        data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5" data-size="100%x320px">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Leave Status Breakdown</div>
                                                <div class="card-body">
                                                    <div data-chart="bar" data-title="Status" data-labels="Approved,Pending,Rejected"
                                                        data-set1="{{ isset($data['status_counts']) ? implode(',', [$data['status_counts']['approved'] ?? 0, $data['status_counts']['pending'] ?? 0, $data['status_counts']['rejected'] ?? 0]) : '' }}"
                                                        data-settings="labels,tooltip,legend,animation" data-size="100%x240px"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-12">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Monthly Leave Requests</div>
                                                <div class="card-body">
                                                    <div data-chart="line" data-title="Requests Over Time"
                                                        data-labels="{{ isset($data['monthly']['labels']) ? implode(',', $data['monthly']['labels']) : '' }}"
                                                        data-set1="{{ isset($data['monthly']['counts']) ? implode(',', $data['monthly']['counts']) : '' }}"
                                                        data-settings="smooth,area,tooltip,legend,animation" data-size="100%x240px"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
            </div>
            {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************ --}}
        </div>
    </div>
@endsection
