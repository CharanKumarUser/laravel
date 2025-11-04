{{-- Template: Assign Asset Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Assign Asset')
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
                <h3 class="mb-1">Assign Asset</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/asset-management') }}">Asset Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Assign Asset</a></li>
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
                    {{-- Assign Asset Header --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="m-0">
                            <i class="ti ti-exchange me-1 align-middle d-inline-block"></i> Assign Asset
                        </h6>
                        <button class="btn btn-primary skeleton-popup"
                                data-token="@skeletonToken('business_asset_assignment')_a"
                                data-type="add">
                            Assign Asset
                        </button>
                    </div>

                    {{-- Assign Asset Table --}}
                    <div data-skeleton-table-set="@skeletonToken('business_asset_assignment')_t"
                        data-bulk="update">
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
