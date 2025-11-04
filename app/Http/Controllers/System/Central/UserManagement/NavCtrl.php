<?php
namespace App\Http\Controllers\System\Central\UserManagement;
use App\Http\Controllers\Controller;
use App\Facades\{CentralDB, Skeleton, Data, Developer, Scope};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the UserManagement module.
 */
class NavCtrl extends Controller
{
    /**
     * Renders dashboard-related views based on route parameters.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters (module, section, item, token)
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request, array $params)
    {
        try {
            // Extract route parameters
            $baseView = 'system.central.' . strtolower('user-management');
            $module = $params['module'] ?? 'UserManagement';
            $section = $params['section'] ?? null;
            $item = $params['item'] ?? null;
            $token = $params['token'] ?? null;
            // Build view path
            $viewPath = $baseView;
            if ($section) {
                $viewPath .= "." . $section;
                if ($item) {
                    $viewPath .= "." . $item;
                }
            } else {
                $viewPath .= '.index';
            }
            // Extract view name and normalize path
            $viewName = strtolower(str_replace(' ', '-', str_replace("{$baseView}.", '', $viewPath)));
            $viewPath = strtolower(str_replace(' ', '-', $viewPath));
            // Initialize base data
            $data = [
                'status' => true,
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different view names
            switch ($viewName) {
                case 'permissions':
                    $system = Skeleton::getUserSystem();
                    $roles = Data::fetch($system, 'roles', ['where' => [
                        'role_id' => ['operator' => 'NOT IN', 'value' => ['SUPREME', 'DEVELOPER']]
                    ]]);
                    if (!$roles['status']) {
                        return ResponseHelper::moduleError('Auth Logs Fetch Failed', $roles['message'], 400);
                    }
                    // Explicitly set role_id 'all' for "All Users"
                    $roleList = ['all' => 'All Users'];
                    foreach ($roles['data'] as $role) {
                        $roleList[$role['role_id']] = $role['name'];
                    }
                    $data = [
                        'roles' => $roleList,
                    ];
                    break;
                    case 'scope':
                        $business_id = Skeleton::authUser()->business_id;
                        $business = CentralDB::table('business_systems')
                            ->where('business_id', $business_id)
                            ->value('name') ?: 'business';
                            $scopes = Scope::getScopePaths('all', null, true);
                            $data['scopes'] = $scopes;
                            $data['business'] = $business;
                            break;
                default:
                    break;
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
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while loading the page.', 500);
        }
    }
}
