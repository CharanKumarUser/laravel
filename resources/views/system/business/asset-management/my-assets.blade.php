{{-- Template: My Assets Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'My Assets')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">My Assets</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/asset-management') }}">Asset Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">My Assets</a></li>
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
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="assets-tabs" role="tablist">
                            {{-- Tab Item - Navigation link for Assets Table tab --}}
                            <li class="nav-item">
                                <a class="nav-link active" id="assets-table-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#assetstable" role="tab" aria-controls="assets-table" aria-selected="true"
                                    data-type="add">
                                    <i class="ti ti-list"></i>
                                </a>
                            </li>
                            {{-- Tab Item - Navigation link for Assets Card tab --}}
                            <li class="nav-item">
                                <a class="nav-link" id="assets-card-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#assetscard" role="tab" aria-controls="assets-card" aria-selected="false"
                                    data-type="add">
                                    <i class="ti ti-layout-grid"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    {{-- Tabs Content - Contains content for each tab --}}
                    <div class="tab-content mt-2 pt-2 border-top">
                        {{-- Tab Pane - Content area for Assets Table tab --}}
                        <div class="tab-pane fade show active" id="assetstable" role="tabpanel"
                            aria-labelledby="assets-table-tab">
                            <div data-skeleton-table-set="@skeletonToken('business_my_assets')_t" data-bulk="update"></div>
                        </div>
                        {{-- Tab Pane - Content area for Assets Card tab --}}
                        <div class="tab-pane fade" id="assetscard" role="tabpanel" aria-labelledby="assets-card-tab">
                            <div data-skeleton-card-set="@skeletonToken('business_my_assets')_c" data-placeholder="card|9"
                                data-type="scroll" data-limit="10" data-filters="sort|search" data-container="row">
                            </div>
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