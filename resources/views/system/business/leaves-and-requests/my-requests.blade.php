{{-- Template: Requests Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Requests')
@push('scripts')
@vite(['resources/js/page/calendar.js'])
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-calender]').forEach(calendarEl => {
            try {
                const events = JSON.parse(calendarEl.dataset.calender || '[]');
                console.log(events);
                window.page.calendar({
                    selector: `[data-calender]`,
                    options: {
                        initialDate: '2025-09-01',
                        dayMaxEvents: true,
                        locale: 'en',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,listMonth,listWeek,listDay'
                        }
                    },
                    events: events.filter(event => event && event.id && event.start)
                });
            } catch (error) {
                console.error(`Failed to initialize calendar:`, error);
                calendarEl.innerHTML = '<div class="alert alert-warning">Failed to load calendar.</div>';
            }
        });
    });
</script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Requests</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/leave-management') }}">Leave Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Requests</a></li>
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
        @php
        $counts=$data['counts'] ?? [];
        @endphp
            <div class="row">
                <!-- Total Requests -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-primary rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-primary-transparent">
                                                <i class="ti ti-list fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Total Requests</p>
                                            <h4>{{ $counts->total ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-warning rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-warning-transparent">
                                                <i class="ti ti-hourglass fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Pending Requests</p>
                                            <h4>{{ $counts->pending ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved Requests -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-success rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-success-transparent">
                                                <i class="ti ti-check fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Approved Requests</p>
                                            <h4>{{ $counts->approved ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejected Requests -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-danger rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-danger-transparent">
                                                <i class="ti ti-x fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Rejected Requests</p>
                                            <h4>{{ $counts->rejected ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-4 col-md-12 col-sm-12 order-1 order-xl-2 ps-1">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Leave Balances</h5>
                                </div>
                                <div class="card-body p-0">
                                    @if ($data['leaveBalances']->isEmpty())
                                        <div class="p-3 text-center text-muted">
                                            No leave balances available
                                        </div>
                                    @else
                                        <div class="d-flex flex-column">
                                           @foreach ($data['leaveBalances'] as $leave)
                                                <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                    <div>
                                                        <a href="javascript:void(0);">
                                                            {{ $leave->request_type_name }}
                                                        </a>
                                                        <div class="text-muted small">
                                                            Year: {{ $leave->year }}
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                         @php
                                                            $allocated = (int) $leave->allocated_days;
                                                            $used = (int) $leave->used_days;
                                                        @endphp

                                                        @if ($used > $allocated)
                                                            <span class="badge bg-danger rounded-pill">
                                                                {{ $used }} / {{ $allocated }} (Exceeded)
                                                            </span>
                                                        @elseif ($used === $allocated)
                                                            <span class="badge bg-warning text-dark rounded-pill">
                                                                {{ $used }} / {{ $allocated }} (Reached)
                                                            </span>
                                                        @else
                                                            <span class="badge bg-primary rounded-pill">
                                                                {{ $used }} / {{ $allocated }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>Requests</h5>
                                </div>
                                <div class="card-body p-0">
                                    @php
                                        $categories = $data['categories'] ?? [];
                                        $types      = $data['types'] ?? [];
                                    @endphp
                                    @foreach ($categories as $category)
                                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                            <a href="javascript:void(0);">{{ $category->category }}</a>
                                            <div class="d-flex align-items-center">
                                                <span class="badge badge-xs bg-dark rounded-circle">{{ $category->count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5>Request Types</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="d-flex flex-column">
                                        @foreach ($types as $type)
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <a href="javascript:void(0);">{{ $type['type'] }}</a>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-xs bg-dark rounded-circle">{{ $type['count'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 order-2 order-xl-1 pe-1">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <ul class="nav nav-tabs card-header-tabs data-skl-action" id="requestTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active btn-outline-primary shadow-none" id="card-tab"
                                            data-skl-action="b" data-bs-toggle="tab" data-bs-target="#card-view" type="button"
                                            role="tab" aria-controls="card-view" aria-selected="true">
                                            <i class="ti ti-layout-grid"></i>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-outline-primary shadow-none" id="calendar-tab"
                                            data-skl-action="b" data-bs-toggle="tab" data-bs-target="#calendar-view"
                                            type="button" role="tab" aria-controls="calendar-view" aria-selected="true">
                                            <i class="ti ti-calendar"></i>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-outline-primary shadow-none" id="table-tab"
                                            data-skl-action="b" data-bs-toggle="tab" data-bs-target="#table-view" type="button"
                                            role="tab" aria-controls="table-view" aria-selected="false">
                                            <i class="ti ti-list"></i>
                                        </button>
                                    </li>
                                </ul>
                                <button class="btn btn-primary skeleton-popup"
                                    data-token="@skeletonToken('business_requests')_a" data-type="add">
                                    Request
                                </button>
                            </div>
                            <div class="card-body tab-content" id="requestTabsContent">
                                <div class="tab-pane fade show active" id="card-view" role="tabpanel" aria-labelledby="table-tab">
                                    <div data-skeleton-card-set="@skeletonToken('business_requests')_c" data-placeholder="card|9"
                                        data-type="scroll" data-limit="10" data-filters="sort|search"
                                        data-container="row"></div>
                                </div>
                                <div class="tab-pane fade" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
                                    <div id="requests-calendar" data-calender='@json($data["requests"])'></div>
                                </div>
                                <div class="tab-pane fade" id="table-view" role="tabpanel" aria-labelledby="table-tab">
                                    <div data-skeleton-table-set="@skeletonToken('business_requests')_t" data-bulk="update"></div>
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
