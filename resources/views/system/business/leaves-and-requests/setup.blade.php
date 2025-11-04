{{-- Template: Types Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Setup')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Types</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/leave-management') }}">Request And Leaves</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Types</a></li>
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
                            id="skeleton-setup" role="tablist">
                            
                            <!-- Setup Tab -->
                            <li class="nav-item">
                                <a class="nav-link active" id="setup-tab" data-skl-action="b"
                                data-bs-toggle="tab" href="#setup" role="tab"
                                aria-controls="setup" aria-selected="true" data-type="add"
                                data-token="@skeletonToken('business_request_types')_a" 
                                data-text="Add Request Type"
                                data-target="#leave_type_add_btn">
                                Request Types
                                </a>
                            </li>
                            <!-- Assign Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="assign-tab" data-skl-action="b"
                                data-bs-toggle="tab" href="#assign" role="tab"
                                aria-controls="assign" aria-selected="false" data-type="add"
                                data-token="@skeletonToken('business_assign_request_types')_a" 
                                data-text="Add Assign Request Type"
                                data-target="#assign_type_add_btn">
                                Assign Request Types
                                </a>
                            </li>

                            <!-- Settings Tab -->
                            {{-- <li class="nav-item">
                                <a class="nav-link" id="settings-tab" data-skl-action="b"
                                data-bs-toggle="tab" href="#settings" role="tab"
                                aria-controls="settings" aria-selected="false">
                                Settings
                                </a>
                            </li> --}}
                        </ul>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        <!-- Setup Tab Content -->
                        <div class="tab-pane fade show active" id="setup" role="tabpanel" aria-labelledby="setup-tab">
                            <div class="d-flex justify-content-end mb-3">
                                <div class="action-area">
                                    <button class="btn btn-primary skeleton-popup" id="leave_type_add_btn">Add Request Type</button>
                                </div>
                            </div>
                            <div data-skeleton-table-set="@skeletonToken('business_request_types')_t" data-bulk="update"></div>
                        </div>
                        <!-- Assign Tab Content -->
                        <div class="tab-pane fade" id="assign" role="tabpanel" aria-labelledby="assign-tab">
                            <div class="d-flex justify-content-end mb-3">
                                <div class="action-area">
                                    <button class="btn btn-primary skeleton-popup" id="assign_type_add_btn">Add Assign Request Type</button>
                                </div>
                            </div>
                            <div data-skeleton-table-set="@skeletonToken('business_assign_request_types')_t" data-bulk="update"></div>
                        </div>

                        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <h4>Settings</h4>
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