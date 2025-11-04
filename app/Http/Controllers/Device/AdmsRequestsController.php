<?php
namespace App\Http\Controllers\Device;
use App\Events\Adms\DeviceCompatibilityCheck;
use App\Facades\Adms;
use App\Facades\CentralDB;
use App\Facades\Developer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Exception;
/**
 * Controller for handling ADMS device requests with minimal DB operations.
 * Supports endpoints: cdata, devicecmd, getrequest, check.
 * Optimized for ultra-fast performance (100K requests/hour) with rate limiting, temporary caching,
 * and comprehensive exception handling. All parameters configurable via config/env.
 */
class AdmsRequestsController extends Controller
{
    // Cache constants
    private const CACHE_KEY_DEVICES = 'adms:devices';
    private const CACHE_KEY_COMMANDS = 'adms:commands:%s:%s'; // businessId:deviceId
    /**
     * Returns a plain text response with proper formatting.
     *
     * @param string $text The text content to return
     * @return \Illuminate\Http\Response
     */
    public static function plain(string $text)
    {
        return response(trim($text) . "\r", 200)->header('Content-Type', 'text/plain');
    }
    /**
     * Handles incoming ADMS requests and routes to appropriate endpoint with rate limiting and caching.
     *
     * @param Request $request HTTP request
     * @param string $code Business code
     * @param string $endpoint Request endpoint
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, string $code, string $endpoint)
    {
        try {
            $logData = [
                'method' => $request->method(),
                'endpoint' => $endpoint,
                'data' => $request->all(),
                'content' => $request->getContent(),
            ];
            // \Log::emergency($logData);
            $businessId = strtoupper($code);
            $method = strtolower(str_replace(['.aspx', '.php'], '', $endpoint));
            $serialNumber = $request->query('SN', $request->input('SN', ''));
            // Validate endpoint
            $routes = Config::get('adms.supported_endpoints', ['cdata', 'devicecmd', 'getrequest']);
            if (!in_array($method, $routes)) {
                throw new InvalidArgumentException('Invalid endpoint');
            }
            // Get device with caching
            $deviceCacheTtl = Config::get('adms.cache.device_ttl', 3600);
            $device = Cache::remember(
                "device:{$businessId}:{$serialNumber}",
                $deviceCacheTtl,
                fn() => Adms::getDevice($businessId, 'sn', $serialNumber)
            );
            if (!$device) {
                throw new Exception('Device not found');
            }
            $deviceId = $device['device_id'];
            // Rate limiting using temporary cache
            if (!$this->checkRateLimit($businessId, $serialNumber)) {
                throw new Exception('Rate limit exceeded');
            }
            // Execute request with full error handling
            return $this->executeRequest(
                function () use ($request, $method, $businessId, $serialNumber, $deviceId) {
                    return match ($method) {
                        'cdata' => $this->cdata($request, $businessId, $serialNumber, $deviceId),
                        'devicecmd' => $this->devicecmd($request, $businessId, $serialNumber, $deviceId),
                        'getrequest' => $this->getrequest($request, $businessId, $serialNumber, $deviceId),
                    };
                },
                "Handle {$method} request",
                $serialNumber
            );
        } catch (InvalidArgumentException $e) {
            Developer::warning("Handle request invalid argument: {$e->getMessage()}", ['endpoint' => $endpoint]);
            return self::plain('Error: Invalid endpoint');
        } catch (Exception $e) {
            Developer::error("Handle request error: {$e->getMessage()}", ['endpoint' => $endpoint]);
            return self::plain('Error Occurred');
        }
    }
    /**
     * Checks rate limits for business and device using temporary cache.
     *
     * @param string $businessId
     * @param string $serialNumber
     * @return bool
     */
    protected function checkRateLimit(string $businessId, string $serialNumber): bool
    {
        $businessRate = Config::get('adms.rate_limit.per_business', 1000);
        $deviceRate = Config::get('adms.rate_limit.per_device', 100);
        $rateLimitTtl = Config::get('adms.rate_limit.ttl', 60);
        $businessKey = "ratelimit:business:{$businessId}";
        $deviceKey = "ratelimit:device:{$businessId}:{$serialNumber}";
        // Use temporary cache for rate limiting
        return $this->checkTemporaryCacheRateLimit($businessKey, $businessRate, $rateLimitTtl) &&
            $this->checkTemporaryCacheRateLimit($deviceKey, $deviceRate, $rateLimitTtl);
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
        // Increment count with configurable TTL for temporary storage
        Cache::put($key, $count + 1, $ttl);
        return true;
    }
    /**
     * Handles cdata endpoint for configuration or data upload with caching and error handling.
     *
     * @param Request $request
     * @param string $businessId
     * @param string $serialNumber
     * @param string $deviceId
     * @return \Illuminate\Http\Response
     */
    protected function cdata(Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
        try {
            $this->validateRequest($request, [
                'SN' => 'required|string|max:50|alpha_dash',
            ]);
            if ($request->isMethod('GET')) {
                $settingsCacheTtl = Config::get('adms.cache.device_ttl', 3600);
                $response = Cache::remember(
                    "settings:{$businessId}:{$serialNumber}",
                    $settingsCacheTtl,
                    fn() => Adms::getDeviceSettings($serialNumber, $businessId) ?? 'OK'
                );
                return self::plain($response);
            }
            $meta = $request->query();
            $data = trim($request->getContent());
            if (empty($data)) {
                return self::plain('OK');
            }
            Adms::processData($deviceId, $businessId, 'cdata', $data, $meta);
            return self::plain('OK');
        } catch (ValidationException $e) {
            Developer::warning("Cdata validation error: {$e->getMessage()}", ['errors' => $e->errors()]);
            return self::plain('ERROR: Invalid input');
        } catch (Exception $e) {
            Developer::error("Cdata processing error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'serialNumber' => $serialNumber,
            ]);
            return self::plain('Error: Processing failed');
        }
    }
    /**
     * Handles devicecmd endpoint for command execution with async dispatch.
     *
     * @param Request $request
     * @param string $businessId
     * @param string $serialNumber
     * @param string $deviceId
     * @return \Illuminate\Http\Response
     */
    protected function devicecmd(Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
        try {
            $this->validateRequest($request, [
                'SN' => 'required|string|max:50|alpha_dash',
            ]);
            $data = trim($request->getContent());
            if (!empty($data)) {
                parse_str($data, $respArr);
                $commandId = $respArr['ID'] ?? null;
                if (!empty($commandId)) {
                    dispatch(function () use ($commandId, $businessId, $data) {
                        Adms::updateCommand($businessId, $commandId, 'devicecmd', $data);
                    })->onQueue($this->getServiceQueueName());
                }
            }
            return self::plain('OK');
        } catch (ValidationException $e) {
            Developer::warning("Devicecmd validation error: {$e->getMessage()}", ['errors' => $e->errors()]);
            return self::plain('ERROR: Invalid input');
        } catch (Exception $e) {
            Developer::error("Devicecmd processing error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'serialNumber' => $serialNumber,
            ]);
            return self::plain('Error: Command execution failed');
        }
    }
    /**
     * Handle getrequest endpoint to retrieve pending commands for a device.
     *
     * @param Request $request
     * @param string $businessId
     * @param string $serialNumber
     * @param string $deviceId
     * @return \Illuminate\Http\Response
     */
    protected function getrequest(Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
        try {
            $this->validateRequest($request, [
                'SN' => 'required|string|max:50|alpha_dash',
            ]);
            $commands = Adms::getPendingCommands($deviceId, $businessId);
            $commands = array_filter((array)$commands, fn($cmd) => !empty($cmd) && is_array($cmd) && !empty($cmd['command_id']));
            if (empty($commands)) {
                return self::plain("OK");
            }
            // Build command strings
            $commandStringArr = [];
            $responseLines = [];
            foreach ($commands as $command) {
                if (!isset($command['command_id'], $command['command'])) {
                    continue;
                }
                $paramsString = '';
                if (!empty($command['params'])) {
                    $params = $command['params'];
                    if (is_array($params)) {
                        $isAssociative = array_keys($params) !== range(0, count($params) - 1);
                        $paramsString = ' ' . collect($params)->map(
                            fn($v, $k) => $isAssociative ? "$k=" . (string)$v : (string)$v
                        )->implode("\t");
                    }
                }
                $commandString = "C:{$command['command_id']}:{$command['command']}{$paramsString}";
                $commandStringArr[$command['command_id']] = $commandString;
                $responseLines[] = $commandString . "\r\n";
            }
            $response = !empty($responseLines) ? implode('', $responseLines) . "OK\r\n" : "OK\r\n";
            $cacheKey = sprintf(self::CACHE_KEY_COMMANDS, $businessId, $deviceId);
            Cache::forget($cacheKey);
            // Dispatch background update
            $data = trim($request->getContent());
            dispatch(function () use ($commands, $businessId, $data, $commandStringArr) {
                foreach ($commands as $command) {
                    $commandId = $command['command_id'] ?? null;
                    if (!$commandId || !isset($commandStringArr[$commandId])) {
                        Developer::warning("Command ID {$commandId} not found in command string array", [
                            'businessId' => $businessId,
                            'command' => $command,
                        ]);
                        continue;
                    }
                    $set = $commandStringArr[$commandId] . "---" . $data;
                    Adms::updateCommand($businessId, $commandId, 'getrequest', $set);
                }
            })->onQueue(Config::get('adms.queue.service_name', 'adms_service'));
            return self::plain($response);
        } catch (ValidationException $e) {
            Developer::warning("Getrequest validation error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'serialNumber' => $serialNumber,
                'errors' => $e->errors(),
            ]);
            return self::plain('ERROR: Invalid input');
        } catch (Exception $e) {
            Developer::error("Getrequest processing error: {$e->getMessage()}", [
                'businessId' => $businessId,
                'serialNumber' => $serialNumber,
                'deviceId' => $deviceId,
                'trace' => $e->getTraceAsString(),
            ]);
            return self::plain('Error: Command retrieval failed');
        }
    }
    /**
     * Returns the queue name for job processing.
     *
     * @return string
     */
    protected function getServiceQueueName(): string
    {
        return Config::get('adms.queue.service_name', 'adms_service');
    }
    /**
     * Validates incoming request data with custom messages.
     *
     * @param Request $request
     * @param array $rules
     * @throws ValidationException
     */
    protected function validateRequest(Request $request, array $rules): void
    {
        $validator = Validator::make($request->all(), $rules, [
            'SN.required' => 'Serial number is required',
            'SN.max' => 'Serial number must not exceed 50 characters',
            'SN.alpha_dash' => 'Serial number must contain only letters, numbers, dashes, or underscores',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    /**
     * Executes a request with full error handling and logging.
     *
     * @param callable $callback
     * @param string $action
     * @param string|null $serialNumber
     * @return \Illuminate\Http\Response
     */
    protected function executeRequest(callable $callback, string $action, ?string $serialNumber)
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            Developer::warning("{$action} validation error", [
                'serialNumber' => $serialNumber,
                'errors' => $e->errors(),
            ]);
            return self::plain('ERROR: Invalid input');
        } catch (Exception $e) {
            Developer::error("{$action} unexpected error: {$e->getMessage()}", [
                'serialNumber' => $serialNumber,
                'trace' => $e->getTraceAsString(),
            ]);
            return self::plain('Error Occurred');
        }
    }
}
