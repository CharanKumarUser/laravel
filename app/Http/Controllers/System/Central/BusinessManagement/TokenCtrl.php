<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Data;
use App\Facades\Database;
use App\Facades\Developer;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * Token controller for handling specific BusinessManagement module operations.
 */
class TokenCtrl extends Controller
{
    /**
     * Handles custom operations for the BusinessManagement module.
     *
     * @param  Request  $request  HTTP request object
     * @param  array  $params  Route parameters
     * @return JsonResponse Response with operation result
     */
    public function index(Request $request, array $params)
    {
        try {
            // Extract and validate action
            $action = $params['redirect'][2];
            $token = $params['redirect'][3];
            if (! $action) {
                return response()->view('errors.404', ['status' => false, 'title' => 'Action is Missing', 'message' => 'No action was provided.'], 404);
            }
            $baseView = 'system.central.business-management';
            // Default View
            $viewPath = 'errors.404';
            $data = [
                'status' => true,
                'token' => $token,
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add custom logic here
            switch ($action) {
                case 'info':
                    $businessResult = Data::get('central', 'businesses', ['where' => ['business_id' => $token]]);
                    $businessDetails = $businessResult['data'][0] ?? null;
                    $colors = [
                        '#f6d365', '#fda085', '#a1c4fd', '#c2e9fb',
                        '#84fab0', '#8fd3f4', '#fccb90', '#d57eeb',
                        '#ff9a9e', '#fad0c4', '#ffecd2', '#fcb69f',
                    ];
                    $randomColor = $colors[array_rand($colors)];
                    $systemResult = Data::get('central', 'systems', ['where' => ['business_id' => $token]]);
                    $deviceUserCount = 0;
                    $userCount = 0;
                    $roleCount = 0;
                    $attendanceCount = 0;
                    $users = [];
                    try {
                        $conn = Database::setupBusinessConnection($token);
                        Developer::alert('the conn', ['connection' => $conn]);
                        // $device_users = DB::connection($conn)->table('device_users')->get();
                        $users = DB::connection($conn)->table('users')->where('email', $businessDetails['admin_email'])->whereNull('deleted_at')->get();
                        Developer::info($users);
                        $roles = DB::connection($conn)->table('roles')->whereNull('deleted_at')->get();
                        $attendance = DB::connection($conn)->table('device_attendance')->get();
                        // $deviceUserCount = $device_users->count();
                        $userCount = $users->count();
                        $roleCount = $roles->count();
                        $attendanceCount = $attendance->count();
                    } catch (\Exception $e) {
                        Developer::alert('Connection Error', ['error' => $e->getMessage()]);
                    }
                    $data = [
                        'data' => $businessDetails ? (object) $businessDetails : null,
                        'user_info' => $users,
                        'bg_color' => $randomColor,
                        'database' => [
                            'status' => (! empty($systemResult['data'])) ? 'created' : 'not-created',
                        ],
                        'counts' => [
                            'device_users' => $deviceUserCount,
                            'users' => $userCount,
                            'roles' => $roleCount,
                            'attendance' => $attendanceCount,
                        ],
                    ];
                    return view('system.central.business-management.info', compact('data'));
                    break;
                case 'device-stats':
                    $devArr = explode('--', $token);
                    $devices = Data::fetch($devArr[0], 'devices', [], 'all');
                    $users = Data::fetch($devArr[0], 'device_users', ['device_id' => $devArr[1]], 'all');
                    // Re-index devices by device_id
                    $devicesById = [];
                    foreach ($devices['data'] as $device) {
                        $devicesById[$device['device_id']] = $device;
                    }
                    $data = [
                        'devices' => $devicesById,
                        'users' => $users['data'],
                        'current_device_id' =>$devArr[1],
                        'business_id' =>$devArr[0],
                        'serial_number' =>$devicesById[$devArr[1]]['serial_number'] ?? ''
                    ];
                    return view('system.central.business-management.device.stats', compact('data'));
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Render view if it exists
            if (View::exists($viewPath)) {
                return view($viewPath, compact('data'));
            }

            // Return 404 view if view does not exist
            return response()->view('errors.404', ['status' => false, 'title' => 'Page Not Found', 'message' => 'The requested page does not exist.'], 404);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
