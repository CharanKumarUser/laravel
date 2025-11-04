{{-- Template: Scope View Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Scope View')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">{{ $data['scope_info']['name'] ?? 'Scope View' }}</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/scope-management/scope') }}">Scope Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a
                                href="#">{{ $data['scope_info']['name'] ?? 'Scope View' }}</a></li>
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
                            id="skeleton-scopes" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" id="scopes_card-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#scope_card" role="tab" aria-controls="scopes_card" aria-selected="false"
                                    data-type="add"
                                    data-token="@skeletonToken('open_scopes')_a_{{ $data['scope_info']['parent_id'] ?? 'company' }}"
                                    data-text="Add {{ $data['scope_info']['group'] ?? '' }}"
                                    data-target="#scopes-add-btn">Card View</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="scopes-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#scope_table" role="tab" aria-controls="scopes" aria-selected="false"
                                    data-type="add"
                                    data-token="@skeletonToken('open_scopes')_a_{{ $data['scope_info']['parent_id'] ?? 'company' }}"
                                    data-text="Add {{ $data['scope_info']['group'] ?? '' }}"
                                    data-target="#scopes-add-btn">Table View</a>
                            </li>
                        </ul>
                        <div class="action-area">
                            <button class="btn btn-secondary skeleton-popup"
                                data-token="@skeletonToken('open_scope_users')_a_{{ $data['scope_id'] }}">Add User</button>
                            @if (!empty($data['scope_info']['group']) && $data['scope_info']['group'] != null)
                                <button class="btn btn-primary skeleton-popup" id="scopes-add-btn">Default</button>
                            @endif
                        </div>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        <div class="tab-pane fade" id="scope_card" role="tabpanel" aria-labelledby="scopes_card-tab">
                            <div data-skeleton-card-set="@skeletonToken('open_scope_view')_c_{{ $data['child_str'] }}"
                                data-placeholder="card|9" data-type="scroll" data-limit="10"
                                data-filters="sort|search|counts" data-container="row">
                            </div>
                        </div>
                        <div class="tab-pane fade" id="scope_table" role="tabpanel" aria-labelledby="scopes-tab">
                            <div data-skeleton-table-set="@skeletonToken('open_scope_view')_t_{{ $data['child_str'] }}"></div>
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
