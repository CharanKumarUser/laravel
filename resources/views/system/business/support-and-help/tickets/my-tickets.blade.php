{{-- Template: My Tickets Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'My Tickets')
@push('styles')
    {{-- Header Styles --}}
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
    <script>
        window.general.select();
    </script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">My Tickets</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support') }}">Support</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support/tickets') }}">Tickets</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">My Tickets</a></li>
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
                                            <h4>{{ $summary->total_tickets ?? 0}}</h4>
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
                                            <h4>{{ $summary->open_tickets ?? 0}}</h4>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">Tickets</h4>
                        <button class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_support_my_tickets')_a"
                            data-type="add">Raise
                            Tickets
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-xl-8">
                            <div data-skeleton-card-set="@skeletonToken('business_support_my_tickets')_c" data-placeholder="card|9" data-type="scroll"
                                    data-limit="10" data-filters="sort|search|counts" data-container="row"></div>
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
