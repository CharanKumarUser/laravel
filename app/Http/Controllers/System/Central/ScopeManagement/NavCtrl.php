<?php
namespace App\Http\Controllers\System\Central\ScopeManagement;
use App\Http\Controllers\Controller;
use App\Facades\{CentralDB, Scope, Skeleton};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the ScopeManagement module.
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
            $baseView = 'system.central.' . strtolower('scope-management');
            $module = $params['module'] ?? 'ScopeManagement';
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
            $viewName = strtolower(str_replace("{$baseView}.", '', $viewPath));
            $viewPath = strtolower(str_replace(' ', '-', $viewPath));
            // Initialize base data
            $data = [
                'status' => true,
                'module' => $module,
                'section' => $section,
                'item' => $item,
                'token' => $token,
                'title' => 'Page Loaded',
                'message' => 'ScopeManagement module page loaded successfully.'
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different view names
            switch ($viewName) {
                 case 'scope':
                $business_id = Skeleton::authUser()->business_id;
                $business = CentralDB::table('business_systems')
                    ->where('business_id', $business_id)
                    ->value('name') ?: 'business';
                    $scopes = Scope::getScopePaths('all', null, true);
                    $data['scopes'] = $scopes;
                    $data['business'] = $business;
                    break;
                case 'settings':
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
