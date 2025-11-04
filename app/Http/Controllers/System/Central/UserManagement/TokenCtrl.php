<?php
namespace App\Http\Controllers\System\Central\UserManagement;
use App\Http\Controllers\Controller;
use App\Facades\{Data, Developer, Scope, Skeleton};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Token controller for handling specific ScopeManagement module operations.
 */
class TokenCtrl extends Controller
{
    /**
     * Handles custom operations for the ScopeManagement module.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters
     * @return JsonResponse Response with operation result
     */
    public function index(Request $request, array $params)
    {
        try {
            // Extract and validate action
            $action = $params['redirect'][2];
            $token = $params['redirect'][3];
            if (!$action) {
                return response()->view('errors.404', ['status' => false, 'title' => 'Action is Missing', 'message' => 'No action was provided.'], 404);
            }
            $baseView = 'system.central.user-management';
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
                case 'user':
                    $viewPath = $baseView . '.user-view';
                    $system = Skeleton::authUser('system');
                    // Fetch data from multiple tables
                    $tables = ['users', 'user_info', 'scope_mapping', 'scope_data'];
                    $fetchedData = [];
                    foreach ($tables as $table) {
                        $fetchedData[$table] = Data::fetch($system, $table, ['where' => ['user_id' => $token]]);
                    }
                    $userRolesData = Data::fetch($system, 'user_roles', [
                        'columns' => [
                            'user_roles.*',
                            'roles.role_id as role_id',
                            'roles.name as role_name',
                            'roles.description as role_description'
                        ],
                        'joins' => [
                            [
                                'type' => 'inner',
                                'table' => 'roles',
                                'on' => ['user_roles.role_id', 'roles.role_id']
                            ]
                        ],
                        'where' => ['user_roles.user_id' => $token]
                    ]);
                    // Extract first entries
                    $user = $fetchedData['users']['data'][0] ?? null;
                    $userInfo = $fetchedData['user_info']['data'][0] ?? null;
                    $scopeUser = $fetchedData['scope_mapping']['data'][0] ?? null;
                    $scopeInfo = $fetchedData['scope_data']['data'][0] ?? null;
                    $userRoles = $userRolesData['data'] ?? [];
                    // Build final response data
                    $data = [
                        'status' => true,
                        'user_id' => $token,
                        'user' => $user,
                        'user_info' => $userInfo,
                        'scope_user' => $scopeUser,
                        'scope_info' => $scopeInfo,
                        'user_roles' => $userRoles,
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
