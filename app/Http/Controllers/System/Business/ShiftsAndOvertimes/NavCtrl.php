<?php

namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;

use App\Http\Controllers\Controller;
use App\Facades\{Data, Skeleton};
use App\Http\Helpers\{ResponseHelper, ScheduleShiftHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Config, View};
use Carbon\Carbon;

/**
 * Controller for rendering navigation views for the ShiftsAndOvertimes module.
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
            $baseView = 'system.business.shifts-and-overtimes';
            $module = $params['module'] ?? 'ShiftsAndOvertimes';
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
                case 'my-shifts':
                    $data = $this->getMyShiftsData();
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

    /**
     * Get data for My Shifts page including today's shifts
     *
     * @return array
     */
    private function getMyShiftsData(): array
    {
        $data = [
            'status' => true,
            'today_shifts' => [],
            'today_schedules' => []
        ];

        try {
            $userId = Auth::user()->user_id ?? null;
            if (!$userId) {
                \Log::info(['MyShifts.debug' => 'No userId']);
                return $data;
            }

            // Get user's assigned schedules
            $userSchedules = Data::fetch('business', 'shift_mapping', [
                'columns' => ['shift_mapping.ref_id', 'shift_mapping.start_date_time', 'shift_mapping.end_date_time', 'shift_schedules.pattern', 'shift_schedules.name'],
                'joins' => [
                    ['type' => 'left', 'table' => 'shift_schedules', 'on' => ['shift_mapping.ref_id', 'shift_schedules.schedule_id']]
                ],
                'where' => [
                    'shift_mapping.user_id' => $userId,
                    'shift_mapping.type' => 'schedule',
                    'shift_mapping.is_active' => '1',
                    'shift_schedules.is_active' => '1'
                ]
            ]);
            \Log::info(['MyShifts.debug.userSchedules' => $userSchedules]);

            // Collect all today's shift IDs from schedules
            $todayShiftIds = [];
            $todaySchedules = [];

            if (!empty($userSchedules['data'])) {
                foreach ($userSchedules['data'] as $schedule) {
                    $pattern = $schedule['pattern'] ?? '{}';
                    $scheduleShifts = ScheduleShiftHelper::getTodayShiftsForScheduleMapping(
                        $pattern,
                        $schedule['start_date_time'] ?? null,
                        $schedule['end_date_time'] ?? null
                    );
                    if (!empty($scheduleShifts)) {
                        $todayShiftIds = array_merge($todayShiftIds, $scheduleShifts);
                        $todaySchedules[] = [
                            'name' => $schedule['name'] ?? '',
                            'shifts' => $scheduleShifts
                        ];
                    }
                }
            }

            // Get direct shift assignments
            $directShifts = Data::fetch('business', 'shift_mapping', [
                'columns' => ['shift_mapping.ref_id', 'shift_mapping.start_date_time', 'shift_mapping.end_date_time'],
                'where' => [
                    'shift_mapping.user_id' => $userId,
                    'shift_mapping.type' => 'shift',
                    'shift_mapping.is_active' => '1'
                ]
            ]);
            \Log::info(['MyShifts.debug.directShifts' => $directShifts]);

            if (!empty($directShifts['data'])) {
                foreach ($directShifts['data'] as $shift) {
                    $start = $shift['start_date_time'] ?? null;
                    $end = $shift['end_date_time'] ?? null;
                    if (ScheduleShiftHelper::isInAssignmentWindow($start, $end)) {
                        $todayShiftIds[] = $shift['ref_id'];
                    }
                }
            }

            // Remove duplicates
            $todayShiftIds = array_values(array_unique(array_filter($todayShiftIds)));
            \Log::info(['MyShifts.debug.todayShiftIds' => $todayShiftIds]);

            // Get shift details for today's shifts (merge per-id; avoids IN quirks)
            $data['today_shifts'] = [];
            if (!empty($todayShiftIds)) {
                foreach ($todayShiftIds as $sid) {
                    $one = Data::fetch('business', 'shifts', [
                        'columns' => [
                            'shifts.id',
                            'shifts.shift_id',
                            'shifts.name',
                            'shifts.start_time',
                            'shifts.end_time',
                            'shifts.min_work_hours',
                            'shifts.half_day_hours',
                            'shifts.break_duration_minutes',
                            'shifts.grace_in_minutes',
                            'shifts.grace_out_minutes',
                            'shifts.max_overtime_minutes',
                            'shifts.overtime_eligible',
                            'shifts.overtime_rate_type',
                            'shifts.overtime_rate_value',
                            'shifts.auto_overtime_detection',
                            'shifts.overtime_approval_required',
                            'shifts.is_cross_day_shift',
                            'shifts.is_dynamic_break',
                            'shifts.auto_deduct_break',
                            'shifts.allow_multiple_sessions',
                            'shifts.allow_inferred_sessions',
                            'shifts.is_holiday_shift',
                            'shifts.is_week_off_shift',
                            'shifts.is_active'
                        ],
                        'where' => [
                            'shifts.shift_id' => $sid,
                            'shifts.is_active' => '1'
                        ]
                    ]);
                    \Log::info(['MyShifts.debug.fetchShift' => $sid, 'result' => $one]);
                    if (!empty($one['data'])) {
                        // merge rows
                        foreach ($one['data'] as $row) {
                            $data['today_shifts'][] = $row;
                        }
                    }
                }
            } else {
                \Log::info(['MyShifts.debug' => 'No todayShiftIds after merge']);
            }

            $data['today_schedules'] = $todaySchedules;
        } catch (Exception $e) {
            \Log::error('Error getting My Shifts data: ' . $e->getMessage());
        }

        return $data;
    }
}
