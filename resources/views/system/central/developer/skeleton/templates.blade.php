{{-- Template: Templates Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Templates')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Templates</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer') }}">Developer</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer/skeleton') }}">Skeleton</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Templates</a></li>
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
                    {{-- Tabs Navigation and Action Button - Displays tabs and action button --}}
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Tabs Navigation - Tab links for email and whatsapp --}}
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-templates" role="tablist">
                            @php
                                $tabs = [
                                    'email' => 'Email',
                                    'whatsapp' => 'Whatsapp',
                                ];
                            @endphp
                            @foreach ($tabs as $id => $label)
                                {{-- Tab Item - Navigation link for {{ $label }} tab --}}
                                <li class="nav-item"><a class="nav-link" id="{{ $id }}-tab" data-skl-action="b"
                                        data-bs-toggle="tab" href="#{{ $id }}" role="tab"
                                        aria-controls="{{ $id }}" aria-selected="false" data-type="add"
                                        data-token="@skeletonToken('central_skeleton_templates')_a_{{ $id }}"
                                        data-text="Add {{ $label }}"
                                        data-target="#templates-add-btn">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                        {{-- Action Button - Default button for triggering actions --}}
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="templates-add-btn">Default</button>
                        </div>
                    </div>
                    {{-- Tabs Content - Contains content for each tab --}}
                    <div class="tab-content mt-2 pt-2 border-top">
                        @foreach ($tabs as $id => $label)
                            {{-- Tab Pane - Content area for {{ $label }} tab --}}
                            <div class="tab-pane fade" id="{{ $id }}" role="tabpanel"
                                aria-labelledby="{{ $id }}-tab">
                                <div data-skeleton-table-set="@skeletonToken('central_skeleton_templates')_t_{{ $id }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************ --}}
        </div>
    </div>
@endsection
