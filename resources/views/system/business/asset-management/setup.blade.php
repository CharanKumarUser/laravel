{{-- Template: Setup Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Setup')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Setup</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/asset-management') }}">Asset Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Setup</a></li>
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
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************--}}
        <div class="card">
    <div class="card-body">
        {{-- Directly show module tabs without company --}}
        @php
            $tabs = [
                'assets' => '<i class="ti ti-box me-1 align-middle d-inline-block"></i> Assets',
                'asset_categories' => '<i class="ti ti-category me-1 align-middle d-inline-block"></i> Categories',
                'asset_maintenance' => '<i class="ti ti-tools me-1 align-middle d-inline-block"></i> Maintenance',
            ];
        @endphp

        <div class="row">
            <div class="col-xl-3 col-md-3 col-sm-12">
                <div class="card">
                    <div class="card-body p-0">
                        <ul class="nav flex-column nav-pills data-skl-action"
                            id="module-tabs"
                            role="tablist">
                            @foreach ($tabs as $id => $label)
                                <li class="nav-item bg-light border-bottom w-100">
                                    <a class="nav-link text-start m-2 shadow-none @if($loop->first) active @endif"
                                    id="{{ $id }}-tab"
                                    data-bs-toggle="tab"
                                    data-skl-action="b"
                                    href="#{{ $id }}"
                                    role="tab"
                                    aria-controls="{{ $id }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    data-type="add"
                                    data-token="@skeletonToken('business_' . $id)_a"
                                    data-text="Add {{ strip_tags($label) }}"
                                    data-target="#setup-add-btn">
                                        {!! $label !!}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="accordion" id="companyAccordion">
                    @forelse ($data['companies'] as $index => $company)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-{{ $company['company_id'] }}">
                                <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse-{{ $company['company_id'] }}" 
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                        aria-controls="collapse-{{ $company['company_id'] }}">
                                    <h5 class="mb-0">
                                        <i class="ti ti-building me-2 text-primary"></i>{{ $company['company_name'] }}
                                    </h5>
                                </button>
                            </h2>

                            <div id="collapse-{{ $company['company_id'] }}" 
                                class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                aria-labelledby="heading-{{ $company['company_id'] }}" 
                                data-bs-parent="#companyAccordion">
                                <div class="accordion-body">

                                    {{-- 🧱 Assets Count --}}
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="ti ti-box me-2 text-primary"></i>Assets</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-database me-2 text-primary"></i>Total</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['assets']->total ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-circle-check me-2 text-success"></i>Available</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['assets']->available ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-user-check me-2 text-warning"></i>Assigned</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['assets']->assigned ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-tools me-2 text-info"></i>Under Maintenance</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['assets']->under_maintenance ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between p-3">
                                                <span><i class="ti ti-archive me-2 text-secondary"></i>Retired</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['assets']->retired ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 🗂️ Asset Categories --}}
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="ti ti-category me-2 text-success"></i>Asset Categories</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            @forelse ($company['categories'] as $category)
                                                <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                    <span><i class="ti ti-folder me-2 text-muted"></i>{{ $category->name }}</span>
                                                    <span class="badge bg-dark rounded-pill">{{ $category->count }}</span>
                                                </div>
                                            @empty
                                                <div class="p-3 text-muted">No Categories Found</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- 🧰 Maintenance Counts --}}
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="ti ti-wrench me-2 text-danger"></i>Asset Maintenance</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-database me-2 text-primary"></i>Total</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['maintenance']->total ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-calendar-time me-2 text-info"></i>Scheduled</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['maintenance']->scheduled ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-progress me-2 text-warning"></i>In Progress</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['maintenance']->in_progress ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                                                <span><i class="ti ti-checks me-2 text-success"></i>Completed</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['maintenance']->completed ?? 0 }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between p-3">
                                                <span><i class="ti ti-alert-triangle me-2 text-danger"></i>Overdue</span>
                                                <span class="badge bg-dark rounded-pill">{{ $company['counts']['maintenance']->overdue ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-5">
                            <img src="{{ asset('errors/empty.svg') }}" alt="No Companies" class="img-fluid w-25 mb-3">
                            <h6 class="fw-bold mb-2">No company details available</h6>
                            <p class="text-muted">Please add a company to view asset management data.</p>
                            <a class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_companies')_a">
                                <i class="ti ti-plus me-1"></i>Add Company
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Tab Content --}}
            <div class="col-xl-9 col-md-9 col-sm-12 order-2 order-xl-1">
                <div class="tab-content mt-2 pt-2 border-top">
                    @foreach ($tabs as $id => $label)
                        <div class="tab-pane fade @if($loop->first) show active @endif"
                            id="{{ $id }}"
                            role="tabpanel"
                            aria-labelledby="{{ $id }}-tab">

                            {{-- Assets Nested Tabs --}}
                            @if($id === 'assets')
                                {{-- Assets Tabs + Action Button --}}
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <ul class="nav nav-tabs data-skl-action" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active btn-outline-primary shadow-none"
                                            id="card-tab"
                                            data-bs-toggle="tab"
                                            data-skl-action="b"
                                            data-bs-target="#card-view"
                                            type="button" role="tab"
                                            aria-controls="card-view"
                                            aria-selected="true">
                                                <i class="ti ti-layout-grid"></i>
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link btn-outline-primary shadow-none"
                                            id="table-tab"
                                            data-bs-toggle="tab"
                                            data-skl-action="b"
                                            data-bs-target="#table-view"
                                            type="button" role="tab"
                                            aria-controls="table-view"
                                            aria-selected="false">
                                                <i class="ti ti-list"></i>
                                            </a>
                                        </li>
                                    </ul>

                                    {{-- Action Button --}}
                                    <button class="btn btn-primary skeleton-popup"
                                            id="setup-add-btn"
                                            data-token="@skeletonToken('business_assets')_a">
                                        Add Asset
                                    </button>
                                </div>

                                {{-- Assets Views --}}
                                <div class="tab-content">
                                    <div class="tab-pane fade show active"
                                        id="card-view"
                                        role="tabpanel"
                                        aria-labelledby="card-tab">
                                        <div data-skeleton-card-set="@skeletonToken('business_assets')_c"
                                            data-placeholder="card|9"
                                            data-type="scroll"
                                            data-limit="10"
                                            data-filters="sort|search"
                                            data-container="row"></div>
                                    </div>
                                    <div class="tab-pane fade"
                                        id="table-view"
                                        role="tabpanel"
                                        aria-labelledby="table-tab">
                                        <div data-skeleton-table-set="@skeletonToken('business_assets')_t"></div>
                                    </div>
                                </div>
                            @else
                                {{-- Other Tabs + Action Button --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="m-0">{!! $label !!}</h6>
                                    <button class="btn btn-primary skeleton-popup"
                                            id="setup-add-btn"
                                            data-token="@skeletonToken('business_' . $id)_a">
                                        Add {{ strip_tags($label) }}
                                    </button>
                                </div>
                                <div data-skeleton-table-set="@skeletonToken('business_' . $id)_t"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>







        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************--}}
    </div>
</div>
@endsection