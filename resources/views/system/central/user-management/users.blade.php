{{-- Template: Users Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Users')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Users</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/user-management') }}">User Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Users</a></li>
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
                    {{-- External Tabs (Admins, Users) --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action" id="outer-tabs" role="tablist">
                            @php
                                $tabs = [
                                    'admins' => 'Admins',
                                    'users'  => 'Users',
                                ];
                            @endphp
                            @foreach ($tabs as $id => $label)
                                <li class="nav-item">
                                    <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $id }}-tab"
                                    data-skl-action="b"
                                    data-bs-toggle="tab"
                                    href="#{{ $id }}"
                                    role="tab"
                                    aria-controls="{{ $id }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    data-type="add"
                                    data-token="@skeletonToken('open_um_users')_a"
                                    data-text="Add User"
                                    data-target="#outer-add-btn">
                                    {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Single Action Button --}}
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="outer-add-btn">Default</button>
                        </div>
                    </div>

                    <div class="tab-content mt-3" id="outer-tabs-content">
                        {{-- ================= Admins Section ================= --}}
                        <div class="tab-pane fade show active" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                            {{-- Internal Tabs for Admins --}}
                            <ul class="nav nav-tabs border-bottom-0" id="admins-inner-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="admins-card-tab" data-bs-toggle="tab" href="#admins-card" role="tab">
                                        <i class="ti ti-layout-grid"></i>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="admins-table-tab" data-bs-toggle="tab" href="#admins-table" role="tab">
                                        <i class="ti ti-list"></i>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content mt-2">
                                <div class="tab-pane fade show active" id="admins-card" role="tabpanel">
                                    <div data-skeleton-card-set="@skeletonToken('open_um_users')_c_ADMIN"
                                        data-placeholder="card|9"
                                        data-type="scroll"
                                        data-limit="10"
                                        data-filters="sort|search|counts"
                                        data-container="row"></div>
                                </div>
                                <div class="tab-pane fade" id="admins-table" role="tabpanel">
                                    <div data-skeleton-table-set="@skeletonToken('open_um_users')_t_ADMIN"></div>
                                </div>
                            </div>
                        </div>

                        {{-- ================= Users Section ================= --}}
                        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                            {{-- Internal Tabs for Users --}}
                            <ul class="nav nav-tabs" id="users-inner-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="users-card-tab" data-bs-toggle="tab" href="#users-card" role="tab">
                                        <i class="ti ti-layout-grid"></i>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="users-table-tab" data-bs-toggle="tab" href="#users-table" role="tab">
                                        <i class="ti ti-list"></i>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content mt-2">
                                <div class="tab-pane fade show active" id="users-card" role="tabpanel">
                                    <div data-skeleton-card-set="@skeletonToken('open_um_users')_c"
                                        data-placeholder="card|9"
                                        data-type="scroll"
                                        data-limit="10"
                                        data-filters="sort|search|counts"
                                        data-container="row">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="users-table" role="tabpanel">
                                    <div data-skeleton-table-set="@skeletonToken('open_um_users')_t"></div>
                                </div>
                            </div>
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
