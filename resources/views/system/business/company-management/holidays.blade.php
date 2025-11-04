{{-- Template: Holidays Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Holidays')

@push('styles')
<style>
.h-px-32 {
    height: 32px;
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/page/calendar.js'])
<script>
document.addEventListener('DOMContentLoaded', () => {
    const initCalendars = () => {
        document.querySelectorAll('[data-calender]').forEach(calendarEl => {
            try {
                const companyId = calendarEl.dataset.id;
                const events = JSON.parse(calendarEl.dataset.calender || '[]');

                if (!companyId) {
                    console.warn('Missing company ID for calendar:', calendarEl);
                    return;
                }

                if (!Array.isArray(events)) {
                    console.error(`Invalid events data for company ${companyId}`);
                    return;
                }

                window.page.calendar({
                    selector: `#company-${companyId}-calendar [data-calender]`,
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
                console.error(`Failed to initialize calendar for company ${calendarEl.dataset.id}:`, error);
                calendarEl.innerHTML = '<div class="alert alert-warning">Failed to load calendar.</div>';
            }
        });
    };
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
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/company-management') }}">Company Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Holidays</a></li>
                </ol>
            </nav>
        </div>

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
            $companies = $data['companies'] ?? [];
        @endphp

        <div class="card">
            <div class="card-body">
                {{-- Tabs Navigation and Action Button --}}
                <div class="d-flex justify-content-between align-items-center">
                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                        id="company-tabs" role="tablist">
                        @forelse ($companies as $company)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                                    id="company-{{ $company->company_id }}-tab"
                                    data-bs-toggle="tab"
                                    href="#company-{{ $company->company_id }}-content"
                                    role="tab"
                                    aria-controls="company-{{ $company->company_id }}-content"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    data-skl-action="b"
                                    data-target="#holiday-add-btn"
                                    data-type="add"
                                    data-id="{{ $company->company_id }}"
                                    data-token="@skeletonToken('business_company_holidays')_a_{{ $company->company_id }}"
                                    data-text="Add Holiday">
                                    {{ $company->name }}
                                </a>
                            </li>
                        @empty
                            <div class="d-flex flex-column align-items-center justify-content-center text-center w-100"
                                style="height:calc(80vh - 100px) !important;">
                                <img src="{{ asset('errors/empty.svg') }}" alt="No Company Details"
                                    class="img-fluid mb-2 w-25">
                                <h6 class="mb-2 fw-bold">No company details available</h6>
                                <p class="text-muted">Select a company tab to view details or add a new company.</p>
                            </div>
                        @endforelse
                    </ul>

                    {{-- Action Button --}}
                    <div class="action-area">
                        <button class="btn btn-primary skeleton-popup" id="holiday-add-btn">
                            <i class="fa-solid fa-plus me-2"></i>Default
                        </button>
                    </div>
                </div>

                {{-- Tabs Content --}}
                <div class="tab-content mt-2 pt-2">
                    @forelse ($companies as $company)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="company-{{ $company->company_id }}-content"
                            role="tabpanel"
                            aria-labelledby="company-{{ $company->company_id }}-tab">

                            {{-- Sub Tabs --}}
                            <ul class="nav nav-tabs nav-tabs border-bottom-0 data-skl-action d-flex justify-content-between align-items-center gap-2"
                                id="company-{{ $company->company_id }}-subtabs">

                                {{-- Company summary stats --}}
                                <div class="d-flex flex-wrap gap-2">
                                    <!-- Total Holidays -->
                                    <div class="d-flex align-items-center rounded-pill border pe-2 fs-13 h-px-32">
                                        <div class="avatar avatar-sm br-15 bg-outline-purple me-2">
                                            <i class="ti ti-calendar-stats text-purple fs-13"></i>
                                        </div>
                                        <span>Total Holidays - <b>{{ $company->total ?? 0 }}</b></span>
                                    </div>

                                    <!-- This Month Holidays -->
                                    <div class="d-flex align-items-center rounded-pill border pe-2 py-1 fs-13 h-px-32">
                                        <div class="avatar avatar-sm br-15 bg-outline-info me-2">
                                            <i class="ti ti-calendar-month text-info fs-14"></i>
                                        </div>
                                        <span>This Month Holidays - <b>{{ $company->this_month ?? 0 }}</b></span>
                                    </div>

                                    <!-- Working Days -->
                                    <div class="d-flex align-items-center rounded-pill border pe-2 py-1 fs-13 h-px-32">
                                        <div class="avatar avatar-sm br-15 bg-outline-success me-2">
                                            <i class="ti ti-briefcase text-success fs-14"></i>
                                        </div>
                                        <span>Working Days - <b>{{ $company->working_days ?? 0 }}</b></span>
                                    </div>
                                </div>

                                {{-- Sub-tab buttons --}}
                                <div class="d-flex">
                                    @foreach (['calendar' => 'Calendar', 'list' => 'List'] as $tabKey => $label)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $loop->first ? 'active' : '' }} btn-outline-primary shadow-none"
                                                id="company-{{ $company->company_id }}-{{ $tabKey }}-tab"
                                                data-bs-toggle="tab"
                                                href="#company-{{ $company->company_id }}-{{ $tabKey }}"
                                                role="tab"
                                                aria-controls="company-{{ $company->company_id }}-{{ $tabKey }}"
                                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                <i class="ti ti-{{ $tabKey === 'calendar' ? 'calendar' : 'list' }}"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </div>
                            </ul>

                            {{-- Sub Tab Content --}}
                            <div class="tab-content mt-2 pt-2 border-top">
                                <div class="tab-pane fade show active"
                                    id="company-{{ $company->company_id }}-calendar"
                                    role="tabpanel"
                                    aria-labelledby="company-{{ $company->company_id }}-calendar-tab">
                                    <div data-calender='@json($company->events)' data-id="{{ $company->company_id }}"></div>
                                </div>

                                <div class="tab-pane fade"
                                    id="company-{{ $company->company_id }}-list"
                                    role="tabpanel"
                                    aria-labelledby="company-{{ $company->company_id }}-list-tab">
                                    <div data-skeleton-table-set="@skeletonToken('business_company_holidays')_t_{{ $company->company_id }}"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info">No companies found.</div>
                    @endforelse
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
