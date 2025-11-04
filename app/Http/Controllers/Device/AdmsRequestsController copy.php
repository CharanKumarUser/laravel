<?php
namespace App\Http\Controllers\Device;
use App\Events\Adms\DeviceCompatibilityCheck;
use App\Facades\Adms;
use App\Facades\CentralDB;
use App\Facades\Developer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
/**
 * Controller for handling ADMS device requests with minimal DB operations.
 * Supports endpoints: cdata, devicecmd, getrequest, check.
 */
class AdmsRequestsController extends Controller
{
    /**
     * Returns a plain text response with proper formatting.
     *
     * @param  string  $text  The text content to return
     * @return \Illuminate\Http\Response
     */
    public static function plain(string $text)
    {
        return response(trim($text) . "\r", 200)->header('Content-Type', 'text/plain');
    }
    /**
     * Handles incoming ADMS requests and routes to appropriate endpoint.
     *
     * @param Request $request HTTP request
     * @param string $code Business code
     * @param string $endpoint Request endpoint
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, string $code, string $endpoint)
    {
        $logData = [
            'timestamp' => now()->toDateTimeString(),
            'method' => $request->method(),
            'endpoint' => $endpoint,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'headers' => collect($request->headers->all())->except(['authorization'])->toArray(),
            'query_params' => $request->query(),
            'post_data' => $request->post(),
            'content_length' => $request->getContent(),
        ];
        // \Log::emergency($logData);
        $businessId = strtoupper($code);
        $method = strtolower(str_replace(['.aspx', '.php'], '', $endpoint));
        $routes = ['cdata', 'devicecmd', 'getrequest'];
        if (!in_array($method, $routes)) {
            return self::plain('Error Occurred');
        }
        return $this->executeRequest(function () use ($request, $method, $businessId) {
            $serialNumber = $request->query('SN', $request->input('SN', ''));
            $device = Adms::getDevice($businessId, 'sn', $serialNumber);
            if (!$device) {
                return self::plain('Error Occurred');
            }
            $deviceId = $device['device_id'];
            return match ($method) {
                'cdata' => $this->cdata($request, $businessId, $serialNumber, $deviceId),
                'devicecmd' => $this->devicecmd($request, $businessId, $serialNumber, $deviceId),
                'getrequest' => $this->getrequest($request, $businessId, $serialNumber, $deviceId),
            };
        }, "Handle {$method} request", $request->query('SN', null));
    }
    /**
     * Handles cdata endpoint for configuration or data upload.
     *
     * @param Request $request HTTP request
     * @param string $businessId Business ID
     * @param string $serialNumber Device serial number
     * @param string $deviceId Device ID
     * @return \Illuminate\Http\Response
     */
    protected function cdata(Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
        $this->validateRequest($request, [
            'SN' => 'required|string|max:50|alpha_dash',
        ]);
        if ($request->isMethod('GET')) {
            $response = Adms::getDeviceSettings($serialNumber, $businessId) ?? 'OK';
            return self::plain($response);
        }
        $meta = $request->query();
        $data = trim($request->getContent());
        if (empty($data)) {
            return self::plain('OK');
        }
        Adms::processData($deviceId, $businessId, 'cdata', $data, $meta);
        return self::plain('OK');
    }
    /**
     * Handles devicecmd endpoint for command execution.
     *
     * @param Request $request HTTP request
     * @param string $businessId Business ID
     * @param string $serialNumber Device serial number
     * @param string $deviceId Device ID
     * @return \Illuminate\Http\Response
     */
    protected function devicecmd(Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
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
                })->onQueue(Config::get('adms.queue.service_name', 'adms_service'));
            }
        }
        return self::plain('OK');
    }
    /**
     * Handles getrequest endpoint to retrieve pending commands.
     *
     * @param \Illuminate\Http\Request $request HTTP request
     * @param string $businessId Business ID
     * @param string $serialNumber Device serial number
     * @param string $deviceId Device ID
     * @return \Illuminate\Http\Response
     */
    protected function getrequest(\Illuminate\Http\Request $request, string $businessId, string $serialNumber, string $deviceId)
    {
        // Validate input
        $this->validateRequest($request, [
            'SN' => 'required|string|max:50|alpha_dash',
        ]);
        // Get pending commands
        $commands = Adms::getPendingCommands($deviceId, $businessId);
        // Normalize to array and remove empty/falsy values
        if (!is_array($commands)) {
            $commands = (array) $commands;
        }
        $commands = array_filter($commands, fn($cmd) => !empty($cmd));
        // If there are no valid commands, respond with plain OK
        if (empty($commands)) {
            return $this->plain("OK");
        }
        // Build the response string
        $response = collect($commands)
            ->map(function ($command) {
                // Ensure command is array-like
                if (!is_array($command)) {
                    return '';
                }
                $params = !empty($command['params'])
                    ? ' ' . collect($command['params'])
                    ->map(fn($v, $k) => "$k=" . (string)$v)
                    ->implode("\t")
                    : '';
                return "C:{$command['command_id']}:{$command['command']}{$params}\r\n";
            })
            ->implode('') . "OK\r\n";
        // Dispatch async updates for all commands
        $data = trim($request->getContent());
        dispatch(function () use ($commands, $businessId, $data) {
            foreach ($commands as $command) {
                if (!empty($command['command_id'])) {
                    Adms::updateCommand($businessId, $command['command_id'], 'getrequest', $data);
                }
            }
        })->onQueue(Config::get('adms.queue.service_name', 'adms_service'));
        // Return plain response to device
        return $this->plain("OK\r\n" . $response);
    }
    /**
     * Handles device check requests for onboarding.
     *
     * @param Request $request HTTP request
     * @param string $code Business code
     * @param string $endpoint Request endpoint
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request, string $code, string $endpoint)
    {
        $method = strtolower(str_replace(['.aspx', '.php'], '', $endpoint));
        $routes = ['cdata', 'devicecmd', 'getrequest'];
        if (!in_array($method, $routes)) {
            return self::plain('Error Occurred');
        }
        return $this->executeRequest(function () use ($request, $method, $code) {
            $serialNumber = $request->query('SN', $request->input('SN', ''));
            $onboarding = CentralDB::table('business_onboarding')
                ->where('device_code', $code)
                ->first();
            if (!$onboarding) {
                return self::plain('Error Occurred');
            }
            return match ($method) {
                'cdata' => $this->checkCdata($request, $onboarding->id, $onboarding->onboarding_id, $serialNumber, $code, $onboarding->device_count),
                'devicecmd' => $this->checkDevicecmd($request, $onboarding->id, $onboarding->onboarding_id, $serialNumber, $onboarding->device_count),
                'getrequest' => $this->checkGetrequest($request, $onboarding->id, $onboarding->onboarding_id, $serialNumber, $code),
            };
        }, "Check {$method} request", $request->query('SN', null));
    }
    /**
     * Handles cdata endpoint for onboarding configuration or data upload.
     *
     * @param Request $request HTTP request
     * @param string $onboardingId Onboarding ID
     * @param string $businessOnboardingId Business onboarding ID
     * @param string $serialNumber Device serial number
     * @param string $code Device code
     * @param int $deviceCount Expected device count
     * @return \Illuminate\Http\Response
     */
    protected function checkCdata(Request $request, string $onboardingId, string $businessOnboardingId, string $serialNumber, string $code, int $deviceCount)
    {
        $this->validateRequest($request, [
            'SN' => 'required|string|max:50|alpha_dash',
        ]);
        if ($request->isMethod('GET')) {
            $settings = [
                'trans_stamp' => 0,
                'attlog_stamp' => 0,
                'op_stamp' => 0,
                'operlog_stamp' => 0,
                'photo_stamp' => 0,
                'attphoto_stamp' => 0,
                'error_delay' => 30,
                'delay' => 10,
                'trans_times' => '09:00;18:30',
                'trans_interval' => 10,
                'trans_flag' => '111111111111',
                'realtime' => 1,
                'timeout' => 30,
                'timezone' => 330,
                'encrypt' => 1,
            ];
            $response = "GET OPTION FROM: {$serialNumber}\r" .
                "Stamp={$settings['trans_stamp']}\r" .
                "ATTLOGStamp={$settings['attlog_stamp']}\r" .
                "OpStamp={$settings['op_stamp']}\r" .
                "OPERLOGStamp={$settings['operlog_stamp']}\r" .
                "PhotoStamp={$settings['photo_stamp']}\r" .
                "ATTPHOTOStamp={$settings['attphoto_stamp']}\r" .
                "ErrorDelay={$settings['error_delay']}\r" .
                "Delay={$settings['delay']}\r" .
                "TransTimes={$settings['trans_times']}\r" .
                "TransInterval={$settings['trans_interval']}\r" .
                "TransFlag={$settings['trans_flag']}\r" .
                "Realtime={$settings['realtime']}\r" .
                "TimeOut={$settings['timeout']}\r" .
                "TimeZone={$settings['timezone']}\r" .
                "Encrypt={$settings['encrypt']}\r\r" .
                'OK';
            return self::plain($response);
        }
        $meta = $request->query();
        $data = trim($request->getContent());
        if (empty($data)) {
            return self::plain('ERROR Occurred');
        }
        Adms::processData($onboardingId, $businessOnboardingId, 'cdata', $data, $meta);
        return self::plain('OK');
    }
    /**
     * Handles devicecmd endpoint for processing device info during onboarding.
     *
     * @param Request $request HTTP request
     * @param string $onboardingId Onboarding ID
     * @param string $businessOnboardingId Business onboarding ID
     * @param string $serialNumber Device serial number
     * @param int $deviceCount Expected device count
     * @return \Illuminate\Http\Response
     */
    protected function checkDevicecmd(Request $request, string $onboardingId, string $businessOnboardingId, string $serialNumber, int $deviceCount)
    {
        $this->validateRequest($request, [
            'SN' => 'required|string|max:50|alpha_dash',
        ]);
        $data = trim($request->getContent());
        if (empty($data)) {
            return self::plain('Error Occurred');
        }
        parse_str($data, $respArr);
        $commandId = $respArr['ID'] ?? null;
        if (!empty($commandId) && str_contains($data, 'INFO')) {
            $deviceInfo = $this->parseDeviceInfo($data);
            if ($deviceInfo && isset($deviceInfo['SerialNumber'])) {
                $this->storeDeviceInfo($businessOnboardingId, $deviceInfo['SerialNumber'], $deviceInfo, $deviceCount);
            }
        }
        return self::plain('OK');
    }
    /**
     * Handles getrequest endpoint for sending INFO command during onboarding.
     *
     * @param Request $request HTTP request
     * @param string $onboardingId Onboarding ID
     * @param string $businessOnboardingId Business onboarding ID
     * @param string $serialNumber Device serial number
     * @param string $code Device code
     * @return \Illuminate\Http\Response
     */
    protected function checkGetrequest(Request $request, string $onboardingId, string $businessOnboardingId, string $serialNumber, string $code)
    {
        $this->validateRequest($request, [
            'SN' => 'required|string|max:50|alpha_dash',
        ]);
        $command = "C:{$code}:INFO";
        return self::plain($command . "\r\n");
    }
    /**
     * Parses device info from response string.
     *
     * @param string $data Raw response data
     * @return array|null Parsed device info or null if parsing fails
     */
    protected function parseDeviceInfo(string $data): ?array
    {
        $parts = explode("\n", $data, 2);
        $urlEncodedPart = $parts[0] ?? '';
        $keyValuePart = $parts[1] ?? '';
        parse_str($urlEncodedPart, $parsed);
        $lines = array_filter(array_map('trim', explode("\n", $keyValuePart)));
        foreach ($lines as $line) {
            if (empty($line) || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $key = ltrim($key, '~');
            $parsed[$key] = $value;
        }
        return !empty($parsed) ? $parsed : null;
    }
    /**
     * Stores device info in business_onboarding table.
     *
     * @param string $businessOnboardingId Business onboarding ID
     * @param string $serialNumber Device serial number
     * @param array $deviceInfo Device information
     * @param int $deviceCount Expected device count
     */
    protected function storeDeviceInfo(string $businessOnboardingId, string $serialNumber, array $deviceInfo, int $deviceCount): void
    {
        $onboarding = CentralDB::table('business_onboarding')
            ->where('onboarding_id', $businessOnboardingId)
            ->first();
        $existingDevices = ($onboarding->device_info ?? '') ? json_decode($onboarding->device_info, true) : [];
        if (isset($existingDevices[$serialNumber])) {
            return;
        }
        $existingDevices[$serialNumber] = $deviceInfo;
        $syncedCount = count($existingDevices);
        $deviceCheck = $syncedCount >= $deviceCount ? 'passed' : 'partial';
        CentralDB::table('business_onboarding')
            ->where('onboarding_id', $businessOnboardingId)
            ->update([
                'device_info' => json_encode($existingDevices),
                'device_check' => $deviceCheck,
                'updated_at' => now(),
            ]);
        $this->broadcastDeviceUpdate($businessOnboardingId, $deviceCount, $deviceInfo);
    }
    /**
     * Broadcasts device sync update.
     *
     * @param string $businessOnboardingId Business onboarding ID
     * @param int $deviceCount Expected device count
     * @param array|null $latestDevice Latest device info
     */
    protected function broadcastDeviceUpdate(string $businessOnboardingId, int $deviceCount, ?array $latestDevice = null): void
    {
        $onboarding = CentralDB::table('business_onboarding')
            ->where('onboarding_id', $businessOnboardingId)
            ->first();
        $syncedDevices = count(json_decode($onboarding->device_info ?? '{}', true));
        event(new DeviceCompatibilityCheck($businessOnboardingId, $deviceCount, $syncedDevices, $latestDevice));
    }
    /**
     * Validates incoming request data.
     *
     * @param Request $request HTTP request
     * @param array $rules Validation rules
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
     * Executes a request with error handling.
     *
     * @param callable $callback Request callback
     * @param string $action Action description
     * @param string|null $serialNumber Device serial number
     * @return \Illuminate\Http\Response
     */
    protected function executeRequest(callable $callback, string $action, ?string $serialNumber = null)
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            Developer::warning("{$action} validation error", [
                'serialNumber' => $serialNumber,
                'errors' => $e->errors(),
            ]);
            return self::plain('ERROR: Invalid input');
        } catch (\Exception $e) {
            Developer::error("{$action} unexpected error: {$e->getMessage()}", [
                'serialNumber' => $serialNumber,
            ]);
            return self::plain('Error Occurred');
        }
    }
}
