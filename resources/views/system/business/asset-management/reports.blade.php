{{-- Template: Reports Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Reports')
@push('styles')
    <style>
        /* Existing styles for asset-activity */
        .asset-activity { margin-bottom: 0 !important; }
        .asset-activity .card-body { padding-bottom: 0 !important; }
        .asset-activity .tab-content { margin-top: .25rem !important; margin-bottom: 0 !important; padding-bottom: 0 !important; }
        .asset-activity .tab-pane { margin-bottom: 0 !important; padding-bottom: 0 !important; }
        .asset-activity .table { margin-bottom: 0 !important; }
        .asset-activity .table-responsive { margin-bottom: 0 !important; padding-bottom: 0 !important; }

        /* New styles for the main card */
        .main-card { margin-bottom: 0 !important; padding-bottom: 0 !important; }
        .main-card .card-body { padding-bottom: 0 !important; margin-bottom: 0 !important; }
        #company-reports + .tab-content { margin-bottom: 0 !important; padding-bottom: 0 !important; }
        #company-reports + .tab-content > .tab-pane { margin-bottom: 0 !important; padding-bottom: 0 !important; min-height: auto; }
        .nav-tabs-solid { margin-bottom: 0 !important; }
    </style>
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
    <script>
        window.addEventListener("load", () => {
            window.skeleton.charts();
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
                        <li class="breadcrumb-item"><a href="{{ url('/asset-management') }}">Asset Management</a></li>
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
            <div class="card main-card">
                <div class="card-body pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0" id="company-reports" role="tablist">
                            @if(isset($data['companies']) && count($data['companies']) > 0)
                                @foreach($data['companies'] as $index => $company)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                                           id="company-{{ $company['company_id'] }}-tab" 
                                           data-bs-toggle="tab" 
                                           href="#company-{{ $company['company_id'] }}" 
                                           role="tab" 
                                           aria-controls="company-{{ $company['company_id'] }}" 
                                           aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                            {{ $company['company_name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li class="nav-item">
                                    <a class="nav-link active" id="default-tab" data-bs-toggle="tab" href="#default" role="tab" aria-controls="default" aria-selected="true">Reports</a>
                                </li>
                            @endif
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-primary btn-sm" id="downloadPdfBtn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Download PDF Report">
                                <i class="fas fa-download me-1"></i> Download PDF
                            </button>
                        </div>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top mb-0">
                        @if(isset($data['companies']) && count($data['companies']) > 0)
                            @foreach($data['companies'] as $index => $company)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                                     id="company-{{ $company['company_id'] }}" 
                                     role="tabpanel" 
                                     aria-labelledby="company-{{ $company['company_id'] }}-tab">
                                    <div class="container-fluid py-3">
                                        <!-- Summary KPI Cards -->
                                        <div class="row g-3 mb-0">
                                            <!-- Total Assets -->
                                            <div class="col-md col-6">
                                                <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                            <div>
                                                                <p class="fw-medium mb-1 text-muted small">Total Assets</p>
                                                                <h5 class="mb-0 fw-bold">{{ $company['asset_count'] ?? 0 }}</h5>
                                                            </div>
                                                            <div class="avatar avatar-md br-10 icon-rotate bg-primary">
                                                                <span class="d-flex align-items-center"><i class="fa-solid fa-shelves text-white fs-16"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Available -->
                                            <div class="col-md col-6">
                                                <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                            <div>
                                                                <p class="fw-medium mb-1 text-muted small">Available</p>
                                                                <h5 class="mb-0 fw-bold">{{ $company['available_count'] ?? 0 }}</h5>
                                                            </div>
                                                            <div class="avatar avatar-md br-10 icon-rotate bg-purple">
                                                                <span class="d-flex align-items-center"><i class="fa-solid fa-badge-check text-white fs-16"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Assigned -->
                                            <div class="col-md col-6">
                                                <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                            <div>
                                                                <p class="fw-medium mb-1 text-muted small">Assigned</p>
                                                                <h5 class="mb-0 fw-bold">{{ $company['assigned_count'] ?? 0 }}</h5>
                                                            </div>
                                                            <div class="avatar avatar-md br-10 icon-rotate bg-warning">
                                                                <span class="d-flex align-items-center"><i class="fa-solid fa-user-check text-white fs-16"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Under Maintenance -->
                                            <div class="col-md col-6">
                                                <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                            <div>
                                                                <p class="fw-medium mb-1 text-muted small">Under Maintenance</p>
                                                                <h5 class="mb-0 fw-bold">{{ $company['maintenance_count'] ?? 0 }}</h5>
                                                            </div>
                                                            <div class="avatar avatar-md br-10 icon-rotate bg-pink">
                                                                <span class="d-flex align-items-center"><i class="fa-solid fa-toolbox text-white fs-16"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Maintenance Cost -->
                                            <div class="col-md col-6">
                                                <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                            <div>
                                                                <p class="fw-medium mb-1 text-muted small">Maintenance Cost</p>
                                                                <h5 class="mb-0 fw-bold">{{ $company['maintenance_cost_total'] ?? 0 }}</h5>
                                                            </div>
                                                            <div class="avatar avatar-md br-10 icon-rotate bg-info">
                                                                <span class="d-flex align-items-center"><i class="fa-solid fa-money-bill-trend-up text-white fs-16"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Charts Row -->
                                        <div class="row g-3 mb-4">
                                            <div class="col-lg-4 col-md-6">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Asset Distribution by Category</div>
                                                    <div class="card-body">
                                                        <div data-chart="pie" data-title="Assets by Category"
                                                            data-labels="{{ isset($company['counts']) ? trim(implode(',', array_keys($company['counts']))) : '' }}"
                                                            data-set1="{{ isset($company['counts']) ? trim(implode(',', array_values($company['counts']))) : '' }}"
                                                            data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                                                            data-label-settings="offset:10,truncate:12,align:left,rotate:45"
                                                            data-rich="size:10"
                                                            data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5,#9B59B6,#3498DB,#E74C3C,#2ECC71,#F39C12"
                                                            data-size="100%x320px"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Asset Status Breakdown</div>
                                                    <div class="card-body">
                                                        <div data-chart="bar" data-title="Status"
                                                            data-labels="Available,Assigned,Under Maintenance,Retired"
                                                            data-set1="{{ isset($company['status_counts']) ? implode(',', [$company['status_counts']['available'] ?? 0, $company['status_counts']['assigned'] ?? 0, $company['status_counts']['under_maintenance'] ?? 0, $company['status_counts']['retired'] ?? 0]) : '' }}"
                                                            data-settings="labels,tooltip,legend,animation" data-size="100%x320px"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-12">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header fw-bold">Maintenance Cost Over Time</div>
                                                    <div class="card-body">
                                                        <div data-chart="line" data-title="Maintenance Cost"
                                                            data-labels="{{ isset($company['monthly']['labels']) ? implode(',', $company['monthly']['labels']) : '' }}"
                                                            data-set1="{{ isset($company['monthly']['costs']) ? implode(',', $company['monthly']['costs']) : '' }}"
                                                            data-settings="smooth,area,tooltip,legend,animation" data-size="100%x320px"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Tables Row -->
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="card shadow-sm asset-activity">
                                                    <div class="card-header fw-bold">Asset Activity</div>
                                                    <div class="card-body pb-0">
                                                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action mb-2" id="asset-tabs-{{ $company['company_id'] }}" role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active" id="recent-assignment-tab-{{ $company['company_id'] }}" data-bs-toggle="tab"
                                                                    href="#recent-assignment-{{ $company['company_id'] }}" role="tab" aria-controls="recent-assignment-{{ $company['company_id'] }}"
                                                                    aria-selected="true">Recent Asset Assignments</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" id="recent-maintenance-tab-{{ $company['company_id'] }}" data-bs-toggle="tab"
                                                                    href="#recent-maintenance-{{ $company['company_id'] }}" role="tab" aria-controls="recent-maintenance-{{ $company['company_id'] }}"
                                                                    aria-selected="false">Recent Maintenance Records</a>
                                                            </li>
                                                        </ul>
                                                        <div class="tab-content mt-1 mb-0">
                                                            <div class="tab-pane fade show active" id="recent-assignment-{{ $company['company_id'] }}" role="tabpanel"
                                                                aria-labelledby="recent-assignment-tab-{{ $company['company_id'] }}">
                                                                <div class="table-responsive mb-0">
                                                                    <table class="table table-md mb-0 align-middle p-2">
                                                                        <thead>
                                                                            <tr class="p-2">
                                                                                <th>sno</th>
                                                                                <th>assignment_id</th>
                                                                                <th>asset_id</th>
                                                                                <th>user_id</th>
                                                                                <th>assigned_date</th>
                                                                                <th>return_date</th>
                                                                                <th>status</th>
                                                                                <th>notes</th>
                                                                                <th>created_by</th>
                                                                                <th>updated_by</th>
                                                                                <th>created_at</th>
                                                                                <th>updated_at</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="align-middle">
                                                                            @php $assignRows = $company['recent_assignments'] ?? []; @endphp
                                                                            @if(!empty($assignRows))
                                                                                @foreach($assignRows as $arow)
                                                                                    <tr class="p-2">
                                                                                        <td>{{ $arow['sno'] ?? '-' }}</td>
                                                                                        <td>{{ $arow['assignment_id'] ?? '-' }}</td>
                                                                                        <td>{{ $arow['asset_id'] ?? '-' }}</td>
                                                                                        <td>{{ $arow['user_id'] ?? '-' }}</td>
                                                                                        <td>{{ !empty($arow['assigned_date']) ? \Carbon\Carbon::parse($arow['assigned_date'])->format('d M Y') : '-' }}</td>
                                                                                        <td>{{ !empty($arow['return_date']) ? \Carbon\Carbon::parse($arow['return_date'])->format('d M Y') : '-' }}</td>
                                                                                        <td><span class="badge bg-light">{{ $arow['status'] ?? '-' }}</span></td>
                                                                                        <td>{{ $arow['notes'] ?? '-' }}</td>
                                                                                        <td>{{ $arow['created_by'] ?? '-' }}</td>
                                                                                        <td>{{ $arow['updated_by'] ?? '-' }}</td>
                                                                                        <td>{{ !empty($arow['created_at']) ? \Carbon\Carbon::parse($arow['created_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                        <td>{{ !empty($arow['updated_at']) ? \Carbon\Carbon::parse($arow['updated_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            @else
                                                                                <tr><td colspan="12" class="text-center py-3">No recent assignments</td></tr>
                                                                            @endif
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <div class="tab-pane fade" id="recent-maintenance-{{ $company['company_id'] }}" role="tabpanel"
                                                                aria-labelledby="recent-maintenance-tab-{{ $company['company_id'] }}">
                                                                <div class="table-responsive mb-0">
                                                                    <table class="table table-md mb-0 align-middle">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>sno</th>
                                                                                <th>maintenance_id</th>
                                                                                <th>asset_id</th>
                                                                                <th>maintenance_type</th>
                                                                                <th>description</th>
                                                                                <th>maintenance_date</th>
                                                                                <th>cost</th>
                                                                                <th>vendor_name</th>
                                                                                <th>vendor_contact</th>
                                                                                <th>next_due_date</th>
                                                                                <th>status</th>
                                                                                <th>created_by</th>
                                                                                <th>updated_by</th>
                                                                                <th>created_at</th>
                                                                                <th>updated_at</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="align-middle">
                                                                            @php $maintRows = $company['recent_maintenance'] ?? []; @endphp
                                                                            @if(!empty($maintRows))
                                                                                @foreach($maintRows as $mrow)
                                                                                    <tr>
                                                                                        <td>{{ $mrow['sno'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['maintenance_id'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['asset_id'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['maintenance_type'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['description'] ?? '-' }}</td>
                                                                                        <td>{{ !empty($mrow['maintenance_date']) ? \Carbon\Carbon::parse($mrow['maintenance_date'])->format('d M Y') : '-' }}</td>
                                                                                        <td>{{ isset($mrow['cost']) ? number_format((float)$mrow['cost'], 2) : '-' }}</td>
                                                                                        <td>{{ $mrow['vendor_name'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['vendor_contact'] ?? '-' }}</td>
                                                                                        <td>{{ !empty($mrow['next_due_date']) ? \Carbon\Carbon::parse($mrow['next_due_date'])->format('d M Y') : '-' }}</td>
                                                                                        <td><span class="badge bg-light">{{ $mrow['status'] ?? '-' }}</span></td>
                                                                                        <td>{{ $mrow['created_by'] ?? '-' }}</td>
                                                                                        <td>{{ $mrow['updated_by'] ?? '-' }}</td>
                                                                                        <td>{{ !empty($mrow['created_at']) ? \Carbon\Carbon::parse($mrow['created_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                        <td>{{ !empty($mrow['updated_at']) ? \Carbon\Carbon::parse($mrow['updated_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            @else
                                                                                <tr><td colspan="15" class="text-center py-3">No recent maintenance records</td></tr>
                                                                            @endif
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
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
                                    <!-- Summary KPI Cards -->
                                    <div class="row g-3 mb-0">
                                        <!-- Total Assets -->
                                        <div class="col-md col-6">
                                            <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                        <div>
                                                            <p class="fw-medium mb-1 text-muted small">Total Assets</p>
                                                            <h5 class="mb-0 fw-bold">{{ $data['asset_count'] ?? 0 }}</h5>
                                                        </div>
                                                        <div class="avatar avatar-md br-10 icon-rotate bg-primary">
                                                            <span class="d-flex align-items-center"><i class="fa-solid fa-shelves text-white fs-16"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Available -->
                                        <div class="col-md col-6">
                                            <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                        <div>
                                                            <p class="fw-medium mb-1 text-muted small">Available</p>
                                                            <h5 class="mb-0 fw-bold">{{ $data['available_count'] ?? 0 }}</h5>
                                                        </div>
                                                        <div class="avatar avatar-md br-10 icon-rotate bg-purple">
                                                            <span class="d-flex align-items-center"><i class="fa-solid fa-badge-check text-white fs-16"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Assigned -->
                                        <div class="col-md col-6">
                                            <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                        <div>
                                                            <p class="fw-medium mb-1 text-muted small">Assigned</p>
                                                            <h5 class="mb-0 fw-bold">{{ $data['assigned_count'] ?? 0 }}</h5>
                                                        </div>
                                                        <div class="avatar avatar-md br-10 icon-rotate bg-warning">
                                                            <span class="d-flex align-items-center"><i class="fa-solid fa-user-check text-white fs-16"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Under Maintenance -->
                                        <div class="col-md col-6">
                                            <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                        <div>
                                                            <p class="fw-medium mb-1 text-muted small">Under Maintenance</p>
                                                            <h5 class="mb-0 fw-bold">{{ $data['maintenance_count'] ?? 0 }}</h5>
                                                        </div>
                                                        <div class="avatar avatar-md br-10 icon-rotate bg-pink">
                                                            <span class="d-flex align-items-center"><i class="fa-solid fa-toolbox text-white fs-16"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Maintenance Cost -->
                                        <div class="col-md col-6">
                                            <div class="card bg-linear-gradiant border-white border-2 overlay-bg-3 position-relative">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                        <div>
                                                            <p class="fw-medium mb-1 text-muted small">Maintenance Cost</p>
                                                            <h5 class="mb-0 fw-bold">{{ $data['maintenance_cost_total'] ?? 0 }}</h5>
                                                        </div>
                                                        <div class="avatar avatar-md br-10 icon-rotate bg-info">
                                                            <span class="d-flex align-items-center"><i class="fa-solid fa-money-bill-trend-up text-white fs-16"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Charts Row -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Asset Distribution by Category</div>
                                                <div class="card-body">
                                                    <div data-chart="pie" data-title="Assets by Category"
                                                        data-labels="{{ isset($data['counts']) ? trim(implode(',', array_keys($data['counts']))) : '' }}"
                                                        data-set1="{{ isset($data['counts']) ? trim(implode(',', array_values($data['counts']))) : '' }}"
                                                        data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                                                        data-label-settings="offset:10,truncate:12,align:left,rotate:45"
                                                        data-rich="size:10"
                                                        data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5,#9B59B6,#3498DB,#E74C3C,#2ECC71,#F39C12"
                                                        data-size="100%x320px"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Asset Status Breakdown</div>
                                                <div class="card-body">
                                                    <div data-chart="bar" data-title="Status"
                                                        data-labels="Available,Assigned,Under Maintenance,Retired"
                                                        data-set1="{{ isset($data['status_counts']) ? implode(',', [$data['status_counts']['available'] ?? 0, $data['status_counts']['assigned'] ?? 0, $data['status_counts']['under_maintenance'] ?? 0, $data['status_counts']['retired'] ?? 0]) : '' }}"
                                                        data-settings="labels,tooltip,legend,animation" data-size="100%x320px"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header fw-bold">Maintenance Cost Over Time</div>
                                                <div class="card-body">
                                                    <div data-chart="line" data-title="Maintenance Cost"
                                                        data-labels="{{ isset($data['monthly']['labels']) ? implode(',', $data['monthly']['labels']) : '' }}"
                                                        data-set1="{{ isset($data['monthly']['costs']) ? implode(',', $data['monthly']['costs']) : '' }}"
                                                        data-settings="smooth,area,tooltip,legend,animation" data-size="100%x320px"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Tables Row -->
                                    <div class="row g-3">
                                        <div class="col-lg-12">
                                            <div class="card shadow-sm asset-activity">
                                                <div class="card-header fw-bold">Asset Activity</div>
                                                <div class="card-body pb-0">
                                                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action mb-2" id="asset-tabs" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active" id="recent-assignment-tab" data-bs-toggle="tab"
                                                                href="#recent-assignment" role="tab" aria-controls="recent-assignment"
                                                                aria-selected="true">Recent Asset Assignments</a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="recent-maintenance-tab" data-bs-toggle="tab"
                                                                href="#recent-maintenance" role="tab" aria-controls="recent-maintenance"
                                                                aria-selected="false">Recent Maintenance Records</a>
                                                        </li>
                                                    </ul>
                                                    <div class="tab-content mt-1 mb-0">
                                                        <div class="tab-pane fade show active" id="recent-assignment" role="tabpanel"
                                                            aria-labelledby="recent-assignment-tab">
                                                            <div class="table-responsive mb-0">
                                                                <table class="table table-md mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>sno</th>
                                                                            <th>assignment_id</th>
                                                                            <th>asset_id</th>
                                                                            <th>user_id</th>
                                                                            <th>assigned_date</th>
                                                                            <th>return_date</th>
                                                                            <th>status</th>
                                                                            <th>notes</th>
                                                                            <th>created_by</th>
                                                                            <th>updated_by</th>
                                                                            <th>created_at</th>
                                                                            <th>updated_at</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @php $assignRows = $data['recent_assignments'] ?? []; @endphp
                                                                        @if(!empty($assignRows))
                                                                            @foreach($assignRows as $arow)
                                                                                <tr>
                                                                                    <td>{{ $arow['sno'] ?? '-' }}</td>
                                                                                    <td>{{ $arow['assignment_id'] ?? '-' }}</td>
                                                                                    <td>{{ $arow['asset_id'] ?? '-' }}</td>
                                                                                    <td>{{ $arow['user_id'] ?? '-' }}</td>
                                                                                    <td>{{ !empty($arow['assigned_date']) ? \Carbon\Carbon::parse($arow['assigned_date'])->format('d M Y') : '-' }}</td>
                                                                                    <td>{{ !empty($arow['return_date']) ? \Carbon\Carbon::parse($arow['return_date'])->format('d M Y') : '-' }}</td>
                                                                                    <td><span class="badge bg-light">{{ $arow['status'] ?? '-' }}</span></td>
                                                                                    <td>{{ $arow['notes'] ?? '-' }}</td>
                                                                                    <td>{{ $arow['created_by'] ?? '-' }}</td>
                                                                                    <td>{{ $arow['updated_by'] ?? '-' }}</td>
                                                                                    <td>{{ !empty($arow['created_at']) ? \Carbon\Carbon::parse($arow['created_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                    <td>{{ !empty($arow['updated_at']) ? \Carbon\Carbon::parse($arow['updated_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @else
                                                                            <tr><td colspan="12" class="text-center">No recent assignments</td></tr>
                                                                        @endif
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <div class="tab-pane fade" id="recent-maintenance" role="tabpanel"
                                                            aria-labelledby="recent-maintenance-tab">
                                                            <div class="table-responsive mb-0">
                                                                <table class="table table-md mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>maintenance_id</th>
                                                                            <th>asset_id</th>
                                                                            <th>maintenance_type</th>
                                                                            <th>description</th>
                                                                            <th>maintenance_date</th>
                                                                            <th>cost</th>
                                                                            <th>vendor_name</th>
                                                                            <th>vendor_contact</th>
                                                                            <th>next_due_date</th>
                                                                            <th>status</th>
                                                                            <th>created_by</th>
                                                                            <th>updated_by</th>
                                                                            <th>created_at</th>
                                                                            <th>updated_at</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @php $maintRows = $data['recent_maintenance'] ?? []; @endphp
                                                                        @if(!empty($maintRows))
                                                                            @foreach($maintRows as $mrow)
                                                                                <tr>
                                                                                    <td>{{ $mrow['maintenance_id'] ?? '-' }}</td>
                                                                                    <td>{{ $mrow['asset_id'] ?? '-' }}</td>
                                                                                    <td>{{ $mrow['maintenance_type'] ?? '-' }}</td>
                                                                                    <td>{{ $mrow['description'] ?? '-' }}</td>
                                                                                    <td>{{ !empty($mrow['maintenance_date']) ? \Carbon\Carbon::parse($mrow['maintenance_date'])->format('d M Y') : '-' }}</td>
                                                                                    <td>{{ isset($mrow['cost']) ? number_format((float)$mrow['cost'], 2) : '-' }}</td>
                                                                                    <td>{{ $mrow['vendor_name'] ?? '-' }}</td>
                                                                                    <td>{{ $mrow['vendor_contact'] ?? '-' }}</td>
                                                                                    <td>{{ !empty($mrow['next_due_date']) ? \Carbon\Carbon::parse($mrow['next_due_date'])->format('d M Y') : '-' }}</td>
                                                                                    <td><span class="badge bg-light">{{ $mrow['status'] ?? '-' }}</span></td>
                                                                                    <td>{{ $mrow['created_by'] ?? '-' }}</td>
                                                                                    <td>{{ $mrow['updated_by'] ?? '-' }}</td>
                                                                                    <td>{{ !empty($mrow['created_at']) ? \Carbon\Carbon::parse($mrow['created_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                    <td>{{ !empty($mrow['updated_at']) ? \Carbon\Carbon::parse($mrow['updated_at'])->format('d M Y h:i A') : '-' }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @else
                                                                            <tr><td colspan="15" class="text-center">No recent maintenance records</td></tr>
                                                                        @endif
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection