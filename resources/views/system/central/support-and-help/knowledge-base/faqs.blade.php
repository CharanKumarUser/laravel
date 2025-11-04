{{-- Template: Faqs Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Faqs')
@push('styles')
    {{-- Header Styles --}}
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
    {{-- Body Scripts --}}
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Faqs</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support') }}">Support</a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/support/knowledge-base') }}">Knowledge Base</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Faqs</a></li>
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
            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="row">
                        <!-- Left: Guide -->
                        <div class="col-lg-5 border-end d-none d-lg-block">
                            <div class="text-center mb-3">
                                <div class="bg-primary bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-circle-question text-white"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-1">FAQ Guide</h5>
                                <p class="text-muted mb-0 small">Create and manage FAQs</p>
                            </div>

                            <div class="row g-3">
                                <!-- Company and FAQ ID -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-primary bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-building text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-primary fw-semibold mb-0 small">Company</h6>
                                                <small class="text-muted">Select company</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Pick the FAQ's company.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-success bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-id-badge text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-success fw-semibold mb-0 small">FAQ ID</h6>
                                                <small class="text-muted">Unique ID</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Auto-generated if empty.</p>
                                    </div>
                                </div>

                                <!-- Question -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-info bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-question text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-info fw-semibold mb-0 small">Question</h6>
                                                <small class="text-muted">FAQ question</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Enter the FAQ question.</p>
                                    </div>
                                </div>

                                <!-- Answer -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-warning bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-align-left text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-warning fw-semibold mb-0 small">Answer</h6>
                                                <small class="text-muted">FAQ answer</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Provide the FAQ answer.</p>
                                    </div>
                                </div>

                                <!-- Category and Tags -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-danger bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-folder-open text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-danger fw-semibold mb-0 small">Category</h6>
                                                <small class="text-muted">FAQ category</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Specify the FAQ category.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-primary bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-tags text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-primary fw-semibold mb-0 small">Tags</h6>
                                                <small class="text-muted">FAQ tags</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Add comma-separated tags.</p>
                                    </div>
                                </div>

                                <!-- Is Public and Is Active -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-success bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-globe text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-success fw-semibold mb-0 small">Public?</h6>
                                                <small class="text-muted">Set visibility</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Choose if FAQ is public.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-info bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center"
                                                style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-toggle-on text-white small"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-info fw-semibold mb-0 small">Active?</h6>
                                                <small class="text-muted">Set status</small>
                                            </div>
                                        </div>
                                        <p class="text-muted ps-4 mb-0 small">Choose active or inactive.</p>
                                    </div>
                                </div>

                                <!-- Tip -->
                                <div class="col-md-12">
                                    <div class="alert alert-primary border-0 bg-primary bg-gradient text-white mb-3 py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-lightbulb me-2"></i>
                                            <div>
                                                <h6 class="alert-heading text-white fw-semibold mb-0 small">Tip</h6>
                                                <p class="mb-0 small">Fill all fields for quick processing.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form (No Modal, No Button) -->
                        <div class="col-lg-7 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">Create FAQ</h4>
                                <span class="badge bg-primary">FAQ Form</span>
                            </div>

                            <form action="{{ url('/skeleton-action/') }}/@skeletonToken('business_support_faqs')_f" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="save_token" value="@skeletonToken('business_support_faqs')">
                                <input type="hidden" name="form_type" value="business_support_faqs">

                                <div class="row g-3 mb-2">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <select id="company_id" name="company_id" class="form-float-input"
                                                data-select="dropdown" required>
                                                {!! \App\Facades\Select::options('companies', 'html', ['company_id' => 'name']) !!}
                                            </select>
                                            <label for="company_id" class="form-float-label">Company</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="faq_id" name="faq_id" class="form-float-input"
                                                placeholder="Auto-generated if empty">
                                            <label for="faq_id" class="form-float-label">FAQ ID</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="category" name="category"
                                                class="form-float-input" required>
                                            <label for="category" class="form-float-label">Category</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="tags" name="tags" class="form-float-input"
                                                placeholder="Comma-separated tags">
                                            <label for="tags" class="form-float-label">Tags</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <select id="is_public" name="is_public" class="form-float-input"
                                                data-select="dropdown" required>
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label for="is_public" class="form-float-label">Public?</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <select id="is_active" name="is_active" class="form-float-input"
                                                data-select="dropdown" required>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                            <label for="is_active" class="form-float-label">Active?</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="float-input-control">
                                            <input type="text" id="question" name="question"
                                                class="form-float-input" maxlength="255" required>
                                            <label for="question" class="form-float-label">Question</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="float-input-control">
                                            <textarea id="answer" name="answer" class="form-float-input" rows="4" maxlength="1000" required></textarea>
                                            <label for="answer" class="form-float-label">Answer</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save me-1"></i> Save FAQ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">Faqs</h4>
                        <button class="btn btn-primary skeleton-popup" data-token="@skeletonToken('business_support_faqs')_a"
                            data-type="add">Add
                            Faqs
                        </button>
                    </div>

                    <div data-skeleton-table-set="@skeletonToken('business_support_faqs')_t" data-bulk="update"></div>
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
