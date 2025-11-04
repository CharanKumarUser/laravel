{{-- Template: Plans And Pricing Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Plans And Pricing')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Plans And Pricing</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/business-management') }}">Business Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Plans And Pricing</a></li>
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
                <!-- Tabs Navigation and Action Button -->
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                        id="roles-users" role="tablist">
                        <!-- Module Pricing Tab -->
                        <li class="nav-item">
                            <a class="nav-link" id="module-pricing-tab" data-skl-action="b" data-bs-toggle="tab"
                                href="#module-pricing" role="tab" aria-controls="module-pricing"
                                aria-selected="false" data-type="add" data-token="@skeletonToken('central_business_module_pricings')_a"
                                data-text="Add Module Pricing" data-target="#users-add-btn">
                                Module Pricing
                            </a>
                        </li>
                        <!-- Plans Tab -->
                        <li class="nav-item">
                            <a class="nav-link" id="business-plans-tab" data-skl-action="b" data-bs-toggle="tab"
                                href="#business-plans" role="tab" aria-controls="business-plans"
                                aria-selected="false" data-type="add" data-token="@skeletonToken('central_business_plans')_a"
                                data-text="Add Plans" data-target="#users-add-btn">
                                Plans
                            </a>
                        </li>
                    </ul>
                    <!-- Action Button -->
                    <div class="action-area">
                        <button class="btn btn-primary skeleton-popup" id="users-add-btn">Default</button>
                    </div>
                </div>
                <!-- Tabs Content -->
                <div class="tab-content mt-2 pt-2 border-top">
                    <!-- Module Pricing Content -->
                    <div class="tab-pane fade" id="module-pricing" role="tabpanel" aria-labelledby="module-pricing-tab">
                        <div data-skeleton-card-set="@skeletonToken('central_business_module_pricings')_c" data-placeholder="card|9" data-type="scroll"
                            data-limit="10" data-filters="sort|search|counts" data-container="row">
                        </div>
                    </div>
                    <!-- Plans Content -->
                    <div class="tab-pane fade" id="business-plans" role="tabpanel" aria-labelledby="business-plans-tab">
                        <div data-skeleton-table-set="@skeletonToken('central_business_plans')_t"></div>
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