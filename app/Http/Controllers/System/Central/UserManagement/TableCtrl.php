<?php
namespace App\Http\Controllers\System\Central\UserManagement;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
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
                Developer::warning('TableCtrl: No token provided', [
                    'params' => $params,
                    'request' => $request->except(['password', 'token'])
                ]);
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token and validate configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                Developer::warning('TableCtrl: Invalid token configuration', [
                    'token' => $token,
                    'reqSet' => $reqSet
                ]);
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
                Developer::warning('TableCtrl: Invalid filters format', [
                    'filters' => $reqSet['filters'],
                    'token' => $token
                ]);
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];

            Developer::alert('TableCtrl: Processing table data request', [
                'token' => $token,
                'reqSet' => $reqSet,
                'request' => $request->except(['password', 'token'])
            ]);
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_um_user_permissions':
                    case 'open_um_users':
                        $columns = [
                            'user_id'    => ['users.user_id', false],
                            'first_name' => ['users.first_name', false],
                            'last_name'  => ['users.last_name', false],
                            'profile'    => ['users.profile', false],
                            'user_info'  => ['users.user_id AS user_info', true], // Custom UI
                            'role'       => ['roles.name AS role', true],
                            'email'      => ['users.email', true],
                            'username'   => ['users.username', true],
                            'created_at' => ['users.created_at', true],
                            'permissions'=> ['users.user_id', true], // Fixed: was users.id
                        ];
                    
                        $permissionsToken = Skeleton::skeletonToken('open_um_user_permissions') . '_e_';
                    
                        // Custom UI render
                        $custom = [
                            [
                                'type'   => 'modify',
                                'column' => 'user_info',
                                'view'   => '
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg avatar-rounded rounded-circle">
                                        <img src="::~\App\Services\FileService->getFile(::profile::)~::" alt="User Avatar"
                                            class="img-fluid h-auto w-auto">
                                    </div>
                                    <div class="ms-2">
                                        <div class="sf-13 fw-medium">
                                            <a href="' . url('/') . '/t/user-management/user/::user_id::">::first_name:: ::last_name::</a>
                                        </div>
                                        <span class="sf-10 fw-normal">::IF(roles.name, ::roles.name::, No Role Assigned)::</span>
                                    </div>
                                </div>',
                                'renderHtml' => true
                            ],
                            [
                                'type'   => 'addon',
                                'column' => 'view',
                                'view'   => '<a href="' . url('/') . '/t/user-management/user/::user_id::" class="btn btn-sm btn-info"><i class="fa-regular fa-circle-info me-2"></i>View</a>',
                                'renderHtml' => true
                            ],
                        ];
                    
                        $joins = [
                            ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                            ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                            ['type' => 'left', 'table' => 'scope_mapping', 'on' => [['users.user_id', 'scope_mapping.user_id']]],
                            ['type' => 'left', 'table' => 'scopes', 'on' => [['scope_mapping.scope_id', 'scopes.scope_id']]],
                            ['type' => 'left', 'table' => 'user_info', 'on' => [['users.user_id', 'user_info.user_id']]],
                        ];
                    
                        // Conditions based on role
                        if (isset($reqSet['id']) && $reqSet['id'] === 'ADMIN' && $reqSet['key'] !== 'open_um_user_permissions') {
                            $conditions = [
                                ['column' => 'roles.role_id', 'operator' => '=', 'value' => 'ADMIN'],
                            ];
                        } elseif ($reqSet['key'] === 'open_um_user_permissions' && isset($reqSet['id']) && $reqSet['id'] === 'all') {
                            $conditions = [
                                ['column' => 'roles.role_id', 'operator' => 'NOT IN', 'value' => ['DEVELOPER', 'SUPREME']],
                            ];
                            $reqSet['actions'] = 'cv';
                            $custom[] = [
                                'type'   => 'addon',
                                'column' => 'permissions',
                                'view'   => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::user_id::">Assign</button>',
                                'renderHtml' => true
                            ];
                        } elseif ($reqSet['key'] === 'open_um_user_permissions' && isset($reqSet['id']) && $reqSet['id'] !== '') {
                            $conditions = [
                                ['column' => 'user_roles.role_id', 'operator' => '=', 'value' => $reqSet['id']],
                                ['column' => 'roles.role_id', 'operator' => '!=', 'value' => 'DEVELOPER'],
                            ];
                            $reqSet['actions'] = 'cv';
                            $custom[] = [
                                'type'   => 'addon',
                                'column' => 'permissions',
                                'view'   => '<button class="btn btn-sm btn-secondary skeleton-popup" type="button" data-token="' . $permissionsToken . '::user_id::">Assign</button>',
                                'renderHtml' => true
                            ];
                        } else {
                            $conditions = [
                                ['column' => 'roles.role_id', 'operator' => 'NOT IN', 'value' => ['SUPREME', 'DEVELOPER', 'ADMIN']],
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
                    $conditions = [
                        ['column' => 'roles.role_id', 'operator' => 'NOT IN', 'value' => ['SUPREME', 'DEVELOPER']],
                    ];
                   
                    break;
                    case 'business_designation_data':
                        $columns = [
                            'id' => ['designations.id',true],
                            'designation_id' => ['designations.designation_id', true],
                            'name' => ['designations.name', true],
                            'is_active' => ['designations.is_active', true],
                            'created_at' => ['designations.created_at', true],
                        ];
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
