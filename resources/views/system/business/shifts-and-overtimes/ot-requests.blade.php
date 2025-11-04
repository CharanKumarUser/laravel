{{-- OT Requests --}}
@extends('layouts.system-app')
@section('title', 'Overtime Requests')
@push('styles')
@endpush
@push('pre-scripts')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Overtime Requests</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/shifts-and-overtimes') }}">Shifts And Overtimes</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Overtime Requests</a></li>
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
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">My Overtime Requests</h4>
                    <button class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_ot_requests')_a" data-type="add">New OT
                        Request</button>
                </div>
                <div class="card-body">
                    <div data-skeleton-table-set="@skeletonToken('business_ot_requests')_t"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
