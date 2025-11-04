<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Data;
use App\Facades\Developer;
use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Helpers\TableHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX table data requests in the BusinessManagement module.
 */
class TableCtrl extends Controller
{
    /**
     * Handles AJAX requests for table data processing.
     *
     * @param  Request  $request  HTTP request object containing filters and view settings
     * @param  array  $params  Route parameters (module, section, item, token)
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
            if (! isset($reqSet['key']) || ! isset($reqSet['table'])) {
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
            if (! is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $title = 'Data Retrieved';
            $message = 'BusinessManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle business devices table data
                case 'central_business_devices':
                    // Define columns for device data
                    $columns = [
                        'id' => ['business_devices.id', true],
                        'device_id' => ['business_devices.device_id', true],
                        'business_id' => ['business_devices.business_id', true],
                        'serial_number' => ['business_devices.serial_number', true],
                        'name' => ['business_devices.name', true],
                        'ip' => ['business_devices.ip', true],
                        'port' => ['business_devices.port', true],
                        'stats' => ['business_devices.port AS stats', true],
                        'mac_address' => ['business_devices.mac_address', true],
                        'last_sync' => ['business_devices.last_sync', true],
                        'is_approved' => ['business_devices.is_approved', true],
                        'is_active' => ['business_devices.is_active', true],
                        'created_at' => ['business_devices.created_at', true],
                    ];

                    $url = url('/').'/t/business-management/device-stats/';
                    $custom = [
                        ['type' => 'modify', 'column' => 'stats', 'view' => '<a href="'.$url.'::business_id::--::device_id::" class="btn btn-sm btn-primary px-3">Stats</a>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_approved', 'view' => '::IF(is_approved = 1, <span class="badge bg-success">Approved</span>)::ELSE(<span class="badge bg-warning">Pending</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>)::ELSE(<span class="badge bg-secondary">Inactive</span>)::', 'renderHtml' => true],
                    ];
                    break;

                case 'central_business_plans':
                    Developer::info('entered into table controller Business Management central_business_plans case', ['' => $reqSet]);
                    $columns = [
                        'id' => ['business_plans.id', true],
                        'plan_id' => ['business_plans.plan_id', true],
                        'name' => ['business_plans.name', true],
                        'icon' => ['business_plans.icon', true],
                        'type' => ['business_plans.type', true],
                        'amount' => ['business_plans.amount', true],
                        'discount' => ['business_plans.discount', true],
                        'total_amount' => ['business_plans.total_amount', true],
                        'tax' => ['business_plans.tax', true],
                        'display_order' => ['business_plans.display_order', true],
                        'landing_visibility' => ['business_plans.landing_visibility', true],
                        'is_approved' => ['business_plans.is_approved', true],
                    ];
                    $custom = [
                    ];
                    $title = 'Entities Retrieved';
                    $message = 'BusinessManagement plans data retrieved successfully.';
                    break;
                case 'central_module_pricings':
                    $columns = [
                        'id' => ['business_module_pricing.id', true],
                        'module_price_id' => ['business_module_pricing.module_price_id', true],
                        'module_id' => ['business_module_pricing.module_id', true],
                        'module_name' => ['business_module_pricing.module_name', true],
                        'price' => ['business_module_pricing.price', true],
                        'description' => ['business_module_pricing.description', true],
                        'is_approved' => ['business_module_pricing.is_approved', true],
                        'created_at' => ['business_module_pricing.created_at', true],
                        'updated_at' => ['business_module_pricing.updated_at', true],
                    ];

                    $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'is_approved',
                            'view' => '::((is_approved = 1) ~ <span class="badge bg-success">Active</span> || <span class="badge bg-danger">Inactive</span>)::',
                            'renderHtml' => true,
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'price',
                            'view' => '::("₹" . number_format(price, 2))::',
                            'renderHtml' => true,
                        ],
                    ];

                    $title = 'Module Pricing Retrieved';
                    $message = 'Central module pricing data retrieved successfully.';
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
