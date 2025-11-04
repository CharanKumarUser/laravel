{{-- Template: Business Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Business')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Business</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer') }}">Developer</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/developer/skeleton') }}">Skeleton</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Business</a></li>
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
                    <!-- Tabs Navigation and Action Button -->
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-business" role="tablist">
                            <!-- Schemas Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="schemas-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#schemas" role="tab" aria-controls="schemas" aria-selected="false"
                                    data-type="add" data-token="@skeletonToken('central_business_schemas')_a" data-class="d-block"
                                    data-text="Add Schema" data-target="#business-add-btn">Schemas</a>
                            </li>
                            <!-- Progress Tab -->
                            <li class="nav-item">
                                <a class="nav-link" id="progress-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#progress" role="tab" aria-controls="progress" aria-selected="false"
                                    data-class="d-none" data-target="#business-add-btn">Progress</a>
                            </li>
                        </ul>
                        <!-- Action Button -->
                        <div class="action-area">
                            <button class="btn btn-primary skeleton-popup" id="business-add-btn">Default</button>
                        </div>
                    </div>
                    <!-- Tabs Content -->
                    <div class="tab-content mt-2 pt-2 border-top">
                        <!-- Schemas Tab Content -->
                        <div class="tab-pane fade" id="schemas" role="tabpanel" aria-labelledby="schemas-tab">
                            <div data-skeleton-table-set="@skeletonToken('central_business_schemas')_t"></div>
                        </div>
                        <!-- Progress Tab Content -->
                        <div class="tab-pane fade" id="progress" role="tabpanel" aria-labelledby="progress-tab">
                            <div data-skeleton-table-set="@skeletonToken('central_business_schema_progress')_t"></div>
                        </div>
                    </div>
                </div>
                {{-- <div data-drag-container>
                    <div data-drag-area data-input-string=".area_1_values" data-input-sum=".area_1_sum" data-seperator=",">
                        <div data-drag-item data-value="10">Item A1</div>
                        <div data-drag-item data-value="20">Item A2</div>
                        <div data-drag-item data-value="30">Item A3</div>
                        <div data-drag-item data-value="40">Item A4</div>
                        <div data-drag-item data-value="50">Item A5</div>
                    </div>
                    <div data-drag-area data-input-string=".dropped-module-ids" data-input-sum=".area_2_sum" data-seperator=",">
                        <div data-drag-item data-value="apple">Item B1</div>
                        <div data-drag-item data-value="banana">Item B2</div>
                        <div data-drag-item data-value="cherry">Item B3</div>
                        <div data-drag-item data-value="date">Item B4</div>
                        <div data-drag-item data-value="elderberry">Item B5</div>
                    </div>
                </div>
                <!-- Optional: Predefined hidden inputs (can be created dynamically too) -->
                <input type="text" class="dropped-module-ids">
                <input type="text" class="area_1_sum">
                <input type="text" class="area_2_values">
                <input type="text" class="area_2_sum">
                <input type="text" class="area_3_values">
                <input type="text" class="area_3_sum"> --}}
                {{-- <div class="repeater-block w-50" data-repeater-container data-input="tax_pair" data-type="pair" data-preupdate='{"sgst":"1","cgst":"2","gst":"23"}'>
                    <strong>Tax (Pair)</strong>
                    <div data-repeater class="d-flex flex-row gap-2 w-100 align-items-end mt-2">
                        <div class="float-input-control flex-grow-1">
                            <select name="label" class="form-float-input" required>
                                <option value="">-- Select Tax Type --</option>
                                <option value="gst">GST</option>
                                <option value="sgst">SGST</option>
                                <option value="cgst">CGST</option>
                            </select>
                            <label class="form-float-label">Tax Type<span class="text-danger">*</span></label>
                        </div>
                        <div class="float-input-control flex-grow-1">
                            <input type="text" name="value" class="form-float-input" required placeholder="Value">
                            <label class="form-float-label">Value<span class="text-danger">*</span></label>
                        </div>
                        <button data-repeater-add type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div> --}}
                {{-- <div class="p-5 m-5 bg-light">
                <div data-stepper-container data-stepper-type="linear" data-progress-type="bar+icon" data-submit-btn-text="Submit Now">
                <div data-step data-title="Account Info" data-icon="fa-user">
                    <input class="form-control mb-2" name="username" placeholder="Username" required />
                    <input class="form-control mb-2" name="email" placeholder="Email" type="email" required />
                </div>
                <div data-step data-title="Personal Details" data-icon="fa-id-card">
                    <input class="form-control mb-2" name="fullname" placeholder="Full Name" required />
                    <input class="form-control mb-2" name="dob" type="date" required />
                </div>
                <div data-step data-title="Bank Info" data-icon="fa-bank">
                    <input class="form-control mb-2" name="bank_name" placeholder="Bank Name" required />
                    <input class="form-control mb-2" name="ifsc" placeholder="IFSC Code" required />
                    <input class="form-control mb-2" name="account_number" placeholder="Account No." required />
                </div>
                <div data-step data-title="Review & Confirm" data-icon="fa-check">
                    <p>Review your data before submitting.</p>
                </div>
                </div>
                </div> --}}
            </div>
            {{-- ************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************ --}}
        </div>
    </div>
@endsection
