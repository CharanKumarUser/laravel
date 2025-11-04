{{-- Template: Scope Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Scope')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts')
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            const scopeData = {!! $data['scopes'] !!};
            // Safely call the tree render
            if (window.skeleton?.tree) {
                window.skeleton.tree('scope-tree-container', scopeData, focusToken);
            } else {
                console.error('skeleton.tree not found');
            }
            // Re-initialize tooltips after tree render
            if (window.general?.tooltip) {
                window.general.tooltip();
            }
        }, 1000);
    });
</script>
@endpush

@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Scope</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/user-management') }}">User Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Scope</a></li>
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
                <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0" id="scope-designation-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="scope-tab" data-bs-toggle="tab" href="#scope-views" role="tab" aria-controls="scope-views" aria-selected="true">Scope</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="designation-tab" data-bs-toggle="tab" href="#designation-table-view" role="tab" aria-controls="designation-table-view" aria-selected="false">Designation</a>
                    </li>
                </ul>
                <div class="tab-content mt-2 pt-2 border-top">
                    <div class="tab-pane fade show active" id="scope-views" role="tabpanel" aria-labelledby="scope-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action" id="skeleton-scopes" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="scopes_tree-tab" data-skl-action="b" data-bs-toggle="tab"
                                        href="#scopes_tree" role="tab" aria-controls="scopes_tree" aria-selected="true"
                                        data-type="add" data-token="@skeletonToken('open_scopes')_a" data-text="Add Scope"
                                        data-target="#scopes-add-btn">Tree View</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="scopes_card-tab" data-skl-action="b" data-bs-toggle="tab"
                                        href="#scopes_card_view" role="tab" aria-controls="scopes_card_view" aria-selected="false"
                                        data-type="add" data-token="@skeletonToken('open_scopes')_a" data-text="Add Scope"
                                        data-target="#scopes-add-btn">Card View</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="scopes_table-tab" data-skl-action="b" data-bs-toggle="tab"
                                        href="#scopes_table_view" role="tab" aria-controls="scopes_table_view" aria-selected="false"
                                        data-type="add" data-token="@skeletonToken('open_scopes')_a" data-text="Add Scope"
                                        data-target="#scopes-add-btn">Table View</a>
                                </li>
                            </ul>
                            <div class="action-area">
                                <button class="btn btn-primary skeleton-popup" id="scopes-add-btn">Add Scope</button>
                            </div>
                        </div>
                        <div class="tab-content mt-2 pt-2 border-top">
                            <div class="tab-pane fade show active" id="scopes_tree" role="tabpanel" aria-labelledby="scopes_tree-tab">
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-secondary" data-full-screen="#scope-tree-container">
                                        Full Screen
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="location.reload()">
                                        Refresh
                                    </button>
                                </div>
                                <div id="scope-tree-container" style="width:100%; height:600px"
                                    data-root-name="{!! $data['business'] !!}" data-token="@skeletonToken('open_scopes')">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="scopes_card_view" role="tabpanel" aria-labelledby="scopes_card-tab">
                                <div data-skeleton-card-set="@skeletonToken('open_scopes')_c" data-placeholder="card|9" data-type="scroll"
                                    data-limit="10" data-filters="sort|search|counts" data-container="row">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="scopes_table_view" role="tabpanel" aria-labelledby="scopes_table-tab">
                                <div data-skeleton-table-set="@skeletonToken('open_scopes')_t" data-bulk="update"></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="designation-table-view" role="tabpanel" aria-labelledby="designation-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Designations</h5>
                            <button class="btn btn-primary skeleton-popup" data-skl-action="b" data-type="add" data-token="@skeletonToken('business_designation_data')_a" data-text="Add Designation" data-target="#designation-add-btn">Add Designation</button>
                        </div>
                        <div data-skeleton-table-set="@skeletonToken('business_designation_data')_t" data-bulk="update"></div>
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