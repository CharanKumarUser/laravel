<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX table data requests in the LeaveManagement module.
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
            $message = 'LeaveManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_request_types':
                    $columns = [
                        'id' => ['request_types.id', true],
                        'request_type_id' => ['request_types.request_type_id', false],
                        'name' => ['request_types.name', true],
                        'description' => ['request_types.description', true],
                        'max_days_per_year' => ['request_types.max_days_per_year', true],
                        'carry_forward' => ['request_types.carry_forward', true],
                        'forward_leaves' => ['request_types.forward_leaves', true],
                        'is_encashable' => ['request_types.is_encashable', true],
                        'encash_days' => ['request_types.encash_days', true],
                        'consecutive_days' => ['request_types.consecutive_days', true],
                        'is_prorated' => ['request_types.is_prorated', true],
                        'is_active' => ['request_types.is_active', true],
                        'created_by' => ['request_types.created_by', true],
                        'created_at' => ['request_types.created_at', true],
                        'updated_at' => ['request_types.updated_at', true],
                    ];
                
                    $custom = [
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'max_days_per_year', 'view' => '<span class="badge bg-info">::max_days_per_year:: days</span>', 'renderHtml' => true],
                        ['type'=>'modify','column'=>'carry_forward','view'=>'::IF(carry_forward=1,<span class="badge bg-success">Allowed</span>,<span class="badge bg-danger">Not Allowed</span>)::','renderHtml'=>true],
                        ['type' => 'modify', 'column' => 'forward_leaves', 'view' => '<span class="badge bg-primary">::forward_leaves:: days</span>', 'renderHtml' => true],
                        ['type'=>'modify','column'=>'is_encashable','view'=>'::IF(is_encashable=1,<span class="badge bg-success">Encashable</span>,<span class="badge bg-danger">Non-Encashable</span>)::','renderHtml'=>true],
                        ['type' => 'modify', 'column' => 'encash_days', 'view' => '<span class="badge bg-warning">::encash_days:: days</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'consecutive_days', 'view' => '<span class="badge bg-warning">::consecutive_days:: days</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_prorated', 'view' => '::IF(is_prorated = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true],
                    ];
                    $title = 'Request Types Retrieved';
                    $message = 'Request types data retrieved successfully.';
                    break;
                case 'business_assign_request_types':
                    $columns = [
                        'id' => ['assign_request_types.id', true],
                        'assign_id' => ['assign_request_types.assign_id', true],
                        'request_type_id' => ['assign_request_types.request_type_id', true],
                        'scope_id' => ['assign_request_types.scope_id', true],
                        'user_id' => ['assign_request_types.user_id', true],
                        'is_active' => ['assign_request_types.is_active', true],
                        'created_by' => ['assign_request_types.created_by', true],
                        'created_at' => ['assign_request_types.created_at', true],
                        'updated_at' => ['assign_request_types.updated_at', true],
                    ];
                    $title = 'Assign Request Types Retrieved';
                    $message = 'Assign request types data retrieved successfully.';
                    break;
                    
                case 'business_requests':
                case 'business_request_approve':
                    $columns = [
                        'id' => ['requests.id', true],
                        'request_id' => ['requests.request_id', true],
                        'request_type' => ['requests.request_type', true],
                        'user' => ['requests.user_id AS user', true],
                        'company_id' => ['companies.company_id', false],
                        'username' => ['users.username', true],
                        'subject' => ['requests.subject', true],
                        'start_datetime' => ['requests.start_datetime', true],
                        'end_datetime' => ['requests.end_datetime', true],
                        'reason' => ['requests.reason', true],
                        'approval_status' => ['requests.approval_status', true],
                        'decision_by' => ['requests.decision_by', true],
                        'decision_at' => ['requests.decision_at', true],
                        'created_by' => ['requests.created_by', true],
                        'created_at' => ['requests.created_at', true],
                        'updated_at' => ['requests.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'user', 'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::user::, ["flex","lg"], ["role", "scope"], 0)~::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'decision_by', 'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::decision_by::, ["flex","lg"], ["role", "scope"], 0)~::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'created_by', 'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::created_by::, ["flex","lg"], ["role", "scope"], 0)~::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'request_type', 'view' => '<span class="badge ::IF(request_type = "leave", bg-success, bg-info)::">::request_type::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'approval_status', 'view' => '<span class="px-2 py-1 rounded-pill ::IF(approval_status = "approved", text-success, IF(approval_status = "rejected", text-danger, IF(approval_status = "cancelled", text-secondary, text-warning)))::">::approval_status::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_datetime', 'view' => '<code>::start_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_datetime', 'view' => '<code>::end_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'reason', 'view' => '<span title=\"::reason::\">::reason::</span>', 'renderHtml' => true],
                    ];
                    if(isset($reqSet['id']) && $reqSet['key'] == 'business_requests'){
                        $conditions = [
                            ['column' => 'requests.user_id', 'operator' => '=', 'value' => $reqSet['id']],
                        ];
                    }
                    if(isset($reqSet['id']) && $reqSet['key'] == 'business_request_approve'){
                        $conditions = [
                            ['column' => 'companies.company_id', 'operator' => '=', 'value' => $reqSet['id']],
                        ];
                    }
                    $joins = [
                        ['type' => 'left', 'table' => 'users', 'on' => [['requests.user_id', 'users.user_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['users.company_id', 'companies.company_id']]]
                    ];

                    $title = 'Requests Retrieved';
                    $message = 'Requests data retrieved successfully.';
                    break;

                case 'business_request_balances':
                    $columns = [
                        'id' => ['request_balances.id', true],
                        'request_balance_id' => ['request_balances.request_balance_id', false],
                        'user_id' => ['request_balances.user_id', true],
                        'request_type' => ['request_types.name AS request_type', true],
                        'year' => ['request_balances.year', true],
                        'allocated_days' => ['request_balances.allocated_days', true],
                        'used_days' => ['request_balances.used_days', true],
                        'remaining_days' => ['request_balances.remaining_days', true],
                        'created_by' => ['request_balances.created_by', true],
                        'created_at' => ['request_balances.created_at', true],
                        'updated_at' => ['request_balances.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'request_type_id', 'view' => '<span class="badge bg-secondary">::request_type_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'year', 'view' => '<code>::year::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'allocated_days', 'view' => '<strong class="text-success">::allocated_days:: days</strong>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'used_days', 'view' => '<strong class="text-warning">::used_days:: days</strong>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'remaining_days', 'view' => '<strong class="text-info">::remaining_days:: days</strong>', 'renderHtml' => true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'request_types', 'on' => [['request_balances.request_type_id', 'request_types.request_type_id']]],
                        ['type' => 'left', 'table' => 'users', 'on' => [['request_balances.user_id', 'users.user_id']]]
                    ];
                    $title = 'Leave Balances Retrieved';
                    $message = 'Leave balances data retrieved successfully.';
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
