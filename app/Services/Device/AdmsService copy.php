<?php

namespace App\Services;

use App\Jobs\Adms\AdmsDataJob;
use App\Facades\{Database, Developer};
use Illuminate\Support\Facades\{Cache, Config, DB, Log};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Carbon\Carbon;
use Illuminate\Database\Connection;

/**
 * Service for iClock ADMS operations with optimized caching, batch processing, and error handling.
 * Supports 100,000 devices and 10M requests/hour across multiple businesses.
 * Handles tables: devices, device_users, device_attendance, device_fingerprints, device_faces,
 * device_logs, device_commands, device_settings.
 */
class AdmsService
{
    // ----------------------------------- Device Management -----------------------------------
    /**
     * Cache all active devices and their database mappings with Redis lock.
     *
     * @return void
     */
    protected function cacheDevices(): void
    {
        $cacheKey = 'adms:devices:global';
        $cacheTtl = Config::get('adms.cache.ttl.device', 86400);
        // Prevent concurrent cache rebuilds
        $lock = Cache::lock($cacheKey . ':lock', 60);
        if (!$lock->get()) {
            Developer::info('Cache rebuild skipped due to existing lock', ['key' => $cacheKey]);
            return;
        }
        try {
            if (Cache::has($cacheKey)) {
                return;
            }
            $devices = DB::table('devices')
                ->where('is_active', 1)
                ->select('serial_number', 'device_id', 'business_id')
                ->get();
            $systems = DB::table('business_systems')
                ->where('is_active', 1)
                ->pluck('database', 'business_id')
                ->toArray();
            $deviceMap = [];
            foreach ($devices as $device) {
                $deviceMap[$device->serial_number] = [
                    'device_id' => $device->device_id,
                    'business_id' => $device->business_id,
                    'database' => $systems[$device->business_id] ?? null,
                    'serial_number' => $device->serial_number,
                ];
                Cache::put("adms:device:serial:{$device->serial_number}", $deviceMap[$device->serial_number], $cacheTtl);
            }
            Cache::put($cacheKey, $deviceMap, $cacheTtl);
            Developer::notice('Devices cached', ['count' => count($deviceMap)]);
        } finally {
            $lock->release();
        }
    }
    /**
     * Retrieve device details by serial number with caching.
     *
     * @param string $serialNumber Device serial number
     * @return array|null Device details
     */
    public function getDevice(string $businessId, string $serialNumber): ?array
    {
        return $this->executeOperation(function () use ($businessId, $serialNumber) {
            if (empty($serialNumber)) {
                throw new InvalidArgumentException('Invalid serial number');
            }
            $cacheKey = "adms:devices:business:{$businessId}";
            try {
                $devices = Cache::get($cacheKey);
                if (!$devices) {
                    $connection = Database::getConnection($businessId);
                    $rows = $connection->table('devices')
                        ->where('serial_number',  $serialNumber)
                        ->whereNull('deleted_at')
                        ->get();
                    if ($rows->isEmpty()) {
                        Developer::warning('No devices found for business', [
                            'businessId' => $businessId
                        ]);
                        return null;
                    }
                    $devices = [];
                    foreach ($rows as $row) {
                        $devices[$row->serial_number] = (array) $row;
                    }
                    Cache::put($cacheKey, $devices, 3600);
                }

                // 3. Lookup specific device by serial number
                if (!isset($devices[$serialNumber])) {
                    Developer::warning('Device not found in cache', [
                        'serialNumber' => $serialNumber,
                        'businessId'   => $businessId
                    ]);
                    return null;
                }
                return $devices[$serialNumber];

            } catch (\Exception $e) {
                Developer::error('Error while fetching device(s)', [
                    'serialNumber' => $serialNumber,
                    'businessId'   => $businessId,
                    'error'        => $e->getMessage()
                ]);
                throw new \Exception('Unable to fetch device(s)', 0, $e);
            }
        }, "Get device: {$serialNumber}");
    }
    /**
     * Ping a device and update its last sync timestamp.
     *
     * @param string $serialNumber Device serial number
     * @return array|null Device details
     */
    public function pingDevice(string $businessId, string $serialNumber): ?array
    {
        return $this->executeOperation(function () use ($businessId, $serialNumber) {
            $device = $this->getDevice($businessId, $serialNumber);
            if (!$device) {
                throw new InvalidArgumentException('Device not found');
            }
            $connection = DataBase::getConnection($businessId);
            Developer::info("came to ping device");
            $this->executeDatabaseOperation($connection, function () use ($connection, $device) {
                    $connection->table('devices')
                    ->where('device_id', $device['device_id'])
                    ->update(['last_sync' => now(), 'updated_at' => now()]);
            }, 'Ping device', $businessId, $device['device_id']);
            Cache::put("adms:device:{$device['device_id']}:last_sync", now()->toDateTimeString(), Config::get('adms.cache.ttl.device', 86400));
            $this->logAction($businessId, $device['device_id'], null, null, 'LAST_SYNC', ['status' => 'ping success']);
            return $device;
        }, "Ping device: {$serialNumber}");
    }
    // ----------------------------------- Device Settings -----------------------------------
    /**
     * Retrieve device settings with caching.
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @return array|null Settings
     */
    public function getDeviceSettings(string $deviceId, string $businessId): ?array
    {
        $cacheKey = "adms:device:{$deviceId}:settings";
        $cacheTtl = Config::get('adms.cache.ttl.settings', 600);
        return $this->executeOperation(function () use ($cacheKey, $deviceId, $businessId, $cacheTtl) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($deviceId, $businessId) {
                $connection = Database::getConnection($businessId);
                return $this->executeDatabaseOperation($connection, function () use ($connection, $deviceId) {
                    $settings = $connection->table('device_settings')
                        ->where('device_id', $deviceId)
                        ->first([
                            'trans_stamp',
                            'attlog_stamp',
                            'op_stamp',
                            'operlog_stamp',
                            'photo_stamp',
                            'attphoto_stamp',
                            'error_delay',
                            'delay',
                            'trans_times',
                            'trans_interval',
                            'trans_flag',
                            'realtime',
                            'timeout',
                            'timezone',
                            'encrypt',
                            'memory_alert',
                            'memory_threshold',
                            'memory_interval',
                            'attlog_alert',
                            'attlog_threshold',
                            'attlog_interval',
                            'auto_remove_logs',
                            'auto_remove_age',
                            'auto_remove_threshold'
                        ]);
                    return $settings ? (array) $settings : null;
                }, 'Get settings', $businessId, $deviceId);
            });
        }, "Get settings: {$deviceId}", $businessId, $deviceId);
    }
    // ----------------------------------- Command Management -----------------------------------
    /**
     * Store a command for a device (deprecated, use command() instead).
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @param string $commandId Command ID
     * @param string $command Command name
     * @return void
     */
    public function storeCommand(string $deviceId, string $businessId, string $commandId, string $command): void
    {
        $this->executeOperation(function () use ($deviceId, $businessId, $commandId, $command) {
            $connection = Database::getConnection($businessId);
            $this->executeDatabaseOperation($connection, function () use ($connection, $businessId, $deviceId, $commandId, $command) {
                DB::connection($connection)->table('device_commands')->insert([
                    'command_id' => $commandId,
                    'device_id' => $deviceId,
                    'command' => $command,
                    'status' => 'PENDING',
                    'created_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Cache::forget("adms:device:{$deviceId}:pending_commands");
                $this->logAction($businessId, $deviceId, null, $commandId, 'COMMAND_CREATE', ['command' => $command]);
            }, 'Store command', $businessId, $deviceId, $commandId);
        }, "Store command: {$commandId}", $businessId, $deviceId, $commandId);
    }
    /**
     * Create a new command with validation and parameter mapping.
     *
     * @param string $serialNumber Device serial number
     * @param string $businessId Business ID
     * @param string $name Command name
     * @param array $params Command parameters
     * @return array Command details
     */
    public function command(string $serialNumber, string $businessId, string $name, array $params = []): array
    {
        return $this->executeOperation(function () use ($serialNumber, $businessId, $name, $params) {
            $commandMapping = [
                'ADD USER' => ['command' => 'DATA USER', 'rules' => [
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
                ]],
                'DELETE USER' => ['command' => 'DATA DEL_USER', 'rules' => ['PIN' => 'required|alpha_dash|max:50']],
                'ADD FINGERPRINT' => ['command' => 'DATA FP', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                    'Valid' => 'required|integer|in:0,1',
                    'SIZE' => 'required|integer|min:1',
                    'TMP' => 'required|string',
                ]],
                'ENROLL FINGERPRINT' => ['command' => 'ENROLL_FP', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                    'RETRY' => 'nullable|integer|min:1',
                    'OVERWRITE' => 'nullable|integer|in:0,1',
                ]],
                'ADD FACE' => ['command' => 'DATA UPDATE FACE', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                    'Valid' => 'required|integer|in:0,1',
                    'SIZE' => 'required|integer|min:1',
                    'TMP' => 'required|string',
                ]],
                'ENROLL FACE' => ['command' => 'ENROLL_FP', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                    'RETRY' => 'nullable|integer|min:1',
                    'OVERWRITE' => 'nullable|integer|in:0,1',
                ]],
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
                'QUERY ATTENDANCE LOG' => ['command' => 'DATA QUERY ATTLOG', 'rules' => [
                    'StartTime' => 'required|date_format:Y-m-d H:i:s',
                    'EndTime' => 'required|date_format:Y-m-d H:i:s|after_or_equal:StartTime',
                ]],
                'GET OPERATION LOG' => ['command' => 'GET OPLOG', 'rules' => []],
                'GET ILLEGAL LOG' => ['command' => 'GET ILLEGALLOG', 'rules' => []],
                'GET CARD' => ['command' => 'GET CARD', 'rules' => ['PIN' => 'required|alpha_dash|max:50']],
                'GET ALL CARDS' => ['command' => 'GET CARD', 'rules' => []],
                'GET DEVICE INFO' => ['command' => 'GET DEVINFO', 'rules' => []],
                'GET USER COUNT' => ['command' => 'GET USER COUNT', 'rules' => []],
                'GET LOG COUNT' => ['command' => 'GET LOG COUNT', 'rules' => []],
                'GET FINGERPRINT DATA' => ['command' => 'GET DATA FP', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                ]],
                'GET FACE DATA' => ['command' => 'GET DATA FACE', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'FID' => 'required|integer|min:1',
                ]],
                'GET BIODATA' => ['command' => 'GET DATA BIODATA', 'rules' => [
                    'PIN' => 'required|alpha_dash|max:50',
                    'Type' => 'required|integer|min:1',
                    'Index' => 'required|integer|min:1',
                ]],
                'CHANGE WEB ADDRESS' => ['command' => 'SET OPTION', 'rules' => ['ICLOCKSVRURL' => 'required|url']],
                'CHANGE WEB PORT' => ['command' => 'SET OPTION', 'rules' => ['IclockSvrPort' => 'required|integer|min:1|max:65535']],
            ];
            $device = $this->getDevice($businessId, $serialNumber);
            if (!$device) {
                throw new InvalidArgumentException("Device not found for serial number: {$serialNumber}");
            }
            if (!isset($commandMapping[$name])) {
                throw new InvalidArgumentException("Invalid command name: {$name}");
            }
            $commandConfig = $commandMapping[$name];
            $validator = Validator::make($params, $commandConfig['rules']);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $allowedParams = array_keys($commandConfig['rules']);
            $extraParams = array_diff(array_keys($params), $allowedParams);
            if (!empty($extraParams)) {
                throw new ValidationException(Validator::make([], [], [
                    'params' => "Unexpected parameters: " . implode(', ', $extraParams),
                ]));
            }
            $commandId = 'CMD' . Carbon::now()->timestamp . rand(1000, 9999);
            $connection = Database::getConnection($businessId);
            $commandData = [
                'command_id' => $commandId,
                'device_id' => $device['device_id'],
                'name' => $name,
                'command' => $commandConfig['command'],
                'params' => !empty($params) ? json_encode($params) : null,
                'status' => 'PENDING',
                'created_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->executeDatabaseOperation($connection, function () use ($connection, $commandData, $device) {
                $connection->table('device_commands')->insert($commandData);
                Cache::forget("adms:device:{$device['device_id']}:pending_commands");
            }, 'Create command', $businessId, $device['device_id'], $commandId);
            $this->logAction($businessId, $device['device_id'], null, $commandId, 'COMMAND_CREATE', [
                'command' => $name,
                'params' => $params,
            ]);
            return [
                'command_id' => $commandId,
                'device_id' => $device['device_id'],
                'name' => $name,
                'command' => $commandConfig['command'],
                'parameters' => $params,
            ];
        }, "Create command: {$name}", $businessId);
    }
    /**
     * Update command status asynchronously.
     *
     * @param string $businessId Business ID
     * @param string $commandId Command ID
     * @param string $status Status (PENDING, SENT, EXECUTED, FAILED)
     * @param string|null $response Response data
     * @param string|null $reason Failure reason
     * @return void
     */
    public function updateStatus(string $businessId, string $commandId, string $type, ?string $data = null): void
    {
        Log::info('Update Status');
        $connection = Database::getConnection($businessId);

        if ($type === "getrequest") {
            $connection->table('device_commands')
                ->where('command_id', $commandId)
                ->update([
                    'status' => 'SENT',
                    'updated_by' => 'system',
                    'updated_at' => now(),
                ]);
            return;
        }

        if ($type === "devicecmd") {
            parse_str($data, $respArr);

            // Remove tildes (~) and convert CMD section to key-values
            $cmdData = $respArr['CMD'] ?? '';
            $lines = preg_split('/\r\n|\n|\r/', $cmdData);
            $infoArray = [];

            foreach ($lines as $line) {
                $line = ltrim($line, '~');
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $infoArray[trim($key)] = trim($value);
                }
            }
            // Prepare for update
            $updateData = [
                'status' => 'EXECUTED',
                'response' => $respArr['Return'] ?? null,
                'reason' => $cmdData,
                'updated_by' => 'system',
                'updated_at' => now(),
            ];

            $connection->table('device_commands')
                ->where('command_id', $commandId)
                ->update($updateData);

            // Also update the devices table if the data is large
            if (strlen($data) > 150) {
                $connection->table('devices')
                    ->where('serial_number', $infoArray['SerialNumber'] ?? '')
                    ->update([
                        'mac_address' => $infoArray['MAC'] ?? null,
                        'ip' => $infoArray['IPAddress'] ?? null,
                        'info_json' => json_encode($infoArray),
                        'updated_at' => now(),
                    ]);

                    // DB::connection('central')->table('devices')
                    // ->where('serial_number', $infoArray['SerialNumber'] ?? '')
                    // ->update([
                    //     'info_json' => json_encode($infoArray),
                    //     'updated_at' => now(),
                    // ]);
            }
        }
    }


    /**
     * Retrieve pending commands with caching.
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @return array Pending commands
     */
    public function getPendingCommands(string $deviceId, string $businessId): array
    {
        $cacheKey = "adms:device:{$deviceId}:pending_commands";
        $cacheTtl = Config::get('adms.cache.ttl.commands', 30);

        try {
            return $this->executeOperation(function () use ($cacheKey, $deviceId, $businessId, $cacheTtl) {
                return Cache::remember($cacheKey, $cacheTtl, function () use ($deviceId, $businessId) {
                    $connection = Database::getConnection($businessId);

                    // Directly query the DB
                    $commands = $connection->table('device_commands')
                        ->where('device_id', $deviceId)
                        ->where('status', 'PENDING')
                        ->select('command_id', 'name', 'command', 'params')
                        ->limit(20)
                        ->get()
                        ->map(function ($command) {
                            $command->params = $command->params
                                ? json_decode($command->params, true) ?? []
                                : [];
                            return (array) $command;
                        })
                        ->toArray();

                    // Log retrieved commands
                    Developer::info("Retrieved pending commands", [
                        'businessId' => $businessId,
                        'deviceId'   => $deviceId,
                        'count'      => count($commands),
                        'commands'   => $commands,
                    ]);

                    return $commands;
                }) ?? [];
            }, "Get pending commands: {$deviceId}", $businessId, $deviceId);
        } catch (\Illuminate\Database\QueryException $e) {
            Developer::error("Database query failed for getPendingCommands: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId'   => $deviceId,
            ]);
            return []; // Return empty array if DB fails
        } catch (\Throwable $e) {
            Developer::error("Unexpected error in getPendingCommands: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId'   => $deviceId,
            ]);
            return []; // Return empty array on any unexpected error
        }
    }


    // ----------------------------------- Data Processing -----------------------------------
    /**
     * Queue data processing with deduplication.
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @param string $type Data type (cdata, fdata)
     * @param string $data Raw data
     * @param array $meta Metadata
     * @return void
     */
    public function queueDataProcess(string $deviceId, string $businessId, string $type, string $data, array $meta): void
    {
        $this->executeOperation(function () use ($deviceId, $businessId, $type, $data, $meta) {
            if (!in_array($type, ['cdata', 'fdata'])) {
                throw new InvalidArgumentException("Invalid data type: {$type}");
            }
            if (empty(trim($data))) {
                throw new InvalidArgumentException('Empty data payload');
            }
            // Deduplicate job dispatching
            $dataHash = md5($data);
            $lockKey = "adms:job:{$deviceId}:{$type}:{$dataHash}";
            $lockTtl = Config::get('adms.cache.ttl.request', 120);
            $lock = Cache::lock($lockKey, $lockTtl);
            if (!$lock->get()) {
                Developer::info("Duplicate job skipped", [
                    'deviceId' => $deviceId,
                    'businessId' => $businessId,
                    'type' => $type,
                    'dataHash' => $dataHash,
                ]);
                return;
            }
            try {
                AdmsDataJob::dispatch($deviceId, $businessId, $type, $data, $meta)
                    ->onQueue(Config::get('adms.queue.prefix', 'adms:') . $businessId);
            } finally {
                $lock->release();
            }
        }, "Queue data: {$type}", $businessId, $deviceId);
    }
    // ----------------------------------- User and Biometric Data Management -----------------------------------
    /**
     * Store user data in bulk with upsert.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Array of pre-formatted user records
     * @return void
     */
    public function storeUser(string $businessId, string $deviceId, array $records): void
    {
        if (empty($records)) {
            return;
        }
        Log::info("hello Store User");
        $this->storeBiometricOrUser($businessId, $deviceId, 'device_users', $records, ['device_user_id', 'device_id'], ['USER_UPDATE']);
    }
    /**
     * Store attendance data in bulk with deduplication and user validation.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Array of pre-formatted attendance records
     * @return void
     */
    public function storeAttendance(string $businessId, string $deviceId, array $records): void
    {
        if (empty($records)) {
            return;
        }
        $this->executeOperation(function () use ($businessId, $deviceId, $records) {
            $connection = Database::getConnection($businessId);
            $this->executeDatabaseOperation($connection, function () use ($connection, $records) {
                // Use raw query for INSERT IGNORE to handle batch deduplication
                if (!empty($records)) {
                    $columns = implode(',', array_keys($records[0]));
                    $placeholders = rtrim(str_repeat('(' . implode(',', array_fill(0, count($records[0]), '?')) . '),', count($records)), ',');
                    $values = array_merge(...array_map('array_values', $records));
                    DB::connection($connection)->statement(
                        "INSERT IGNORE INTO device_attendance ({$columns}) VALUES {$placeholders}",
                        $values
                    );
                }
            }, 'Store attendance', $businessId, $deviceId);
            $this->logAction($businessId, $deviceId, null, null, 'ATTLOG', [
                'count' => count($records),
                'data' => array_keys($records[0]),
            ]);
        }, "Store attendance: {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Store fingerprint data in bulk with deduplication and user validation.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Array of pre-formatted fingerprint records
     * @return void
     */
    public function storeFingerprint(string $businessId, string $deviceId, array $records): void
    {
        if (empty($records)) {
            return;
        }
        $this->storeBiometricOrUser($businessId, $deviceId, 'device_fingerprints', $records, ['device_user_id', 'device_id', 'fid'], ['FP_UPLOAD']);
    }
    /**
     * Store face data in bulk with deduplication and user validation.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Array of pre-formatted face records
     * @return void
     */
    public function storeFace(string $businessId, string $deviceId, array $records): void
    {
        if (empty($records)) {
            return;
        }
        $this->storeBiometricOrUser($businessId, $deviceId, 'device_faces', $records, ['device_user_id', 'device_id', 'fid'], ['FACE_UPLOAD']);
    }
    /**
     * Store user or biometric data in bulk with upsert.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param string $table Table name
     * @param array $records Records to store
     * @param array $uniqueKeys Unique keys for upsert
     * @param array $actionTypes Action types for logging
     * @return void
     */
    protected function storeBiometricOrUser(string $businessId, string $deviceId, string $table, array $records, array $uniqueKeys, array $actionTypes): void
    {
        if (empty($records)) {
            return;
        }
        $this->executeOperation(function () use ($businessId, $deviceId, $table, $records, $uniqueKeys, $actionTypes) {
            $connection = Database::getConnection($businessId);
            $this->executeDatabaseOperation($connection, function () use ($connection, $table, $records, $uniqueKeys) {
                // Determine the update columns (all except the unique keys)
                $updateColumns = array_diff(array_keys($records[0]), $uniqueKeys);
                $connection->table($table)->upsert(
                    $records,
                    $uniqueKeys,
                    $updateColumns
                );
            }, "Store {$table}", $businessId, $deviceId);
            $this->logAction($businessId, $deviceId, null, null, $actionTypes[0], [
                'count' => count($records),
                'data' => array_keys($records[0]),
            ]);
        }, "Store {$table}: {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Get business database connection.
     *
     * @param string $businessId Business ID
     * @return string Connection name
     */
    protected function getBusinessConnection(string $businessId): string
    {
        $connection = Config::get('adms.database.central', 'central');
        $systems = Cache::remember('adms:systems', 86400, function () {
            return DB::table('business_systems')->where('is_active', 1)->pluck('database', 'business_id')->toArray();
        });
        return $systems[$businessId] ?? $connection;
    }
    /**
     * Log an action to device_logs with structured JSON output.
     *
     * @param string|null $businessId Business ID
     * @param string|null $deviceId Device ID
     * @param string|null $deviceUserId User ID
     * @param string|null $commandId Command ID
     * @param string $actionType Action type
     * @param array $details Log details
     * @return void
     */
    public function logAction(?string $businessId, ?string $deviceId, ?string $deviceUserId, ?string $commandId, string $actionType, array $details): void
    {
        if (!$businessId) {
            return;
        }
        $logEntry = [
            'device_id' => $deviceId,
            'device_user_id' => $deviceUserId,
            'command_id' => $commandId,
            'action_type' => $actionType,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_by' => 'system',
            'created_at' => now(),
        ];
        try {
            $connection = Database::getConnection($businessId);
            $this->executeDatabaseOperation($connection, function () use ($connection, $logEntry) {
                $connection->table('device_logs')->insert($logEntry);
            }, 'Log action', $businessId, $deviceId, $commandId);
        } catch (\Exception $e) {
            Developer::error("Failed to log action: {$e->getMessage()}", array_merge($logEntry, ['exception' => $e->getMessage()]));
        }
    }
    /**
     * Execute a database operation with retries and transaction support.
     *
     * @param string $connection Database connection
     * @param callable $callback Operation to execute
     * @param string $action Action description
     * @param string|null $businessId Business ID
     * @param string|null $deviceId Device ID
     * @param string|null $commandId Command ID
     * @return mixed
     */
    protected function executeDatabaseOperation(Connection $connection, callable $callback, string $action, ?string $businessId = null, ?string $deviceId = null, ?string $commandId = null)
    {
        $maxRetries = Config::get('adms.retry.max_attempts', 3);
        $initialDelay = Config::get('adms.retry.initial_delay_ms', 200);
        $backoffFactor = Config::get('adms.retry.backoff_factor', 2);
        $attempts = $maxRetries;
        $delay = $initialDelay;

        while ($attempts > 0) {
            try {
                return Database::getConnection($businessId)->transaction($callback);
            } catch (\Illuminate\Database\QueryException $e) {
                $context = [
                    'businessId' => $businessId,
                    'deviceId' => $deviceId,
                    'commandId' => $commandId,
                    'action' => $action,
                    'sqlErrorCode' => $e->getCode(),
                    'sql' => $e->getSql(),
                ];

                if ($e->getCode() === '23000') {
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        Developer::info("Duplicate entry in {$action}, skipped", $context);
                        return null;
                    }
                    if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                        Developer::warning("Foreign key constraint violation in {$action}: {$e->getMessage()}", $context);
                        return null;
                    }
                }

                if (in_array($e->getCode(), ['40001', 'HY000']) && --$attempts > 0) {
                    usleep($delay * 1000);
                    $delay *= $backoffFactor;
                    continue;
                }

                Developer::error("{$action} database error: {$e->getMessage()}", $context);
                $this->logAction($businessId, $deviceId, null, $commandId, 'DATABASE_ERROR', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        throw new \Exception("{$action} failed after {$maxRetries} attempts");
    }

    /**
     * Execute an operation with error handling and logging.
     *
     * @param callable $callback Operation to execute
     * @param string $action Action description
     * @param string|null $businessId Business ID
     * @param string|null $deviceId Device ID
     * @param string|null $commandId Command ID
     * @return mixed
     */
    protected function executeOperation(callable $callback, string $action, ?string $businessId = null, ?string $deviceId = null, ?string $commandId = null)
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            Developer::error("{$action} validation error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
                'errors' => $e->errors(),
            ]);
            $this->logAction($businessId, $deviceId, null, $commandId, 'VALIDATION_ERROR', ['error' => $e->getMessage()]);
            throw $e;
        } catch (InvalidArgumentException $e) {
            Developer::error("{$action} validation error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
            ]);
            $this->logAction($businessId, $deviceId, null, $commandId, 'ERROR', ['error' => 'Invalid data']);
            throw $e;
        } catch (\Exception $e) {
            Developer::error("{$action} unexpected error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
            ]);
            $this->logAction($businessId, $deviceId, null, $commandId, 'ERROR', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
