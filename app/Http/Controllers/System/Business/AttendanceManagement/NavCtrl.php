<?php
namespace App\Http\Controllers\System\Business\AttendanceManagement;
use App\Http\Controllers\Controller;
use App\Facades\Skeleton;
use App\Facades\BusinessDB;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
/**
 * Controller for rendering navigation views for the AttendanceManagement module.
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
            $baseView = 'system.business.attendance-management';
            $module = $params['module'] ?? 'AttendanceManagement';
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
                case 'my-daily-attendance':
                    $user = Skeleton::authUser();
                    $userId = $user->user_id ?? null;
                    $businessId = $user->business_id ?? null;

                    // Initialize AttendanceService for shift management
                    $attendanceService = new \App\Services\AttendanceService();

                    // Get current date and time periods
                    $now = now();
                    $today = $now->toDateString();
                    $startOfWeek = (clone $now)->startOfWeek();
                    $endOfWeek = (clone $now)->endOfWeek();
                    $startOfMonth = (clone $now)->startOfMonth();
                    $endOfMonth = (clone $now)->endOfMonth();

                    // Get user's active shifts for today
                    $todayShifts = [];
                    try {
                        $todayShifts = $attendanceService->getShifts($businessId, $userId, $today);
                    } catch (\Exception $e) {
                        // Fallback: get shifts from shifts table if shift_mapping fails
                        $todayShifts = BusinessDB::table('shifts')
                            ->where('is_active', 1)
                            ->whereNull('deleted_at')
                            ->pluck('shift_id')
                            ->toArray();
                    }

                    // Get user's active shifts for the week
                    $weekShifts = [];
                    try {
                        $weekShifts = $attendanceService->getShifts($businessId, $userId, $startOfWeek->toDateString(), $endOfWeek->toDateString());
                    } catch (\Exception $e) {
                        $weekShifts = $todayShifts; // Fallback to today's shifts
                    }

                    // Get user's active shifts for the month
                    $monthShifts = [];
                    try {
                        $monthShifts = $attendanceService->getShifts($businessId, $userId, $startOfMonth->toDateString(), $endOfMonth->toDateString());
                    } catch (\Exception $e) {
                        $monthShifts = $todayShifts; // Fallback to today's shifts
                    }

                    // Fetch latest attendance records for the logged-in user (shift-aware)
                    $attendanceQuery = BusinessDB::table('attendance')
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')
                        ->orderByDesc('attendance_date')
                        ->orderByDesc('created_at')
                        ->limit(60);

                    // Filter by shifts if available
                    if (!empty($todayShifts)) {
                        $attendanceQuery->whereIn('shift_id', $todayShifts);
                    }

                    $attendance = $attendanceQuery->get()
                        ->map(function ($row) {
                            // Get shift details for this attendance record
                            if ($row->shift_id) {
                                $shift = BusinessDB::table('shifts')
                                    ->where('shift_id', $row->shift_id)
                                    ->where('is_active', 1)
                                    ->whereNull('deleted_at')
                                    ->first();
                                
                                $row->shift_name = $shift->name ?? 'Unknown Shift';
                                $row->shift_start = $shift->start_time ?? null;
                                $row->shift_end = $shift->end_time ?? null;
                            } else {
                                $row->shift_name = 'No Shift Assigned';
                                $row->shift_start = null;
                                $row->shift_end = null;
                            }
                            
                            // Derive a simple status
                            $row->status = ($row->check_in && $row->check_out) ? 'present' : 'absent';
                            return $row;
                        });

                    // Today aggregates (shift-aware)
                    $todayAggQuery = BusinessDB::table('attendance')
                        ->where('user_id', $userId)
                        ->whereDate('attendance_date', $today)
                        ->whereNull('deleted_at');

                    if (!empty($todayShifts)) {
                        $todayAggQuery->whereIn('shift_id', $todayShifts);
                    }

                    $todayAgg = $todayAggQuery
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(working_hours,'00:00:00')))) as working")
                        ->selectRaw("SUM(COALESCE(break_in_minutes,0)) as break_minutes")
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(overtime,'00:00:00')))) as overtime")
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(late_in,'00:00:00')))) as late")
                        ->first();

                    // Week aggregates (working hours) - shift-aware
                    $weekAggQuery = BusinessDB::table('attendance')
                        ->where('user_id', $userId)
                        ->whereBetween('attendance_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                        ->whereNull('deleted_at');

                    if (!empty($weekShifts)) {
                        $weekAggQuery->whereIn('shift_id', $weekShifts);
                    }

                    $weekAgg = $weekAggQuery
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(working_hours,'00:00:00')))) as working")
                        ->first();

                    // Month aggregates (working hours + overtime) - shift-aware
                    $monthAggQuery = BusinessDB::table('attendance')
                        ->where('user_id', $userId)
                        ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                        ->whereNull('deleted_at');

                    if (!empty($monthShifts)) {
                        $monthAggQuery->whereIn('shift_id', $monthShifts);
                    }

                    $monthAgg = $monthAggQuery
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(working_hours,'00:00:00')))) as working")
                        ->selectRaw("SEC_TO_TIME(SUM(TIME_TO_SEC(COALESCE(overtime,'00:00:00')))) as overtime")
                        ->first();

                    // First punch-in time today (shift-aware)
                    $firstInQuery = BusinessDB::table('attendance')
                        ->where('user_id', $userId)
                        ->whereDate('attendance_date', $today)
                        ->whereNull('deleted_at');

                    if (!empty($todayShifts)) {
                        $firstInQuery->whereIn('shift_id', $todayShifts);
                    }

                    // Earliest punch-in row with method
                    $firstInRow = $firstInQuery
                        ->orderBy('check_in', 'asc')
                        ->select('check_in', 'method')
                        ->first();

                    $formatTime = function ($timeString) {
                        if (empty($timeString)) return null;
                        try { return \Carbon\Carbon::createFromFormat('H:i:s', $timeString)->format('h:i A'); } catch (\Throwable $e) { return $timeString; }
                    };
                    $formatHM = function ($timeString) {
                        if (empty($timeString)) return '-';
                        // expects HH:MM:SS
                        try {
                            [$h,$m] = array_map('intval', explode(':', $timeString));
                            return sprintf('%02dh %02dm', $h, $m);
                        } catch (\Throwable $e) { return $timeString; }
                    };
                    $minutesToHM = function ($minutes) {
                        $minutes = (int)($minutes ?? 0);
                        $h = intdiv($minutes, 60);
                        $m = $minutes % 60;
                        return sprintf('%02dh %02dm', $h, $m);
                    };

                    $kpis = [
                        'now' => $now->format('h:i A, d M Y'),
                        'greeting_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'User'),
                        'today_working' => $todayAgg->working ?? '00:00:00',
                        'today_break' => (string)($todayAgg->break_minutes ?? 0),
                        'today_overtime' => $todayAgg->overtime ?? '00:00:00',
                        'today_late' => $todayAgg->late ?? '00:00:00',
                        'punch_in' => $firstInRow->check_in ?? null,
                        'punch_in_method' => $firstInRow->method ?? null,
                        'week_working' => $weekAgg->working ?? '00:00:00',
                        'month_working' => $monthAgg->working ?? '00:00:00',
                        'month_overtime' => $monthAgg->overtime ?? '00:00:00',
                        // Derived
                        'today_working_hm' => $formatHM($todayAgg->working ?? '00:00:00'),
                        'today_overtime_hm' => $formatHM($todayAgg->overtime ?? '00:00:00'),
                        'today_break_hm' => $minutesToHM($todayAgg->break_minutes ?? 0),
                        'week_working_hm' => $formatHM($weekAgg->working ?? '00:00:00'),
                        'month_working_hm' => $formatHM($monthAgg->working ?? '00:00:00'),
                        'month_overtime_hm' => $formatHM($monthAgg->overtime ?? '00:00:00'),
                        'punch_in_display' => $formatTime($firstInRow->check_in ?? null),
                    ];

                    // Get shift information for display
                    $userShifts = [];
                    if (!empty($todayShifts)) {
                        $userShifts = BusinessDB::table('shifts')
                            ->whereIn('shift_id', $todayShifts)
                            ->where('is_active', 1)
                            ->whereNull('deleted_at')
                            ->select('shift_id', 'name', 'start_time', 'end_time')
                            ->get()
                            ->map(function ($shift) use ($formatTime) {
                                return [
                                    'shift_id' => $shift->shift_id,
                                    'name' => $shift->name,
                                    'start_time' => $shift->start_time,
                                    'end_time' => $shift->end_time,
                                    'start_time_formatted' => $formatTime($shift->start_time),
                                    'end_time_formatted' => $formatTime($shift->end_time),
                                ];
                            });
                    }

                    // Determine active/next shift for circular progress around avatar
                    $circleProgressPercent = 0;
                    $activeShiftName = null;
                    $activeShiftStartDisp = null;
                    $activeShiftEndDisp = null;
                    if (!empty($userShifts)) {
                        $nowTs = now();
                        $pickedShift = null;
                        foreach ($userShifts as $s) {
                            // Build start and end datetime for today, handle cross-day end
                            $startDT = \Carbon\Carbon::parse($today . ' ' . $s['start_time']);
                            $endDT = \Carbon\Carbon::parse($today . ' ' . $s['end_time']);
                            if ($endDT->lessThanOrEqualTo($startDT)) {
                                $endDT->addDay();
                            }
                            if ($nowTs->betweenIncluded($startDT, $endDT)) {
                                $pickedShift = [ $s, $startDT, $endDT ];
                                break;
                            }
                        }
                        // If not inside any, pick the nearest upcoming (or last ended)
                        if ($pickedShift === null) {
                            $minDiff = PHP_INT_MAX;
                            foreach ($userShifts as $s) {
                                $startDT = \Carbon\Carbon::parse($today . ' ' . $s['start_time']);
                                $endDT = \Carbon\Carbon::parse($today . ' ' . $s['end_time']);
                                if ($endDT->lessThanOrEqualTo($startDT)) {
                                    $endDT->addDay();
                                }
                                $diff = abs($nowTs->diffInSeconds($startDT));
                                if ($diff < $minDiff) {
                                    $minDiff = $diff;
                                    $pickedShift = [ $s, $startDT, $endDT ];
                                }
                            }
                        }
                        if ($pickedShift !== null) {
                            [$s, $startDT, $endDT] = $pickedShift;
                            $activeShiftName = $s['name'];
                            // Display-friendly times
                            try {
                                $activeShiftStartDisp = $startDT->format('h:i A');
                                $activeShiftEndDisp = $endDT->format('h:i A');
                            } catch (\Throwable $e) {}
                            $total = max(1, $startDT->diffInSeconds($endDT));
                            $elapsed = 0;
                            if ($nowTs->lessThanOrEqualTo($startDT)) {
                                $elapsed = 0;
                            } elseif ($nowTs->greaterThanOrEqualTo($endDT)) {
                                $elapsed = $total;
                            } else {
                                $elapsed = $startDT->diffInSeconds($nowTs);
                            }
                            $circleProgressPercent = max(0, min(100, ($elapsed / $total) * 100));
                        }
                    }

                    $profileUrl = $user->profile_photo_url ?? asset('default/preview-profile.svg');

                    $data = [
                        'status' => true,
                        'user_id' => $userId,
                        'attendance' => $attendance,
                        'kpis' => $kpis,
                        'circle_progress_percent' => round($circleProgressPercent, 2),
                        'active_shift_name' => $activeShiftName,
                        'active_shift_start' => $activeShiftStartDisp,
                        'active_shift_end' => $activeShiftEndDisp,
                        'profile_image_url' => $profileUrl,
                        'user_shifts' => $userShifts,
                        'today_shifts' => $todayShifts,
                        'shift_count' => count($todayShifts),
                    ];
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