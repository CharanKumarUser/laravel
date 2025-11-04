<?php
namespace App\Http\Controllers\System\Business\SupportAndHelp;
use App\Http\Controllers\Controller;
use App\Facades\{Skeleton, BusinessDB, Developer};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the SupportAndHelp module.
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
            $baseView = 'system.business.support-and-help';
            $module = $params['module'] ?? 'SupportAndHelp';
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
             Developer::info($viewName);
            switch ($viewName) {
                case 'tickets.my-tickets':

                // --- Summary counts
                $counts = BusinessDB::table('supports')
                    ->select(
                        BusinessDB::raw("COUNT(*) as total_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status = 'Open' THEN 1 ELSE 0 END) as open_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status IN ('Resolved','Closed') THEN 1 ELSE 0 END) as solved_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status = 'In Progress' THEN 1 ELSE 0 END) as pending_tickets")
                    )
                    ->where('is_active', 1)
                    ->where('user_id', Skeleton::authUser()->user_id)
                    ->first();

                    $categories = BusinessDB::table('supports')
                        ->select('issue_category', BusinessDB::raw('COUNT(*) as total'))
                        ->where('is_active', 1)
                        ->groupBy('issue_category')
                        ->orderBy('issue_category', 'asc')
                        ->where('user_id', Skeleton::authUser()->user_id)
                        ->get();

                    $priorityMap = ['Low' => 'success', 'Medium' => 'info', 'High' => 'warning', 'Critical' => 'danger'];

                    $priorityCounts = BusinessDB::table('supports')
                        ->select('issue_priority', BusinessDB::raw('COUNT(*) as total'))
                        ->where('is_active', 1)
                        ->where('user_id', Skeleton::authUser()->user_id)
                        ->groupBy('issue_priority')
                        ->pluck('total', 'issue_priority')
                        ->toArray();

                    $priorities = collect($priorityMap)->map(function ($color, $priority) use ($priorityCounts) {
                        return [
                            'priority' => $priority,
                            'count'    => $priorityCounts[$priority] ?? 0,
                            'color'    => $color
                        ];
                    })->values();
                    $data = [
                        'summary'    => $counts,
                        'categories' => $categories,
                        'priorities' => $priorities,
                    ];
                    break;
            case 'tickets.all-tickets':

                // --- Summary counts
                $counts = BusinessDB::table('supports')
                    ->select(
                        BusinessDB::raw("COUNT(*) as total_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status = 'Open' THEN 1 ELSE 0 END) as open_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status IN ('Resolved','Closed') THEN 1 ELSE 0 END) as solved_tickets"),
                        BusinessDB::raw("SUM(CASE WHEN issue_status = 'In Progress' THEN 1 ELSE 0 END) as pending_tickets")
                    )
                    ->where('is_active', 1)
                    ->first();

                    $categories = BusinessDB::table('supports')
                        ->select('issue_category', BusinessDB::raw('COUNT(*) as total'))
                        ->where('is_active', 1)
                        ->groupBy('issue_category')
                        ->orderBy('issue_category', 'asc')
                        ->get();

                    $priorityMap = ['Low' => 'success', 'Medium' => 'info', 'High' => 'warning', 'Critical' => 'danger'];

                    $priorityCounts = BusinessDB::table('supports')
                        ->select('issue_priority', BusinessDB::raw('COUNT(*) as total'))
                        ->where('is_active', 1)
                        ->groupBy('issue_priority')
                        ->pluck('total', 'issue_priority')
                        ->toArray();

                    $priorities = collect($priorityMap)->map(function ($color, $priority) use ($priorityCounts) {
                        return [
                            'priority' => $priority,
                            'count'    => $priorityCounts[$priority] ?? 0,
                            'color'    => $color
                        ];
                    })->values();

                    $data = [
                        'summary'    => $counts,
                        'categories' => $categories,
                        'priorities' => $priorities,
                    ];
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