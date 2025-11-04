<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batch Processing Configuration
    |--------------------------------------------------------------------------
    | Defines batch size for bulk operations, optimized for high volume (100K pins).
    */
    'batch_size' => env('ADMS_BATCH_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Defines TTLs for caching device data and commands for quick responses.
    */
    'cache' => [
        'device_ttl' => env('ADMS_DEVICE_TTL', 3600),
        'commands_ttl' => env('ADMS_COMMANDS_TTL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Rate Limit Configuration
    |--------------------------------------------------------------------------
    | Defines rate limits per minute for requests, optimized for 10K devices.
    | Supports priority-based limits for business and device.
    */
    'rate_limit' => [
        'requests_per_minute' => env('ADMS_REQUEST_RATE_LIMIT', 1000),
        'commands_per_minute' => env('ADMS_COMMANDS_RATE_LIMIT', 100),
        'ttl' => env('ADMS_RATE_LIMIT_TTL', 60),
        'per_business' => env('ADMS_BUSINESS_RATE_LIMIT', 1500),
        'per_device' => env('ADMS_DEVICE_RATE_LIMIT', 150),
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Expiration Configuration
    |--------------------------------------------------------------------------
    | Defines expiration time for commands in seconds.
    */
    'commands' => [
        'expiration' => env('ADMS_COMMAND_EXPIRATION', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    | Defines queue names and settings for job processing.
    */
    'queue' => [
        'name' => env('ADMS_QUEUE_NAME', 'adms'),
        'service_name' => env('ADMS_QUEUE_SERVICE_NAME', 'adms_service'),
        'onboarding_name' => env('ADMS_QUEUE_ONBOARDING_NAME', 'adms_onboarding'),
        'retries' => env('ADMS_QUEUE_MAX_RETRIES', 3),
        'timeout' => env('ADMS_QUEUE_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------|
    | Priority Configuration
    |--------------------------------------------------------------------------|
    | Defines priority levels and per-business/device priorities for processing.
    */
    'priority_levels' => [
        'high' => 1,
        'medium' => 2,
        'low' => 3,
    ],
    'priority' => [
        // Example: 'BUSINESS_ID' => ['DEVICE_ID' => 'high|medium|low']
        // 'ABC123' => ['DEV456' => 'high'],
    ],
    'low_priority_delay_ms' => env('ADMS_LOW_PRIORITY_DELAY_MS', 5),

    /*
    |--------------------------------------------------------------------------|
    | Device Update Configuration
    |--------------------------------------------------------------------------|
    | Defines minimum data length for device updates.
    */
    'min_device_update_data_length' => env('ADMS_MIN_DEVICE_UPDATE_DATA_LENGTH', 150),

    /*
    |--------------------------------------------------------------------------|
    | Supported Types and Endpoints
    |--------------------------------------------------------------------------|
    | Defines supported endpoints, data types, and command types.
    */
    'supported_endpoints' => ['cdata', 'devicecmd', 'getrequest'],
    'supported_data_types' => ['cdata', 'fdata'],
    'supported_command_types' => ['getrequest', 'devicecmd'],

    /*
    |--------------------------------------------------------------------------|
    | Command Mapping and Validation Rules
    |--------------------------------------------------------------------------|
    | Defines supported commands and their validation rules.
    */
    'command_mapping' => [
        'ADD USER' => [
            'command' => 'DATA USER',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'Name' => 'required|string|max:255',
                'Pri' => 'nullable|integer|min:0|max:14',
                'Verify' => 'nullable|integer|in:0,1',
                'Card' => 'nullable|string|max:50',
                'Grp' => 'nullable|integer|min:1',
                'Passwd' => 'nullable|string|max:50',
                'Expires' => 'nullable|integer|in:0,1',
                'StartDatetime' => 'nullable|date_format:Y-m-d',
                'EndDatetime' => 'nullable|date_format:Y-m-d|after_or_equal:StartDatetime',
            ],
        ],
        'DELETE USER' => [
            'command' => 'DATA DEL_USER',
            'rules' => ['PIN' => 'required|alpha_dash|max:50'],
        ],
        'ADD FINGERPRINT' => [
            'command' => 'DATA FP',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
                'Valid' => 'required|integer|in:0,1',
                'SIZE' => 'required|integer|min:1',
                'TMP' => 'required|string',
            ],
        ],
        'ENROLL FINGERPRINT' => [
            'command' => 'ENROLL_FP',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
                'RETRY' => 'nullable|integer|min:1',
                'OVERWRITE' => 'nullable|integer|in:0,1',
            ],
        ],
        'ADD FACE' => [
            'command' => 'DATA UPDATE FACE',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
                'Valid' => 'required|integer|in:0,1',
                'SIZE' => 'required|integer|min:1',
                'TMP' => 'required|string',
            ],
        ],
        'ENROLL FACE' => [
            'command' => 'ENROLL_FP',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
                'RETRY' => 'nullable|integer|min:1',
                'OVERWRITE' => 'nullable|integer|in:0,1',
            ],
        ],
        'REBOOT DEVICE' => ['command' => 'REBOOT', 'rules' => []],
        'CLEAR LOG' => ['command' => 'CLEAR LOG', 'rules' => []],
        'CLEAR DATA' => ['command' => 'CLEAR DATA', 'rules' => []],
        'CHECK DEVICE' => ['command' => 'CHECK', 'rules' => []],
        'DEVICE INFO' => ['command' => 'INFO', 'rules' => []],
        'GET TIME' => ['command' => 'GET TIME', 'rules' => []],
        'SET TIME' => ['command' => 'SET TIME', 'rules' => ['Timestamp' => 'required|integer|min:0']],
        'UNLOCK ACCESS' => ['command' => 'AC_UNLOCK', 'rules' => []],
        'GET ATTENDANCE LOG' => ['command' => 'GET ATTLOG', 'rules' => []],
        'GET USER INFO' => ['command' => 'GET USERINFO', 'rules' => []],
        'GET PHOTO' => ['command' => 'GET PHOTO', 'rules' => []],
        'QUERY ATTENDANCE LOG' => [
            'command' => 'DATA QUERY ATTLOG',
            'rules' => [
                'StartTime' => 'required|date_format:Y-m-d H:i:s',
                'EndTime' => 'required|date_format:Y-m-d H:i:s|after_or_equal:StartTime',
            ],
        ],
        'GET OPERATION LOG' => ['command' => 'GET OPLOG', 'rules' => []],
        'GET ILLEGAL LOG' => ['command' => 'GET ILLEGALLOG', 'rules' => []],
        'GET CARD' => ['command' => 'GET CARD', 'rules' => ['PIN' => 'required|alpha_dash|max:50']],
        'GET ALL CARDS' => ['command' => 'GET CARD', 'rules' => []],
        'GET DEVICE INFO' => ['command' => 'GET DEVINFO', 'rules' => []],
        'GET USER COUNT' => ['command' => 'GET USER COUNT', 'rules' => []],
        'GET LOG COUNT' => ['command' => 'GET LOG COUNT', 'rules' => []],
        'GET FINGERPRINT DATA' => [
            'command' => 'GET DATA FP',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
            ],
        ],
        'GET FACE DATA' => [
            'command' => 'GET DATA FACE',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'FID' => 'required|integer|min:1',
            ],
        ],
        'GET BIODATA' => [
            'command' => 'GET DATA BIODATA',
            'rules' => [
                'PIN' => 'required|alpha_dash|max:50',
                'Type' => 'required|integer|min:1',
                'Index' => 'required|integer|min:1',
            ],
        ],
        'CHANGE WEB ADDRESS' => ['command' => 'SET OPTION', 'rules' => ['ICLOCKSVRURL' => 'required|url|max:255']],
        'CHANGE WEB PORT' => ['command' => 'SET OPTION', 'rules' => ['IclockSvrPort' => 'required|integer|min:1|max:65535']],
    ],

    /*
    |--------------------------------------------------------------------------|
    | Validation Configuration
    |--------------------------------------------------------------------------|
    | Defines common validation rules for commands.
    */
    'validation' => [
        'common_rules' => [
            'serial_number' => 'required|string|max:255',
            'business_id' => 'required|string',
            'device_id' => 'required|string',
        ],
    ],

    /*
    |--------------------------------------------------------------------------|
    | Excluded Parameters
    |--------------------------------------------------------------------------|
    | Defines parameters to exclude from command processing.
    */
    'excluded_params' => ['_token', 'save_token', 'form_type', 'device_id'],
];