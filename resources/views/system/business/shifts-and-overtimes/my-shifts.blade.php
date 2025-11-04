{{-- Template: My Shifts Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'My Shifts')
@push('styles')
    {{-- Header Styles --}}
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
    {{-- Body Scripts --}}
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">My Shifts</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/shifts-and-overtimes') }}">Shifts And Overtimes</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">My Shifts</a></li>
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
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="my-shifts-schedules-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" id="today-tab" data-skl-action="b" data-bs-toggle="tab" href="#today"
                                    role="tab" aria-controls="today" aria-selected="false" data-type="view"
                                    data-token="@skeletonToken('business_my_shifts')_c" data-text="">
                                    Today
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="shifts-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#shifts" role="tab" aria-controls="shifts" aria-selected="true"
                                    data-type="view" data-token="@skeletonToken('business_my_shifts')_t" data-text="">
                                    Shifts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="schedules-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#schedules" role="tab" aria-controls="schedules" aria-selected="false"
                                    data-type="view" data-token="@skeletonToken('business_my_schedules')_t" data-text="">
                                    Schedules
                                </a>
                            </li>
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-outline-secondary" disabled>Read-only</button>
                        </div>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        {{-- Today Tab Pane --}}
                        <div class="tab-pane fade" id="today" role="tabpanel" aria-labelledby="today-tab">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Today's Shifts</h6>
                                                <span
                                                    class="badge bg-info">{{ Carbon\Carbon::now()->format('M d, Y') }}</span>
                                            </div>

                                            @if (!empty($data['today_shifts']))
                                                <div class="row g-3">
                                                    @foreach ($data['today_shifts'] as $shift)
                                                        <div class="col-xl-6 col-lg-6 col-md-6">
                                                            <div class="card h-100 border-0 rounded-4 shadow-sm">
                                                                <div class="card-body p-3">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-start mb-2">
                                                                        <span class="badge bg-info">Today's Shift</span>
                                                                        <span>
                                                                            @if ($shift['is_active'] == 1)
                                                                                <span class="badge bg-success">Active</span>
                                                                            @else
                                                                                <span
                                                                                    class="badge bg-danger">Inactive</span>
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center mb-1">
                                                                        <h6 class="fw-bold mb-0">{{ $shift['name'] }}</h6>
                                                                        <span
                                                                            class="badge bg-primary ms-2">{{ $shift['shift_id'] }}</span>
                                                                    </div>
                                                                    <div class="sf-11 text-muted mb-2">
                                                                        <i
                                                                            class="ti ti-clock me-1"></i>{{ $shift['start_time'] }}
                                                                        - {{ $shift['end_time'] }}
                                                                    </div>
                                                                    <div class="row g-2 sf-11 mb-2">
                                                                        <div class="col-6"><b class="text-muted">Min
                                                                                Hours:</b>
                                                                            <span>{{ $shift['min_work_hours'] }}</span>
                                                                        </div>
                                                                        <div class="col-6"><b
                                                                                class="text-muted">Half-day:</b>
                                                                            <span>{{ $shift['half_day_hours'] > 0 ? $shift['half_day_hours'] : '-' }}</span>
                                                                        </div>
                                                                        <div class="col-6"><b
                                                                                class="text-muted">Break:</b>
                                                                            <span>{{ $shift['break_duration_minutes'] }}
                                                                                min</span></div>
                                                                        <div class="col-6"><b class="text-muted">Grace
                                                                                In:</b>
                                                                            <span>{{ $shift['grace_in_minutes'] }}
                                                                                min</span></div>
                                                                        <div class="col-6"><b class="text-muted">Grace
                                                                                Out:</b>
                                                                            <span>{{ $shift['grace_out_minutes'] }}
                                                                                min</span></div>
                                                                        <div class="col-6"><b class="text-muted">Max
                                                                                OT:</b>
                                                                            <span>{{ $shift['max_overtime_minutes'] }}
                                                                                min</span></div>
                                                                    </div>
                                                                    <div class="sf-11 text-muted mb-2">
                                                                        <b class="text-muted">Overtime:</b>
                                                                        @if ($shift['overtime_eligible'] == 1)
                                                                            <span
                                                                                class="badge bg-success ms-1">Eligible</span>
                                                                            <span
                                                                                class="ms-2 badge bg-light text-dark">{{ $shift['overtime_rate_type'] }}
                                                                                {{ $shift['overtime_rate_value'] }}</span>
                                                                        @else
                                                                            <span class="badge bg-light text-dark ms-1">Not
                                                                                Eligible</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="d-flex flex-wrap gap-2 sf-11 mb-2">
                                                                        @if ($shift['is_cross_day_shift'] == 1)
                                                                            <span class="badge bg-info">Cross-day</span>
                                                                        @endif
                                                                        @if ($shift['allow_multiple_sessions'] == 1)
                                                                            <span class="badge bg-success">Multiple
                                                                                Sessions</span>
                                                                        @endif
                                                                        @if ($shift['auto_overtime_detection'] == 1)
                                                                            <span class="badge bg-success">Auto OT
                                                                                Detection</span>
                                                                        @endif
                                                                        @if ($shift['overtime_approval_required'] == 1)
                                                                            <span class="badge bg-warning">OT Approval
                                                                                Required</span>
                                                                        @endif
                                                                        @if ($shift['is_holiday_shift'] == 1)
                                                                            <span class="badge bg-info">Holiday
                                                                                Shift</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="row g-2 sf-11 mb-2">
                                                                        <div class="col-6"><b class="text-muted">Dynamic
                                                                                Break:</b>
                                                                            <span>{{ $shift['is_dynamic_break'] == 1 ? 'Yes' : 'No' }}</span>
                                                                        </div>
                                                                        <div class="col-6"><b
                                                                                class="text-muted">Auto-deduct Break:</b>
                                                                            <span>{{ $shift['auto_deduct_break'] == 1 ? 'Yes' : 'No' }}</span>
                                                                        </div>
                                                                        <div class="col-6"><b class="text-muted">Inferred
                                                                                Sessions:</b>
                                                                            <span>{{ $shift['allow_inferred_sessions'] == 1 ? 'Yes' : 'No' }}</span>
                                                                        </div>
                                                                        <div class="col-6"><b class="text-muted">Week-off
                                                                                Shift:</b>
                                                                            <span>{{ $shift['is_week_off_shift'] == 1 ? 'Yes' : 'No' }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-4">
                                                    <div class="mb-3">
                                                        <i class="ti ti-calendar-off text-muted"
                                                            style="font-size: 3rem;"></i>
                                                    </div>
                                                    <h6 class="text-muted">No shifts assigned for today</h6>
                                                    <p class="text-muted sf-11">Check your schedules or contact your
                                                        supervisor for shift assignments.</p>
                                                </div>
                                            @endif

                                            @if (!empty($data['today_schedules']))
                                                <div class="mt-4">
                                                    <h6 class="mb-3">Active Schedules for Today</h6>
                                                    <div class="row g-2">
                                                        @foreach ($data['today_schedules'] as $schedule)
                                                            <div class="col-md-6">
                                                                <div class="card border-0 bg-light">
                                                                    <div class="card-body p-2">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center">
                                                                            <span
                                                                                class="fw-bold sf-12">{{ $schedule['name'] }}</span>
                                                                            <span
                                                                                class="badge bg-primary sf-11">{{ count($schedule['shifts']) }}
                                                                                shift(s)</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Shifts Tab Pane --}}
                        <div class="tab-pane fade show active" id="shifts" role="tabpanel"
                            aria-labelledby="shifts-tab">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                                            id="shifts-inner-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="shifts-table-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#shifts-table-view" role="tab"
                                                    aria-controls="shifts-table-view" aria-selected="true"
                                                    data-type="view" data-token="@skeletonToken('business_my_shifts')_t" data-text="">
                                                    <i class="ti ti-list"></i>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="shifts-card-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#shifts-card-view" role="tab"
                                                    aria-controls="shifts-card-view" aria-selected="false"
                                                    data-type="view" data-token="@skeletonToken('business_my_shifts')_c" data-text="">
                                                    <i class="ti ti-layout-grid"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="tab-content mt-2 pt-2 border-top">
                                        <div class="tab-pane fade show active" id="shifts-table-view" role="tabpanel"
                                            aria-labelledby="shifts-table-tab">
                                            <div data-skeleton-table-set="@skeletonToken('business_my_shifts')_t"></div>
                                        </div>
                                        <div class="tab-pane fade" id="shifts-card-view" role="tabpanel"
                                            aria-labelledby="shifts-card-tab">
                                            <div data-skeleton-card-set="@skeletonToken('business_my_shifts')_c" data-placeholder="card|9"
                                                data-type="scroll" data-limit="10" data-filters="sort|search|counts"
                                                data-container="row"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Schedules Tab Pane --}}
                        <div class="tab-pane fade" id="schedules" role="tabpanel" aria-labelledby="schedules-tab">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                                            id="schedules-inner-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="schedules-table-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#schedules-table-view" role="tab"
                                                    aria-controls="schedules-table-view" aria-selected="true"
                                                    data-type="view" data-token="@skeletonToken('business_my_schedules')_t" data-text="">
                                                    <i class="ti ti-list"></i>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="schedules-card-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#schedules-card-view" role="tab"
                                                    aria-controls="schedules-card-view" aria-selected="false"
                                                    data-type="view" data-token="@skeletonToken('business_my_schedules')_c" data-text="">
                                                    <i class="ti ti-layout-grid"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="tab-content mt-2 pt-2 border-top">
                                        <div class="tab-pane fade show active" id="schedules-table-view" role="tabpanel"
                                            aria-labelledby="schedules-table-tab">
                                            <div data-skeleton-table-set="@skeletonToken('business_my_schedules')_t"></div>
                                        </div>
                                        <div class="tab-pane fade" id="schedules-card-view" role="tabpanel"
                                            aria-labelledby="schedules-card-tab">
                                            <div data-skeleton-card-set="@skeletonToken('business_my_schedules')_c" data-placeholder="card|9"
                                                data-type="scroll" data-limit="10" data-filters="sort|search|counts"
                                                data-container="row"></div>
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
    </div>
@endsection
