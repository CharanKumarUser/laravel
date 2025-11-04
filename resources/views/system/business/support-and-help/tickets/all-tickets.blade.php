{{-- Template: All Tickets Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'All Tickets')
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
                <h3 class="mb-1">All Tickets</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support') }}">Support</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support/tickets') }}">Tickets</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">All Tickets</a></li>
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
                $summary= $data['summary'] ?? [];
                $categories= $data['categories'] ?? [];
                $priorities= $data['priorities'] ?? [];
            @endphp
            <div class="row">
                <!-- New Tickets -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-primary rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-primary-transparent ">
                                                <i class="ti ti-ticket fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Total Tickets</p>
                                            <h4>{{ $summary->total_tickets ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Open Tickets -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-purple rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-transparent-purple">
                                                <i class="ti ti-folder-open fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Open Tickets</p>
                                            <h4>{{ $summary->open_tickets ?? 0  }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solved Tickets -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-success rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-success-transparent">
                                                <i class="ti ti-checks fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Solved Tickets</p>
                                            <h4>{{ $summary->solved_tickets ?? 0 }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Tickets -->
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div>
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="border border-dashed border-info rounded-circle d-inline-flex align-items-center justify-content-center p-1">
                                            <span class="avatar avatar-lg avatar-rounded bg-info-transparent">
                                                <i class="ti ti-progress-alert fs-20"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="fw-medium fs-12 mb-1">Pending Tickets</p>
                                            <h4>{{ $summary->pending_tickets ?? 0 }}</h4>
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
                        <div class="col-xl-8">
                            <!-- Tab Navigation -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action rounded" id="ticketTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active btn-outline-primary shadow-none" id="all-tickets-tab" data-bs-toggle="tab" data-bs-target="#all-tickets" type="button" role="tab" aria-controls="all-tickets" aria-selected="true"><i class="ti ti-layout-grid"></i></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-outline-primary shadow-none" id="open-tickets-tab" data-bs-toggle="tab" data-bs-target="#open-tickets" type="button" role="tab" aria-controls="open-tickets" aria-selected="false"><i class="ti ti-list"></i></button>
                                    </li>
                                </ul>
                                <div class="action-area">
                                    <button class="btn btn-primary skeleton-popup sf-12" id="asset-add-btn"
                                        data-token="@skeletonToken('business_support_tickets')_a" data-type="add">
                                        <i class="fa-solid fa-plus me-2"></i>Raise Ticket
                                    </button>
                                </div>
                            </div>
                            <!-- Tab Content -->
                            <div class="tab-content" id="ticketTabsContent">
                                <div class="tab-pane fade show active" id="all-tickets" role="tabpanel" aria-labelledby="all-tickets-tab">
                                    <div data-skeleton-card-set="@skeletonToken('business_support_tickets')_c" data-placeholder="card|9" data-type="scroll"
                                        data-limit="10" data-filters="sort|search|counts" data-container="row"></div>
                                </div>
                                <div class="tab-pane fade" id="open-tickets" role="tabpanel" aria-labelledby="open-tickets-tab">
                                    <div data-skeleton-table-set="@skeletonToken('business_support_tickets')_t"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Ticket Categories</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="d-flex flex-column">
                                        @foreach ($categories as $category)
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <a href="javascript:void(0);">{{$category->issue_category ?? ''}}</a>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-xs bg-dark rounded-circle">{{$category->total ?? 'N/A'}}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5>Priority Tickets</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="d-flex flex-column">
                                        @foreach ($priorities as $priority)
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span class="d-flex align-items-center">
                                                    <span class="badge rounded-circle bg-{{ $priority['color'] }} me-2">&nbsp;</span>
                                                    {{ $priority['priority'] }}
                                                </span>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-xs bg-dark rounded-circle">{{ $priority['count'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
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
