{{-- Template: Business Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Business')
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
                <h3 class="mb-1">Business</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/business-management') }}">Business Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Business</a></li>
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
                        {{-- Tabs Navigation --}}
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-business" role="tablist">
                            <!-- Onboard Tab -->
                            <li class="nav-item">
                                <a class="nav-link active" id="onboard-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#onboard" role="tab" aria-controls="onboard" aria-selected="true"
                                    data-type="add" data-token="@skeletonToken('central_onboard_business')_a" data-class="d-block"
                                    data-text="Onboard Admin" data-target="#business-add-btn">
                                    Onboard
                                </a>
                            </li>
                            <!-- Business Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="business-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#business" role="tab" aria-controls="business" aria-selected="false"
                                    data-class="d-none" data-target="#business-add-btn">
                                    Business
                                </a>
                            </li>
                            <!-- Devices Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="devices-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#devices" role="tab" aria-controls="devices" aria-selected="false"
                                    data-class="d-block" data-target="#business-add-btn" data-token="@skeletonToken('central_business_devices')_a"
                                    data-text="Add Device">
                                    Devices
                                </a>
                            </li>
                        </ul>

                        {{-- Action Button --}}
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup d-block" id="business-add-btn">Onboard
                                Admin</button>
                        </div>
                    </div>

                    {{-- Tab Content --}}
                    <div class="tab-content mt-2 pt-2 border-top">
                        {{-- Onboard Content --}}
                        <div class="tab-pane fade show active" id="onboard" role="tabpanel" aria-labelledby="onboard-tab">
                            <div data-skeleton-card-set="@skeletonToken('central_onboard_business')_c" data-placeholder="card|9" data-type="scroll"
                                data-limit="10" data-filters="sort|search|counts" data-container="row">
                            </div>
                        </div>
                        {{-- Business Content --}}
                        <div class="tab-pane fade" id="business" role="tabpanel" aria-labelledby="business-tab">
                            <div data-skeleton-card-set="@skeletonToken('central_businesses')_c" data-placeholder="card|9" data-type="scroll"
                                data-limit="10" data-filters="sort|search|counts" data-container="row">
                            </div>
                        </div>
                        {{-- Devices Content --}}
                        <div class="tab-pane fade" id="devices" role="tabpanel" aria-labelledby="devices-tab">
                            <div data-skeleton-table-set="@skeletonToken('central_business_devices')_t" data-bulk="update"></div>
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
