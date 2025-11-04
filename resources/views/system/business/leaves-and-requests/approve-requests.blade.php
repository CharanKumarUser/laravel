{{-- Template: Approve Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Approve')
@push('styles')
@endpush
@push('scripts')
@vite(['resources/js/page/calendar.js'])
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const initCalendars = () => {
            document.querySelectorAll('[data-calender]').forEach(calendarEl => {
                try {
                    const companyId = calendarEl.dataset.id;
                    console.log(companyId);
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
        }
        setTimeout(initCalendars, 200);
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                setTimeout(initCalendars, 200); 
            });
        });
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                setTimeout(initCalendars, 200);
            });
        });
    });
</script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Approve Requests</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/leave-management') }}">Leave Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Approve Requests</a></li>
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
                    <div class="row">
                        <!-- Left Side: Companies Counts -->
                        <div class="col-xl-4 col-md-12 col-sm-12 order-1 order-xl-2 ps-1">
                            <div class="accordion" id="companyAccordion">
                                @forelse ($data['companies'] as $index => $company)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-{{ $company['company_id'] }}">
                                            <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse-{{ $company['company_id'] }}" 
                                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                                    aria-controls="collapse-{{ $company['company_id'] }}">
                                                <h5><i class="ti ti-building me-2"></i>{{ $company['company_name'] }}</h5>
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ $company['company_id'] }}" 
                                            class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                            aria-labelledby="heading-{{ $company['company_id'] }}" 
                                            data-bs-parent="#companyAccordion">
                                            <div class="accordion-body">

                                                {{-- Requests --}}
                                                <div class="card">
                                                    <div class="card-header"><h6>Requests</h6></div>
                                                    <div class="card-body p-0">
                                                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                            <span><span class="badge rounded-circle bg-primary me-2">&nbsp;</span>Total</span>
                                                            <span class="badge bg-dark rounded-circle">{{ $company['counts']->total ?? 0 }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                            <span><span class="badge rounded-circle bg-warning me-2">&nbsp;</span>Pending</span>
                                                            <span class="badge bg-dark rounded-circle">{{ $company['counts']->pending ?? 0 }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                            <span><span class="badge rounded-circle bg-success me-2">&nbsp;</span>Approved</span>
                                                            <span class="badge bg-dark rounded-circle">{{ $company['counts']->approved ?? 0 }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                            <span><span class="badge rounded-circle bg-danger me-2">&nbsp;</span>Rejected</span>
                                                            <span class="badge bg-dark rounded-circle">{{ $company['counts']->rejected ?? 0 }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Requests Categories --}}
                                                <div class="card mt-3">
                                                    <div class="card-header"><h6>Requests Categories</h6></div>
                                                    <div class="card-body p-0">
                                                        @forelse ($company['categories'] as $category)
                                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                                <span>{{ $category->category }}</span>
                                                                <span class="badge bg-dark rounded-circle">{{ $category->count }}</span>
                                                            </div>
                                                        @empty
                                                            <div class="p-3">No Categories Found</div>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                {{-- Request Types --}}
                                                <div class="card mt-3">
                                                    <div class="card-header"><h6>Request Types</h6></div>
                                                    <div class="card-body p-0">
                                                        @forelse ($company['types'] as $type)
                                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                                <span>{{ $type['type'] }}</span>
                                                                <span class="badge bg-dark rounded-circle">{{ $type['count'] }}</span>
                                                            </div>
                                                        @empty
                                                            <div class="p-3">No Request Types Found</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="d-flex flex-column align-items-center justify-content-center text-center w-100 h-100"
                                        style="height:calc(80vh - 100px) !important;">
                                        <img src="{{ asset('errors/empty.svg') }}" alt="No Company Details" class="img-fluid mb-2 w-25">
                                        <h6 class="mb-2 fw-bold">No company details available</h6>
                                        <p class="text-muted">Please add a company to view details and start Leave & Requests.</p>
                                        <a class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_companies')_a">Add Company</a>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Right Side: Single Requests Section -->
                        <div class="col-xl-8 order-2 order-xl-1 pe-1">
                            <div class="card-header d-flex align-items-center justify-content-start pt-0">
                                <ul class="nav nav-tabs card-header-tabs data-skl-action" id="requestTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active btn-outline-primary shadow-none"
                                                id="card-tab"
                                                data-bs-toggle="tab" data-skl-action="b"
                                                data-bs-target="#card-view"
                                                type="button" role="tab" aria-controls="card-view" aria-selected="true">
                                            <i class="ti ti-layout-grid"></i>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-outline-primary shadow-none"
                                                id="calendar-tab"
                                                data-bs-toggle="tab" data-skl-action="b"
                                                data-bs-target="#calendar-view"
                                                type="button" role="tab" aria-controls="calendar-view" aria-selected="false">
                                            <i class="ti ti-calendar"></i>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-outline-primary shadow-none"
                                                id="table-tab"
                                                data-bs-toggle="tab" data-skl-action="b"
                                                data-bs-target="#table-view"
                                                type="button" role="tab" aria-controls="table-view" aria-selected="false">
                                            <i class="ti ti-list"></i>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body tab-content" id="requestTabsContent">
                                <div class="tab-pane fade show active" id="card-view" role="tabpanel">
                                    <div data-skeleton-card-set="@skeletonToken('business_request_approve')_c"
                                        data-placeholder="card|9" data-type="scroll" data-limit="10" data-filters="sort|search"
                                        data-container="row"></div>
                                </div>
                                <div class="tab-pane fade" id="calendar-view" role="tabpanel">
                                    <div id="requests-calendar" data-calender='@json($data["requests"])'></div>
                                </div>
                                <div class="tab-pane fade" id="table-view" role="tabpanel">
                                    <div data-skeleton-table-set="@skeletonToken('business_request_approve')_t" data-bulk="update"></div>
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
