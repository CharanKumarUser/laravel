<?php
namespace App\Http\Controllers\System\Business\UserManagement;
use App\Facades\{Data, Developer, Random, Skeleton, Scope, Profile};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper, ProfileHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the UserManagement module.
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
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_um_user_permissions':
                case 'open_um_users':
                    $scopeIds = Scope::userChildScopes();
                    $columns = [
                        'user_id'    => ['users.user_id', false],
                        'first_name' => ['users.first_name', false],
                        'last_name'  => ['users.last_name', false],
                        'profile'    => ['users.profile', false],
                        'user'  => ['users.user_id AS user', true], // Custom UI
                        'role'       => ['roles.name AS role', true],
                        'email'      => ['users.email', true],
                        'phone'      => ['user_info.phone', true],
                        'username'   => ['users.username', true],
                        'created_at' => ['users.created_at', true],
                    ];
                    $permissionsToken = Skeleton::skeletonToken('open_um_user_permissions') . '_e_';

                    // Common UI
                    $custom = [
                        [
                            'type'   => 'modify',
                            'column' => 'user',
                            'view'   => '::~\App\Http\Helpers\ProfileHelper->userProfile(::user_id::, ["flex","lg"], ["company","role", "scope"], 1)~::',
                            'renderHtml' => true
                        ],
                        [
                            'type'   => 'addon',
                            'column' => 'view',
                            'view'   => '<a href="' . url('/') . '/t/user-management/user/::user_id::" class="btn btn-sm btn-info"><i class="fa-regular fa-circle-info me-2"></i>View</a>',
                            'renderHtml' => true
                        ],
                        [
                            'type'   => 'modify',
                            'column' => 'role',
                            'view'   => '<span class="badge bg-success rounded-pill sf-10">::role::</span>',
                            'renderHtml' => true
                        ],
                    ];

                    $joins = [
                        ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                        ['type' => 'left', 'table' => 'user_info', 'on' => [['users.user_id', 'user_info.user_id']]],
                        ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                        ['type' => 'left', 'table' => 'scope_mapping', 'on' => [['users.user_id', 'scope_mapping.user_id']]],
                        ['type' => 'left', 'table' => 'scopes', 'on' => [['scope_mapping.scope_id', 'scopes.scope_id']]],
                    ];

                    // Conditions
                    if (isset($reqSet['id']) && $reqSet['id'] === 'ADMIN' && $reqSet['key'] !== 'open_um_user_permissions') {
                        // Only admin users
                        $conditions = [
                            ['column' => 'roles.role_id', 'operator' => '=', 'value' => 'ADMIN'],
                            ['column' => 'users.account_status', 'operator' => '=', 'value' => 'active'],
                            ['column' => 'users.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                        ];

                    } elseif ($reqSet['key'] === 'open_um_user_permissions' && isset($reqSet['id']) && $reqSet['id'] === 'all') {
                        // All users except supreme & developer
                        $conditions = [
                            ['column' => 'roles.role_id', 'operator' => 'NOT IN', 'value' => ['DEVELOPER', 'SUPREME']],
                            ['column' => 'users.account_status', 'operator' => '=', 'value' => 'active'],
                            ['column' => 'users.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                        ];
                        $reqSet['actions'] = 'cv';
                        $custom[] = [
                            'type'   => 'addon',
                            'column' => 'permissions',
                            'view'   => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::user_id::">Assign</button>',
                            'renderHtml' => true
                        ];

                    } elseif ($reqSet['key'] === 'open_um_user_permissions' && isset($reqSet['id']) && $reqSet['id'] !== '') {
                        // Filter by specific role
                        $conditions = [
                            ['column' => 'user_roles.role_id', 'operator' => '=', 'value' => $reqSet['id']],
                            ['column' => 'roles.role_id', 'operator' => '!=', 'value' => 'DEVELOPER'],
                            ['column' => 'users.account_status', 'operator' => '=', 'value' => 'active'],
                            ['column' => 'users.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                        ];
                        $reqSet['actions'] = 'cv';
                        $custom[] = [
                            'type'   => 'addon',
                            'column' => 'permissions',
                            'view'   => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::user_id::">Assign</button>',
                            'renderHtml' => true
                        ];

                    } else {
                        // Default: Exclude higher roles
                        $conditions = [
                            ['column' => 'roles.role_id', 'operator' => 'NOT IN', 'value' => ['SUPREME', 'DEVELOPER', 'ADMIN']],
                            ['column' => 'users.account_status', 'operator' => '=', 'value' => 'active'],
                            ['column' => 'users.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                        ];
                    }

                    $title   = 'User Retrieved';
                    $message = 'Users data retrieved successfully.';
                break;

                case 'open_um_roles':
                    $columns = [
                        'id' => ['roles.id', false],
                        'sno' => ['roles.sno', true],
                        'role_id' => ['roles.role_id', true],
                        'name' => ['roles.name', true],
                        'description' => ['roles.description', true],
                        'is_system_role' => ['roles.is_system_role', true],
                        'is_active' => ['roles.is_active', true],
                        'created_at' => ['roles.created_at', true],
                    ];
                    $permissionsToken = Skeleton::skeletonToken('open_um_role_permissions') . '_e_';
                    $custom = [
                        ['type' => 'addon', 'column' => 'permissions', 'view' => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::role_id::">Assign</button>', 'renderHtml' => true],
                    ];
                    $user=Skeleton::getAuthenticatedUser();
                    $roleId = array_key_first($user['roles']);
                    $roles = ($roleId === 'ADMIN') ? Profile::getChildRoles('all', null) : Profile::getChildRoles('role', $roleId);    
                    $roleIds = array_keys($roles);
                    $conditions = [
                        ['column' => 'roles.role_id', 'operator' => 'IN', 'value' => $roleIds],
                    ];
                    $title = 'Roles Retrieved';
                    $message = 'Roles retrieved successfully.';
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
