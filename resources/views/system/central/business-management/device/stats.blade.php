{{-- Template: Support Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Support')
@push('styles')
    {{-- Header Styles --}}
@endpush
@push('pre-scripts')
    {{-- Header Scripts --}}
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const commandSelect = document.getElementById('commandSelect');
            const commandFormContainer = document.getElementById('commandFormContainer');
            const csrfToken = '{{ csrf_token() }}';
            const commandDescriptions = {
                'ADD USER': 'Add a new user to the device with details like PIN, Name, etc.',
                'DELETE USER': 'Delete a user by PIN.',
                'ADD FINGERPRINT': 'Add fingerprint data for a user.',
                'ENROLL FINGERPRINT': 'Enroll a new fingerprint for a user.',
                'ADD FACE': 'Add face data for a user.',
                'ENROLL FACE': 'Enroll a new face for a user.',
                'REBOOT DEVICE': 'Reboot the device remotely.',
                'CLEAR LOG': 'Clear all logs on the device.',
                'CLEAR DATA': 'Clear all data on the device.',
                'CHECK DEVICE': 'Check device status.',
                'DEVICE INFO': 'Get device information.',
                'GET TIME': 'Get current time from device.',
                'SET TIME': 'Set time on the device using a timestamp.',
                'UNLOCK ACCESS': 'Unlock access control.',
                'GET ATTENDANCE LOG': 'Retrieve attendance logs.',
                'GET USER INFO': 'Get user information.',
                'GET PHOTO': 'Get photos from device.',
                'QUERY ATTENDANCE LOG': 'Query attendance logs between specific dates and times.',
                'GET OPERATION LOG': 'Get operation logs.',
                'GET ILLEGAL LOG': 'Get illegal access logs.',
                'GET CARD': 'Get card info for a specific user.',
                'GET ALL CARDS': 'Get all cards registered on the device.',
                'GET DEVICE INFO': 'Get detailed device info.',
                'GET USER COUNT': 'Get count of users on the device.',
                'GET LOG COUNT': 'Get count of logs on the device.',
                'GET FINGERPRINT DATA': 'Get fingerprint data for a user.',
                'GET FACE DATA': 'Get face data for a user.',
                'GET BIODATA': 'Get biodata for a user.',
                'CHANGE WEB ADDRESS': 'Change the web server URL.',
                'CHANGE WEB PORT': 'Change the web server port.'
            };
            const commandConfigs = {
                'ADD USER': [{
                        label: 'User PIN',
                        name: 'PIN',
                        type: 'text',
                        required: true,
                        placeholder: 'User PIN (alphanumeric, dashes, underscores, max 50)',
                        maxLength: 50,
                        pattern: '^[a-zA-Z0-9_-]+$',
                        error: 'Valid PIN required (alphanumeric, dashes, underscores, max 50)'
                    },
                    {
                        label: 'User Name',
                        name: 'Name',
                        type: 'text',
                        required: true,
                        placeholder: 'User Name (max 255 characters)',
                        maxLength: 255,
                        error: 'Name required (max 255 characters)'
                    },
                    {
                        label: 'Privilege',
                        name: 'Pri',
                        type: 'number',
                        placeholder: 'Privilege (0-14)',
                        min: 0,
                        max: 14,
                        error: 'Privilege must be between 0 and 14'
                    },
                    {
                        label: 'Verify',
                        name: 'Verify',
                        type: 'number',
                        placeholder: 'Verify (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Verify must be 0 or 1'
                    },
                    {
                        label: 'Card Number',
                        name: 'Card',
                        type: 'text',
                        placeholder: 'Card Number (max 50)',
                        maxLength: 50,
                        error: 'Card number max 50 characters'
                    },
                    {
                        label: 'Group',
                        name: 'Grp',
                        type: 'number',
                        placeholder: 'Group (minimum 1)',
                        min: 1,
                        error: 'Group must be at least 1'
                    },
                    {
                        label: 'Password',
                        name: 'Passwd',
                        type: 'password',
                        placeholder: 'Password (max 50)',
                        maxLength: 50,
                        error: 'Password max 50 characters'
                    },
                    {
                        label: 'Expires',
                        name: 'Expires',
                        type: 'number',
                        placeholder: 'Expires (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Expires must be 0 or 1'
                    },
                    {
                        label: 'Start Date',
                        name: 'StartDatetime',
                        type: 'date',
                        placeholder: 'Start Date (YYYY-MM-DD)',
                        error: 'Valid start date required'
                    },
                    {
                        label: 'End Date',
                        name: 'EndDatetime',
                        type: 'date',
                        placeholder: 'End Date (YYYY-MM-DD)',
                        error: 'Valid end date required'
                    }
                ],
                'DELETE USER': [{
                    label: 'User PIN',
                    name: 'PIN',
                    type: 'text',
                    required: true,
                    placeholder: 'User PIN',
                    maxLength: 50,
                    pattern: '^[a-zA-Z0-9_-]+$',
                    error: 'Valid PIN required'
                }],
                'ADD FINGERPRINT': [{
                        label: 'User PIN',
                        name: 'PIN',
                        type: 'text',
                        required: true,
                        placeholder: 'User PIN',
                        maxLength: 50,
                        pattern: '^[a-zA-Z0-9_-]+$',
                        error: 'Valid PIN required'
                    },
                    {
                        label: 'Fingerprint ID',
                        name: 'FID',
                        type: 'number',
                        required: true,
                        placeholder: 'Fingerprint ID (minimum 1)',
                        min: 1,
                        error: 'Fingerprint ID must be at least 1'
                    },
                    {
                        label: 'Valid',
                        name: 'Valid',
                        type: 'number',
                        required: true,
                        placeholder: 'Valid (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Valid must be 0 or 1'
                    },
                    {
                        label: 'Size',
                        name: 'SIZE',
                        type: 'number',
                        required: true,
                        placeholder: 'Size (minimum 1)',
                        min: 1,
                        error: 'Size must be at least 1'
                    },
                    {
                        label: 'Fingerprint Template',
                        name: 'TMP',
                        type: 'text',
                        required: true,
                        placeholder: 'Fingerprint Template',
                        error: 'Fingerprint template required'
                    }
                ],
                'ENROLL FINGERPRINT': [{
                        label: 'User PIN',
                        name: 'PIN',
                        type: 'text',
                        required: true,
                        placeholder: 'User PIN',
                        maxLength: 50,
                        pattern: '^[a-zA-Z0-9_-]+$',
                        error: 'Valid PIN required'
                    },
                    {
                        label: 'Fingerprint ID',
                        name: 'FID',
                        type: 'number',
                        required: true,
                        placeholder: 'Fingerprint ID (minimum 1)',
                        min: 1,
                        error: 'Fingerprint ID must be at least 1'
                    },
                    {
                        label: 'Retry Count',
                        name: 'RETRY',
                        type: 'number',
                        placeholder: 'Retry Count (minimum 1)',
                        min: 1,
                        error: 'Retry count must be at least 1'
                    },
                    {
                        label: 'Overwrite',
                        name: 'OVERWRITE',
                        type: 'number',
                        placeholder: 'Overwrite (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Overwrite must be 0 or 1'
                    }
                ],
                'ADD FACE': [{
                        label: 'User PIN',
                        name: 'PIN',
                        type: 'text',
                        required: true,
                        placeholder: 'User PIN',
                        maxLength: 50,
                        pattern: '^[a-zA-Z0-9_-]+$',
                        error: 'Valid PIN required'
                    },
                    {
                        label: 'Face ID',
                        name: 'FID',
                        type: 'number',
                        required: true,
                        placeholder: 'Face ID (minimum 1)',
                        min: 1,
                        error: 'Face ID must be at least 1'
                    },
                    {
                        label: 'Valid',
                        name: 'Valid',
                        type: 'number',
                        required: true,
                        placeholder: 'Valid (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Valid must be 0 or 1'
                    },
                    {
                        label: 'Size',
                        name: 'SIZE',
                        type: 'number',
                        required: true,
                        placeholder: 'Size (minimum 1)',
                        min: 1,
                        error: 'Size must be at least 1'
                    },
                    {
                        label: 'Face Template',
                        name: 'TMP',
                        type: 'text',
                        required: true,
                        placeholder: 'Face Template',
                        error: 'Face template required'
                    }
                ],
                'ENROLL FACE': [{
                        label: 'User PIN',
                        name: 'PIN',
                        type: 'text',
                        required: true,
                        placeholder: 'User PIN',
                        maxLength: 50,
                        pattern: '^[a-zA-Z0-9_-]+$',
                        error: 'Valid PIN required'
                    },
                    {
                        label: 'Face ID',
                        name: 'FID',
                        type: 'number',
                        required: true,
                        placeholder: 'Face ID (minimum 1)',
                        min: 1,
                        error: 'Face ID must be at least 1'
                    },
                    {
                        label: 'Retry Count',
                        name: 'RETRY',
                        type: 'number',
                        placeholder: 'Retry Count (minimum 1)',
                        min: 1,
                        error: 'Retry count must be at least 1'
                    },
                    {
                        label: 'Overwrite',
                        name: 'OVERWRITE',
                        type: 'number',
                        placeholder: 'Overwrite (0 or 1)',
                        min: 0,
                        max: 1,
                        error: 'Overwrite must be 0 or 1'
                    }
                ],
                'REBOOT DEVICE': [],
                'CLEAR LOG': [],
                'CLEAR DATA': [],
                'CHECK DEVICE': [],
                'DEVICE INFO': [],
                'UNLOCK DOOR': [],
                'GET LOGS': [],
                'QUERY ATTENDANCE LOG': [{
                        label: 'Start Time',
                        name: 'StartTime',
                        type: 'text',
                        required: true,
                        placeholder: 'Start Time',
                        error: 'Valid start time required'
                    },
                    {
                        label: 'End Time',
                        name: 'EndTime',
                        type: 'text',
                        required: true,
                        placeholder: 'End Time',
                        error: 'Valid end time required'
                    }
                ],
                'CHANGE WEB ADDRESS': [{
                    label: 'Server URL',
                    name: 'ICLOCKSVRURL',
                    type: 'url',
                    required: true,
                    placeholder: 'Server URL (e.g., https://example.com)',
                    error: 'Valid URL required'
                }],
                'CHANGE WEB PORT': [{
                    label: 'Port',
                    name: 'IclockSvrPort',
                    type: 'number',
                    required: true,
                    placeholder: 'Port (1-65535)',
                    min: 1,
                    max: 65535,
                    error: 'Port must be between 1 and 65535'
                }]
            };
            function generateCommandForm(selectedCommand) {
                commandFormContainer.innerHTML = '';
                // Description
                const descP = document.createElement('p');
                descP.className = 'text-muted mb-2';
                descP.innerHTML =
                    `<i class="bi bi-info-circle text-secondary me-2"></i>${commandDescriptions[selectedCommand] || 'No description available.'}`;
                commandFormContainer.appendChild(descP);
                // Form
                const form = document.createElement('form');
                form.className = 'row g-3 needs-validation';
                form.id = 'commandForm';
                form.method = 'POST';
                form.setAttribute('novalidate', '');
                form.setAttribute('action', "{{ url('/skeleton-action/') }}/@skeletonToken('central_business_devices')_f");
                // Hidden inputs
                form.innerHTML = `
        <input type="hidden" name="_token" value="${csrfToken}">
        <input type="hidden" name="save_token" value="@skeletonToken('central_business_devices')">
        <input type="hidden" name="form_type" value="commands">
        <input type="hidden" name="business_id" value="{{ $data['business_id'] ?? '' }}">
        <input type="hidden" name="device_id" value="{{ $data['current_device_id'] ?? '' }}">
        <input type="hidden" name="serial_number" value="{{ $data['serial_number'] ?? '' }}">
        <input type="hidden" name="command" value="${selectedCommand}">
    `;
                // Dynamic fields
                const fields = commandConfigs[selectedCommand] || [];
                if (fields.length === 0) {
                    const noFieldsMsg = document.createElement('div');
                    noFieldsMsg.className = 'alert alert-info';
                    noFieldsMsg.textContent = 'This command does not require any parameters.';
                    form.appendChild(noFieldsMsg);
                } else {
                    fields.forEach(field => {
                        const col = document.createElement('div');
                        col.className =
                            `col-md-${Math.floor(12 / Math.min(fields.length, 2))} col-sm-6 col-12`;
                        let formGroup = document.createElement('div');
                        let input;
                        if (field.type === 'select') {
                            input = document.createElement('select');
                            input.className = 'form-select';
                            input.name = field.name;
                            if (field.required) input.required = true;
                            const placeholderOpt = document.createElement('option');
                            placeholderOpt.value = '';
                            placeholderOpt.textContent = field.placeholder || 'Select...';
                            input.appendChild(placeholderOpt);
                            field.options.forEach(opt => {
                                const option = document.createElement('option');
                                option.value = opt.value;
                                option.textContent = opt.text;
                                input.appendChild(option);
                            });
                        } else {
                            input = document.createElement('input');
                            input.className = 'form-control';
                            input.type = field.type;
                            input.name = field.name;
                            input.placeholder = field.placeholder || '';
                            if (field.required) input.required = true;
                            if (field.maxLength) input.maxLength = field.maxLength;
                            if (field.min !== undefined) input.min = field.min;
                            if (field.max !== undefined) input.max = field.max;
                            if (field.pattern) input.pattern = field.pattern;
                        }
                        const label = document.createElement('label');
                        label.className = 'form-label ms-1 mb-0 p-0';
                        label.textContent = field.label + (field.required ? ' *' : '');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = field.error || `Please provide a valid ${field.label}.`;
                        formGroup.appendChild(label);
                        formGroup.appendChild(input);
                        formGroup.appendChild(feedback);
                        col.appendChild(formGroup);
                        form.appendChild(col);
                    });
                }
                // Submit button
                const buttonCol = document.createElement('div');
                buttonCol.className = 'col-12 text-end';
                const submitButton = document.createElement('button');
                submitButton.type = 'submit';
                submitButton.className = 'btn btn-success';
                submitButton.innerHTML = '<i class="bi bi-play-fill me-2"></i>Execute Command';
                buttonCol.appendChild(submitButton);
                form.appendChild(buttonCol);
                commandFormContainer.appendChild(form);
            }
            commandSelect.addEventListener('change', function() {
                const selectedCommand = this.value;
                if (!selectedCommand) {
                    commandFormContainer.innerHTML = `
                <div class="bg-warning-100 p-2 rounded-2 border-start border-3 border-warning">No command selected. Please choose a command to continue.</div>
            `;
                    return;
                }
                generateCommandForm(selectedCommand);
            });
            // Default state
            commandFormContainer.innerHTML = `
        <div class="p-2 text-info">Please select a command to generate its form.</div>
    `;
        });
    </script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Support</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/business-management') }}">Business Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Support</a></li>
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
            <div class="container-fluid">
                <ul class="nav nav-pills mb-4 data-skl-action" id="deviceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-skl-action="b" id="analytics-tab" data-bs-toggle="tab"
                            data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics"
                            aria-selected="true">
                            <i class="bi bi-graph-up me-2"></i>Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-skl-action="b" id="settings-tab" data-bs-toggle="tab"
                            data-bs-target="#settings" type="button" role="tab" aria-controls="settings"
                            aria-selected="false">
                            <i class="bi bi-gear me-2"></i>Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-skl-action="b" id="commands-tab" data-bs-toggle="tab"
                            data-bs-target="#commands" type="button" role="tab" aria-controls="commands"
                            aria-selected="false">
                            <i class="bi bi-arrow-left-right me-2"></i>Commands
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-skl-action="b" id="users-tab" data-bs-toggle="tab"
                            data-bs-target="#users" type="button" role="tab" aria-controls="users"
                            aria-selected="false">
                            <i class="bi bi-people me-2"></i>Users
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Analytics Tab -->
                    <div class="tab-pane fade show active" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                        <div
                            class="bg-info-100 p-2 my-3 rounded-2 border-start border-3 border-info d-flex gap-2 align-items-center">
                            <i class="ti ti-bulb"></i>
                            <div><strong>Note:</strong> View device information and statistics, including user counts,
                                transaction logs, and feature support.</div>
                        </div>
                        @php
                            $users = $data['users'] ?? [];
                            $allDevices = $data['devices'] ?? [];
                            $curentDevice = $allDevices[$data['current_device_id']] ?? [];
                            $device = json_decode($curentDevice['info_json'], true);
                            $deviceInfo = [
                                'DeviceName' => [
                                    'label' => 'Device Name',
                                    'value' => $curentDevice['name'] ?? 'N/A',
                                    'icon' => 'bi bi-laptop',
                                ],
                                'Modal' => [
                                    'label' => 'Modal Name',
                                    'value' => $device['DeviceName'] ?? 'N/A',
                                    'icon' => 'bi bi-pc-display',
                                ],
                                'SerialNumber' => [
                                    'label' => 'Serial Number',
                                    'value' => $device['SerialNumber'] ?? 'N/A',
                                    'icon' => 'bi bi-upc',
                                ],
                                'IPAddress' => [
                                    'label' => 'IP Address',
                                    'value' => $device['IPAddress'] ?? 'N/A',
                                    'icon' => 'bi bi-globe',
                                ],
                                'MAC' => [
                                    'label' => 'MAC Address',
                                    'value' => $device['MAC'] ?? 'N/A',
                                    'icon' => 'bi bi-ethernet',
                                ],
                                'FWVersion' => [
                                    'label' => 'Firmware Version',
                                    'value' => $device['FWVersion'] ?? 'N/A',
                                    'icon' => 'bi bi-cpu',
                                ],
                                'Platform' => [
                                    'label' => 'Platform',
                                    'value' => $device['Platform'] ?? 'N/A',
                                    'icon' => 'bi bi-server',
                                ],
                                'PushVersion' => [
                                    'label' => 'Push Version',
                                    'value' => $device['PushVersion'] ?? 'N/A',
                                    'icon' => 'bi bi-upload',
                                ],
                            ];
                            $deviceStats = [
                                'UserCount' => [
                                    'label' => 'Current Users',
                                    'value' => $device['UserCount'] ?? '0',
                                    'icon' => 'bi bi-people',
                                ],
                                'MaxUserCount' => [
                                    'label' => 'Max Users',
                                    'value' => $device['MaxUserCount'] ?? '0',
                                    'icon' => 'bi bi-person-plus',
                                ],
                                'TransactionCount' => [
                                    'label' => 'Transaction Count',
                                    'value' => $device['TransactionCount'] ?? '0',
                                    'icon' => 'bi bi-list-task',
                                ],
                                'MaxAttLogCount' => [
                                    'label' => 'Max Attendance Logs',
                                    'value' => $device['MaxAttLogCount'] ?? '0',
                                    'icon' => 'bi bi-clipboard-data',
                                ],
                                'FPCount' => [
                                    'label' => 'Fingerprint Count',
                                    'value' => $device['FPCount'] ?? '0',
                                    'icon' => 'bi bi-fingerprint',
                                ],
                                'MaxFingerCount' => [
                                    'label' => 'Max Fingerprints',
                                    'value' => $device['MaxFingerCount'] ?? '0',
                                    'icon' => 'bi bi-fingerprint',
                                ],
                                'FaceCount' => [
                                    'label' => 'Face Count',
                                    'value' => $device['FaceCount'] ?? '0',
                                    'icon' => 'bi bi-person-bounding-box',
                                ],
                                'MaxFaceCount' => [
                                    'label' => 'Max Faces',
                                    'value' => $device['MaxFaceCount'] ?? '0',
                                    'icon' => 'bi bi-person-bounding-box',
                                ],
                                'UserPhotoCount' => [
                                    'label' => 'User Photo Count',
                                    'value' => $device['UserPhotoCount'] ?? '0',
                                    'icon' => 'bi bi-camera',
                                ],
                                'ATTPhotoCount' => [
                                    'label' => 'Attendance Photo Count',
                                    'value' => $device['ATTPhotoCount'] ?? '0',
                                    'icon' => 'bi bi-camera-reels',
                                ],
                            ];
                        @endphp
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2"><i class="bi bi-info-circle me-2"></i>Device
                                    Information</h6>
                                <div class="row g-3">
                                    @foreach ($deviceInfo as $key => $info)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center ms-4">
                                                <i class="{{ $info['icon'] }} text-muted me-2 sf-13"></i>
                                                <div class="sf-13">
                                                    <strong>{{ $info['label'] }}:</strong> {{ $info['value'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2"><i class="bi bi-bar-chart me-2"></i>Device
                                    Statistics</h6>
                                <div class="row g-3">
                                    @foreach ($deviceStats as $key => $stat)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center ms-4">
                                                <i class="{{ $stat['icon'] }} text-muted me-2 sf-13"></i>
                                                <div class="sf-13">
                                                    <strong>{{ $stat['label'] }}:</strong> {{ $stat['value'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2"><i class="bi bi-pc-display me-2"></i>All Devices
                                    in this Business</h6>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-responsive">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col">Name</th>
                                                <th scope="col">Serial Number</th>
                                                <th scope="col">IP</th>
                                                <th scope="col">Port</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($allDevices as $device)
                                                <tr>
                                                    <td>{{ $device['name'] ?? 'N/A' }}</td>
                                                    <td>{{ $device['serial_number'] ?? 'N/A' }}</td>
                                                    <td>{{ $device['ip'] ?? 'N/A' }}</td>
                                                    <td>{{ $device['port'] ?? 'N/A' }}</td>
                                                    <td>
                                                        <form class="static"
                                                            action={{ url('/skeleton-action/') }}/@skeletonToken('central_business_devices')_f>
                                                            <input type="hidden" name='save_token'
                                                                value=@skeletonToken('central_business_devices')>
                                                            <input type="hidden" name='form_type' value="updateStatus">
                                                            <input type="hidden" name='business_id'
                                                                value={{ $data['business_id'] ?? '' }}>
                                                            <input type="hidden" name='device_id'
                                                                value={{ $device['device_id'] ?? '' }}>
                                                            @if ($device['is_active'])
                                                                <input type="hidden" name='is_active' value='0'>
                                                                <button type="submit" class="btn btn-warning btn-sm"
                                                                    title="Deactivate Device">
                                                                    Deactivate
                                                                </button>
                                                            @else
                                                                <input type="hidden" name='is_active' value='1'>
                                                                <button type="submit" class="btn btn-success btn-sm"
                                                                    title="Activate Device">
                                                                    Activate
                                                                </button>
                                                            @endif
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">No users found. Add users using
                                                        the Commands tab.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                        <div
                            class="bg-info-100 p-2 my-3 rounded-2 border-start border-3 border-info d-flex gap-2 align-items-center">
                            <i class="ti ti-bulb"></i>
                            <div><strong>Note:</strong> Update device settings below. Ensure values are valid before saving
                                to avoid errors.</div>
                        </div>
                        @php
                            $settings = json_decode($curentDevice['settings_json'], true);
                            $settingsData = [
                                'Stamp' => [
                                    'value' => $settings['Stamp'] ?? '',
                                    'desc' => 'General data sync stamp',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid stamp required',
                                ],
                                'ATTLOGStamp' => [
                                    'value' => $settings['ATTLOGStamp'] ?? 'None',
                                    'desc' => 'Last attendance log synced',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid attendance log stamp required',
                                ],
                                'OpStamp' => [
                                    'value' => $settings['OpStamp'] ?? '',
                                    'desc' => 'Operation log sync marker',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid operation stamp required',
                                ],
                                'OPERLOGStamp' => [
                                    'value' => $settings['OPERLOGStamp'] ?? 'None',
                                    'desc' => 'Administrative log sync marker',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid administrative log stamp required',
                                ],
                                'PhotoStamp' => [
                                    'value' => $settings['PhotoStamp'] ?? '',
                                    'desc' => 'Last photo synced',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid photo stamp required',
                                ],
                                'ATTPHOTOStamp' => [
                                    'value' => $settings['ATTPHOTOStamp'] ?? 'None',
                                    'desc' => 'Attendance photo sync marker',
                                    'type' => 'text',
                                    'pattern' => '',
                                    'error' => 'Valid attendance photo stamp required',
                                ],
                                'ErrorDelay' => [
                                    'value' => $settings['ErrorDelay'] ?? 13,
                                    'desc' => 'Delay after error (seconds)',
                                    'type' => 'number',
                                    'min' => 0,
                                    'error' => 'Must be a positive number',
                                ],
                                'Delay' => [
                                    'value' => $settings['Delay'] ?? 4,
                                    'desc' => 'Regular communication delay (seconds)',
                                    'type' => 'number',
                                    'min' => 0,
                                    'error' => 'Must be a positive number',
                                ],
                                'TransTimes' => [
                                    'value' => $settings['TransTimes'] ?? '09:00;18:30',
                                    'desc' => 'Scheduled upload times (HH:MM;...)',
                                    'type' => 'text',
                                    'pattern' => '^([0-1][0-9]|2[0-3]):[0-5][0-9](;([0-1][0-9]|2[0-3]):[0-5][0-9])*$',
                                    'error' => 'Format as HH:MM;HH:MM',
                                ],
                                'TransInterval' => [
                                    'value' => $settings['TransInterval'] ?? 7,
                                    'desc' => 'Auto upload interval (minutes)',
                                    'type' => 'number',
                                    'min' => 0,
                                    'error' => 'Must be a positive number',
                                ],
                                'TransFlag' => [
                                    'value' => $settings['TransFlag'] ?? '111111101101',
                                    'desc' => 'Data type transmission flags',
                                    'type' => 'text',
                                    'pattern' => '^[01]+$',
                                    'error' => 'Must be a binary string (0s and 1s)',
                                ],
                                'Realtime' => [
                                    'value' => $settings['Realtime'] ?? 1,
                                    'desc' => 'Real-time upload mode (1: enabled)',
                                    'type' => 'select',
                                    'options' => [['value' => 0, 'text' => '0'], ['value' => 1, 'text' => '1']],
                                    'error' => 'Must be 0 or 1',
                                ],
                                'TimeOut' => [
                                    'value' => $settings['TimeOut'] ?? 9,
                                    'desc' => 'Network timeout (seconds)',
                                    'type' => 'number',
                                    'min' => 0,
                                    'error' => 'Must be a positive number',
                                ],
                                'TimeZone' => [
                                    'value' => $settings['TimeZone'] ?? '',
                                    'desc' => 'Device timezone (e.g., UTC+8)',
                                    'type' => 'text',
                                    'pattern' => '^(UTC)?[+-]?[0-9]+$',
                                    'error' => 'Format as UTC+8 or +8',
                                ],
                                'Encrypt' => [
                                    'value' => $settings['Encrypt'] ?? 0,
                                    'desc' => 'Encryption toggle (0: off)',
                                    'type' => 'select',
                                    'options' => [['value' => 0, 'text' => '0'], ['value' => 1, 'text' => '1']],
                                    'error' => 'Must be 0 or 1',
                                ],
                            ];
                        @endphp
                        <div class="table-responsive">
                            <form method="POST" action={{ url('/skeleton-action/') }}/@skeletonToken('central_business_devices')_f
                                class="needs-validation" novalidate>
                                <input type="hidden" name='save_token' value=@skeletonToken('central_business_devices')>
                                <input type="hidden" name="form_type" value="settings">
                                <input type="hidden" name='business_id' value={{ $data['business_id'] ?? '' }}>
                                <input type="hidden" name='device_id' value={{ $data['current_device_id'] ?? '' }}>
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Setting</th>
                                            <th scope="col">Value</th>
                                            <th scope="col">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @csrf
                                        @foreach ($settingsData as $key => $data)
                                            <tr>
                                                <td>{{ $key }}</td>
                                                <td>
                                                    <div class="float-input-control">
                                                        @if ($data['type'] === 'select')
                                                            <select name="{{ $key }}"
                                                                class="form-select form-float-input" required>
                                                                <option value="">{{ $key }}</option>
                                                                @foreach ($data['options'] as $option)
                                                                    <option value="{{ $option['value'] }}"
                                                                        {{ $data['value'] == $option['value'] ? 'selected' : '' }}>
                                                                        {{ $option['text'] }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <input type="{{ $data['type'] }}" name="{{ $key }}"
                                                                class="form-control form-float-input"
                                                                value="{{ $data['value'] }}" required
                                                                placeholder="{{ $key }}"
                                                                @if (isset($data['min'])) min="{{ $data['min'] }}" @endif
                                                                @if (!empty($data['pattern'])) pattern="{{ $data['pattern'] }}" @endif>
                                                        @endif
                                                        <label class="form-float-label">{{ $key }} *</label>
                                                        <div class="invalid-feedback">{{ $data['error'] }}</div>
                                                    </div>
                                                </td>
                                                <td class="text-muted">{{ $data['desc'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class='text-end my-2'>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-save me-2"></i>Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Commands Tab -->
                    <div class="tab-pane fade" id="commands" role="tabpanel" aria-labelledby="commands-tab">
                        <div
                            class="bg-info-100 p-2 my-3 rounded-2 border-start border-3 border-info d-flex gap-2 align-items-center">
                            <i class="ti ti-bulb"></i>
                            <div><strong>Note:</strong> Select a command from the dropdown. The form will update dynamically
                                with required fields. Ensure all fields are valid before submission.</div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="float-input-control">
                                            <select id="commandSelect" class="form-select form-float-input">
                                                <option value="">Select a Command</option>
                                                @foreach (['ADD USER', 'DELETE USER', 'ADD FINGERPRINT', 'ENROLL FINGERPRINT', 'ADD FACE', 'ENROLL FACE', 'REBOOT DEVICE', 'CLEAR LOG', 'CLEAR DATA', 'CHECK DEVICE', 'DEVICE INFO', 'GET TIME', 'SET TIME', 'UNLOCK ACCESS', 'GET ATTENDANCE LOG', 'GET USER INFO', 'GET PHOTO', 'QUERY ATTENDANCE LOG', 'GET OPERATION LOG', 'GET ILLEGAL LOG', 'GET CARD', 'GET ALL CARDS', 'GET DEVICE INFO', 'GET USER COUNT', 'GET LOG COUNT', 'GET FINGERPRINT DATA', 'GET FACE DATA', 'GET BIODATA', 'CHANGE WEB ADDRESS', 'CHANGE WEB PORT'] as $command)
                                                    <option value="{{ $command }}">{{ $command }}</option>
                                                @endforeach
                                            </select>
                                            <label class="form-float-label">Select Command</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div id="commandFormContainer"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Users Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div
                            class="bg-info-100 p-2 my-3 rounded-2 border-start border-3 border-info d-flex gap-2 align-items-center">
                            <i class="ti ti-bulb"></i>
                            <div><strong>Note:</strong> List of users registered on the device. Use commands in the Commands
                                tab to add or delete users.</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">User ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Privilege</th>
                                        <th scope="col">Group</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr>
                                            <td>{{ $user['device_user_id'] ?? 'N/A' }}</td>
                                            <td>{{ $user['name'] ?? 'N/A' }}</td>
                                            <td>{{ $user['privilege'] ?? 'N/A' }}</td>
                                            <td>{{ $user['group_id'] ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No users found. Add users using the
                                                Commands tab.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
