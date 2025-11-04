<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Http\Controllers\Controller;
use App\Facades\{Skeleton, BusinessDB, Developer, FileManager};
use App\Http\Helpers\ResponseHelper;
use App\Services\SelfContainedPdfService;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, DB, View};

/**
 * Controller for rendering navigation views for the LeaveManagement module.
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
            $baseView = 'system.business.leaves-and-requests';
            $module = $params['module'] ?? 'LeavesAndRequests';
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
                case 'my-requests':
                   $userId = Skeleton::authUser()->user_id;
                    $year   = now()->year;

                    // 1. Counts
                    $counts = BusinessDB::table('requests')
                        ->selectRaw("COUNT(*) as total")
                        ->selectRaw("SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending")
                        ->selectRaw("SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved")
                        ->selectRaw("SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected")
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')
                        ->first();

                    // 2. Categories
                    $categories = BusinessDB::table('requests as r')
                        ->join('request_types as rt', 'rt.request_type_id', '=', 'r.request_type_id')
                        ->select('rt.name as category', BusinessDB::raw('COUNT(r.request_id) as count'))
                        ->where('r.user_id', $userId)
                        ->whereNull('r.deleted_at')
                        ->groupBy('rt.name')
                        ->get();

                    // 3. Types
                    $types = BusinessDB::table('requests')
                        ->select('request_type', BusinessDB::raw('COUNT(*) as count'))
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')
                        ->groupBy('request_type')
                        ->get()
                        ->map(fn($row) => [
                            'type'  => $row->request_type,
                            'count' => $row->count,
                        ]);

                    // 4. Leave Balances (current year)
                    $leaveBalances = BusinessDB::table('request_balances as rb')
                    ->join('request_types as rt', 'rt.request_type_id', '=', 'rb.request_type_id')
                    ->select([
                        'rb.id',
                        'rb.request_balance_id',
                        'rb.user_id',
                        'rb.year',
                        'rb.allocated_days',
                        'rb.used_days',
                        'rb.remaining_days',
                        'rt.name as request_type_name',
                        'rt.description as request_type_description',
                        'rt.max_days_per_year',
                    ])
                    ->where('rb.user_id', $userId)
                    ->whereNull('rb.deleted_at')
                    ->whereNull('rt.deleted_at')
                    ->orderBy('rb.year', 'desc')    
                    ->get();

                    $requests = BusinessDB::table('requests as lr')
                        ->join('users as u', 'u.user_id', '=', 'lr.user_id')
                        ->leftJoin('request_types as lt', 'lt.request_type_id', '=', 'lr.request_type_id')
                        ->leftJoin('scopes as sc', 'sc.scope_id', '=', 'u.scope_id')
                        ->whereNull('lr.deleted_at')
                        ->where('lr.user_id', $userId)
                        ->select([
                            'lr.request_id',
                            'lr.user_id',
                            'lr.request_type',
                            'lr.start_datetime',
                            'lr.end_datetime',
                            'lr.subject',
                            'lr.reason',
                            'lr.approval_status',
                            'u.first_name',
                            'u.last_name',
                            'sc.name',
                            'sc.group',
                            'u.profile as profile_photo',
                            'lt.name as leave_type',
                        ])
                        ->get()
                         ->map(function ($request) {
                                $statusColors = [
                                    'approved' => '#28a745',
                                    'pending'  => '#ffc107',
                                    'rejected' => '#f45959',
                                ];

                                $defaultColor = $statusColors[strtolower($request->approval_status)] ?? '#3788d8';

                                $start = $request->start_datetime ? \Carbon\Carbon::parse($request->start_datetime) : null;
                                $end   = $request->end_datetime ? \Carbon\Carbon::parse($request->end_datetime) : null;
                                $allDay = false;
                                $duration = '';

                                if ($start && $end) {
                                    if (strtolower($request->request_type) === 'full-day') {
                                        $days = ($start->diffInDays($end)>1)? $start->diffInDays($end)  : 1; // inclusive
                                        $duration = $days . ' day' . ($days > 1 ? 's' : '');
                                        $startDate = $start->format('Y-m-d');
                                        $endDate   = $end->format('Y-m-d');
                                        $allDay = true;
                                    } else {
                                        $diffHours = $start->diffInHours($end);
                                        $diffMinutes = $start->diffInMinutes($end) % 60;
                                        $duration = $diffHours . 'h ' . $diffMinutes . 'm';
                                        $startDate = $start->format('Y-m-d H:i');
                                        $endDate   = $end->format('Y-m-d H:i');
                                    }
                                } else {
                                    $startDate = $endDate = null;
                                }

                                $profile = $request->profile_photo
                                    ? FileManager::getFile($request->profile_photo)
                                    : asset('default/preview-square.svg');

                                $html = '<div class="bg-white text-dark p-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="avatar avatar-sm avatar-rounded border rounded-circle">
                                                    <img src="'.$profile.'" alt="User Avatar" class="img-fluid rounded-circle">
                                                </span>
                                                <div class="ms-2">
                                                    <div class="fs-15 fw-semibold">'.$request->first_name.' '.$request->last_name.'</div>
                                                    <p class="mb-0 text-muted fs-11"></p>
                                                    <div class="d-flex align-items-center">
                                                        <span class="sf-12">'.($request->group ?? 'No group').'</span>
                                                        <span class="mx-2">|</span>
                                                        <span class="sf-11 badge bg-light rounded-pill">'.($request->name ?? 'No Designation').'</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="fw-bold text-primary mb-1 text-nowrap me-2">'.$request->request_type.'</h6>
                                                <span class="sf-12"><strong>Duration: </strong>'.$duration.'</span>
                                            </div>
                                            <span class="sf-12">'.($request->reason ?? 'No reason').'</span>
                                        </div>';

                                return [
                                    'allDay'   => $allDay,
                                    'assigness'=> [],
                                    'id'       => $request->request_id,
                                    'title'    => $request->subject ?? 'No Title',
                                    'img'      => FileManager::getFile($request->profile_photo) ??
                                                'https://i.pravatar.cc/40?img=' . (is_numeric($request->user_id)
                                                    ? ((int)$request->user_id % 70)
                                                    : (abs(crc32($request->user_id)) % 70)),
                                    'start'    => $startDate,
                                    'end'      => $endDate,
                                    'html'     => $html,
                                    'type'     => $request->approval_status,
                                    'color'    => $defaultColor,
                                ];
                            })
                        ->toArray();

                    $data = [
                        'user_id' => $userId ?? '',
                        'counts' => $counts ?? '',
                        'categories' => $categories ?? [],
                        'types'      => $types ?? [],
                        'requests'   => $requests ?? [],
                        'leaveBalances'   => $leaveBalances ?? [],
                    ];
                    Developer::info('nav data',[$data['leaveBalances']]);
                break;
                case 'approve-requests':
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
                    $currentUserId = Skeleton::authUser()->user_id;
                        Developer::alert($currentUserId);
                        $requests = BusinessDB::table('requests as lr')
                            ->join('users as u', 'u.user_id', '=', 'lr.user_id')
                            ->leftJoin('request_types as lt', 'lt.request_type_id', '=', 'lr.request_type_id')
                            ->leftJoin('scopes as sc', 'sc.scope_id', '=', 'u.scope_id')
                            ->leftJoin('companies as co', 'co.company_id', '=', 'u.company_id')
                            ->whereNotNull('lr.tag_to')
                            ->where('lr.tag_to', '!=', '')
                            ->whereRaw('FIND_IN_SET(?, lr.tag_to)', [$currentUserId]) 
                            ->whereNull('lr.deleted_at')
                            ->select([
                                'lr.request_id',
                                'lr.user_id',
                                'lr.request_type',
                                'lr.start_datetime',
                                'lr.end_datetime',
                                'lr.subject',
                                'lr.reason',
                                'lr.approval_status',
                                'u.first_name',
                                'u.last_name',
                                'co.name as company_name',
                                'sc.name',
                                'sc.group',
                                'u.profile as profile_photo',
                                'lt.name as leave_type',
                            ])
                            ->get()

                            ->map(function ($request) {
                                $statusColors = [
                                    'approved' => '#28a745',
                                    'pending'  => '#ffc107',
                                    'rejected' => '#f45959',
                                ];

                                $defaultColor = $statusColors[strtolower($request->approval_status)] ?? '#3788d8';

                                $start = $request->start_datetime ? \Carbon\Carbon::parse($request->start_datetime) : null;
                                $end   = $request->end_datetime ? \Carbon\Carbon::parse($request->end_datetime) : null;

                                $allDay = false;
                                $duration = '';

                                if ($start && $end) {
                                    if (strtolower($request->request_type) === 'full-day') {
                                        $days = ($start->diffInDays($end)>1)? $start->diffInDays($end)  : 1; // inclusive
                                        $duration = $days . ' day' . ($days > 1 ? 's' : '');
                                        $startDate = $start->format('Y-m-d');
                                        $endDate   = $end->format('Y-m-d');
                                        $allDay = true;
                                    } else {
                                        $diffHours = $start->diffInHours($end);
                                        $diffMinutes = $start->diffInMinutes($end) % 60;
                                        $duration = $diffHours . 'h ' . $diffMinutes . 'm';
                                        $startDate = $start->format('Y-m-d H:i');
                                        $endDate   = $end->format('Y-m-d H:i');
                                    }
                                } else {
                                    $startDate = $endDate = null;
                                }

                                $profile = $request->profile_photo
                                    ? FileManager::getFile($request->profile_photo)
                                    : asset('default/preview-square.svg');

                                $html = '<div class="bg-white text-dark p-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="avatar avatar-sm avatar-rounded border rounded-circle">
                                                    <img src="'.$profile.'" alt="User Avatar" class="img-fluid rounded-circle">
                                                </span>
                                                <div class="ms-2">
                                                    <div class="d-flex gap-2 fs-12 fw-semibold"><span>'.$request->first_name.' '.$request->last_name.'</span> <span class="badge bg-primary rounded-pill">'.($request->company_name ?? "No Company").'</span></div>
                                                    <p class="mb-0 text-muted fs-11"></p>
                                                    <div class="d-flex align-items-center">
                                                        <span class="sf-12">'.($request->group ?? 'No group').'</span>
                                                        <span class="mx-2">|</span>
                                                        <span class="sf-11 badge bg-light rounded-pill">'.($request->name ?? 'No Designation').'</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="fw-bold text-primary mb-1 text-nowrap me-2">'.$request->request_type.'</h6>
                                                <span class="sf-12"><strong>Duration: </strong>'.$duration.'</span>
                                            </div>
                                            <span class="sf-12">'.($request->reason ?? 'No reason').'</span>
                                        </div>';

                                return [
                                    'allDay'   => $allDay,
                                    'assigness'=> [],
                                    'id'       => $request->request_id,
                                    'title'    => $request->subject ?? 'No Title',
                                    'img'      => FileManager::getFile($request->profile_photo) ??
                                                'https://i.pravatar.cc/40?img=' . (is_numeric($request->user_id)
                                                    ? ((int)$request->user_id % 70)
                                                    : (abs(crc32($request->user_id)) % 70)),
                                    'start'    => $startDate,
                                    'end'      => $endDate,
                                    'html'     => $html,
                                    'type'     => $request->approval_status,
                                    'color'    => $defaultColor,
                                ];
                            })
                        ->toArray();
                    
                    $result = [];
                    foreach ($companies as $company) {
                        // Counts by status
                        $counts = BusinessDB::table('requests as r')
                            ->join('users as u', 'u.user_id', '=', 'r.user_id')
                            ->where('u.company_id', $company->company_id)
                            ->select(
                                BusinessDB::raw("COUNT(*) as total"),
                                BusinessDB::raw("SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending"),
                                BusinessDB::raw("SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved"),
                                BusinessDB::raw("SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected")
                            )
                            ->first();

                        // Types
                        $types = BusinessDB::table('requests as r')
                            ->join('users as u', 'u.user_id', '=', 'r.user_id')
                            ->where('u.company_id', $company->company_id)
                            ->select('r.request_type', BusinessDB::raw('COUNT(*) as count'))
                            ->groupBy('r.request_type')
                            ->get()
                            ->map(fn($row) => [
                                'type'  => $row->request_type,
                                'count' => $row->count,
                            ]);

                        // Categories
                        $categories = BusinessDB::table('requests as r')
                            ->join('users as u', 'u.user_id', '=', 'r.user_id')
                            ->join('request_types as rt', 'rt.request_type_id', '=', 'r.request_type_id')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('r.deleted_at')
                            ->select(
                                'rt.name as category',
                                BusinessDB::raw('COUNT(r.request_id) as count')
                            )
                            ->groupBy('rt.name')
                            ->get();

                        // Requests list
                             Developer::alert($requests);
                        

                        // Final company dataset
                        $result[] = [
                            'company_id'   => $company->company_id,
                            'company_name' => $company->name,
                            'counts'       => $counts,
                            'types'        => $types,
                            'categories'   => $categories,
                            
                        ];
                    }

                    $data = [
                        'user_id'  => $userId,
                        'companies' => $result,
                        'requests'     => $requests,
                    ];
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
                        $baseQuery = BusinessDB::table('requests as r')
                            ->join('users as u', 'u.user_id', '=', 'r.user_id')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('r.deleted_at');

                        // If not admin, filter by user
                        if (!$isAdmin && $authUserId) {
                            $baseQuery->where('r.user_id', $authUserId);
                        }

                        // Get totals for this company
                        $totalRequests = (clone $baseQuery)->count();

                        // Status counts for this company
                        $approvedCount = (clone $baseQuery)->where('r.approval_status', 'approved')->count();
                        $rejectedCount = (clone $baseQuery)->where('r.approval_status', 'rejected')->count();
                        $pendingCount = max(0, $totalRequests - $approvedCount - $rejectedCount);

                        // Type distribution for this company
                        $typeMap = BusinessDB::table('request_types')
                            ->whereNull('deleted_at')
                            ->pluck('name', 'request_type_id')
                            ->toArray();
                        
                        $typeCounts = [];
                        foreach ($typeMap as $typeId => $typeName) {
                            $query = BusinessDB::table('requests as r')
                                ->join('users as u', 'u.user_id', '=', 'r.user_id')
                                ->where('u.company_id', $company->company_id)
                                ->whereNull('r.deleted_at')
                                ->where('r.request_type_id', $typeId);
                            
                            if (!$isAdmin && $authUserId) {
                                $query->where('r.user_id', $authUserId);
                            }
                            
                            $typeCounts[$typeName] = $query->count();
                        }

                        // Reduce legend clutter: keep top 5 types and bucket the rest as "Others"
                        if (!empty($typeCounts) && count($typeCounts) > 6) {
                            arsort($typeCounts);
                            $top = array_slice($typeCounts, 0, 5, true);
                            $othersSum = array_sum(array_slice($typeCounts, 5));
                            $typeCounts = $top + ($othersSum > 0 ? ['Others' => $othersSum] : []);
                        }

                        // Monthly trend for this company (last 6 months)
                        $labels = [];
                        $counts = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $start = now()->startOfMonth()->subMonths($i);
                            $end = (clone $start)->endOfMonth();
                            $labels[] = $start->format('M');
                            
                            $monthlyQuery = BusinessDB::table('requests as r')
                                ->join('users as u', 'u.user_id', '=', 'r.user_id')
                                ->where('u.company_id', $company->company_id)
                                ->whereNull('r.deleted_at')
                                ->whereBetween('r.created_at', [$start->toDateTimeString(), $end->toDateTimeString()]);
                            
                            if (!$isAdmin && $authUserId) {
                                $monthlyQuery->where('r.user_id', $authUserId);
                            }
                            
                            $counts[] = $monthlyQuery->count();
                        }

                        // Build detailed items list for this company
                        $itemsQuery = BusinessDB::table('requests as r')
                            ->join('users as u', 'u.user_id', '=', 'r.user_id')
                            ->leftJoin('request_types as rt', 'rt.request_type_id', '=', 'r.request_type_id')
                            ->where('u.company_id', $company->company_id)
                            ->whereNull('r.deleted_at')
                            ->select([
                                'r.request_id',
                                'u.first_name',
                                'u.last_name',
                                'rt.name as leave_type',
                                'r.request_type',
                                'r.subject',
                                'r.reason',
                                'r.start_datetime',
                                'r.end_datetime',
                                'r.approval_status',
                                'r.created_at',
                            ])
                            ->orderBy('r.created_at', 'desc');
                        
                        if (!$isAdmin && $authUserId) {
                            $itemsQuery->where('r.user_id', $authUserId);
                        }
                        
                        $items = $itemsQuery->get()->map(function ($row) {
                            $start = $row->start_datetime ? \Carbon\Carbon::parse($row->start_datetime) : null;
                            $end   = $row->end_datetime ? \Carbon\Carbon::parse($row->end_datetime) : null;
                            $duration = '';
                            if ($start && $end) {
                                if (strtolower((string)$row->request_type) === 'full-day') {
                                    $days = ($start->diffInDays($end) > 1) ? $start->diffInDays($end) : 1;
                                    $duration = $days . ' day' . ($days > 1 ? 's' : '');
                                } else {
                                    $diffHours = $start->diffInHours($end);
                                    $diffMinutes = $start->diffInMinutes($end) % 60;
                                    $duration = $diffHours . 'h ' . $diffMinutes . 'm';
                                }
                            }
                            return [
                                'employee' => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                                'type' => $row->leave_type ?? $row->request_type ?? '-',
                                'subject' => $row->subject ?? '-',
                                'start' => $start ? $start->format('Y-m-d H:i') : '-',
                                'end' => $end ? $end->format('Y-m-d H:i') : '-',
                                'duration' => $duration,
                                'status' => ucfirst((string)$row->approval_status ?? '-'),
                                'reason' => $row->reason ?? '-',
                            ];
                        })->toArray();

                        // Store company data
                        $companyData[] = [
                            'company_id' => $company->company_id,
                            'company_name' => $company->name,
                            'total_requests' => $totalRequests,
                            'status_counts' => [
                                'approved' => $approvedCount,
                                'rejected' => $rejectedCount,
                                'pending' => $pendingCount,
                            ],
                            'type_counts' => $typeCounts,
                            'monthly' => [
                                'labels' => $labels,
                                'counts' => $counts,
                            ],
                            'items' => $items,
                        ];
                    }

                    // Set the first company as default data for backward compatibility
                    $data = $companyData[0] ?? [
                        'total_requests' => 0,
                        'status_counts' => ['approved' => 0, 'rejected' => 0, 'pending' => 0],
                        'type_counts' => [],
                        'monthly' => ['labels' => [], 'counts' => []],
                        'items' => [],
                    ];

                    // Add companies data for tabs
                    $data['companies'] = $companyData;

                    // If export requested, render the PDF-only view (no layout/logo) if exists; else use same view
                    if (strtolower((string)$request->query('export')) === 'pdf') {
                        $pdf = app(SelfContainedPdfService::class);
                        $pdfView = View::exists($baseView . '.reports-pdf') ? ($baseView . '.reports-pdf') : $viewPath;
                        $overrides = [
                            'filename' => 'leave-reports-' . now()->format('Y-m-d'),
                            'header' => [
                                'title' => 'Leave Reports',
                                'logo_url' => null,
                            ],
                        ];
                        return $pdf->downloadFromView($pdfView, compact('data'), $overrides);
                    }
                    break;
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
}