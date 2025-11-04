{{-- Template: Schedule Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Schedule')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Schedule</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/shift-management') }}">Shift Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Schedule</a></li>
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
            {{-- Outer Tabs: Shifts and Schedule (styled like modules page) --}}
            <div class="container-fluid p-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="shifts-schedule-tabs" role="tablist">
                            <li class="nav-item"><a class="nav-link" id="shifts-tab" data-skl-action="b"
                                    data-bs-toggle="tab" href="#shifts" role="tab" aria-controls="shifts"
                                    aria-selected="false" data-type="add" data-token="@skeletonToken('business_shifts')_a"
                                    data-text="Add Shift" data-target="#outer-add-btn">Shifts</a></li>
                            <li class="nav-item"><a class="nav-link" id="schedule-tab" data-skl-action="b"
                                    data-bs-toggle="tab" href="#schedule" role="tab" aria-controls="schedule"
                                    aria-selected="false" data-type="add" data-token="@skeletonToken('business_shift_schedule')_a"
                                    data-text="Add Schedule" data-target="#outer-add-btn">Schedule</a></li>
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="outer-add-btn" data-type="add"
                                data-token="@skeletonToken('business_shifts')_a">
                                <i class="fa-solid fa-plus me-2"></i>Add Shift
                            </button>
                        </div>
                    </div>
                    <div class="tab-content mt-0 pt-2">
                        {{-- Shifts Tab Pane --}}
                        <div class="tab-pane fade" id="shifts" role="tabpanel" aria-labelledby="shifts-tab">
                            <div class="card">
                                <div class="card-body">
                                    {{-- Tabs Navigation and Action Button - Displays tabs and action button --}}
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                                            id="shift-users" role="tablist">
                                            {{-- Card View Tab (first) --}}
                                            <li class="nav-item">
                                                <a class="nav-link" id="shift-card-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#shift-card-view" role="tab"
                                                    aria-controls="shift-card-view" aria-selected="false" data-type="add"
                                                    data-token="@skeletonToken('business_shifts')_a" data-text="Add Shift"
                                                    data-target="#outer-add-btn">
                                                    <i class="ti ti-layout-grid"></i>
                                                </a>
                                            </li>
                                            {{-- Table View Tab (second) --}}
                                            <li class="nav-item">
                                                <a class="nav-link" id="shift-table-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#shift-table-view" role="tab"
                                                    aria-controls="shift-table-view" aria-selected="false" data-type="add"
                                                    data-token="@skeletonToken('business_shifts')_a" data-text="Add Shift"
                                                    data-target="#outer-add-btn">
                                                    <i class="ti ti-list"></i>
                                                </a>
                                            </li>
                                        </ul>
                                        {{-- Action Button removed to avoid duplicates; outer button is used --}}
                                    </div>
                                    {{-- Tabs Content - Contains content for each tab --}}
                                    <div class="tab-content mt-2 pt-2 border-top">
                                        {{-- Card View Pane --}}
                                        <div class="tab-pane fade" id="shift-card-view" role="tabpanel"
                                            aria-labelledby="shift-card-tab">
                                            <div data-skeleton-card-set="@skeletonToken('business_shifts')_c" data-placeholder="card|9"
                                                data-type="scroll" data-limit="10" data-filters="sort|search|counts"
                                                data-container="row"></div>
                                        </div>
                                        {{-- Table View Pane --}}
                                        <div class="tab-pane fade" id="shift-table-view" role="tabpanel"
                                            aria-labelledby="shift-table-tab">
                                            <div data-skeleton-table-set="@skeletonToken('business_shifts')_t"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Schedule Tab Pane --}}
                        <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                            <div class="card">
                                <div class="card-body">
                                    {{-- Tabs Navigation and Action Button - Displays tabs and action button --}}
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                                            id="schedule-inner-tabs" role="tablist">
                                            {{-- Card View Tab (first) --}}
                                            <li class="nav-item">
                                                <a class="nav-link" id="schedule-card-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#schedule-card-view" role="tab"
                                                    aria-controls="schedule-card-view" aria-selected="false"
                                                    data-type="add" data-token="@skeletonToken('business_shift_schedule')_a"
                                                    data-text="Add Schedule" data-target="#outer-add-btn">
                                                    <i class="ti ti-layout-grid"></i>
                                                </a>
                                            </li>
                                            {{-- Table View Tab (second) --}}
                                            <li class="nav-item">
                                                <a class="nav-link" id="schedule-table-tab" data-skl-action="b"
                                                    data-bs-toggle="tab" href="#schedule-table-view" role="tab"
                                                    aria-controls="schedule-table-view" aria-selected="false"
                                                    data-type="add" data-token="@skeletonToken('business_shift_schedule')_a"
                                                    data-text="Add Schedule" data-target="#outer-add-btn"> <i
                                                        class="ti ti-list"></i>
                                                </a>
                                            </li>
                                        </ul>
                                        {{-- Action Button removed to avoid duplicates; outer button is used --}}
                                    </div>
                                    {{-- Tabs Content - Contains content for each tab --}}
                                    <div class="tab-content mt-2 pt-2 border-top">
                                        {{-- Card View Pane --}}
                                        <div class="tab-pane fade" id="schedule-card-view" role="tabpanel"
                                            aria-labelledby="schedule-card-tab">
                                            <div data-skeleton-card-set="@skeletonToken('business_shift_schedule')_c" data-placeholder="card|9"
                                                data-type="scroll" data-limit="10" data-filters="sort|search|counts"
                                                data-container="row"></div>
                                        </div>
                                        {{-- Table View Pane --}}
                                        <div class="tab-pane fade" id="schedule-table-view" role="tabpanel"
                                            aria-labelledby="schedule-table-tab">
                                            <div data-skeleton-table-set="@skeletonToken('business_shift_schedule')_t"></div>
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
