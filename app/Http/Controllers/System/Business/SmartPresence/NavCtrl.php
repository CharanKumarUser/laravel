<?php
namespace App\Http\Controllers\System\Business\SmartPresence;

use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SmartPresence\GenerateQrJob;
use Illuminate\Support\Facades\Queue;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the SmartPresence module.
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
            $baseView = 'system.business.smart-presence';
            $module = $params['module'] ?? 'SmartPresence';
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

        public function start(Request $request)
    {
        $businessId = Skeleton::authUser()->business_id; // Can be dynamic if needed
        $companyIds = json_decode($request->input('company_ids', '[]'), true);

        if (!is_array($companyIds) || empty($companyIds)) {
            return response()->json(['status' => 'error', 'message' => 'No company IDs provided'], 400);
        }
 
        $response = [];
        foreach ($companyIds as $companyId) {
            // Clear any existing flags and jobs
            Cache::forget("qr_active_{$businessId}_{$companyId}");
            Cache::forget("qr_job_running_{$businessId}_{$companyId}");
            Cache::forget("last_scan_{$businessId}_{$companyId}");

            // Flush any pending unique jobs for this key
            Queue::clear("default", "generate_qr_job_{$businessId}_{$companyId}"); // Adjust queue name if not 'default'

            // Set new flags
            Cache::put("qr_active_{$businessId}_{$companyId}", true, now()->addMinutes(10));
            Cache::put("last_scan_{$businessId}_{$companyId}", now(), now()->addMinutes(10));

            // Dispatch (unique job will handle duplicates)
            if (!Cache::get("qr_job_running_{$businessId}_{$companyId}")) {
                Cache::put("qr_job_running_{$businessId}_{$companyId}", true, now()->addMinutes(10));
                GenerateQrJob::dispatch($businessId, $companyId)->onQueue('default');
                $response[$companyId] = 'started';
            } else {
                $response[$companyId] = 'already_running';
            }
        }

        return response()->json(['status' => 'started', 'details' => $response]);
    }

    public function stop(Request $request)
    {
        $businessId = Skeleton::authUser()->business_id;
        $companyIds = json_decode($request->input('company_ids', '[]'), true);

        if (!is_array($companyIds) || empty($companyIds)) {
            return response()->json(['status' => 'error', 'message' => 'No company IDs provided'], 400);
        }

        $response = [];
        foreach ($companyIds as $companyId) {
            // Forget cache flags
            Cache::forget("qr_active_{$businessId}_{$companyId}");
            Cache::forget("qr_job_running_{$businessId}_{$companyId}");
            Cache::forget("last_scan_{$businessId}_{$companyId}");

            // Clear any pending jobs in queue using unique ID
            Queue::clear("default", "generate_qr_job_{$businessId}_{$companyId}"); // Adjust queue name

            $response[$companyId] = 'stopped';
        }

        return response()->json(['status' => 'stopped', 'details' => $response]);
    }
}