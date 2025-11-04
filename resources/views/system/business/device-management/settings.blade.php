{{-- Template: Settings Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Settings')
@push('styles')
@endpush
@push('scripts')
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Settings</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/device-management') }}">Device Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Settings</a></li>
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
                        <div class="col-lg-6 mt-4">
                            <div class="border-end pe-3">
                                <div class="text-center mb-3">
                                    <div class="bg-primary bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                        <i class="fa-solid fa-gear text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mb-1">Device Settings Guide</h5>
                                    <p class="text-muted mb-0 small">Configure how your devices sync and manage logs</p>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-primary bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-clock-rotate-left text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-primary fw-semibold mb-0 small">Transfer Schedule</h6>
                                            <small class="text-muted">Use HH:MM;HH:MM format for multiple times</small>
                                        </div>
                                    </div>
                                    <p class="text-muted ps-4 mb-0 small">`trans_times`, `trans_interval`, `realtime` control how often the device sends data.</p>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-info bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-circle-info text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-info fw-semibold mb-0 small">Quick Tips</h6>
                                            <small class="text-muted">Keep it simple</small>
                                        </div>
                                    </div>
                                    <p class="text-muted ps-4 mb-0 small">Fill Device ID, choose a schedule (times or interval), and optionally enable alerts or encryption if your device supports it.</p>
                                </div>
                                <!-- Device & Stamps -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-info bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-id-card text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-info fw-semibold mb-0 small">Device & Stamps</h6>
                                            <small class="text-muted">Identifiers and last-synced markers</small>
                                        </div>
                                    </div>
                                    <ul class="text-muted ps-4 mb-0 small" style="list-style: none; padding-left: 0;">
                                        <li><span class="fw-semibold text-dark">Device ID</span>: Unique device identifier (required).</li>
                                        <li><span class="fw-semibold text-dark">Stamps</span>: `trans_stamp`, `attlog_stamp`, `op_stamp`, `operlog_stamp`, `photo_stamp`, `attphoto_stamp` hold last sync positions; leave blank to start fresh.</li>
                                    </ul>
                                </div>
                                <!-- Timing & Intervals -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-secondary bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-stopwatch text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-secondary fw-semibold mb-0 small">Timing & Intervals</h6>
                                            <small class="text-muted">Schedule and pacing controls</small>
                                        </div>
                                    </div>
                                    <ul class="text-muted ps-4 mb-0 small" style="list-style: none; padding-left: 0;">
                                        <li><span class="fw-semibold text-dark">Error Delay</span>: Seconds to wait after failures.</li>
                                        <li><span class="fw-semibold text-dark">Delay</span>: Base delay between operations.</li>
                                        <li><span class="fw-semibold text-dark">Timeout</span>: Request timeout in seconds.</li>
                                        <li><span class="fw-semibold text-dark">Trans Times</span>: Specific times to push (e.g. 09:00;18:30).</li>
                                        <li><span class="fw-semibold text-dark">Trans Interval</span>: Minutes between pushes when not using fixed times.</li>
                                    </ul>
                                </div>
                                <!-- Flags, Realtime & Timezone -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-dark bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-sliders text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-dark fw-semibold mb-0 small">Flags, Realtime & Timezone</h6>
                                            <small class="text-muted">Fine-tune data subjects</small>
                                        </div>
                                    </div>
                                    <ul class="text-muted ps-4 mb-0 small" style="list-style: none; padding-left: 0;">
                                        <li><span class="fw-semibold text-dark">Trans Flag</span>: Bitmask string to select what to transfer.</li>
                                        <li><span class="fw-semibold text-dark">Realtime</span>: Push changes immediately if supported.</li>
                                        <li><span class="fw-semibold text-dark">Timezone</span>: Device timezone offset/index.</li>
                                    </ul>
                                </div>
                                <!-- Alerts & Cleanup -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-danger bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-triangle-exclamation text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-danger fw-semibold mb-0 small">Alerts & Cleanup</h6>
                                            <small class="text-muted">Prevent overflow and keep devices tidy</small>
                                        </div>
                                    </div>
                                    <ul class="text-muted ps-4 mb-0 small" style="list-style: none; padding-left: 0;">
                                        <li><span class="fw-semibold text-dark">Memory Alert</span>: Enable with `memory_threshold` (KB) and `memory_interval` (min).</li>
                                        <li><span class="fw-semibold text-dark">Attlog Alert</span>: Enable with `attlog_threshold` and `attlog_interval`.</li>
                                        <li><span class="fw-semibold text-dark">Auto Remove Logs</span>: Periodic cleanup using `auto_remove_age` (days) and `auto_remove_threshold`.</li>
                                    </ul>
                                </div>
                                <!-- Defaults -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-teal bg-gradient rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa-solid fa-check text-white small"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-semibold mb-0 small" style="color:#0aa5a5;">Defaults</h6>
                                            <small class="text-muted">Pre-filled for convenience</small>
                                        </div>
                                    </div>
                                    <p class="text-muted ps-4 mb-0 small">Common defaults are applied (e.g., `attlog_stamp=None`, `trans_times=09:00;18:30`, `realtime=on`). Adjust to match device behavior.</p>
                                </div>
                                <div class="alert alert-primary border-0 bg-primary bg-gradient text-white mb-3 py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-lightbulb me-2"></i>
                                        <div>
                                            <h6 class="alert-heading text-white fw-semibold mb-0 small">Tip</h6>
                                            <p class="mb-0 small">Set realistic intervals to balance freshness and server load.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">Device Settings</h4>
                                <span class="badge bg-primary">Configuration Form</span>
                            </div>
                            <form action="{{ url('/skeleton-action/') }}/@skeletonToken('business_device_settings')_f" method="POST">
                                @csrf
                                <input type="hidden" name="save_token" value="@skeletonToken('business_device_settings')">
                                <input type="hidden" name="form_type" value="business_device_settings">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <div class="float-input-control">
                                            <select id="device_id" name="device_id" class="form-float-input" data-select="dropdown" required>
                                                {!! \App\Facades\Select::options('devices', 'html', ['device_id' => 'name']) !!}
                                            </select>
                                            <label for="device_id" class="form-float-label">Device</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="trans_stamp" name="trans_stamp" class="form-float-input">
                                            <label for="trans_stamp" class="form-float-label">Trans Stamp</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="attlog_stamp" name="attlog_stamp" class="form-float-input">
                                            <label for="attlog_stamp" class="form-float-label">Attlog Stamp</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="op_stamp" name="op_stamp" class="form-float-input">
                                            <label for="op_stamp" class="form-float-label">Op Stamp</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="operlog_stamp" name="operlog_stamp" class="form-float-input">
                                            <label for="operlog_stamp" class="form-float-label">Operlog Stamp</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="photo_stamp" name="photo_stamp" class="form-float-input">
                                            <label for="photo_stamp" class="form-float-label">Photo Stamp</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="attphoto_stamp" name="attphoto_stamp" class="form-float-input">
                                            <label for="attphoto_stamp" class="form-float-label">Attphoto Stamp</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="float-input-control">
                                            <input type="number" id="error_delay" name="error_delay" class="form-float-input">
                                            <label for="error_delay" class="form-float-label">Error Delay (s)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="float-input-control">
                                            <input type="number" id="delay" name="delay" class="form-float-input">
                                            <label for="delay" class="form-float-label">Delay (s)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="float-input-control">
                                            <input type="number" id="timeout" name="timeout" class="form-float-input">
                                            <label for="timeout" class="form-float-label">Timeout (s)</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="trans_times" name="trans_times" class="form-float-input">
                                            <label for="trans_times" class="form-float-label">Trans Times</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="trans_interval" name="trans_interval" class="form-float-input">
                                            <label for="trans_interval" class="form-float-label">Trans Interval (min)</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="text" id="trans_flag" name="trans_flag" class="form-float-input">
                                            <label for="trans_flag" class="form-float-label">Trans Flag</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="timezone" name="timezone" class="form-float-input">
                                            <label for="timezone" class="form-float-label">Timezone</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="realtime" name="realtime" value="1">
                                            <label class="form-check-label" for="realtime">Enable Realtime</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="encrypt" name="encrypt" value="1">
                                            <label class="form-check-label" for="encrypt">Encrypt</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="memory_alert" name="memory_alert" value="1">
                                            <label class="form-check-label" for="memory_alert">Memory Alert</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="memory_threshold" name="memory_threshold" class="form-float-input">
                                            <label for="memory_threshold" class="form-float-label">Memory Threshold (KB)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="memory_interval" name="memory_interval" class="form-float-input">
                                            <label for="memory_interval" class="form-float-label">Memory Interval (min)</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="attlog_alert" name="attlog_alert" value="1">
                                            <label class="form-check-label" for="attlog_alert">Attendance Log Alert</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="attlog_threshold" name="attlog_threshold" class="form-float-input">
                                            <label for="attlog_threshold" class="form-float-label">Attlog Threshold</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="attlog_interval" name="attlog_interval" class="form-float-input">
                                            <label for="attlog_interval" class="form-float-label">Attlog Interval (min)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="auto_remove_logs" name="auto_remove_logs" value="1">
                                            <label class="form-check-label" for="auto_remove_logs">Auto Remove Logs</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="auto_remove_age" name="auto_remove_age" class="form-float-input">
                                            <label for="auto_remove_age" class="form-float-label">Auto Remove Age (days)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="float-input-control">
                                            <input type="number" id="auto_remove_threshold" name="auto_remove_threshold" class="form-float-input">
                                            <label for="auto_remove_threshold" class="form-float-label">Auto Remove Threshold</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save me-1"></i>Save Settings
                                    </button>
                                </div>
                            </form>
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
