{{-- Template: Geo Face Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Geo Face')
@push('styles')
@vite(['resources/css/system/realtime/smart.css'])
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
@vite(['resources/js/system/realtime/smart.js'])
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Geo Face</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/smart-presence') }}">Smart Presence</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/smart-presence/setup') }}">Setup</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Geo Face</a></li>
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
                            id="skeleton-tabs" role="tablist">
                            <!-- Location Tab -->
                            <li class="nav-item">
                                <a class="nav-link active" id="location-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#location" role="tab" aria-controls="location" aria-selected="true"
                                    data-type="add" data-token="@skeletonToken('business_smart_geo_location')_a" data-text="Add Location"
                                    data-target="#tabs-add-btn" data-class="d-block">
                                    Locations
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="enroll-face-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#enroll-face" role="tab" aria-controls="enroll-face" aria-selected="true"
                                    data-type="add" data-token="@skeletonToken('business_smart_enroll_face')_a" data-text="Enroll Face"
                                    data-target="#tabs-add-btn" data-class="d-block">
                                    Enroll Face
                                </a>
                            </li>
                            {{-- <!-- Settings Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="settings-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#settings" role="tab" aria-controls="settings" aria-selected="false" data-class="d-none">
                                    Settings
                                </a>
                            </li> --}}
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="tabs-add-btn">Default</button>
                        </div>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        <!-- Location Tab Content -->
                        <div class="tab-pane fade show active" id="location" role="tabpanel"
                            aria-labelledby="location-tab">
                            <div data-skeleton-table-set="@skeletonToken('business_smart_geo_location')_t"></div>
                        </div>
                        <!-- Enroll Face -->
                        <div class="tab-pane fade" id="enroll-face" role="tabpanel" aria-labelledby="enroll-face-tab">
                            <div data-skeleton-table-set="@skeletonToken('business_smart_enroll_face')_t"></div>
                        </div>
                        {{-- <!-- Settings Tab Content -->
                        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <div data-skeleton-table-set="@skeletonToken('business_smart_geo_face')_t"></div>
                        </div> --}}
                        {{-- <div id="smart-match" data-container-size="100%*300px" data-name="smart_match" data-maps-api={{ env('GOOGLE_MAPS_API') }}></div>
                            <div id="result-notes" class="alert"></div> --}}
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
