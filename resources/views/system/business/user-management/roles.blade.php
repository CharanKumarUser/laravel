{{-- Template: Roles Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Roles')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Roles</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/user-management') }}">User Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Roles</a></li>
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
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************--}}
        <div class="card">
                <div class="card-body">
                    {{-- Tabs Navigation and Action Button - Displays tabs and action button --}}
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Tabs Navigation - Tab links for Roles (table & card view) --}}
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="roles-tabs" role="tablist">
                            {{-- Tab Item - Roles --}}
                            <li class="nav-item">
                                <a class="nav-link" id="roles-view-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#card-view" role="tab" aria-controls="roles" aria-selected="false"
                                    data-type="add" data-token="@skeletonToken('open_um_roles')_a" data-text="Add Role"
                                    data-target="#roles-add-btn">
                                    <i class="ti ti-layout-grid"></i>
                                </a>
                            </li>
                            {{-- Tab Item - Card View --}}
                            <li class="nav-item">
                                <a class="nav-link" id="role-assign-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#table-view" role="tab" aria-controls="roles" aria-selected="false"
                                    data-type="add" data-token="@skeletonToken('open_um_roles')_a" data-text="Add Role"
                                    data-target="#roles-add-btn">
                                    <i class="ti ti-list"></i>
                                </a>
                            </li>
                        </ul>
                        {{-- Action Button - Default button for triggering actions --}}
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="roles-add-btn">Default</button>
                        </div>
                    </div>
                    {{-- Tabs Content - Contains content for each tab --}}
                    <div class="tab-content mt-2 pt-2 border-top">
                        {{-- Tab Pane - Table View --}}
                        <div class="tab-pane fade" id="card-view" role="tabpanel" aria-labelledby="roles-view-tab">
                            <div data-skeleton-card-set="@skeletonToken('open_um_roles')_c" data-placeholder="card|9" data-type="scroll"
                                data-limit="10" data-filters="sort|search|counts" data-container="row"></div>
                        </div>
                        {{-- Tab Pane - Card View --}}
                        <div class="tab-pane fade" id="table-view" role="tabpanel" aria-labelledby="role-assign-tab">
                            <div data-skeleton-table-set="@skeletonToken('open_um_roles')_t"></div>
                        </div>
                    </div>
                </div>
        </div>
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************--}}
    </div>
</div>
@endsection