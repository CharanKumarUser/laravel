{{-- Template: Permissions Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Permissions')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Permissions</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/user-management') }}">User Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Permissions</a></li>
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
                <div class="d-flex justify-content-between align-items-center">
                    <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                        id="skeleton-modules" role="tablist">
                        @php $tabs = $data['roles']; @endphp
                        @foreach ($tabs as $id => $label)
                            <li class="nav-item">
                                <a class="nav-link @if ($loop->first) active @endif"
                                    id="{{ $id }}-tab" data-bs-toggle="tab" href="#{{ $id }}"
                                    role="tab" aria-controls="{{ $id }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="tab-content mt-2 pt-2 border-top">
                    @foreach ($tabs as $id => $label)
                        <div class="tab-pane fade @if ($loop->first) show active @endif"
                            id="{{ $id }}" role="tabpanel" aria-labelledby="{{ $id }}-tab">
                            <div data-skeleton-table-set="@skeletonToken('open_um_user_permissions')_t_{{ $id }}" data-bulk="update">
                            </div>
                        </div>
                    @endforeach
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