{{-- Template: Skeleton Modules --}}
@extends('layouts.system-app')

{{-- Section: Title - Sets the page title in the browser --}}
@section('title', 'Skeleton Modules')

{{-- Section: Top Style - Placeholder for custom CSS (currently empty) --}}
@section('top-style')
@endsection

{{-- Section: Bottom Script - Placeholder for custom JavaScript (currently empty) --}}
@section('bottom-script')
@endsection

{{-- Section: Content - Main content area with breadcrumb, tabs, and tabbed content --}}
@section('content')
<div class="content">
    {{-- Page Header and Breadcrumb - Displays page title and navigation --}}
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        {{-- Page Title and Navigation - Contains breadcrumb links --}}
        <div class="my-auto mb-2">
            <h3 class="mb-1">Skeleton Modules</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/developer') }}">Developer</a></li>
                    <li class="breadcrumb-item active">Skeleton Modules</li>
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
                <a href="javascript:void(0);" data-bs-toggle="tooltip" title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action" id="skeleton-modules" role="tablist">
                        @php
                            $tabs = [
                                'modules' => 'Modules',
                                'sections' => 'Sections',
                                'items' => 'Items'
                            ];
                        @endphp
                        @foreach($tabs as $id => $label)
                            <li class="nav-item"><a class="nav-link" id="{{ $id }}-tab" data-skl-action="b" data-bs-toggle="tab" href="#{{ $id }}" role="tab" aria-controls="{{ $id }}" aria-selected="false" data-type="add" data-token="@skeletonToken('central_skeleton_' . $id)_a" data-text="Add {{ $label }}" data-target="#modules-add-btn">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                    <div class="action-area">
                        <button class="btn btn-primary skeleton-popup" id="modules-add-btn">Default</button>
                    </div>
                </div>
                <div class="tab-content mt-2 pt-2 border-top">
                    @foreach($tabs as $id => $label)
                        <div class="tab-pane fade" id="{{ $id }}" role="tabpanel" aria-labelledby="{{ $id }}-tab">
                            <div data-skeleton-table-set="@skeletonToken('central_skeleton_' . $id)_t" data-bulk="update"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
           {{-- <input
            id="teamMembers"
            data-pills="user" 
            data-pills-list='[
                {"id":"u1","value":"Alice","avatar":"https://i.pravatar.cc/30?img=1","role":"Designer","group":"UI Team"},
                {"id":"u2","value":"Bob","avatar":"https://i.pravatar.cc/30?img=2","role":"Developer","group":"Backend Team"},
                {"id":"u3","value":"Charlie","avatar":"https://i.pravatar.cc/30?img=3","role":"Manager","group":"Management"}
            ]'
            data-max-tags="3"
            required
            /> --}}
            <!-- Input field for Tagify -->
<input id="user-single" data-select="dropdown" data-options='[
  {"value": "1", "view": "John Doe"},
  {"value": "2", "view": "Jane Smith"},
  {"value": "3", "view": "Alice Johnson"}
]' data-value="2" placeholder="Select a user" />

    </div>
</div>
@endsection