<?php
namespace App\Http\Controllers\System\Business\UserManagement;
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
            $baseView = 'system.business.user-management';
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
                        $conditions = ['user_id' => $token];

                        // Add extra condition for 'users' table only
                        if ($table === 'users') {
                            $conditions['account_status'] = 'active';
                        }

                        $fetchedData[$table] = Data::fetch($system, $table, $conditions);
                    }
                    $user = $fetchedData['users']['data'][0] ?? null;
                    $userInfo = $fetchedData['user_info']['data'][0] ?? null;
                    $scopeUser = $fetchedData['scope_mapping']['data'][0] ?? null;
                    $scopeInfo = $fetchedData['scope_data']['data'][0] ?? null;
                    $data = [
                        'status' => true,
                        'user_id' => $token,
                        'user' => $user,
                        'user_info' => $userInfo,
                        'scope_user' => $scopeUser,
                        'scope_info' => $scopeInfo,
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
