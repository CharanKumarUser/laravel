<?php

namespace App\Http\Controllers\System\Business\DeviceManagement;

use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX table data requests in the DeviceManagement module.
 */
class TableCtrl extends Controller
{
    /**
     * Handles AJAX requests for table data processing.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed table data or error response
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token and validate configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or lacks required configuration.', 400);
            }
            // Set view to table and parse filters
            $reqSet['view'] = 'table';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? [],
                'dateRange' => $filters['dateRange'] ?? [],
                'columns' => $filters['columns'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 10],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $title = 'Data Retrieved';
            $message = 'DeviceManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_devices':
                    $columns = [
                        'id' => ['devices.id', false],
                        'device_id' => ['devices.device_id', true],
                        'company_id' => ['devices.company_id', true],
                        'serial_number' => ['devices.serial_number', true],
                        'name' => ['devices.name', true],
                        'location' => ['devices.location', true],
                        'ip' => ['devices.ip', true],
                        'port' => ['devices.port', true],
                        'mac_address' => ['devices.mac_address', true],
                        'last_sync' => ['devices.last_sync', true],
                        'is_approved' => ['devices.is_approved', true],
                        'is_active' => ['devices.is_active', true],
                        'created_at' => ['devices.created_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'device_id', 'view' => '<span class="badge bg-primary">::device_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_approved', 'view' => '::IF(is_approved = 1, <span class="badge bg-info">Approved</span>, <span class="badge bg-warning">Pending</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'ip', 'view' => '<code>::ip::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'mac_address', 'view' => '<code>::mac_address::</code>', 'renderHtml' => true]
                    ];
                    $title = 'Devices Retrieved';
                    $message = 'Business devices data retrieved successfully.';
                    break;
                case 'business_device_settings':
                    $columns = [
                        'id' => ['device_settings.id', true],
                        'device_id' => ['device_settings.device_id', true],
                        'trans_stamp' => ['device_settings.trans_stamp', true],
                        'attlog_stamp' => ['device_settings.attlog_stamp', true],
                        'op_stamp' => ['device_settings.op_stamp', true],
                        'operlog_stamp' => ['device_settings.operlog_stamp', true],
                        'photo_stamp' => ['device_settings.photo_stamp', true],
                        'attphoto_stamp' => ['device_settings.attphoto_stamp', true],
                        'error_delay' => ['device_settings.error_delay', true],
                        'delay' => ['device_settings.delay', true],
                        'trans_times' => ['device_settings.trans_times', true],
                        'trans_interval' => ['device_settings.trans_interval', true],
                        'trans_flag' => ['device_settings.trans_flag', true],
                        'realtime' => ['device_settings.realtime', true],
                        'timeout' => ['device_settings.timeout', true],
                        'timezone' => ['device_settings.timezone', true],
                        'encrypt' => ['device_settings.encrypt', true],
                        'memory_alert' => ['device_settings.memory_alert', true],
                        'memory_threshold' => ['device_settings.memory_threshold', true],
                        'memory_interval' => ['device_settings.memory_interval', true],
                        'attlog_alert' => ['device_settings.attlog_alert', true],
                        'attlog_threshold' => ['device_settings.attlog_threshold', true],
                        'attlog_interval' => ['device_settings.attlog_interval', true],
                        'auto_remove_logs' => ['device_settings.auto_remove_logs', true],
                        'auto_remove_age' => ['device_settings.auto_remove_age', true],
                        'auto_remove_threshold' => ['device_settings.auto_remove_threshold', true],
                        'created_by' => ['device_settings.created_by', true],
                        'updated_by' => ['device_settings.updated_by', true],
                        'created_at' => ['device_settings.created_at', true],
                        'updated_at' => ['device_settings.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'device_id', 'view' => '<span class="badge bg-primary">::device_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'realtime', 'view' => '::IF(realtime = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-secondary">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'encrypt', 'view' => '::IF(encrypt = 1, <span class="badge bg-info">Yes</span>, <span class="badge bg-secondary">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'memory_alert', 'view' => '::IF(memory_alert = 1, <span class="badge bg-warning">Enabled</span>, <span class="badge bg-secondary">Disabled</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'attlog_alert', 'view' => '::IF(attlog_alert = 1, <span class="badge bg-warning">Enabled</span>, <span class="badge bg-secondary">Disabled</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'auto_remove_logs', 'view' => '::IF(auto_remove_logs = 1, <span class="badge bg-info">Enabled</span>, <span class="badge bg-secondary">Disabled</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'trans_times', 'view' => '<code>::trans_times::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'trans_flag', 'view' => '<code>::trans_flag::</code>', 'renderHtml' => true],
                    ];
                    $title = 'Device Settings Retrieved';
                    $message = 'Device settings data retrieved successfully.';
                    break;

                case 'business_device_users':
                    $columns = [
                        'id' => ['device_users.id', true],
                        'device_user_id' => ['device_users.device_user_id', true],
                        'device_id' => ['device_users.device_id', true],
                        'name' => ['device_users.name', true],
                        'privilege' => ['device_users.privilege', true],
                        'password' => ['device_users.password', true],
                        'card_number' => ['device_users.card_number', true],
                        'group_id' => ['device_users.group_id', true],
                        'time_zone' => ['device_users.time_zone', true],
                        'expires' => ['device_users.expires', true],
                        'start_datetime' => ['device_users.start_datetime', true],
                        'end_datetime' => ['device_users.end_datetime', true],
                        'valid_count' => ['device_users.valid_count', true],
                        'created_at' => ['device_users.created_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'device_user_id', 'view' => '<span class="badge bg-primary">::device_user_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'privilege', 'view' => '::IF(privilege > 0, <span class="badge bg-success">Level ::privilege::</span>, <span class="badge bg-secondary">Guest</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'password', 'view' => '::IF(password IS NOT NULL AND password != "", <span class="badge bg-info">Set</span>, <span class="badge bg-secondary">None</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'card_number', 'view' => '<code>::card_number::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'expires', 'view' => '::IF(expires = "0", <span class="badge bg-success">Never</span>, <span class="badge bg-warning">::expires::</span>)::', 'renderHtml' => true],
                    ];
                    $title = 'Device Users Retrieved';
                    $message = 'Device users data retrieved successfully.';
                    break;

           

                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
           // Prepare for set
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'custom' => $custom];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = TableHelper::generateResponse($set, $businessId);
            // Generate and return response using TableHelper
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve table data.', 500);
        }
    }
}


