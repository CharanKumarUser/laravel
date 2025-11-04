<?php
namespace App\Http\Helpers;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Facades\Developer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class AgentHelper
{
    /**
     * Get detailed device information.
     *
     * @return array
     */
    public static function getDeviceInfo(): array
    {
        try {
            $request = Request::capture();
            $agent = new Agent();
            $agent->setUserAgent($request->header('User-Agent', 'Unknown'));
            $device = $agent->device() ?: 'Unknown Device';
            $platform = $agent->platform() ?: 'Unknown Platform';
            $platformVersion = $agent->version($platform) ?: 'Unknown Version';
            $browser = $agent->browser() ?: 'Unknown Browser';
            $browserVersion = $agent->version($browser) ?: 'Unknown Version';
            $deviceType = $agent->isTablet() ? 'Tablet' : ($agent->isMobile() ? 'Mobile' : 'Desktop');
            $languages = $agent->languages() ?: ['Unknown'];
            $screenResolution = $request->header('X-Screen-Resolution', 'Unknown');
            $formatted = sprintf(
                '%s: %s (OS: %s %s, Browser: %s %s, Language: %s)%s',
                $deviceType,
                $device,
                $platform,
                $platformVersion,
                $browser,
                $browserVersion,
                $languages[0] ?? 'Unknown',
                $agent->isRobot() ? ' (Bot)' : ''
            );
            $info = [
                'device' => $device,
                'device_type' => $deviceType,
                'platform' => $platform,
                'platform_version' => $platformVersion,
                'browser' => $browser,
                'browser_version' => $browserVersion,
                'is_bot' => $agent->isRobot(),
                'languages' => $languages[0] ?? 'Unknown',
                'screen_resolution' => $screenResolution,
                'formatted' => $formatted,
            ];
            Developer::info('Device Info Generated', $info);
            return $info;
        } catch (\Throwable $e) {
            Developer::error('Failed to parse device info: ' . $e->getMessage());
            return [
                'device' => 'Unknown Device',
                'device_type' => 'Unknown',
                'platform' => 'Unknown',
                'platform_version' => 'Unknown',
                'browser' => 'Unknown',
                'browser_version' => 'Unknown',
                'is_bot' => false,
                'languages' => 'Unknown',
                'screen_resolution' => 'Unknown',
                'formatted' => 'Unknown Device (Error Parsing)',
            ];
        }
    }
    /**
     * Get live location based on client IP.
     *
     * @return array
     */
    public static function getLocation(): array
    {
        try {
            $request = Request::capture();
            $ipAddress = $request->ip();
            if (in_array($ipAddress, ['127.0.0.1', '::1', ''], true) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $ipAddress = '';
            }
            $url = ($ipAddress = '' ? "http://ip-api.com/json" : "http://ip-api.com/json/{$ipAddress}");
            $response = Http::timeout(5)->get($url);
            $data = $response->json();
            if ($response->ok() && isset($data['status']) && $data['status'] === 'success') {
                $location = [
                    'provider' => $data['isp'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['regionName'] ?? 'Unknown',
                    'country' => $data['country'] ?? 'Unknown',
                    'formatted' => "{$data['city']}, {$data['regionName']}, {$data['country']}",
                ];
                Developer::info('Location from ip-api.com', ['ip' => $ipAddress, 'location' => $location]);
                return $location;
            }
            Developer::warning('Geolocation APIs failed', ['ip' => $ipAddress]);
            return self::unknownLocation();
        } catch (\Throwable $e) {
            Developer::error('Failed to fetch location: ' . $e->getMessage());
            return self::unknownLocation();
        }
    }
    private static function unknownLocation(): array
    {
        return [
            'city' => 'Unknown',
            'region' => 'Unknown',
            'country' => 'Unknown',
            'formatted' => 'Unknown Location',
        ];
    }
    /**
     * Detect browser capabilities.
     *
     * @return array
     */
    public static function getBrowserCapabilities(): array
    {
        try {
            $request = Request::capture();
            $agent = new Agent();
            $agent->setUserAgent($request->header('User-Agent', 'Unknown'));
            $capabilities = [
                'cookies_enabled' => $request->hasCookie('test_cookie'),
                'javascript_enabled' => $request->header('X-JavaScript-Enabled', 'Unknown'),
                'browser' => $agent->browser() ?: 'Unknown',
                'browser_version' => $agent->version($agent->browser()) ?: 'Unknown',
                'accepts' => $request->header('Accept', 'Unknown'),
            ];
            Developer::info('Browser Capabilities Detected', $capabilities);
            return $capabilities;
        } catch (\Throwable $e) {
            Developer::error('Failed to detect browser capabilities: ' . $e->getMessage());
            return [
                'cookies_enabled' => false,
                'javascript_enabled' => 'Unknown',
                'browser' => 'Unknown',
                'browser_version' => 'Unknown',
                'accepts' => 'Unknown',
            ];
        }
    }
    /**
     * Validate user agent to detect spoofing or bots.
     *
     * @return array
     */
    public static function validateUserAgent(): array
    {
        try {
            $request = Request::capture();
            $agent = new Agent();
            $agent->setUserAgent($request->header('User-Agent', 'Unknown'));
            $userAgent = $agent->getUserAgent() ?: 'Unknown';
            $isBot = $agent->isRobot();
            $isSuspicious = false;
            if (strlen($userAgent) < 10) {
                $isSuspicious = true;
            }
            foreach (['bot', 'crawl', 'spider', 'slurp', 'google', 'bing'] as $signature) {
                if (stripos($userAgent, $signature) !== false) {
                    $isSuspicious = true;
                    break;
                }
            }
            $result = [
                'user_agent' => $userAgent,
                'is_bot' => $isBot,
                'is_suspicious' => $isSuspicious,
                'message' => $isSuspicious ? 'Suspicious user agent detected' : 'Valid user agent',
            ];
            Developer::info('User Agent Validation', $result);
            return $result;
        } catch (\Throwable $e) {
            Developer::error('Failed to validate user agent: ' . $e->getMessage());
            return [
                'user_agent' => 'Unknown',
                'is_bot' => false,
                'is_suspicious' => true,
                'message' => 'Error validating user agent',
            ];
        }
    }
    /**
     * Generate a session ID.
     *
     * @return string
     */
    public static function generateSessionId(): string
    {
        return (string) Str::uuid();
    }
}
