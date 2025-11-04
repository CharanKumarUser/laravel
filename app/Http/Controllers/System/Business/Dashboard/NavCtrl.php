<?php
namespace App\Http\Controllers\System\Business\Dashboard;
use App\Http\Controllers\Controller;
use App\Facades\{Notification, Skeleton};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the Dashboard module.
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
            $baseView = 'system.business.' . strtolower('dashboard');
            $module = $params['module'] ?? 'Dashboard';
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
            $viewName = str_replace("\{$baseView}.", '', $viewPath);
            $viewPath = strtolower(str_replace(' ', '-', $viewPath));
            // Initialize base data
            $data = [
                'status' => true,
                'module' => $module,
                'section' => $section,
                'item' => $item,
                'token' => $token,
                'title' => 'Page Loaded',
                'message' => 'Dashboard module page loaded successfully.'
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/

            $success = Notification::user(
						businessId: 'BIZ000001',
					    ids: 'U6QLCJZHGAWAYDR,U8KCOMU8HGIQBIH',
					    title: 'Welcome to the Platform!',
					    message: 'Thank you for joining our platform, Patel! Explore the new features.',
					    category: 'welcome',
					    type: 'success',
					    priority: 'medium',
					    medium: 'app,email',
					    html: '<span>Hi, <b>::base_users_first_name::</b>, welcome from ::target_companies_name::!</span>',
					    image: 'FILE123422',
					    target: 'Company_COMFNJW2'
					);



					// $success = Notification::company(
					//     ids: 'COMP-001,COMP001',
					//     title: 'Annual Company Meeting',
					//     message: 'Join us for the annual meeting on September 1, 2025.',
					//     category: 'event',
					//     type: 'announcement',
					//     priority: 'high',
					//     medium: 'app,email',
					//     html: '<p>Hello ::base_users_first_name::, join ::target_companies_legal_name:: in ::target_companies_city:: for the meeting.</p>',
					//     image: 'FILE_MEETING789',
					//     target: 'Company_COMP-001'
					// );


					// $success = Notification::scope(
					//     ids: 'SCP9NzFim',
					//     title: 'Scope Policy Update',
					//     message: 'New policies have been added to the Digital Kuppam scope.',
					//     category: 'policy',
					//     type: 'update',
					//     priority: 'low',
					//     medium: 'email',
					//     html: '<div>Hello ::base_users_first_name::, contact ::target_users_email:: for details.</div>',
					//     target: 'User_UQVYBOMWAPYMKVV'
					// );

					// $success = Notification::role(
					//     ids: 'ROLE_MANAGER',
					//     title: 'New Project in Digitalhub Academy',
					//     message: 'You have been assigned to a new project in Digitalhub Academy.',
					//     category: 'project',
					//     type: 'info',
					//     priority: 'medium',
					//     medium: 'app,email',
					//     html: '<p>Dear ::base_users_first_name::::base_users_last_name::, you are assigned to ::target_scopes_description::.</p>',
					//     image: 'FILE_PROJECT456',
					//     target: 'Scope_SCPUrA1XB'
					// );

					// $success = Notification::user(
					// 	ids: 'USRDEMOPATEL,UTQPJDFE8CUFTMQ',
					// 	title: 'System Maintenance Scheduled',
					// 	message: 'The platform will undergo maintenance on August 28, 2025.',
					// 	category: 'system',
					// 	type: 'info',
					// 	priority: 'high',
					// 	medium: 'app',
					// 	html: '<p class="sf-9">Dear ::base_users_first_name:: ::base_users_last_name::, you are assigned to ::target_users_first_name::.</p>',
					// 	target: 'User_U6QLCJZHGAWAYDR'
					// );
					// $success = Notification::user(
					// 	ids: 'USRDEMOPATEL,UTQPJDFE8CUFTMQ',
					// 	title: 'System Maintenance Scheduled',
					// 	message: 'Join us for the annual meeting on September 1, 2025.',
					// 	category: 'system',
					// 	type: 'info',
					// 	priority: 'low',
					// 	medium: 'app',
					// 	html: '<p class="sf-9">Dear ::base_users_first_name:: ::base_users_last_name::, you are assigned to ::target_users_first_name::.</p>',
					// 	target: 'User_U6QLCJZHGAWAYDR'
					// );

					// $success = Notification::user(
					// 	ids: 'USRDEMOPATEL,UTQPJDFE8CUFTMQ',
					// 	title: 'System Maintenance Scheduled',
					// 	message: 'The platform will undergo maintenance on August 28, 2025.',
					// 	category: 'system',
					// 	type: 'info',
					// 	priority: 'critical',
					// 	medium: 'app',
					// 	html: '<p class="sf-9">Dear ::base_users_first_name:: ::base_users_last_name::, you are assigned to ::target_users_first_name::.</p>',
					// 	target: 'User_U6QLCJZHGAWAYDR'
					// );


            // Handle different view names
            switch ($viewName) {
                case 'index':
                    $data['dashboard_list'] = [];
                    break;
                default:
                    break;
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add authenticated user data
            $data['user'] = Skeleton::getAuthenticatedUser();
            // Render view if it exists
            if (View::exists($viewPath)) {
                return view($viewPath, $data);
            }
            // Return 404 view if view does not exist
            return response()->view('errors.404', ['status' => false, 'title' => 'Page Not Found', 'message' => 'The requested page does not exist.'], 404);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while loading the page.', 500);
        }
    }
}