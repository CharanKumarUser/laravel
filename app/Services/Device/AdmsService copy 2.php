<?php

namespace App\Services\Device;

use App\Facades\Database;
use App\Facades\Developer;
use App\Jobs\Adms\AdmsDataJob;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

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
     * Return a plain text response with proper formatting.
     *
     * This method is public so it can be accessed from anywhere in the app.
     *
     * @param  string  $text  The text content to return
     * @return \Illuminate\Http\Response
     */
    public static function plain(string $text)
    {
        return response(trim($text)."\r", 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Retrieve all active and approved devices, cached for ultra-fast lookup.
     *
     * Structure:
     * [
     *   'BUSINESS_ID' => [
     *       'DEVICE_ID' => [
     *           'type' => 'device_id',
     *           'settings' => [...],
     *           'serial_number' => 'SERIAL_NUMBER',
     *       ],
     *       'SERIAL_NUMBER' => [
     *           'type' => 'serial_number',
     *           'settings' => [...],
     *           'device_id' => 'DEVICE_ID',
     *       ],
     *   ],
     *   ...
     * ]
     */
    public function getDevices(): ?array
    {
        $cacheKey = 'adms:devices';
        $cacheTTL = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTTL, function () {
            $rows = DB::table('business_devices')
                ->select('business_id', 'device_id', 'serial_number', 'settings_json')
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->get();
            if ($rows->isEmpty()) {
                return null;
            }
            $devices = [];
            foreach ($rows as $row) {
                $businessId = strtoupper($row->business_id);
                $deviceId = strtoupper($row->device_id);
                $serialNumber = strtoupper($row->serial_number);
                $settings = json_decode($row->settings_json, true) ?? [];
                if (! isset($devices[$businessId])) {
                    $devices[$businessId] = [];
                }
                // Store device entry
                $devices[$businessId][$deviceId] = [
                    'type' => 'device_id',
                    'settings' => $settings,
                    'serial_number' => $serialNumber,
                ];
                // Store serial number entry
                $devices[$businessId][$serialNumber] = [
                    'type' => 'serial_number',
                    'settings' => $settings,
                    'device_id' => $deviceId,
                ];
            }

            return $devices;
        });
    }

    /**
     * Retrieve a specific device by device_id (di) or serial_number (sn).
     *
     * @param  string  $businessId  Business identifier
     * @param  string  $type  "di" = device_id, "sn" = serial_number
     * @param  string  $typeId  The actual device_id or serial_number
     * @return array|null [
     *                    'business_id'   => 'BUSINESS_ID',
     *                    'device_id'     => 'DEVICE_ID',
     *                    'serial_number' => 'SERIAL_NUMBER',
     *                    'settings'      => [...],
     *                    ]
     */
    public function getDevice(string $businessId, string $type, string $typeId): ?array
    {
        $businessId = strtoupper($businessId);
        $typeId = strtoupper($typeId);
        $devices = $this->getDevices();
        if (! $devices || ! isset($devices[$businessId])) {
            return null;
        }
        $businessDevices = $devices[$businessId];
        if ($type === 'di') {
            if (! isset($businessDevices[$typeId]) || $businessDevices[$typeId]['type'] !== 'device_id') {
                return null;
            }

            return [
                'business_id' => $businessId,
                'device_id' => $typeId,
                'serial_number' => $businessDevices[$typeId]['serial_number'],
                'settings' => $businessDevices[$typeId]['settings'],
            ];
        }
        if ($type === 'sn') {
            if (! isset($businessDevices[$typeId]) || $businessDevices[$typeId]['type'] !== 'serial_number') {
                return null;
            }

            return [
                'business_id' => $businessId,
                'device_id' => $businessDevices[$typeId]['device_id'],
                'serial_number' => $typeId,
                'settings' => $businessDevices[$typeId]['settings'],
            ];
        }

        return null;
    }

    /**
     * Retrieve device settings in plain-text format expected by terminals.
     *
     * @param  string  $serialNumber  Serial Number
     * @param  string  $businessId  Business ID
     * @return string|null Plain-text response for device or null if not found
     */
    public function getDeviceSettings(string $serialNumber, string $businessId): ?string
    {
        $device = $this->getDevice(strtoupper($businessId), 'sn', $serialNumber);
        if (! $device) {
            return null;
        }
        $settings = $device['settings'] ?? [];
        $serialNumber = $device['serial_number'];
        // Safely map values with defaults to avoid undefined key errors
        $response = "GET OPTION FROM: {$serialNumber}\r".
            'Stamp='.($settings['Stamp'] ?? '0')."\r".
            'ATTLOGStamp='.($settings['ATTLOGStamp'] ?? 'None')."\r".
            'OpStamp='.($settings['OpStamp'] ?? '0')."\r".
            'OPERLOGStamp='.($settings['OPERLOGStamp'] ?? 'None')."\r".
            'PhotoStamp='.($settings['PhotoStamp'] ?? '0')."\r".
            'ATTPHOTOStamp='.($settings['ATTPHOTOStamp'] ?? 'None')."\r".
            'ErrorDelay='.($settings['ErrorDelay'] ?? 30)."\r".
            'Delay='.($settings['Delay'] ?? 1)."\r".
            'TransTimes='.($settings['TransTimes'] ?? '09:00;18:30')."\r".
            'TransInterval='.($settings['TransInterval'] ?? 1)."\r".
            'TransFlag='.($settings['TransFlag'] ?? '111111101101')."\r".
            'Realtime='.($settings['Realtime'] ?? 1)."\r".
            'TimeOut='.($settings['TimeOut'] ?? 30)."\r".
            'TimeZone='.($settings['TimeZone'] ?? '330')."\r".
            'Encrypt='.($settings['Encrypt'] ?? 0)."\r\r".
            'OK';

        return $this->plain($response);
    }


/**
 * Store a command for a device in both central and business databases.
 * Each command expires automatically after 5 minutes.
 *
 * @param  string  $deviceId    Device ID
 * @param  string  $businessId  Business ID
 * @param  string  $commandId   Command ID
 * @param  string  $command     Command name
 * @param  array   $params      Additional command parameters (optional)
 */
public function storeCommand(
    string $deviceId,
    string $businessId,
    string $commandId,
    string $command,
    array $params = []
): void {
    $this->executeOperation(function () use ($deviceId, $businessId, $commandId, $command, $params) {
        // 🔍 Get device info from cached getDevice()
        $device = $this->getDevice($businessId, 'di', $deviceId);
        if (!$device) {
            throw new \RuntimeException("Device {$deviceId} not found in business {$businessId}");
        }

        $serialNumber = $device['serial_number']; // ✅ always from cache
        $connection   = Database::getConnection($businessId);

        DB::transaction(function () use ($connection, $businessId, $deviceId, $serialNumber, $commandId, $command, $params) {
            $now       = now();
            $expiresAt = $now->copy()->addMinutes(5); // ⏳ 5 mins expiry

            // 1️⃣ Insert into CENTRAL DB (business_commands)
            CentralDB::table('business_commands')->insert([
                'business_id'   => $businessId,
                'device_id'     => $deviceId,
                'serial_number' => $serialNumber,
                'command_id'    => $commandId,
                'name'          => $command,
                'command'       => $command,
                'params'        => !empty($params) ? json_encode($params) : null,
                'status'        => 'PENDING',
                'expires_at'    => $expiresAt,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // 2️⃣ Insert into BUSINESS DB (device_commands)
            $connection->table('device_commands')->insert([
                'command_id' => $commandId,
                'device_id'  => $deviceId,
                'name'       => $command,
                'command'    => $command,
                'params'     => !empty($params) ? json_encode($params) : null,
                'status'     => 'PENDING',
                'expires_at' => $expiresAt,
                'created_by' => 'system',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 3️⃣ Clear pending command cache for this device
            Cache::forget("adms:commands");
        }, 5); // retry up to 5 times on deadlock
    }, "Store command: {$commandId}", $businessId, $deviceId, $commandId);
}


    /**
     * Create a new command with validation and parameter mapping.
     *
     * @param  string  $serialNumber  Device serial number
     * @param  string  $businessId  Business ID
     * @param  string  $name  Command name
     * @param  array  $params  Command parameters
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
            if (! $device) {
                throw new InvalidArgumentException("Device not found for serial number: {$serialNumber}");
            }
            if (! isset($commandMapping[$name])) {
                throw new InvalidArgumentException("Invalid command name: {$name}");
            }
            $commandConfig = $commandMapping[$name];
            $validator = Validator::make($params, $commandConfig['rules']);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $allowedParams = array_keys($commandConfig['rules']);
            $extraParams = array_diff(array_keys($params), $allowedParams);
            if (! empty($extraParams)) {
                throw new ValidationException(Validator::make([], [], [
                    'params' => 'Unexpected parameters: '.implode(', ', $extraParams),
                ]));
            }
            $commandId = 'CMD'.Carbon::now()->timestamp.rand(1000, 9999);
            $connection = Database::getConnection($businessId);
            $commandData = [
                'command_id' => $commandId,
                'device_id' => $device['device_id'],
                'name' => $name,
                'command' => $commandConfig['command'],
                'params' => ! empty($params) ? json_encode($params) : null,
                'status' => 'PENDING',
                'created_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->executeDatabaseOperation($connection, function () use ($connection, $commandData, $device) {
                $connection->table('device_commands')->insert($commandData);
                Cache::forget("adms:device:{$device['device_id']}:pending_commands");
            }, 'Create command', $businessId, $device['device_id'], $commandId);
           

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
     * @param  string  $businessId  Business ID
     * @param  string  $commandId  Command ID
     * @param  string  $status  Status (PENDING, SENT, EXECUTED, FAILED)
     * @param  string|null  $response  Response data
     * @param  string|null  $reason  Failure reason
     */
    public function updateStatus(string $businessId, string $commandId, string $type, ?string $data = null): void
    {
        Log::info('Update Status');
        $connection = Database::getConnection($businessId);
        if ($type === 'getrequest') {
            $connection->table('device_commands')
                ->where('command_id', $commandId)
                ->update([
                    'status' => 'SENT',
                    'updated_by' => 'system',
                    'updated_at' => now(),
                ]);

            return;
        }
        if ($type === 'devicecmd') {
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
 * Retrieve pending (non-expired) commands from the central DB with caching.
 *
 * @param  string  $deviceId    Device ID
 * @param  string  $businessId  Business ID
 * @return array<int, array<string, mixed>> Pending commands
 */
public function getPendingCommands(string $deviceId, string $businessId): array
{
    $cacheKey = "adms:device:{$deviceId}:pending_commands";
    $cacheTtl = 300; // 5 minutes

    try {
        return $this->executeOperation(function () use ($cacheKey, $deviceId, $businessId, $cacheTtl) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($deviceId, $businessId) {
                $now = now();

                // ✅ Query central DB only
                $commands = DB::connection('central')
                    ->table('business_commands')
                    ->where('business_id', $businessId)
                    ->where('device_id', $deviceId)
                    ->where('status', 'PENDING')
                    ->where(function ($query) use ($now) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', $now);
                    })
                    ->orderBy('created_at')
                    ->limit(20)
                    ->get(['command_id', 'name', 'command', 'params'])
                    ->map(function ($command) {
                        return [
                            'command_id' => $command->command_id,
                            'name'       => $command->name,
                            'command'    => $command->command,
                            'params'     => $command->params
                                ? json_decode($command->params, true) ?? []
                                : [],
                        ];
                    })
                    ->toArray();

                // 🔍 Debug logging
                Developer::info('Retrieved pending commands (central)', [
                    'businessId' => $businessId,
                    'deviceId'   => $deviceId,
                    'count'      => count($commands),
                ]);

                return $commands;
            }) ?? [];
        }, "Get pending commands: {$deviceId}", $businessId, $deviceId);
    } catch (\Illuminate\Database\QueryException $e) {
        Developer::error("Database query failed for getPendingCommands: {$e->getMessage()}", [
            'businessId' => $businessId,
            'deviceId'   => $deviceId,
        ]);
        return [];
    } catch (\Throwable $e) {
        Developer::error("Unexpected error in getPendingCommands: {$e->getMessage()}", [
            'businessId' => $businessId,
            'deviceId'   => $deviceId,
        ]);
        return [];
    }
}


    // ----------------------------------- Data Processing -----------------------------------
    /**
     * Queue data processing with deduplication.
     *
     * @param  string  $deviceId  Device ID
     * @param  string  $businessId  Business ID
     * @param  string  $type  Data type (cdata, fdata)
     * @param  string  $data  Raw data
     * @param  array  $meta  Metadata
     */
    public function queueDataProcess(string $deviceId, string $businessId, string $type, string $data, array $meta): void
    {
        $this->executeOperation(function () use ($deviceId, $businessId, $type, $data, $meta) {
            if (! in_array($type, ['cdata', 'fdata'])) {
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
            if (! $lock->get()) {
                Developer::info('Duplicate job skipped', [
                    'deviceId' => $deviceId,
                    'businessId' => $businessId,
                    'type' => $type,
                    'dataHash' => $dataHash,
                ]);

                return;
            }
            try {
                AdmsDataJob::dispatch($deviceId, $businessId, $type, $data, $meta)
                    ->onQueue(Config::get('adms.queue.prefix', 'adms:').$businessId);
            } finally {
                $lock->release();
            }
        }, "Queue data: {$type}", $businessId, $deviceId);
    }

    // ----------------------------------- User and Biometric Data Management -----------------------------------
    /**
     * Store user data in bulk with upsert.
     *
     * @param  string  $businessId  Business ID
     * @param  string  $deviceId  Device ID
     * @param  array  $records  Array of pre-formatted user records
     */
    public function storeUser(string $businessId, string $deviceId, array $records): void
    {
        if (empty($records)) {
            return;
        }
        Log::info('hello Store User');
        $this->storeBiometricOrUser($businessId, $deviceId, 'device_users', $records, ['device_user_id', 'device_id'], ['USER_UPDATE']);
    }

    /**
     * Store attendance data in bulk with deduplication and user validation.
     *
     * @param  string  $businessId  Business ID
     * @param  string  $deviceId  Device ID
     * @param  array  $records  Array of pre-formatted attendance records
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
                if (! empty($records)) {
                    $columns = implode(',', array_keys($records[0]));
                    $placeholders = rtrim(str_repeat('('.implode(',', array_fill(0, count($records[0]), '?')).'),', count($records)), ',');
                    $values = array_merge(...array_map('array_values', $records));
                    DB::connection($connection)->statement(
                        "INSERT IGNORE INTO device_attendance ({$columns}) VALUES {$placeholders}",
                        $values
                    );
                }
            }, 'Store attendance', $businessId, $deviceId);

        }, "Store attendance: {$deviceId}", $businessId, $deviceId);
    }

    /**
     * Store fingerprint data in bulk with deduplication and user validation.
     *
     * @param  string  $businessId  Business ID
     * @param  string  $deviceId  Device ID
     * @param  array  $records  Array of pre-formatted fingerprint records
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
     * @param  string  $businessId  Business ID
     * @param  string  $deviceId  Device ID
     * @param  array  $records  Array of pre-formatted face records
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
     * @param  string  $businessId  Business ID
     * @param  string  $deviceId  Device ID
     * @param  string  $table  Table name
     * @param  array  $records  Records to store
     * @param  array  $uniqueKeys  Unique keys for upsert
     * @param  array  $actionTypes  Action types for logging
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
        }, "Store {$table}: {$deviceId}", $businessId, $deviceId);
    }

    /**
     * Get business database connection.
     *
     * @param  string  $businessId  Business ID
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
     * Execute a database operation with retries and transaction support.
     *
     * @param  string  $connection  Database connection
     * @param  callable  $callback  Operation to execute
     * @param  string  $action  Action description
     * @param  string|null  $businessId  Business ID
     * @param  string|null  $deviceId  Device ID
     * @param  string|null  $commandId  Command ID
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
                throw $e;
            }
        }
        throw new \Exception("{$action} failed after {$maxRetries} attempts");
    }

    /**
     * Execute an operation with error handling and logging.
     *
     * @param  callable  $callback  Operation to execute
     * @param  string  $action  Action description
     * @param  string|null  $businessId  Business ID
     * @param  string|null  $deviceId  Device ID
     * @param  string|null  $commandId  Command ID
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
            throw $e;
        } catch (InvalidArgumentException $e) {
            Developer::error("{$action} validation error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
            ]);
            throw $e;
        } catch (\Exception $e) {
            Developer::error("{$action} unexpected error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
            ]);
            throw $e;
        }
    }
}
