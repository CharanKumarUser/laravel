{{-- Template: Skeleton Permissions --}}
@extends('layouts.system-app')

{{-- Section: Title - Sets the page title in the browser --}}
@section('title', 'Skeleton Permissions')

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
                <h3 class="mb-1">Skeleton Permissions</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/developer') }}">Developer</a></li>
                        <li class="breadcrumb-item active">Skeleton Permissions</li>
                    </ol>
                </nav>
            </div>

            {{-- Header Right Controls - Contains live time and collapse button --}}
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                {{-- Live Time Display - Shows current time with clock icon --}}
                <div class="live-time-container head-icons">
                    <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>

                {{-- Collapse Button - Toggles header collapse with tooltip --}}
                <div class="ms-2 head-icons">
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" title="Collapse" id="collapse-header"><i
                            class="ti ti-chevrons-up"></i></a>
                </div>
            </div>
        </div>

        {{-- Main Content Card - Contains tabs and tabbed content --}}
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    {{-- Tabs Navigation and Action Button - Displays tabs and action button --}}
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Tabs Navigation - Tab links for permissions --}}
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-permissions" role="tablist">
                            @php
                                $tabs = [
                                    'permissions' => ['Permissions', 'd-none'],
                                    'custom_permissions' => ['Custom Permissions', 'd-block'],
                                    'role_permissions' => ['Roles', 'd-none'],
                                    'user_permissions' => ['Users', 'd-none'],
                                ];
                            @endphp
                            @foreach ($tabs as $id => $label)
                                {{-- Tab Item - Navigation link for {{ $label }} tab --}}
                                <li class="nav-item"><a class="nav-link" id="{{ $id }}-tab" data-skl-action="b"
                                        data-bs-toggle="tab" href="#{{ $id }}" role="tab"
                                        aria-controls="{{ $id }}" aria-selected="false" data-type="add"
                                        data-token="@skeletonToken('central_skeleton_' . $id)_a" data-text="Add {{ $label[0] }}"
                                        data-target="#permissions-add-btn"
                                        data-class="{{ $label[1] }}">{{ $label[0] }}</a></li>
                            @endforeach
                        </ul>

                        {{-- Action Button - Default button for triggering actions --}}
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="permissions-add-btn">Default</button>
                        </div>
                    </div>

                    {{-- Tabs Content - Contains content for each tab --}}
                    <div class="tab-content mt-2 pt-2 border-top">
                        @foreach ($tabs as $id => $label)
                            {{-- Tab Pane - Content area for {{ $label }} tab --}}
                            <div class="tab-pane fade" id="{{ $id }}" role="tabpanel"
                                aria-labelledby="{{ $id }}-tab">
                                <div data-skeleton-table-set="@skeletonToken('central_skeleton_' . $id)_t"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
