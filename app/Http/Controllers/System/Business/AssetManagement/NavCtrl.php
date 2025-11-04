<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Http\Controllers\Controller;
use App\Facades\{Skeleton, BusinessDB};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the AssetManagement module.
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
            $baseView = 'system.business.asset-management';
            $module = $params['module'] ?? 'AssetManagement';
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
                case 'setup':
                $userId = Skeleton::authUser()->user_id;
                $userRole = Skeleton::authUser('roles');
                $role_id = array_key_first($userRole);

                if ($role_id == 'ADMIN') {
                    $companies = BusinessDB::table('companies')
                        ->whereNull('deleted_at')
                        ->get();
                } else {
                    $companies = BusinessDB::table('companies')
                        ->where('company_id', Skeleton::authUser()->company_id)
                        ->whereNull('deleted_at')
                        ->get();
                }

                $companyData = [];

                foreach ($companies as $company) {
                    $companyId = $company->company_id;

                    // --- Asset Counts ---
                    $assetCounts = BusinessDB::table('assets')
                        ->selectRaw("
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                            SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as under_maintenance,
                            SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired
                        ")
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                        ->first();

                    // --- Asset Categories with Asset Counts ---
                    $assetCategories = BusinessDB::table('asset_categories as ac')
                        ->select(
                            'ac.name',
                            BusinessDB::raw('COUNT(a.id) as count')
                        )
                        ->leftJoin('assets as a', function($join) use ($companyId) {
                            $join->on('a.category_id', '=', 'ac.id')
                                ->where('a.company_id', '=', $companyId)
                                ->whereNull('a.deleted_at');
                        })
                        ->whereNull('ac.deleted_at')
                        ->groupBy('ac.id', 'ac.name')
                        ->get();

                    // --- Maintenance Counts ---
                    $maintenanceCounts = BusinessDB::table('asset_maintenance')
                        ->selectRaw("
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue
                        ")
                        ->whereIn('asset_id', function($query) use ($companyId) {
                            $query->select('asset_id')
                                ->from('assets')
                                ->where('company_id', $companyId)
                                ->whereNull('deleted_at');
                        })
                        ->whereNull('deleted_at')
                        ->first();

                    // --- Build the Company Data ---
                    $companyData[] = [
                        'company_id' => $companyId,
                        'company_name' => $company->name ?? $company->company_name, // adjust per your DB column
                        'counts' => [
                            'assets' => $assetCounts,
                            'maintenance' => $maintenanceCounts
                        ],
                        'categories' => $assetCategories
                    ];
                }

                $data = ['companies' => $companyData];
                break;


                case 'assign-asset':
                    $userId = Skeleton::authUser()->user_id;
                    $userRole=Skeleton::authUser('roles');
                    $role_id= array_key_first($userRole);
                    if($role_id=='ADMIN'){
                        $companies = BusinessDB::table('companies')
                        ->whereNull('deleted_at')
                        ->get();
                    }else{
                        $companies = BusinessDB::table('companies')
                        ->where('company_id', Skeleton::authUser()->company_id)
                        ->whereNull('deleted_at')
                        ->get(); 
                    }
                    $data=['companies' => $companies];
                break;
                case 'reports':
                    $skeleton = app(\App\Services\SkeletonService::class);
                    $role = $skeleton->authUser('role');
                    $roleName = strtoupper($role['name'] ?? '');
                    $roleId = strtoupper($role['role_id'] ?? '');
                    $isAdmin = in_array($roleName, ['ADMIN', 'SUPREME'], true) || in_array($roleId, ['ADMIN', 'SUPREME'], true);
                    $authUser = $skeleton->authUser();
                    $authUserId = is_object($authUser) ? ($authUser->user_id ?? null) : null;

                    // Get companies based on user role
                    if ($isAdmin) {
                        $companies = BusinessDB::table('companies')
                            ->whereNull('deleted_at')
                            ->where('is_active', 1)
                            ->get();
                    } else {
                        $companies = BusinessDB::table('companies')
                            ->where('company_id', $authUser->company_id ?? null)
                            ->whereNull('deleted_at')
                            ->where('is_active', 1)
                            ->get();
                    }

                    // Initialize company data array
                    $companyData = [];
                    
                    foreach ($companies as $company) {
                        // Base query for this company
                        $baseQuery = BusinessDB::table('assets as a')
                            ->join('users as u', 'u.user_id', '=', 'a.created_by')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('a.deleted_at');

                        // If not admin, filter by user
                        if (!$isAdmin && $authUserId) {
                            $baseQuery->where('a.created_by', $authUserId);
                        }

                        // Get totals for this company
                        $assetCount = (clone $baseQuery)->count();
                        $availableCount = (clone $baseQuery)->where('a.status', 'available')->count();
                        $assignedCount = (clone $baseQuery)->where('a.status', 'assigned')->count();
                        $maintenanceCount = (clone $baseQuery)->where('a.status', 'under_maintenance')->count();
                        $retiredCount = (clone $baseQuery)->where('a.status', 'retired')->count();

                        // Maintenance cost for this company
                        $maintenanceCostQuery = BusinessDB::table('asset_maintenance as am')
                            ->join('assets as a', 'a.asset_id', '=', 'am.asset_id')
                            ->join('users as u', 'u.user_id', '=', 'a.created_by')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('am.deleted_at')
                            ->whereNull('a.deleted_at');
                        
                        if (!$isAdmin && $authUserId) {
                            $maintenanceCostQuery->where('a.created_by', $authUserId);
                        }
                        
                        $maintenanceCostTotal = (float) ($maintenanceCostQuery->sum('am.cost') ?? 0);

                        // Category distribution for this company
                    $categoryMap = BusinessDB::table('asset_categories')
                        ->whereNull('deleted_at')
                        ->pluck('name', 'category_id')
                        ->toArray();
                        
                        $counts = [];
                    foreach ($categoryMap as $categoryId => $categoryName) {
                            $query = BusinessDB::table('assets as a')
                                ->join('users as u', 'u.user_id', '=', 'a.created_by')
                                ->where('u.company_id', $company->company_id)
                                ->whereNull('a.deleted_at')
                                ->where('a.category_id', $categoryId);
                            
                            if (!$isAdmin && $authUserId) {
                                $query->where('a.created_by', $authUserId);
                            }
                            
                            $counts[$categoryName] = $query->count();
                        }

                        // Reduce legend clutter: keep top 5 categories and bucket the rest as "Others"
                        if (!empty($counts) && count($counts) > 6) {
                            arsort($counts);
                            $top = array_slice($counts, 0, 5, true);
                            $othersSum = array_sum(array_slice($counts, 5));
                            $counts = $top + ($othersSum > 0 ? ['Others' => $othersSum] : []);
                        }

                        // Maintenance monthly cost trend for this company (last 6 months)
                    $labels = [];
                    $costs = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $start = now()->startOfMonth()->subMonths($i);
                        $end = (clone $start)->endOfMonth();
                        $labels[] = $start->format('M');
                            
                            $monthlyQuery = BusinessDB::table('asset_maintenance as am')
                                ->join('assets as a', 'a.asset_id', '=', 'am.asset_id')
                                ->join('users as u', 'u.user_id', '=', 'a.created_by')
                                ->where('u.company_id', $company->company_id)
                                ->whereNull('am.deleted_at')
                                ->whereNull('a.deleted_at')
                                ->whereBetween('am.maintenance_date', [$start->toDateString(), $end->toDateString()]);
                            
                            if (!$isAdmin && $authUserId) {
                                $monthlyQuery->where('a.created_by', $authUserId);
                            }
                            
                            $costs[] = (float) ($monthlyQuery->sum('am.cost') ?? 0);
                        }

                        // Get detailed asset data for this company
                        $assetsQuery = BusinessDB::table('assets as a')
                            ->leftJoin('asset_categories as ac', 'ac.category_id', '=', 'a.category_id')
                            ->leftJoin('asset_assignments as aa', function($join) {
                                $join->on('aa.asset_id', '=', 'a.asset_id')
                                     ->where('aa.status', 'assigned')
                                     ->whereNull('aa.deleted_at');
                            })
                            ->leftJoin('users as assigned_user', 'assigned_user.user_id', '=', 'aa.user_id')
                            ->leftJoin('asset_maintenance as am', function($join) {
                                $join->on('am.asset_id', '=', 'a.asset_id')
                                     ->whereRaw('am.maintenance_date = (SELECT MAX(am2.maintenance_date) FROM asset_maintenance am2 WHERE am2.asset_id = a.asset_id AND am2.deleted_at IS NULL)');
                            })
                            ->leftJoin('users as u', 'u.user_id', '=', 'a.created_by')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('a.deleted_at')
                            ->whereNull('ac.deleted_at');
                        
                        if (!$isAdmin && $authUserId) {
                            $assetsQuery->where('a.created_by', $authUserId);
                        }
                        
                        $assets = $assetsQuery->select([
                            'a.asset_id',
                            'a.name',
                            'a.sno as serial_number',
                            'a.status',
                            'a.purchase_date',
                            'a.image_url',
                            'ac.name as category_name',
                            'assigned_user.first_name as assigned_to_first_name',
                            'assigned_user.last_name as assigned_to_last_name',
                            'assigned_user.profile as assigned_to_avatar',
                            'am.maintenance_date as last_maintenance_date',
                            'am.maintenance_type as last_maintenance_type',
                            'am.cost as last_maintenance_cost'
                        ])
                        ->orderBy('a.created_at', 'desc')
                        ->limit(50) // Limit to 50 most recent assets
                        ->get();

                        // Calculate total maintenance cost per asset
                        $assetsWithCosts = $assets->map(function($asset) use ($company, $isAdmin, $authUserId) {
                            $totalCostQuery = BusinessDB::table('asset_maintenance as am')
                                ->join('assets as a', 'a.asset_id', '=', 'am.asset_id')
                                ->join('users as u', 'u.user_id', '=', 'a.created_by')
                                ->where('u.company_id', $company->company_id)
                                ->where('am.asset_id', $asset->asset_id)
                                ->whereNull('am.deleted_at')
                                ->whereNull('a.deleted_at');
                            
                            if (!$isAdmin && $authUserId) {
                                $totalCostQuery->where('a.created_by', $authUserId);
                            }
                            
                            $totalCost = (float) $totalCostQuery->sum('am.cost');
                            
                            return [
                                'asset_id' => $asset->asset_id,
                                'name' => $asset->name,
                                'serial_number' => $asset->serial_number,
                                'status' => $asset->status,
                                'purchase_date' => $asset->purchase_date,
                                'image_url' => $asset->image_url,
                                'category_name' => $asset->category_name,
                                'assigned_to_name' => $asset->assigned_to_first_name && $asset->assigned_to_last_name 
                                    ? $asset->assigned_to_first_name . ' ' . $asset->assigned_to_last_name 
                                    : null,
                                'assigned_to_avatar' => $asset->assigned_to_avatar,
                                'last_maintenance_date' => $asset->last_maintenance_date,
                                'last_maintenance_type' => $asset->last_maintenance_type,
                                'total_maintenance_cost' => $totalCost,
                            ];
                        });

                        // Recent asset assignments for this company
                        $assignmentsQuery = BusinessDB::table('asset_assignments as aa')
                            ->leftJoin('assets as a', 'a.asset_id', '=', 'aa.asset_id')
                            ->join('users as au', 'au.user_id', '=', 'aa.user_id')
                            ->where('au.company_id', $company->company_id)
                            ->whereNull('aa.deleted_at');

                        // Show recent assignments
                        // - Admin: across the company
                        // - Non-admin: only records assigned to the current user
                        if (!$isAdmin && $authUserId) {
                            $assignmentsQuery->where('aa.user_id', $authUserId);
                        }

                        $recentAssignments = $assignmentsQuery
                            ->select([
                                'aa.assignment_id',
                                'aa.asset_id',
                                'aa.user_id',
                                'aa.assigned_date',
                                'aa.return_date',
                                'aa.status',
                                'aa.notes',
                                'aa.created_by',
                                'aa.updated_by',
                                'aa.created_at',
                                'aa.updated_at',
                            ])
                            ->orderByRaw('COALESCE(aa.assigned_date, aa.created_at) DESC')
                            ->limit(5)
                            ->get()
                            ->map(function($row) {
                                return [
                                    'assignment_id' => $row->assignment_id,
                                    'asset_id' => $row->asset_id,
                                    'user_id' => $row->user_id,
                                    'assigned_date' => $row->assigned_date,
                                    'return_date' => $row->return_date,
                                    'status' => $row->status,
                                    'notes' => $row->notes,
                                    'created_by' => $row->created_by,
                                    'updated_by' => $row->updated_by,
                                    'created_at' => $row->created_at,
                                    'updated_at' => $row->updated_at,
                                ];
                            })
                            ->toArray();

                        // Recent maintenance records for this company
                        $maintenanceListQuery = BusinessDB::table('asset_maintenance as am')
                            ->join('assets as a', 'a.asset_id', '=', 'am.asset_id')
                            ->leftJoin('asset_assignments as aa', function($join) {
                                $join->on('aa.asset_id', '=', 'a.asset_id')
                                     ->whereNull('aa.deleted_at');
                            })
                            ->where('a.company_id', $company->company_id)
                            ->whereNull('am.deleted_at')
                            ->whereNull('a.deleted_at');

                        // Show recent maintenance
                        // - Admin: across the company
                        // - Non-admin: only for assets assigned to the current user
                        if (!$isAdmin && $authUserId) {
                            $maintenanceListQuery->where('aa.user_id', $authUserId);
                        }

                        $recentMaintenance = $maintenanceListQuery
                            ->select([
                                'am.maintenance_id',
                                'am.asset_id',
                                'am.maintenance_type',
                                'am.description',
                                'am.maintenance_date',
                                'am.cost',
                                'am.vendor_name',
                                'am.vendor_contact',
                                'am.next_due_date',
                                'am.status',
                                'am.created_by',
                                'am.updated_by',
                                'am.created_at',
                                'am.updated_at',
                            ])
                            ->orderByRaw('COALESCE(am.maintenance_date, am.created_at) DESC')
                            ->limit(5)
                            ->get()
                            ->map(function($row) {
                                return [
                                    'maintenance_id' => $row->maintenance_id,
                                    'asset_id' => $row->asset_id,
                                    'maintenance_type' => $row->maintenance_type,
                                    'description' => $row->description,
                                    'maintenance_date' => $row->maintenance_date,
                                    'cost' => (float) ($row->cost ?? 0),
                                    'vendor_name' => $row->vendor_name,
                                    'vendor_contact' => $row->vendor_contact,
                                    'next_due_date' => $row->next_due_date,
                                    'status' => $row->status,
                                    'created_by' => $row->created_by,
                                    'updated_by' => $row->updated_by,
                                    'created_at' => $row->created_at,
                                    'updated_at' => $row->updated_at,
                                ];
                            })
                            ->toArray();

                        // Store company data
                        $companyData[] = [
                            'company_id' => $company->company_id,
                            'company_name' => $company->name,
                            'asset_count' => $assetCount,
                            'available_count' => $availableCount,
                            'assigned_count' => $assignedCount,
                            'maintenance_count' => $maintenanceCount,
                            'retired_count' => $retiredCount,
                            'maintenance_cost_total' => $maintenanceCostTotal,
                            'counts' => $counts,
                            'status_counts' => [
                                'available' => $availableCount,
                                'assigned' => $assignedCount,
                                'under_maintenance' => $maintenanceCount,
                                'retired' => $retiredCount,
                            ],
                            'monthly' => [
                                'labels' => $labels,
                                'costs' => $costs,
                            ],
                            'assets' => $assetsWithCosts->toArray(),
                            'recent_assignments' => $recentAssignments,
                            'recent_maintenance' => $recentMaintenance,
                        ];
                    }

                    // Set the first company as default data for backward compatibility
                    $data = $companyData[0] ?? [
                        'asset_count' => 0,
                        'available_count' => 0,
                        'assigned_count' => 0,
                        'maintenance_count' => 0,
                        'retired_count' => 0,
                        'maintenance_cost_total' => 0,
                        'counts' => [],
                        'status_counts' => ['available' => 0, 'assigned' => 0, 'under_maintenance' => 0, 'retired' => 0],
                        'monthly' => ['labels' => [], 'costs' => []],
                        'assets' => [],
                        'recent_assignments' => [],
                        'recent_maintenance' => [],
                    ];

                    // Add companies data for tabs
                    $data['companies'] = $companyData;
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