{{-- Template: Folders Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Folders')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Folders</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer') }}">Developer</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer/skeleton') }}">Skeleton</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Folders</a></li>
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
                        <ul class="nav nav-links nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-folders" role="tablist">
                            <li class="nav-item"><a class="nav-link active" id="folders-tab" data-skl-action="b"
                                    data-bs-toggle="tab" href="#folders" role="tab" aria-controls="folders"
                                    aria-selected="true" data-token="@skeletonToken('central_skeleton_folders')_a" data-text="Add Folder"
                                    data-target="#folders-add-btn">Folders</a></li>
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="folders-add-btn">Default</button>
                        </div>
                    </div>

                    <div class="tab-content mt-2 pt-2 border-top">
                        <div class="tab-pane fade show active" id="folders" role="tabpanel" aria-labelledby="folders-tab">
                            <div data-skeleton-table-set="@skeletonToken('central_skeleton_folders')_t"></div>
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
