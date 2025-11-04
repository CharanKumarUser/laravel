{{-- Template: Assign Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Assign')
@push('styles')
@endpush
@push('scripts')
    <script>
        window.addEventListener("load", () => {
            window.general.pills();
        });
    </script>
@endpush

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
                        <div class="my-auto mb-2">
                            <h3 class="mb-1">Assign Shifts</h3>
                            <nav>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i
                                                class="ti ti-smart-home"></i></a></li>
                                    <li class="breadcrumb-item"><a href="{{ url('/shift-management') }}">Shift
                                            Management</a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Assign</a></li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                            <div class="live-time-container head-icons">
                                <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                                <div class="live-time"></div>
                            </div>
                            <div class="ms-2 head-icons">
                                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-original-title="Collapse" id="collapse-header"><i
                                        class="ti ti-chevrons-up"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    {{-- ************************************************************************************************
                    *                                                                                                  *
                    *                             >>> MODIFY THIS SECTION (START) <<<                                  *
                    *                                                                                                  *
                    ************************************************************************************************ --}}
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0">Assign shifts</h4>
                            <button class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_shift_assignments')_a"
                                data-type="add">Assign shifts
                            </button>
                        </div>
                        <div class="card-body">
                            <div data-skeleton-table-set="@skeletonToken('business_shift_assignments')_t" data-bulk="update"></div>
                        </div>
                    </div>
                    {{-- ************************************************************************************************
                    *                                                                                                  *
                    *                             >>> MODIFY THIS SECTION (END) <<<                                    *
                    *                                                                                                  *
                    ************************************************************************************************ --}}
                </div>
            </div>
        </div>
    </div>
@endsection
