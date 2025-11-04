{{-- Template: Skeleton Tokens - Displays interface for managing tokens, extends base system-app layout --}}
@extends('layouts.system-app')

{{-- Section: Title - Sets the page title in the browser --}}
@section('title', 'Skeleton Tokens')

{{-- Section: Top Style - Placeholder for custom CSS (currently empty) --}}
@push('styles')
@endpush

{{-- Section: Bottom Script - Placeholder for custom JavaScript (currently empty) --}}
@push('scripts')
@endpush

{{-- Section: Content - Main content area with breadcrumb, tabs, and tabbed content --}}
@section('content')
    <div class="content">
        {{-- Page Header and Breadcrumb - Displays page title and navigation --}}
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            {{-- Page Title and Navigation - Contains breadcrumb links --}}
            <div class="my-auto mb-2">
                <h3 class="mb-1">Skeleton Tokens</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/developer') }}">Developer</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Skeleton Tokens</li>
                    </ol>
                </nav>
            </div>
            <div></div>
            {{-- Header Right Controls - Contains live time and collapse button --}}
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                {{-- Live Time Display - Shows current time with clock icon --}}
                <div class="mb-2">
                    <div class="live-time-container head-icons">
                        <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                        <div class="live-time"></div>
                    </div>
                </div>
                {{-- Collapse Button - Toggles header collapse with tooltip --}}
                <div class="ms-2 head-icons">
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
                </div>
            </div>
        </div>

        {{-- Main Content Card - Contains tabs and tabbed content --}}
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-tokens" role="tablist">
                            @php
                                $tabs = [
                                    'tokens' => 'Tokens',
                                    'dropdowns' => 'Dropdowns',
                                    'restrictions' => 'Restrictions'
                                ];
                            @endphp
                            @foreach ($tabs as $id => $label)
                                <li class="nav-item"><a class="nav-link" id="{{ $id }}-tab" data-skl-action="b"
                                        data-bs-toggle="tab" href="#{{ $id }}" role="tab"
                                        aria-controls="{{ $id }}" aria-selected="false" data-type="add"
                                        data-token="@skeletonToken('central_skeleton_' . $id)_a" data-text="Add {{ $label }}"
                                        data-target="#tokens-add-btn">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="tokens-add-btn">Default</button>
                        </div>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        @foreach ($tabs as $id => $label)
                            <div class="tab-pane fade" id="{{ $id }}" role="tabpanel"
                                aria-labelledby="{{ $id }}-tab">
                                <div data-skeleton-table-set="@skeletonToken('central_skeleton_' . $id)_t" data-bulk="update"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div data-template-id="TMp-121sfskkj_iowe22" style="width: 100%; height: 100dvh;"></div> --}}
    {{-- <div data-template-id="temp-1212jsd" data-template-name="template_data" style="width: 100%; height: 100dvh;"></div><br>
        <div data-template-id="web-template" data-template-name="custom_template_data" style="width: 100%; height: 100dvh;"></div> --}}
    {{-- <div data-editor-id="editor-simple" data-editor-name="editor_content"></div>
<div data-editor-id="editor-complex" data-editor-name="custom_editor_data"></div> --}}
    {{-- <div data-form-builder-id="form-empty" data-form-builder-name="form_data" data-form-builder-fields="text|texarea|select"></div>
<div data-form-builder-id="form-complex" data-form-builder-name="custom_form_data"></div> --}}
    {{-- <div data-skeleton-card-set="@skeletonToken('central_skeleton_tokens')_c" data-placeholder="card|9" data-type="paging" data-limit="10" data-filters="sort|date|search|counts" data-container="row"></div> --}}

@endsection
