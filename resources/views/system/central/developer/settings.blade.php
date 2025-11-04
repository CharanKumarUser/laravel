{{-- Template: General Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'General')
@section('top-style')
@endsection
@section('bottom-script')
@endsection
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Settings</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/central-system-settings') }}">Skeleton</a></li>
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
            {{-- Main Content: Settings Tabs and Forms --}}
            @php
                $settingsMap = collect($data['settings'] ?? [])->keyBy(function ($item) {
                    return strtolower($item->category . '.' . $item->key);
                });
                $formSkeletonToken = \App\Facades\Skeleton::skeletonToken('central_developer_settings') . '_f' ?? '';
                $formAction = url('/skeleton-action') . '/' . $formSkeletonToken;
            @endphp
            <div class="row g-2">
                <!-- Sidebar Tabs -->
                <div class="col-xl-3">
                    <div class="list-group settings-list" id="skeleton-central-settings" role="tablist">
                        <button type="button" class="list-group-item list-group-item-action active" id="app-tab"
                            data-bs-toggle="tab" data-bs-target="#app" role="tab" aria-controls="app"
                            aria-selected="true">
                            <i class="ti ti-settings me-2"></i>Application Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="database-tab"
                            data-bs-toggle="tab" data-bs-target="#database" role="tab" aria-controls="database"
                            aria-selected="false">
                            <i class="ti ti-database me-2"></i>Database Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="session-tab"
                            data-bs-toggle="tab" data-bs-target="#session" role="tab" aria-controls="session"
                            aria-selected="false">
                            <i class="ti ti-clock-question me-2"></i>Session Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="mail-tab"
                            data-bs-toggle="tab" data-bs-target="#mail" role="tab" aria-controls="mail"
                            aria-selected="false">
                            <i class="ti ti-mail me-2"></i>Mail Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="aws-tab"
                            data-bs-toggle="tab" data-bs-target="#aws" role="tab" aria-controls="aws"
                            aria-selected="false">
                            <i class="ti ti-cloud me-2"></i>AWS S3 Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="reverb-tab"
                            data-bs-toggle="tab" data-bs-target="#reverb" role="tab" aria-controls="reverb"
                            aria-selected="false">
                            <i class="ti ti-broadcast me-2"></i>Reverb Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="skeleton-tab"
                            data-bs-toggle="tab" data-bs-target="#skeleton" role="tab" aria-controls="skeleton"
                            aria-selected="false">
                            <i class="ti ti-skull me-2"></i>Skeleton Framework
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="adms-tab"
                            data-bs-toggle="tab" data-bs-target="#adms" role="tab" aria-controls="adms"
                            aria-selected="false">
                            <i class="ti ti-arrows-exchange me-2"></i>ADMS Settings
                        </button>
                        <button type="button" class="list-group-item list-group-item-action" id="social-tab"
                            data-bs-toggle="tab" data-bs-target="#social" role="tab" aria-controls="social"
                            aria-selected="false">
                            <i class="ti ti-social me-2"></i>Social Login Keys
                        </button>
                    </div>
                </div>
                <!-- Tab Content -->
                <div class="col-xl-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Application Settings Tab -->
                                <div class="tab-pane fade show active" id="app" role="tabpanel"
                                    aria-labelledby="app-tab">
                                    <h4>Application Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="application">
                                        <h6 class="mb-3">Core Settings</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-app"></i></span>
                                                    <input type="text" id="app_name" name="app_name"
                                                        class="form-float-input" placeholder="Application Name"
                                                        value="{{ env('APP_NAME', $settingsMap['application.app_name']->value ?? '') }}"
                                                        required>
                                                    <label for="app_name" class="form-float-label">Application
                                                        Name</label>
                                                </div>
                                                <small class="text-muted">Name of the application (e.g., Laravel).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="app_env" name="app_env" class="form-float-input"
                                                        data-select="dropdown" required>
                                                        <option value="local"
                                                            {{ env('APP_ENV', $settingsMap['application.app_env']->value ?? '') === 'local' ? 'selected' : '' }}>
                                                            Local</option>
                                                        <option value="testing"
                                                            {{ env('APP_ENV', $settingsMap['application.app_env']->value ?? '') === 'testing' ? 'selected' : '' }}>
                                                            Testing</option>
                                                        <option value="production"
                                                            {{ env('APP_ENV', $settingsMap['application.app_env']->value ?? '') === 'production' ? 'selected' : '' }}>
                                                            Production</option>
                                                    </select>
                                                    <label for="app_env" class="form-float-label">Environment</label>
                                                </div>
                                                <small class="text-muted">Application environment mode.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="app_key" name="app_key"
                                                        class="form-float-input" placeholder="Application Key"
                                                        value="{{ env('APP_KEY', $settingsMap['application.app_key']->value ?? '') }}"
                                                        required>
                                                    <label for="app_key" class="form-float-label">Application Key</label>
                                                </div>
                                                <small class="text-muted">Unique key for encryption (do not share).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="app_debug" name="app_debug" class="form-float-input"
                                                        data-select="dropdown" required>
                                                        <option value="true"
                                                            {{ env('APP_DEBUG', $settingsMap['application.app_debug']->value ?? '') == 'true' ? 'selected' : '' }}>
                                                            True</option>
                                                        <option value="false"
                                                            {{ env('APP_DEBUG', $settingsMap['application.app_debug']->value ?? '') == 'false' ? 'selected' : '' }}>
                                                            False</option>
                                                    </select>
                                                    <label for="app_debug" class="form-float-label">Debug Mode</label>
                                                </div>
                                                <small class="text-muted">Enable for detailed error output (disable in
                                                    production).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-timezone"></i></span>
                                                    <input type="text" id="app_timezone" name="app_timezone"
                                                        class="form-float-input" placeholder="Timezone"
                                                        value="{{ env('APP_TIMEZONE', $settingsMap['application.app_timezone']->value ?? '') }}"
                                                        required>
                                                    <label for="app_timezone" class="form-float-label">Timezone</label>
                                                </div>
                                                <small class="text-muted">Application timezone (e.g.,
                                                    Asia/Kolkata).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-url"></i></span>
                                                    <input type="text" id="app_url" name="app_url"
                                                        class="form-float-input" placeholder="Base URL"
                                                        value="{{ env('APP_URL', $settingsMap['application.app_url']->value ?? '') }}"
                                                        required>
                                                    <label for="app_url" class="form-float-label">Base URL</label>
                                                </div>
                                                <small class="text-muted">Base URL for the application.</small>
                                            </div>
                                        </div>
                                        <h6 class="mb-3">Localization</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-language"></i></span>
                                                    <input type="text" id="app_locale" name="app_locale"
                                                        class="form-float-input" placeholder="Default Locale"
                                                        value="{{ env('APP_LOCALE', $settingsMap['localization.app_locale']->value ?? '') }}"
                                                        required>
                                                    <label for="app_locale" class="form-float-label">Default
                                                        Locale</label>
                                                </div>
                                                <small class="text-muted">Primary language locale (e.g., en).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-language"></i></span>
                                                    <input type="text" id="app_fallback_locale"
                                                        name="app_fallback_locale" class="form-float-input"
                                                        placeholder="Fallback Locale"
                                                        value="{{ env('APP_FALLBACK_LOCALE', $settingsMap['localization.app_fallback_locale']->value ?? '') }}"
                                                        required>
                                                    <label for="app_fallback_locale" class="form-float-label">Fallback
                                                        Locale</label>
                                                </div>
                                                <small class="text-muted">Fallback language if locale is
                                                    unavailable.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-language"></i></span>
                                                    <input type="text" id="app_faker_locale" name="app_faker_locale"
                                                        class="form-float-input" placeholder="Faker Locale"
                                                        value="{{ env('APP_FAKER_LOCALE', $settingsMap['localization.app_faker_locale']->value ?? '') }}"
                                                        required>
                                                    <label for="app_faker_locale" class="form-float-label">Faker
                                                        Locale</label>
                                                </div>
                                                <small class="text-muted">Locale for Faker (used in
                                                    testing/seeding).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="app_maintenance_driver" name="app_maintenance_driver"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="file"
                                                            {{ env('APP_MAINTENANCE_DRIVER', $settingsMap['maintenance.app_maintenance_driver']->value ?? '') == 'file' ? 'selected' : '' }}>
                                                            File</option>
                                                        <option value="database"
                                                            {{ env('APP_MAINTENANCE_DRIVER', $settingsMap['maintenance.app_maintenance_driver']->value ?? '') == 'database' ? 'selected' : '' }}>
                                                            Database</option>
                                                    </select>
                                                    <label for="app_maintenance_driver"
                                                        class="form-float-label">Maintenance Driver</label>
                                                </div>
                                                <small class="text-muted">Storage driver for maintenance mode.</small>
                                            </div>
                                        </div>
                                        <h6 class="mb-3">Security & Logging</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-shield"></i></span>
                                                    <input type="number" id="bcrypt_rounds" name="bcrypt_rounds"
                                                        class="form-float-input" placeholder="Bcrypt Rounds"
                                                        value="{{ env('BCRYPT_ROUNDS', $settingsMap['security.bcrypt_rounds']->value ?? '') }}"
                                                        required>
                                                    <label for="bcrypt_rounds" class="form-float-label">Bcrypt
                                                        Rounds</label>
                                                </div>
                                                <small class="text-muted">Higher rounds are more secure but slower.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="log_channel" name="log_channel"
                                                        class="form-float-input" placeholder="Log Channel"
                                                        value="{{ env('LOG_CHANNEL', $settingsMap['logging.log_channel']->value ?? '') }}"
                                                        required>
                                                    <label for="log_channel" class="form-float-label">Log Channel</label>
                                                </div>
                                                <small class="text-muted">Default logging channel (stack combines
                                                    multiple).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="log_stack" name="log_stack"
                                                        class="form-float-input" placeholder="Log Stack"
                                                        value="{{ env('LOG_STACK', $settingsMap['logging.log_stack']->value ?? '') }}"
                                                        required>
                                                    <label for="log_stack" class="form-float-label">Log Stack</label>
                                                </div>
                                                <small class="text-muted">Log stack configuration (single, daily,
                                                    etc.).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="log_level" name="log_level"
                                                        class="form-float-input" placeholder="Log Level"
                                                        value="{{ env('LOG_LEVEL', $settingsMap['logging.log_level']->value ?? '') }}"
                                                        required>
                                                    <label for="log_level" class="form-float-label">Log Level</label>
                                                </div>
                                                <small class="text-muted">Minimum log level (debug, info, warning,
                                                    etc.).</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Database Settings Tab -->
                                <div class="tab-pane fade" id="database" role="tabpanel"
                                    aria-labelledby="database-tab">
                                    <h4>Database Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="database">
                                        <h6 class="mb-3">Database Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-database"></i></span>
                                                    <input type="text" id="db_connection" name="db_connection"
                                                        class="form-float-input" placeholder="Connection Name"
                                                        value="{{ env('DB_CONNECTION', $settingsMap['database.db_connection']->value ?? '') }}"
                                                        required>
                                                    <label for="db_connection" class="form-float-label">Connection
                                                        Name</label>
                                                </div>
                                                <small class="text-muted">Database connection name (e.g., central).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-server"></i></span>
                                                    <input type="text" id="db_host" name="db_host"
                                                        class="form-float-input" placeholder="Host"
                                                        value="{{ env('DB_HOST', $settingsMap['database.db_host']->value ?? '') }}"
                                                        required>
                                                    <label for="db_host" class="form-float-label">Host</label>
                                                </div>
                                                <small class="text-muted">Database server host (e.g., 127.0.0.1).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-port"></i></span>
                                                    <input type="number" id="db_port" name="db_port"
                                                        class="form-float-input" placeholder="Port"
                                                        value="{{ env('DB_PORT', $settingsMap['database.db_port']->value ?? '') }}"
                                                        required>
                                                    <label for="db_port" class="form-float-label">Port</label>
                                                </div>
                                                <small class="text-muted">Database port (e.g., 3306 for MySQL).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-database"></i></span>
                                                    <input type="text" id="db_database" name="db_database"
                                                        class="form-float-input" placeholder="Database Name"
                                                        value="{{ env('DB_DATABASE', $settingsMap['database.db_database']->value ?? '') }}"
                                                        required>
                                                    <label for="db_database" class="form-float-label">Database
                                                        Name</label>
                                                </div>
                                                <small class="text-muted">Name of the database (e.g., got_it_v2).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-user"></i></span>
                                                    <input type="text" id="db_username" name="db_username"
                                                        class="form-float-input" placeholder="Username"
                                                        value="{{ env('DB_USERNAME', $settingsMap['database.db_username']->value ?? '') }}"
                                                        required>
                                                    <label for="db_username" class="form-float-label">Username</label>
                                                </div>
                                                <small class="text-muted">Database username (e.g., root).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="db_password" name="db_password"
                                                        class="form-float-input" placeholder="Password"
                                                        value="{{ env('DB_PASSWORD', $settingsMap['database.db_password']->value ?? '') }}">
                                                    <label for="db_password" class="form-float-label">Password</label>
                                                </div>
                                                <small class="text-muted">Leave empty for local environments.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Session Settings Tab -->
                                <div class="tab-pane fade" id="session" role="tabpanel" aria-labelledby="session-tab">
                                    <h4>Session Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="session">
                                        <h6 class="mb-3">Session Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="session_driver" name="session_driver"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="database"
                                                            {{ env('SESSION_DRIVER', $settingsMap['session.session_driver']->value ?? '') == 'database' ? 'selected' : '' }}>
                                                            Database</option>
                                                        <option value="file"
                                                            {{ env('SESSION_DRIVER', $settingsMap['session.session_driver']->value ?? '') == 'file' ? 'selected' : '' }}>
                                                            File</option>
                                                        <option value="redis"
                                                            {{ env('SESSION_DRIVER', $settingsMap['session.session_driver']->value ?? '') == 'redis' ? 'selected' : '' }}>
                                                            Redis</option>
                                                    </select>
                                                    <label for="session_driver" class="form-float-label">Session
                                                        Driver</label>
                                                </div>
                                                <small class="text-muted">Storage driver for sessions.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="session_lifetime" name="session_lifetime"
                                                        class="form-float-input" placeholder="Lifetime (minutes)"
                                                        value="{{ env('SESSION_LIFETIME', $settingsMap['session.session_lifetime']->value ?? '') }}"
                                                        required>
                                                    <label for="session_lifetime" class="form-float-label">Lifetime
                                                        (minutes)</label>
                                                </div>
                                                <small class="text-muted">Session duration before timeout.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="session_encrypt" name="session_encrypt"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="false"
                                                            {{ env('SESSION_ENCRYPT', $settingsMap['session.session_encrypt']->value ?? '') == 'false' ? 'selected' : '' }}>
                                                            False</option>
                                                        <option value="true"
                                                            {{ env('SESSION_ENCRYPT', $settingsMap['session.session_encrypt']->value ?? '') == 'true' ? 'selected' : '' }}>
                                                            True</option>
                                                    </select>
                                                    <label for="session_encrypt" class="form-float-label">Encrypt
                                                        Session</label>
                                                </div>
                                                <small class="text-muted">Disable for performance in local
                                                    environments.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-path"></i></span>
                                                    <input type="text" id="session_path" name="session_path"
                                                        class="form-float-input" placeholder="Session Path"
                                                        value="{{ env('SESSION_PATH', $settingsMap['session.session_path']->value ?? '') }}"
                                                        required>
                                                    <label for="session_path" class="form-float-label">Session
                                                        Path</label>
                                                </div>
                                                <small class="text-muted">Path where sessions are accessible.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Mail Settings Tab -->
                                <div class="tab-pane fade" id="mail" role="tabpanel" aria-labelledby="mail-tab">
                                    <h4>Mail Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="mail">
                                        <h6 class="mb-3">Email Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="mail_mailer" name="mail_mailer" class="form-float-input"
                                                        data-select="dropdown" required>
                                                        <option value="log"
                                                            {{ env('MAIL_MAILER', $settingsMap['mail.mail_mailer']->value ?? '') == 'log' ? 'selected' : '' }}>
                                                            Log</option>
                                                        <option value="smtp"
                                                            {{ env('MAIL_MAILER', $settingsMap['mail.mail_mailer']->value ?? '') == 'smtp' ? 'selected' : '' }}>
                                                            SMTP</option>
                                                        <option value="sendmail"
                                                            {{ env('MAIL_MAILER', $settingsMap['mail.mail_mailer']->value ?? '') == 'sendmail' ? 'selected' : '' }}>
                                                            Sendmail</option>
                                                    </select>
                                                    <label for="mail_mailer" class="form-float-label">Mail Driver</label>
                                                </div>
                                                <small class="text-muted">Logs emails in local environment.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-protocol"></i></span>
                                                    <input type="text" id="mail_scheme" name="mail_scheme"
                                                        class="form-float-input" placeholder="Mail Scheme"
                                                        value="{{ env('MAIL_SCHEME', $settingsMap['mail.mail_scheme']->value ?? '') }}">
                                                    <label for="mail_scheme" class="form-float-label">Mail Scheme</label>
                                                </div>
                                                <small class="text-muted">Protocol for mail (null for log driver).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-server"></i></span>
                                                    <input type="text" id="mail_host" name="mail_host"
                                                        class="form-float-input" placeholder="SMTP Host"
                                                        value="{{ env('MAIL_HOST', $settingsMap['mail.mail_host']->value ?? '') }}">
                                                    <label for="mail_host" class="form-float-label">SMTP Host</label>
                                                </div>
                                                <small class="text-muted">Unused for log driver.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-port"></i></span>
                                                    <input type="number" id="mail_port" name="mail_port"
                                                        class="form-float-input" placeholder="SMTP Port"
                                                        value="{{ env('MAIL_PORT', $settingsMap['mail.mail_port']->value ?? '') }}">
                                                    <label for="mail_port" class="form-float-label">SMTP Port</label>
                                                </div>
                                                <small class="text-muted">Unused for log driver.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-user"></i></span>
                                                    <input type="text" id="mail_username" name="mail_username"
                                                        class="form-float-input" placeholder="SMTP Username"
                                                        value="{{ env('MAIL_USERNAME', $settingsMap['mail.mail_username']->value ?? '') }}">
                                                    <label for="mail_username" class="form-float-label">SMTP
                                                        Username</label>
                                                </div>
                                                <small class="text-muted">SMTP authentication username.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="mail_password" name="mail_password"
                                                        class="form-float-input" placeholder="SMTP Password"
                                                        value="{{ env('MAIL_PASSWORD', $settingsMap['mail.mail_password']->value ?? '') }}">
                                                    <label for="mail_password" class="form-float-label">SMTP
                                                        Password</label>
                                                </div>
                                                <small class="text-muted">SMTP authentication password.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-mail"></i></span>
                                                    <input type="email" id="mail_from_address" name="mail_from_address"
                                                        class="form-float-input" placeholder="From Address"
                                                        value="{{ env('MAIL_FROM_ADDRESS', $settingsMap['mail.mail_from_address']->value ?? '') }}"
                                                        required>
                                                    <label for="mail_from_address" class="form-float-label">From
                                                        Address</label>
                                                </div>
                                                <small class="text-muted">Email address for outgoing emails.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-name"></i></span>
                                                    <input type="text" id="mail_from_name" name="mail_from_name"
                                                        class="form-float-input" placeholder="From Name"
                                                        value="{{ env('MAIL_FROM_NAME', $settingsMap['mail.mail_from_name']->value ?? '') }}"
                                                        required>
                                                    <label for="mail_from_name" class="form-float-label">From Name</label>
                                                </div>
                                                <small class="text-muted">Display name for outgoing emails.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- AWS S3 Settings Tab -->
                                <div class="tab-pane fade" id="aws" role="tabpanel" aria-labelledby="aws-tab">
                                    <h4>AWS S3 Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="aws">
                                        <h6 class="mb-3">AWS S3 Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="aws_access_key_id"
                                                        name="aws_access_key_id" class="form-float-input"
                                                        placeholder="Access Key ID"
                                                        value="{{ env('AWS_ACCESS_KEY_ID', $settingsMap['aws.aws_access_key_id']->value ?? '') }}">
                                                    <label for="aws_access_key_id" class="form-float-label">Access Key
                                                        ID</label>
                                                </div>
                                                <small class="text-muted">AWS access key for S3 authentication.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="aws_secret_access_key"
                                                        name="aws_secret_access_key" class="form-float-input"
                                                        placeholder="Secret Access Key"
                                                        value="{{ env('AWS_SECRET_ACCESS_KEY', $settingsMap['aws.aws_secret_access_key']->value ?? '') }}">
                                                    <label for="aws_secret_access_key" class="form-float-label">Secret
                                                        Access Key</label>
                                                </div>
                                                <small class="text-muted">AWS secret key for S3 authentication.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-region"></i></span>
                                                    <input type="text" id="aws_default_region"
                                                        name="aws_default_region" class="form-float-input"
                                                        placeholder="Default Region"
                                                        value="{{ env('AWS_DEFAULT_REGION', $settingsMap['aws.aws_default_region']->value ?? '') }}"
                                                        required>
                                                    <label for="aws_default_region" class="form-float-label">Default
                                                        Region</label>
                                                </div>
                                                <small class="text-muted">AWS region (e.g., us-east-1).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-bucket"></i></span>
                                                    <input type="text" id="aws_bucket" name="aws_bucket"
                                                        class="form-float-input" placeholder="Bucket Name"
                                                        value="{{ env('AWS_BUCKET', $settingsMap['aws.aws_bucket']->value ?? '') }}">
                                                    <label for="aws_bucket" class="form-float-label">Bucket Name</label>
                                                </div>
                                                <small class="text-muted">Name of the S3 bucket.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="aws_use_path_style_endpoint"
                                                        name="aws_use_path_style_endpoint" class="form-float-input"
                                                        data-select="dropdown" required>
                                                        <option value="false"
                                                            {{ env('AWS_USE_PATH_STYLE_ENDPOINT', $settingsMap['aws.aws_use_path_style_endpoint']->value ?? '') == 'false' ? 'selected' : '' }}>
                                                            False</option>
                                                        <option value="true"
                                                            {{ env('AWS_USE_PATH_STYLE_ENDPOINT', $settingsMap['aws.aws_use_path_style_endpoint']->value ?? '') == 'true' ? 'selected' : '' }}>
                                                            True</option>
                                                    </select>
                                                    <label for="aws_use_path_style_endpoint" class="form-float-label">Path
                                                        Style Endpoint</label>
                                                </div>
                                                <small class="text-muted">Use path-style endpoint for S3.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Reverb Settings Tab -->
                                <div class="tab-pane fade" id="reverb" role="tabpanel" aria-labelledby="reverb-tab">
                                    <h4>Reverb Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="reverb">
                                        <h6 class="mb-3">Reverb Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-id"></i></span>
                                                    <input type="text" id="reverb_app_id" name="reverb_app_id"
                                                        class="form-float-input" placeholder="Application ID"
                                                        value="{{ env('REVERB_APP_ID', $settingsMap['reverb.reverb_app_id']->value ?? '') }}"
                                                        required>
                                                    <label for="reverb_app_id" class="form-float-label">Application
                                                        ID</label>
                                                </div>
                                                <small class="text-muted">Reverb application ID.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="reverb_app_key" name="reverb_app_key"
                                                        class="form-float-input" placeholder="Application Key"
                                                        value="{{ env('REVERB_APP_KEY', $settingsMap['reverb.reverb_app_key']->value ?? '') }}"
                                                        required>
                                                    <label for="reverb_app_key" class="form-float-label">Application
                                                        Key</label>
                                                </div>
                                                <small class="text-muted">Do not share this key.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end toggle-password"><i
                                                            class="ti ti-eye-off"></i></span>
                                                    <input type="password" id="reverb_app_secret"
                                                        name="reverb_app_secret" class="form-float-input"
                                                        placeholder="Application Secret"
                                                        value="{{ env('REVERB_APP_SECRET', $settingsMap['reverb.reverb_app_secret']->value ?? '') }}"
                                                        required>
                                                    <label for="reverb_app_secret" class="form-float-label">Application
                                                        Secret</label>
                                                </div>
                                                <small class="text-muted">Do not share this secret.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-server"></i></span>
                                                    <input type="text" id="reverb_host" name="reverb_host"
                                                        class="form-float-input" placeholder="Host"
                                                        value="{{ env('REVERB_HOST', $settingsMap['reverb.reverb_host']->value ?? '') }}"
                                                        required>
                                                    <label for="reverb_host" class="form-float-label">Host</label>
                                                </div>
                                                <small class="text-muted">Reverb server host.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-port"></i></span>
                                                    <input type="number" id="reverb_port" name="reverb_port"
                                                        class="form-float-input" placeholder="Port"
                                                        value="{{ env('REVERB_PORT', $settingsMap['reverb.reverb_port']->value ?? '') }}"
                                                        required>
                                                    <label for="reverb_port" class="form-float-label">Port</label>
                                                </div>
                                                <small class="text-muted">Reverb server port.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="reverb_scheme" name="reverb_scheme"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="http"
                                                            {{ env('REVERB_SCHEME', $settingsMap['reverb.reverb_scheme']->value ?? '') == 'http' ? 'selected' : '' }}>
                                                            HTTP</option>
                                                        <option value="https"
                                                            {{ env('REVERB_SCHEME', $settingsMap['reverb.reverb_scheme']->value ?? '') == 'https' ? 'selected' : '' }}>
                                                            HTTPS</option>
                                                    </select>
                                                    <label for="reverb_scheme" class="form-float-label">Scheme</label>
                                                </div>
                                                <small class="text-muted">Protocol for Reverb connection.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Skeleton Framework Tab -->
                                <div class="tab-pane fade" id="skeleton" role="tabpanel"
                                    aria-labelledby="skeleton-tab">
                                    <h4>Skeleton Framework Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="skeleton">
                                        <h6 class="mb-3">Skeleton Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="skeleton_developer_mode" name="skeleton_developer_mode"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="true"
                                                            {{ env('SKELETON_DEVELOPER_MODE', $settingsMap['skeleton.skeleton_developer_mode']->value ?? '') == 'true' ? 'selected' : '' }}>
                                                            True</option>
                                                        <option value="false"
                                                            {{ env('SKELETON_DEVELOPER_MODE', $settingsMap['skeleton.skeleton_developer_mode']->value ?? '') == 'false' ? 'selected' : '' }}>
                                                            False</option>
                                                    </select>
                                                    <label for="skeleton_developer_mode"
                                                        class="form-float-label">Developer Mode</label>
                                                </div>
                                                <small class="text-muted">Enable for detailed logging.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="skeleton_developer_logs"
                                                        name="skeleton_developer_logs" class="form-float-input"
                                                        placeholder="Developer Logs"
                                                        value="{{ env('SKELETON_DEVELOPER_LOGS', $settingsMap['skeleton.skeleton_developer_logs']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_developer_logs"
                                                        class="form-float-label">Developer Logs</label>
                                                </div>
                                                <small class="text-muted">Log levels for developer mode.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-database"></i></span>
                                                    <input type="text" id="skeleton_allowed_systems"
                                                        name="skeleton_allowed_systems" class="form-float-input"
                                                        placeholder="Allowed Systems"
                                                        value="{{ env('SKELETON_ALLOWED_SYSTEMS', $settingsMap['skeleton.skeleton_allowed_systems']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_allowed_systems" class="form-float-label">Allowed
                                                        Systems</label>
                                                </div>
                                                <small class="text-muted">Allowed database systems (central: got_it_v2,
                                                    business: infosysdb).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="skeleton_cache_ttl"
                                                        name="skeleton_cache_ttl" class="form-float-input"
                                                        placeholder="Cache TTL"
                                                        value="{{ env('SKELETON_CACHE_TTL', $settingsMap['skeleton.skeleton_cache_ttl']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_cache_ttl" class="form-float-label">Cache TTL
                                                        (seconds)</label>
                                                </div>
                                                <small class="text-muted">Cache duration in seconds.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-token"></i></span>
                                                    <input type="number" id="skeleton_token_length"
                                                        name="skeleton_token_length" class="form-float-input"
                                                        placeholder="Token Length"
                                                        value="{{ env('SKELETON_TOKEN_LENGTH', $settingsMap['skeleton.skeleton_token_length']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_token_length" class="form-float-label">Token
                                                        Length</label>
                                                </div>
                                                <small class="text-muted">Length of generated tokens.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="skeleton_token_reload" name="skeleton_token_reload"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="false"
                                                            {{ env('SKELETON_TOKEN_RELOAD', $settingsMap['skeleton.skeleton_token_reload']->value ?? '') == 'false' ? 'selected' : '' }}>
                                                            False</option>
                                                        <option value="true"
                                                            {{ env('SKELETON_TOKEN_RELOAD', $settingsMap['skeleton.skeleton_token_reload']->value ?? '') == 'true' ? 'selected' : '' }}>
                                                            True</option>
                                                    </select>
                                                    <label for="skeleton_token_reload" class="form-float-label">Token
                                                        Reload</label>
                                                </div>
                                                <small class="text-muted">Reload tokens on each request.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-attempt"></i></span>
                                                    <input type="number" id="skeleton_max_token_attempts"
                                                        name="skeleton_max_token_attempts" class="form-float-input"
                                                        placeholder="Max Token Attempts"
                                                        value="{{ env('SKELETON_MAX_TOKEN_ATTEMPTS', $settingsMap['skeleton.skeleton_max_token_attempts']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_max_token_attempts" class="form-float-label">Max
                                                        Token Attempts</label>
                                                </div>
                                                <small class="text-muted">Maximum token generation attempts.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-key"></i></span>
                                                    <input type="text" id="skeleton_session_db_key"
                                                        name="skeleton_session_db_key" class="form-float-input"
                                                        placeholder="Session DB Key"
                                                        value="{{ env('SKELETON_SESSION_DB_KEY', $settingsMap['skeleton.skeleton_session_db_key']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_session_db_key" class="form-float-label">Session
                                                        DB Key</label>
                                                </div>
                                                <small class="text-muted">Session key for storing business database
                                                    name.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-lock"></i></span>
                                                    <input type="text" id="skeleton_encryption_cipher"
                                                        name="skeleton_encryption_cipher" class="form-float-input"
                                                        placeholder="Encryption Cipher"
                                                        value="{{ env('SKELETON_ENCRYPTION_CIPHER', $settingsMap['skeleton.skeleton_encryption_cipher']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_encryption_cipher"
                                                        class="form-float-label">Encryption Cipher</label>
                                                </div>
                                                <small class="text-muted">Cipher for encryption (e.g.,
                                                    AES-256-CBC).</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-queue"></i></span>
                                                    <input type="text" id="skeleton_encryption_queue"
                                                        name="skeleton_encryption_queue" class="form-float-input"
                                                        placeholder="Encryption Queue"
                                                        value="{{ env('SKELETON_ENCRYPTION_QUEUE', $settingsMap['skeleton.skeleton_encryption_queue']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_encryption_queue"
                                                        class="form-float-label">Encryption Queue</label>
                                                </div>
                                                <small class="text-muted">Queue for encryption tasks.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="skeleton_password_expiry_days"
                                                        name="skeleton_password_expiry_days" class="form-float-input"
                                                        placeholder="Password Expiry Days"
                                                        value="{{ env('SKELETON_PASSWORD_EXPIRY_DAYS', $settingsMap['skeleton.skeleton_password_expiry_days']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_password_expiry_days"
                                                        class="form-float-label">Password Expiry Days</label>
                                                </div>
                                                <small class="text-muted">Days before password expires.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-login"></i></span>
                                                    <input type="number" id="skeleton_max_logins"
                                                        name="skeleton_max_logins" class="form-float-input"
                                                        placeholder="Max Logins"
                                                        value="{{ env('SKELETON_MAX_LOGINS', $settingsMap['skeleton.skeleton_max_logins']->value ?? '') }}"
                                                        required>
                                                    <label for="skeleton_max_logins" class="form-float-label">Max
                                                        Logins</label>
                                                </div>
                                                <small class="text-muted">Max concurrent logins per user.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- ADMS Settings Tab -->
                                <div class="tab-pane fade" id="adms" role="tabpanel" aria-labelledby="adms-tab">
                                    <h4>ADMS Settings</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="adms">
                                        <h6 class="mb-3">ADMS Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-batch"></i></span>
                                                    <input type="number" id="adms_batch_size" name="adms_batch_size"
                                                        class="form-float-input" placeholder="Batch Size"
                                                        value="{{ env('ADMS_BATCH_SIZE', $settingsMap['adms.adms_batch_size']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_batch_size" class="form-float-label">Batch
                                                        Size</label>
                                                </div>
                                                <small class="text-muted">Number of records to process per batch.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="adms_cache_driver" name="adms_cache_driver"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="database"
                                                            {{ env('ADMS_CACHE_DRIVER', $settingsMap['adms.adms_cache_driver']->value ?? '') == 'database' ? 'selected' : '' }}>
                                                            Database</option>
                                                        <option value="redis"
                                                            {{ env('ADMS_CACHE_DRIVER', $settingsMap['adms.adms_cache_driver']->value ?? '') == 'redis' ? 'selected' : '' }}>
                                                            Redis</option>
                                                        <option value="file"
                                                            {{ env('ADMS_CACHE_DRIVER', $settingsMap['adms.adms_cache_driver']->value ?? '') == 'file' ? 'selected' : '' }}>
                                                            File</option>
                                                    </select>
                                                    <label for="adms_cache_driver" class="form-float-label">Cache
                                                        Driver</label>
                                                </div>
                                                <small class="text-muted">Cache storage driver for ADMS.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-prefix"></i></span>
                                                    <input type="text" id="adms_cache_prefix"
                                                        name="adms_cache_prefix" class="form-float-input"
                                                        placeholder="Cache Prefix"
                                                        value="{{ env('ADMS_CACHE_PREFIX', $settingsMap['adms.adms_cache_prefix']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_cache_prefix" class="form-float-label">Cache
                                                        Prefix</label>
                                                </div>
                                                <small class="text-muted">Prefix for ADMS cache keys.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_device_ttl" name="adms_device_ttl"
                                                        class="form-float-input" placeholder="Device TTL"
                                                        value="{{ env('ADMS_DEVICE_TTL', $settingsMap['adms.adms_device_ttl']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_device_ttl" class="form-float-label">Device TTL
                                                        (seconds)</label>
                                                </div>
                                                <small class="text-muted">Device cache duration in seconds.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_settings_ttl"
                                                        name="adms_settings_ttl" class="form-float-input"
                                                        placeholder="Settings TTL"
                                                        value="{{ env('ADMS_SETTINGS_TTL', $settingsMap['adms.adms_settings_ttl']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_settings_ttl" class="form-float-label">Settings TTL
                                                        (seconds)</label>
                                                </div>
                                                <small class="text-muted">Settings cache duration in seconds.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_commands_ttl"
                                                        name="adms_commands_ttl" class="form-float-input"
                                                        placeholder="Commands TTL"
                                                        value="{{ env('ADMS_COMMANDS_TTL', $settingsMap['adms.adms_commands_ttl']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_commands_ttl" class="form-float-label">Commands TTL
                                                        (seconds)</label>
                                                </div>
                                                <small class="text-muted">Commands cache duration in seconds.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_request_ttl"
                                                        name="adms_request_ttl" class="form-float-input"
                                                        placeholder="Request TTL"
                                                        value="{{ env('ADMS_REQUEST_TTL', $settingsMap['adms.adms_request_ttl']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_request_ttl" class="form-float-label">Request TTL
                                                        (seconds)</label>
                                                </div>
                                                <small class="text-muted">Request cache duration in seconds.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <select id="adms_queue_driver" name="adms_queue_driver"
                                                        class="form-float-input" data-select="dropdown" required>
                                                        <option value="database"
                                                            {{ env('ADMS_QUEUE_DRIVER', $settingsMap['adms.adms_queue_driver']->value ?? '') == 'database' ? 'selected' : '' }}>
                                                            Database</option>
                                                        <option value="redis"
                                                            {{ env('ADMS_QUEUE_DRIVER', $settingsMap['adms.adms_queue_driver']->value ?? '') == 'redis' ? 'selected' : '' }}>
                                                            Redis</option>
                                                        <option value="rabbitmq"
                                                            {{ env('ADMS_QUEUE_DRIVER', $settingsMap['adms.adms_queue_driver']->value ?? '') == 'rabbitmq' ? 'selected' : '' }}>
                                                            RabbitMQ</option>
                                                    </select>
                                                    <label for="adms_queue_driver" class="form-float-label">Queue
                                                        Driver</label>
                                                </div>
                                                <small class="text-muted">Queue driver for ADMS tasks.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-queue"></i></span>
                                                    <input type="text" id="adms_queue_connection"
                                                        name="adms_queue_connection" class="form-float-input"
                                                        placeholder="Queue Connection"
                                                        value="{{ env('ADMS_QUEUE_CONNECTION', $settingsMap['adms.adms_queue_connection']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_queue_connection" class="form-float-label">Queue
                                                        Connection</label>
                                                </div>
                                                <small class="text-muted">Queue connection name.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-prefix"></i></span>
                                                    <input type="text" id="adms_queue_prefix"
                                                        name="adms_queue_prefix" class="form-float-input"
                                                        placeholder="Queue Prefix"
                                                        value="{{ env('ADMS_QUEUE_PREFIX', $settingsMap['adms.adms_queue_prefix']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_queue_prefix" class="form-float-label">Queue
                                                        Prefix</label>
                                                </div>
                                                <small class="text-muted">Prefix for queue names.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_queue_retry_after"
                                                        name="adms_queue_retry_after" class="form-float-input"
                                                        placeholder="Retry After"
                                                        value="{{ env('ADMS_QUEUE_RETRY_AFTER', $settingsMap['adms.adms_queue_retry_after']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_queue_retry_after" class="form-float-label">Retry
                                                        After (seconds)</label>
                                                </div>
                                                <small class="text-muted">Delay before retrying failed jobs.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-attempt"></i></span>
                                                    <input type="number" id="adms_max_retries"
                                                        name="adms_max_retries" class="form-float-input"
                                                        placeholder="Max Retries"
                                                        value="{{ env('ADMS_MAX_RETRIES', $settingsMap['adms.adms_max_retries']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_max_retries" class="form-float-label">Max
                                                        Retries</label>
                                                </div>
                                                <small class="text-muted">Maximum retry attempts for failed jobs.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-time"></i></span>
                                                    <input type="number" id="adms_retry_delay_ms"
                                                        name="adms_retry_delay_ms" class="form-float-input"
                                                        placeholder="Retry Delay (ms)"
                                                        value="{{ env('ADMS_RETRY_DELAY_MS', $settingsMap['adms.adms_retry_delay_ms']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_retry_delay_ms" class="form-float-label">Retry
                                                        Delay (ms)</label>
                                                </div>
                                                <small class="text-muted">Delay between retry attempts in
                                                    milliseconds.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-backoff"></i></span>
                                                    <input type="number" id="adms_backoff_factor"
                                                        name="adms_backoff_factor" class="form-float-input"
                                                        placeholder="Backoff Factor"
                                                        value="{{ env('ADMS_BACKOFF_FACTOR', $settingsMap['adms.adms_backoff_factor']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_backoff_factor" class="form-float-label">Backoff
                                                        Factor</label>
                                                </div>
                                                <small class="text-muted">Multiplier for exponential backoff.</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-database"></i></span>
                                                    <input type="text" id="adms_central_db_connection"
                                                        name="adms_central_db_connection" class="form-float-input"
                                                        placeholder="Central DB Connection"
                                                        value="{{ env('ADMS_CENTRAL_DB_CONNECTION', $settingsMap['adms.adms_central_db_connection']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_central_db_connection"
                                                        class="form-float-label">Central DB Connection</label>
                                                </div>
                                                <small class="text-muted">Database connection for ADMS.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="adms_log_level" name="adms_log_level"
                                                        class="form-float-input" placeholder="Log Level"
                                                        value="{{ env('ADMS_LOG_LEVEL', $settingsMap['adms.adms_log_level']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_log_level" class="form-float-label">Log
                                                        Level</label>
                                                </div>
                                                <small class="text-muted">Minimum log level for ADMS (e.g., info).</small>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="float-input-control">
                                                    <span class="float-group-end"><i class="ti ti-log"></i></span>
                                                    <input type="text" id="adms_log_channel"
                                                        name="adms_log_channel" class="form-float-input"
                                                        placeholder="Log Channel"
                                                        value="{{ env('ADMS_LOG_CHANNEL', $settingsMap['adms.adms_log_channel']->value ?? '') }}"
                                                        required>
                                                    <label for="adms_log_channel" class="form-float-label">Log
                                                        Channel</label>
                                                </div>
                                                <small class="text-muted">Logging channel for ADMS.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Social Login Keys Tab -->
                                <div class="tab-pane fade" id="social" role="tabpanel"
                                    aria-labelledby="social-tab">
                                    <h4>Social Login Keys</h4>
                                    <form method="POST" action="{{ $formAction }}">
                                        @csrf
                                        <input type="hidden" name="save_token" value="{{ $formSkeletonToken }}">
                                        <input type="hidden" name="form_type" value="social">
                                        <h6 class="mb-3">Social Login Configuration</h6>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h6><i class="ti ti-brand-google me-2"></i>Google</h6>
                                                        <p class="text-muted">Configure Google OAuth for user
                                                            authentication. Obtain credentials from Google Cloud Console.
                                                        </p>
                                                        <div class="float-input-control">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-id"></i></span>
                                                            <input type="text" id="google_client_id"
                                                                name="google_client_id" class="form-float-input"
                                                                placeholder="Google Client ID"
                                                                value="{{ env('GOOGLE_CLIENT_ID', $settingsMap['social.google_client_id']->value ?? '') }}"
                                                                required>
                                                            <label for="google_client_id"
                                                                class="form-float-label">Google Client ID</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end toggle-password"><i
                                                                    class="ti ti-eye-off"></i></span>
                                                            <input type="password" id="google_client_secret"
                                                                name="google_client_secret" class="form-float-input"
                                                                placeholder="Google Client Secret"
                                                                value="{{ env('GOOGLE_CLIENT_SECRET', $settingsMap['social.google_client_secret']->value ?? '') }}"
                                                                required>
                                                            <label for="google_client_secret"
                                                                class="form-float-label">Google Client Secret</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-url"></i></span>
                                                            <input type="url" id="google_redirect_uri"
                                                                name="google_redirect_uri" class="form-float-input"
                                                                placeholder="Google Redirect URI"
                                                                value="{{ env('GOOGLE_REDIRECT_URI', $settingsMap['social.google_redirect_uri']->value ?? '') }}"
                                                                required>
                                                            <label for="google_redirect_uri"
                                                                class="form-float-label">Google Redirect URI</label>
                                                        </div>
                                                        <small class="text-muted">Redirect URI must match the one
                                                            configured in Google Cloud Console.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h6><i class="ti ti-brand-facebook me-2"></i>Facebook</h6>
                                                        <p class="text-muted">Set up Facebook OAuth for login. Obtain
                                                            credentials from Facebook Developer Portal.</p>
                                                        <div class="float-input-control">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-id"></i></span>
                                                            <input type="text" id="facebook_client_id"
                                                                name="facebook_client_id" class="form-float-input"
                                                                placeholder="Facebook App ID"
                                                                value="{{ env('FACEBOOK_CLIENT_ID', $settingsMap['social.facebook_client_id']->value ?? '') }}"
                                                                required>
                                                            <label for="facebook_client_id"
                                                                class="form-float-label">Facebook App ID</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end toggle-password"><i
                                                                    class="ti ti-eye-off"></i></span>
                                                            <input type="password" id="facebook_client_secret"
                                                                name="facebook_client_secret" class="form-float-input"
                                                                placeholder="Facebook App Secret"
                                                                value="{{ env('FACEBOOK_CLIENT_SECRET', $settingsMap['social.facebook_client_secret']->value ?? '') }}"
                                                                required>
                                                            <label for="facebook_client_secret"
                                                                class="form-float-label">Facebook App Secret</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-url"></i></span>
                                                            <input type="url" id="facebook_redirect_uri"
                                                                name="facebook_redirect_uri" class="form-float-input"
                                                                placeholder="Facebook Redirect URI"
                                                                value="{{ env('FACEBOOK_REDIRECT_URI', $settingsMap['social.facebook_redirect_uri']->value ?? '') }}"
                                                                required>
                                                            <label for="facebook_redirect_uri"
                                                                class="form-float-label">Facebook Redirect URI</label>
                                                        </div>
                                                        <small class="text-muted">Redirect URI must match the one in
                                                            Facebook Developer Portal.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h6><i class="ti ti-brand-x me-2"></i>X</h6>
                                                        <p class="text-muted">Enable login via X platform. Use credentials
                                                            from X Developer Portal.</p>
                                                        <div class="float-input-control">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-id"></i></span>
                                                            <input type="text" id="x_client_id" name="x_client_id"
                                                                class="form-float-input" placeholder="X API Key"
                                                                value="{{ env('X_CLIENT_ID', $settingsMap['social.x_client_id']->value ?? '') }}"
                                                                required>
                                                            <label for="x_client_id" class="form-float-label">X API
                                                                Key</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end toggle-password"><i
                                                                    class="ti ti-eye-off"></i></span>
                                                            <input type="password" id="x_client_secret"
                                                                name="x_client_secret" class="form-float-input"
                                                                placeholder="X API Secret"
                                                                value="{{ env('X_CLIENT_SECRET', $settingsMap['social.x_client_secret']->value ?? '') }}"
                                                                required>
                                                            <label for="x_client_secret" class="form-float-label">X API
                                                                Secret</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-url"></i></span>
                                                            <input type="url" id="x_redirect_uri"
                                                                name="x_redirect_uri" class="form-float-input"
                                                                placeholder="X Redirect URI"
                                                                value="{{ env('X_REDIRECT_URI', $settingsMap['social.x_redirect_uri']->value ?? '') }}"
                                                                required>
                                                            <label for="x_redirect_uri" class="form-float-label">X
                                                                Redirect URI</label>
                                                        </div>
                                                        <small class="text-muted">Redirect URI must match the one in X
                                                            Developer Portal.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h6><i class="ti ti-brand-github me-2"></i>GitHub</h6>
                                                        <p class="text-muted">Configure GitHub OAuth for authentication.
                                                            Obtain credentials from GitHub Developer Settings.</p>
                                                        <div class="float-input-control">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-id"></i></span>
                                                            <input type="text" id="github_client_id"
                                                                name="github_client_id" class="form-float-input"
                                                                placeholder="GitHub Client ID"
                                                                value="{{ env('GITHUB_CLIENT_ID', $settingsMap['social.github_client_id']->value ?? '') }}"
                                                                required>
                                                            <label for="github_client_id"
                                                                class="form-float-label">GitHub Client ID</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end toggle-password"><i
                                                                    class="ti ti-eye-off"></i></span>
                                                            <input type="password" id="github_client_secret"
                                                                name="github_client_secret" class="form-float-input"
                                                                placeholder="GitHub Client Secret"
                                                                value="{{ env('GITHUB_CLIENT_SECRET', $settingsMap['social.github_client_secret']->value ?? '') }}"
                                                                required>
                                                            <label for="github_client_secret"
                                                                class="form-float-label">GitHub Client Secret</label>
                                                        </div>
                                                        <div class="float-input-control mt-3">
                                                            <span class="float-group-end"><i
                                                                    class="ti ti-url"></i></span>
                                                            <input type="url" id="github_redirect_uri"
                                                                name="github_redirect_uri" class="form-float-input"
                                                                placeholder="GitHub Redirect URI"
                                                                value="{{ env('GITHUB_REDIRECT_URI', $settingsMap['social.github_redirect_uri']->value ?? '') }}"
                                                                required>
                                                            <label for="github_redirect_uri"
                                                                class="form-float-label">GitHub Redirect URI</label>
                                                        </div>
                                                        <small class="text-muted">Redirect URI must match the one in
                                                            GitHub Developer Settings.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-light me-2">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
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
