<?php

namespace App\Http\Controllers\System\Business\ScopeManagement;

use App\Facades\{Data, Developer, Random, Skeleton, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX table data requests in the ScopeManagement module.
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
            $message = 'ScopeManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_scopes':
                    $scopeIds = Scope::userChildScopes(true);
                    $columns = [
                        'id' => ['scopes.id', true],
                        'scope_id' => ['scopes.scope_id', true],
                        'sno' => ['scopes.sno', true],
                        'code' => ['scopes.code', true],
                        'name' => ['scopes.name', true],
                        'background' => ['scopes.background', true],
                        'color' => ['scopes.color', true],
                        'status' => ['scopes.is_active AS status' , true],
                    ];
                    $conditions = [
                            ['column' => 'scopes.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                    ];
                    $custom = [
                        
                        ['type' => 'modify', 'column' => 'status', 'view' => '::IF(status = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">In-Active</span>)::', 'renderHtml' => true],
                        ['type' => 'modify','column' => 'color', 'view' => '<span style="display:inline-block;width:100%;height:20px;border-radius:4px;background-color: ::color::" title="::color::"></span>', 'renderHtml' => true,],
                        ['type' => 'modify', 'column' => 'background',  'view' => '<span style="display:inline-block;width:100%;height:20px;border-radius:4px;background-color: ::background::" title="::background::"></span>', 'renderHtml' => true, ],

                       
                    ];
                    $title = 'Scope Retrieved';
                    $message = 'Scope configuration data retrieved successfully.';
                    break;    
                case 'open_scope_view':
                    $scopeIds = explode('-', $reqSet['id']);
                    $columns = [
                        'id' => ['users.id', false],
                        'user_id' => ['users.user_id', true],
                        'first_name' => ['users.first_name', true],
                        'scope' => ['scopes.name AS scope', true],
                        'group' => ['scopes.group AS group', true],
                        'role' => ['roles.name AS role', true],
                        'email' => ['users.email', true],
                        'username' => ['users.username', true],
                        'permissions' => ['users.id AS permissions', true],
                    ];
                    $permissionsToken = Skeleton::skeletonToken('central_user_permissions') . '_e_';
                    $custom = [
                        ['type' => 'addon', 'column' => 'permissions', 'view' => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::user_id::">Assign</button>',  'renderHtml' => true  ],
                        ['type' => 'addon', 'column' => 'view', 'view' => '<a href="'.url('/').'/t/user-management/page/::user_id::" class="btn btn-sm btn-info"><i class="fa-regular fa-circle-info me-2"></i>View</a>', 'renderHtml' => true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                        ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                        ['type' => 'left', 'table' => 'scope_mapping', 'on' => [['users.user_id', 'scope_mapping.user_id']]],
                        ['type' => 'left', 'table' => 'scopes', 'on' => [['scope_mapping.scope_id', 'scopes.scope_id']]],
                    ];
                    $conditions = [
                        ['column' => 'scope_mapping.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                    ];
                    // Add user_id filter if $reqSet contains IDs
                    if (!empty($reqSet['ids']) && is_array($reqSet['ids'])) {
                        $conditions[] = ['column' => 'users.user_id', 'operator' => 'IN', 'value' => $reqSet['ids']];
                    }
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
