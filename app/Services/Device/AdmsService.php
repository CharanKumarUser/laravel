<?php
namespace App\Services\Device;
use App\Facades\CentralDB;
use App\Facades\Database;
use App\Facades\Developer;
use App\Facades\Data;
use App\Jobs\Adms\AdmsDataJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Exception;
/**
 * Service for iClock ADMS operations, optimized for high throughput (100K requests/hour) with minimal DB operations.
 * Features global caching, rate limiting, batch processing, and comprehensive exception handling for ultra-fast performance.
 * All parameters configurable via config/env.
 */
class AdmsService
{
    // Cache constants
    private const CACHE_KEY_DEVICES = 'adms:devices';
    private const CACHE_KEY_COMMANDS = 'adms:commands:%s:%s'; // businessId:deviceId
    /**
     * Retrieves all active and approved devices, cached for fast lookup.
     *
     * @return array|null Device data or null if none found
     */
    public function getDevices(): ?array
    {
        $deviceTtl = Config::get('adms.cache.device_ttl', 3600);
        return Cache::remember(self::CACHE_KEY_DEVICES, $deviceTtl, function () {
            return $this->executeOperation(function () {
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
                    $devices[$businessId] ??= [];
                    $devices[$businessId][$deviceId] = [
                        'type' => 'device_id',
                        'settings' => $settings,
                        'serial_number' => $serialNumber,
                    ];
                    $devices[$businessId][$serialNumber] = [
                        'type' => 'serial_number',
                        'settings' => $settings,
                        'device_id' => $deviceId,
                    ];
                }
                return $devices;
            }, 'Fetch all devices');
        });
    }
    /**
     * Retrieves a specific device by device_id (di) or serial_number (sn) from cache.
     *
     * @param string $businessId Business identifier
     * @param string $type 'di' for device_id, 'sn' for serial_number
     * @param string $typeId The device_id or serial_number
     * @return array|null Device details or null if not found
     */
    public function getDevice(string $businessId, string $type, string $typeId): ?array
    {
        return $this->executeOperation(function () use ($businessId, $type, $typeId) {
            $businessId = strtoupper($businessId);
            $typeId = strtoupper($typeId);
            $devices = $this->getDevices();
            if (!$devices || !isset($devices[$businessId]) || !isset($devices[$businessId][$typeId])) {
                return null;
            }
            $device = $devices[$businessId][$typeId];
            if ($device['type'] !== ($type === 'di' ? 'device_id' : 'serial_number')) {
                return null;
            }
            return [
                'business_id' => $businessId,
                'device_id' => $type === 'di' ? $typeId : $device['device_id'],
                'serial_number' => $type === 'sn' ? $typeId : $device['serial_number'],
                'settings' => $device['settings'],
            ];
        }, "Fetch device {$typeId}", $businessId, $typeId);
    }
    /**
     * Retrieves device settings in plain-text format for terminals.
     *
     * @param string $serialNumber Device serial number
     * @param string $businessId Business ID
     * @return string|null Plain-text response or null if device not found
     */
    public function getDeviceSettings(string $serialNumber, string $businessId): ?string
    {
        return $this->executeOperation(function () use ($serialNumber, $businessId) {
            $device = $this->getDevice($businessId, 'sn', $serialNumber);
            if (!$device) {
                return null;
            }
            $settings = $device['settings'] ?? [];
            $serialNumber = $device['serial_number'];
            return "GET OPTION FROM: {$serialNumber}\r"
                . 'Stamp=' . ($settings['Stamp'] ?? '') . "\r"
                . 'ATTLOGStamp=' . ($settings['ATTLOGStamp'] ?? 'None') . "\r"
                . 'OpStamp=' . ($settings['OpStamp'] ?? '') . "\r"
                . 'OPERLOGStamp=' . ($settings['OPERLOGStamp'] ?? 'None') . "\r"
                . 'PhotoStamp=' . ($settings['PhotoStamp'] ?? '') . "\r"
                . 'ATTPHOTOStamp=' . ($settings['ATTPHOTOStamp'] ?? 'None') . "\r"
                . 'ErrorDelay=' . ($settings['ErrorDelay'] ?? 13) . "\r"
                . 'Delay=' . ($settings['Delay'] ?? 4) . "\r"
                . 'TransTimes=' . ($settings['TransTimes'] ?? '09:00;18:30') . "\r"
                . 'TransInterval=' . ($settings['TransInterval'] ?? 7) . "\r"
                . 'TransFlag=' . ($settings['TransFlag'] ?? '111111101101') . "\r"
                . 'Realtime=' . ($settings['Realtime'] ?? 1) . "\r"
                . 'TimeOut=' . ($settings['TimeOut'] ?? 9) . "\r"
                . 'TimeZone=' . ($settings['TimeZone'] ?? 330) . "\r"
                . 'Encrypt=' . ($settings['Encrypt'] ?? 0) . "\r\r"
                . 'OK';
        }, "Fetch settings for {$serialNumber}", $businessId, $serialNumber);
    }
    /**
     * Retrieves PENDING commands from cache or central DB with rate limiting.
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @return array Pending commands
     */
    public function getPendingCommands(string $deviceId, string $businessId): array
    {
        $commandsTtl = Config::get('adms.cache.commands_ttl', 30);
        $cacheKey = sprintf(self::CACHE_KEY_COMMANDS, $businessId, $deviceId);
        return Cache::remember($cacheKey, $commandsTtl, function () use ($deviceId, $businessId) {
            return $this->executeOperation(function () use ($deviceId, $businessId) {
                // Rate limit command fetches
                $serialNumber = $this->getDevice($businessId, 'di', $deviceId)['serial_number'] ?? $deviceId;
                $rateLimitKey = "pending_commands:{$businessId}:{$serialNumber}";
                $commandRate = Config::get('adms.rate_limit.commands_per_minute', 100);
                $rateLimitTtl = Config::get('adms.rate_limit.ttl', 60);
                if (!$this->checkTemporaryCacheRateLimit($rateLimitKey, $commandRate, $rateLimitTtl)) {
                    throw new Exception('Command fetch rate limit exceeded');
                }
                $now = now();
                return DB::connection('central')
                    ->table('business_commands')
                    ->where('business_id', $businessId)
                    ->where('device_id', $deviceId)
                    ->where('status', 'PENDING')
                    ->where(function ($query) use ($now) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', $now);
                    })
                    ->orderBy('created_at')
                    ->get(['command_id', 'name', 'command', 'params'])
                    ->map(function ($command) {
                        return [
                            'command_id' => $command->command_id,
                            'name' => $command->name,
                            'command' => $command->command,
                            'params' => $command->params ? json_decode($command->params, true) ?? [] : [],
                        ];
                    })
                    ->toArray();
            }, "Fetch pending commands for {$deviceId}", $businessId, $deviceId);
        }) ?? [];
    }
    /**
     * Creates a new command with validation, storage, rate limiting, and queueing.
     *
     * @param string $serialNumber Device serial number
     * @param string $businessId Business ID
     * @param string $name Command name
     * @param array $params Command parameters
     * @param bool|null $resp Whether to return a detailed response array
     * @return array Command details or detailed response
     */
    public function command(string $serialNumber, string $businessId, string $name, array $params = [], ?bool $resp = false): array
    {
        return $this->executeOperation(function () use ($serialNumber, $businessId, $name, $params, $resp) {
            // Command mapping with validation rules (unchanged for consistency)
            $commandMapping = [
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
                    ]
                ],
                'DELETE USER' => [
                    'command' => 'DATA DEL_USER',
                    'rules' => ['PIN' => 'required|alpha_dash|max:50']
                ],
                'ADD FINGERPRINT' => [
                    'command' => 'DATA FP',
                    'rules' => [
                        'PIN' => 'required|alpha_dash|max:50',
                        'FID' => 'required|integer|min:1',
                        'Valid' => 'required|integer|in:0,1',
                        'SIZE' => 'required|integer|min:1',
                        'TMP' => 'required|string',
                    ]
                ],
                'ENROLL FINGERPRINT' => [
                    'command' => 'ENROLL_FP',
                    'rules' => [
                        'PIN' => 'required|alpha_dash|max:50',
                        'FID' => 'required|integer|min:1',
                        'RETRY' => 'nullable|integer|min:1',
                        'OVERWRITE' => 'nullable|integer|in:0,1',
                    ]
                ],
                'ADD FACE' => [
                    'command' => 'DATA UPDATE FACE',
                    'rules' => [
                        'PIN' => 'required|alpha_dash|max:50',
                        'FID' => 'required|integer|min:1',
                        'Valid' => 'required|integer|in:0,1',
                        'SIZE' => 'required|integer|min:1',
                        'TMP' => 'required|string',
                    ]
                ],
                'ENROLL FACE' => [
                    'command' => 'ENROLL_FP',
                    'rules' => [
                        'PIN' => 'required|alpha_dash|max:50',
                        'FID' => 'required|integer|min:1',
                        'RETRY' => 'nullable|integer|min:1',
                        'OVERWRITE' => 'nullable|integer|in:0,1',
                    ]
                ],
                'REBOOT DEVICE' => ['command' => 'REBOOT', 'rules' => []],
                'CLEAR LOG' => ['command' => 'CLEAR LOG', 'rules' => []],
                'CLEAR DATA' => ['command' => 'CLEAR DATASSS', 'rules' => []],
                'CHECK DEVICE' => ['command' => 'CHECK', 'rules' => []],
                'DEVICE INFO' => ['command' => 'INFO', 'rules' => []],
                'UNLOCK DOOR' => ['command' => 'AC_UNLOCK', 'rules' => []],
                'GET LOG' => ['command' => 'GET LOG', 'rules' => []],
                'QUERY ATTENDANCE LOG' => [
                    'command' => 'DATA QUERY ATTLOG',
                    'rules' => [
                        'StartTime' => 'required|date_format:Y-m-d H:i:s',
                        'EndTime' => 'required|date_format:Y-m-d H:i:s|after_or_equal:StartTime',
                    ]
                ],
                'CHANGE WEB ADDRESS' => ['command' => 'SET OPTION', 'rules' => ['ICLOCKSVRURL' => 'required|url|max:255']],
                'CHANGE WEB PORT' => ['command' => 'SET OPTION', 'rules' => ['IclockSvrPort' => 'required|integer|min:1|max:65535']],
            ];
            if (!isset($commandMapping[$name])) {
                throw new InvalidArgumentException("Invalid command name: {$name}");
            }
            // Rate limiting for command creation and device
            $commandRateLimitKey = "command:{$businessId}:{$serialNumber}";
            $deviceRateLimitKey = "ratelimit:device:{$businessId}:{$serialNumber}";
            $commandRate = Config::get('adms.rate_limit.commands_per_minute', 100);
            $deviceRate = Config::get('adms.rate_limit.per_device', 100);
            $rateLimitTtl = Config::get('adms.rate_limit.ttl', 60);
            if (!$this->checkTemporaryCacheRateLimit($commandRateLimitKey, $commandRate, $rateLimitTtl)) {
                throw new Exception("Command rate limit exceeded for {$businessId}:{$serialNumber}");
            }
            if (!$this->checkTemporaryCacheRateLimit($deviceRateLimitKey, $deviceRate, $rateLimitTtl)) {
                throw new Exception("Device rate limit exceeded for {$businessId}:{$serialNumber}");
            }
            // Validate input parameters
            $device = $this->getDevice($businessId, 'sn', $serialNumber);
            if (!$device) {
                throw new InvalidArgumentException("Device not found: {$serialNumber}");
            }
            $commonRequired = Config::get('adms.validation.common_rules', [
                'serial_number' => 'required|string|max:255',
                'business_id' => 'required|string',
                'device_id' => 'required|string',
            ]);
            $rules = array_merge($commonRequired, $commandMapping[$name]['rules']);
            $input = array_merge($params, [
                'serial_number' => $serialNumber,
                'business_id' => $businessId,
                'device_id' => $device['device_id'] ?? null,
            ]);
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            // Prepare command
            $filteredParams = collect($params)->except(Config::get('adms.excluded_params', ['_token', 'save_token', 'form_type', 'device_id']));
            $commandParams = $filteredParams->toJson(JSON_UNESCAPED_UNICODE);
            $commandId = Str::upper('CMD' . now()->timestamp . rand(1000, 9999));
            $expirationSeconds = Config::get('adms.commands.expiration', 300);
            $expiresAt = now()->addSeconds((int)$expirationSeconds);
            // Store commands with batch if needed, but single insert here
            $businessResult = Data::insert('central', 'business_commands', [
                'command_id' => $commandId,
                'business_id' => $businessId,
                'serial_number' => $serialNumber,
                'device_id' => $input['device_id'],
                'command' => $commandMapping[$name]['command'],
                'params' => $commandParams,
                'expires_at' => $expiresAt,
                'status' => 'PENDING',
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$businessResult['status']) {
                throw new Exception('Failed to save business command');
            }
            $deviceResult = Data::insert($businessId, 'device_commands', [
                'command_id' => $commandId,
                'device_id' => $input['device_id'],
                'serial_number' => $serialNumber,
                'command' => $commandMapping[$name]['command'],
                'params' => $commandParams,
                'expires_at' => $expiresAt,
                'status' => 'PENDING',
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$deviceResult['status']) {
                throw new Exception('Failed to save device command');
            }
            // Clear cache for quick future responses
            Cache::forget(sprintf(self::CACHE_KEY_COMMANDS, $businessId, $input['device_id']));
            return $resp ? [
                'status' => true,
                'reload_table' => false,
                'reload_card' => false,
                'reload_page' => false,
                'hold_popup' => false,
                'token' => null,
                'affected' => $deviceResult['id'] ?? $commandId,
                'title' => 'Command Sent',
                'message' => "$name executed successfully.",
            ] : [
                'command_id' => $commandId,
                'device_id' => $input['device_id'],
                'name' => $name,
                'command' => $commandMapping[$name]['command'],
                'parameters' => $params,
            ];
        }, "Create command {$name}", $businessId, $serialNumber, $name);
    }
    /**
     * Updates command status and cleans expired commands with error handling.
     *
     * @param string $businessId Business ID
     * @param string $commandId Command ID
     * @param string $type Request type (getrequest or devicecmd)
     * @param string|null $data Response data
     */
    public function updateCommand(string $businessId, string $commandId, string $type, ?string $data = null): void
    {
        $this->executeOperation(function () use ($businessId, $commandId, $type, $data) {
            $supportedTypes = Config::get('adms.supported_command_types', ['getrequest', 'devicecmd']);
            if (!in_array($type, $supportedTypes)) {
                throw new InvalidArgumentException("Invalid type: {$type}");
            }
            // Clean expired commands periodically
            // $expirationSeconds = Config::get('adms.commands.expiration', 300);
            // CentralDB::table('business_commands')
            //     ->where('created_at', '<', now()->subSeconds($expirationSeconds))
            //     ->delete();
            if ($type === 'getrequest') {
                $dataArr = explode('---', $data);
                $updateData = ['command_string' => $dataArr[0], 'status' => 'SENT'];
                Data::update('central', 'business_commands', $updateData, ['command_id' => $commandId]);
                Data::update($businessId, 'device_commands', ['status' => 'SENT'], ['command_id' => $commandId]);
                return;
            }
            if ($type === 'devicecmd') {
                parse_str($data ?? '', $respArr);
                $cmdData = $respArr['CMD'] ?? '';
                $infoArray = [];
                foreach (preg_split('/\r\n|\n|\r/', $cmdData, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                    if (strpos($line = ltrim($line, '~'), '=') !== false) {
                        [$key, $value] = explode('=', $line, 2);
                        $infoArray[trim($key)] = trim($value);
                    }
                }
                $updateCommandData = [
                    'status' => 'EXECUTED',
                    'code' => $respArr['Return'] ?? null,
                    'response' => $cmdData ?? null,
                    'updated_at' => now(),
                ];
                Data::update('central', 'business_commands', $updateCommandData, ['command_id' => $commandId]);
                Data::update($businessId, 'device_commands', $updateCommandData, ['command_id' => $commandId]);
                $minDataLength = Config::get('adms.min_device_update_data_length', 150);
                if (strlen($data ?? '') > $minDataLength && isset($infoArray['SerialNumber'])) {
                    $updateDeviceData = [
                        'mac_address' => $infoArray['MAC'] ?? null,
                        'ip' => $infoArray['IPAddress'] ?? null,
                        'info_json' => json_encode($infoArray),
                        'last_sync' => now(),
                        'updated_at' => now(),
                    ];
                    Data::update('central', 'business_devices', $updateDeviceData, ['serial_number' => $infoArray['SerialNumber']]);
                    Data::update($businessId, 'devices', $updateDeviceData, ['serial_number' => $infoArray['SerialNumber']]);
                }
            }
        }, "Update command {$commandId}", $businessId, null, $commandId);
    }
    /**
     * Queues data processing with no locks for faster execution.
     *
     * @param string $deviceId Device ID
     * @param string $businessId Business ID
     * @param string $type Data type (cdata, fdata)
     * @param string $data Raw data
     * @param array $meta Metadata
     */
    public function processData(string $deviceId, string $businessId, string $type, string $data, array $meta): void
    {
        $this->executeOperation(function () use ($deviceId, $businessId, $type, $data, $meta) {
            $supportedTypes = Config::get('adms.supported_data_types', ['cdata', 'fdata']);
            if (!in_array($type, $supportedTypes)) {
                throw new InvalidArgumentException("Invalid data type: {$type}");
            }
            if (empty(trim($data))) {
                throw new InvalidArgumentException('Empty data payload');
            }
            // Dispatch to queue without deduplication for speed
            $queueName = $this->getQueueName();
            AdmsDataJob::dispatch($deviceId, $businessId, $type, $data, $meta)
                ->onQueue($queueName);
        }, "Process data for {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Stores user data in bulk with upsert and batching for high volume (100K+ pins).
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records User records
     */
    public function storeUser(string $businessId, string $deviceId, array $records): void
    {
        $this->executeOperation(function () use ($businessId, $deviceId, $records) {
            if (empty($records)) {
                return;
            }
            $batchSize = Config::get('adms.batch_size', 1000);
            collect($records)->chunk($batchSize)->each(function ($chunk) use ($businessId, $deviceId) {
                $this->storeBiometricOrUser($businessId, $deviceId, 'device_users', $chunk->toArray(), ['device_user_id', 'device_id']);
            });
        }, "Store users for {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Stores attendance data in bulk with deduplication and batching.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Attendance records
     */
    public function storeAttendance(string $businessId, string $deviceId, array $records): void
    {
        $this->executeOperation(function () use ($businessId, $deviceId, $records) {
            if (empty($records)) {
                return;
            }
            $batchSize = Config::get('adms.batch_size', 1000);
            $connection = Database::getConnection($businessId);
            // Normalize records before insertion
            $normalizedRecords = collect($records)->map(function ($record) {
                return collect($record)->map(function ($value) {
                    if ($value instanceof Carbon) {
                        return $value->toDateTimeString(); // Format Carbon to MySQL DATETIME
                    }
                    return $value;
                })->toArray();
            });
            // Chunk insert for performance
            $normalizedRecords->chunk($batchSize)->each(function ($chunk) use ($connection) {
                $connection->table('device_attendance')->insertOrIgnore($chunk->toArray());
            });
        }, "Store attendance for {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Stores fingerprint data in bulk with deduplication and batching.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Fingerprint records
     */
    public function storeFingerprint(string $businessId, string $deviceId, array $records): void
    {
        $this->executeOperation(function () use ($businessId, $deviceId, $records) {
            if (empty($records)) {
                return;
            }
            $batchSize = Config::get('adms.batch_size', 1000);
            collect($records)->chunk($batchSize)->each(function ($chunk) use ($businessId, $deviceId) {
                $this->storeBiometricOrUser($businessId, $deviceId, 'device_fingerprints', $chunk->toArray(), ['device_user_id', 'device_id', 'fid']);
            });
        }, "Store fingerprints for {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Stores face data in bulk with deduplication and batching.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param array $records Face records
     */
    public function storeFace(string $businessId, string $deviceId, array $records): void
    {
        $this->executeOperation(function () use ($businessId, $deviceId, $records) {
            if (empty($records)) {
                return;
            }
            $batchSize = Config::get('adms.batch_size', 1000);
            collect($records)->chunk($batchSize)->each(function ($chunk) use ($businessId, $deviceId) {
                $this->storeBiometricOrUser($businessId, $deviceId, 'device_faces', $chunk->toArray(), ['device_user_id', 'device_id', 'fid']);
            });
        }, "Store faces for {$deviceId}", $businessId, $deviceId);
    }
    /**
     * Stores biometric or user data in bulk with upsert.
     *
     * @param string $businessId Business ID
     * @param string $deviceId Device ID
     * @param string $table Table name
     * @param array $records Records to store
     * @param array $uniqueKeys Unique keys for upsert
     */
    protected function storeBiometricOrUser(string $businessId, string $deviceId, string $table, array $records, array $uniqueKeys): void
    {
        $connection = Database::getConnection($businessId);
        $updateColumns = array_diff(array_keys($records[0] ?? []), $uniqueKeys);
        $connection->table($table)->upsert($records, $uniqueKeys, $updateColumns);
    }
    /**
     * Returns the queue name for job processing.
     *
     * @return string
     */
    protected function getQueueName(): string
    {
        return Config::get('adms.queue.name', 'adms');
    }
    /**
     * Checks rate limit using temporary cache for a given key and limit.
     *
     * @param string $key Cache key for rate limiting
     * @param int $maxRequests Maximum allowed requests
     * @param int $ttl Cache TTL
     * @return bool
     */
    protected function checkTemporaryCacheRateLimit(string $key, int $maxRequests, int $ttl): bool
    {
        $count = Cache::get($key, 0);
        if ($count >= $maxRequests) {
            return false;
        }
        Cache::put($key, $count + 1, $ttl);
        return true;
    }
    /**
     * Executes an operation with comprehensive error handling and logging.
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
            throw $e;
        } catch (\Exception $e) {
            Developer::error("{$action} unexpected error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'deviceId' => $deviceId,
                'commandId' => $commandId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
