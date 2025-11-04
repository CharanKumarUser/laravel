<?php
namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
/**
 * Controller for handling AJAX card data requests in the ShiftsAndOvertimes module with clean UI.
 */
class CardCtrl extends Controller
{
    /**
     * Handles AJAX requests for card data processing for modules, sections, and items.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed card data or error response
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token and validate configuration
            $reqSet = Skeleton::resolveToken($token);
            Developer::info(['ShiftsAndOvertimes.CardCtrl.resolveToken' => $reqSet]);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or lacks required configuration.', 400);
            }
            // Set view to card and parse filters
            $reqSet['view'] = 'card';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? '',
                'dateRange' => $filters['dateRange'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 12],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $view = '';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_shifts':
                    $columns = [
                        'id' => ['shifts.id', true],
                        'shift_id' => ['shifts.shift_id', true],
                        'scope_id' => ['shifts.scope_id', true],
                        'name' => ['shifts.name', true],
                        'start_time' => ['shifts.start_time', true],
                        'end_time' => ['shifts.end_time', true],
                        'is_cross_day_shift' => ['shifts.is_cross_day_shift', true],
                        'break_minutes' => ['shifts.break_minutes', true],
                        'minimum_work_hours' => ['shifts.minimum_work_hours', true],
                        'half_day_hours_limit' => ['shifts.half_day_hours_limit', true],
                        'grace_in_minutes' => ['shifts.grace_in_minutes', true],
                        'grace_out_minutes' => ['shifts.grace_out_minutes', true],
                        'shift_color_code' => ['shifts.shift_color_code', true],
                        'status' => ['shifts.status', true],
                    ];
                    $view = <<<HTML
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card h-auto border-0 rounded-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning" style="background: ::shift_color_code::">ID: ::shift_id::</span>
                                    <span>::IF(status = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::</span>
                                </div>
                                <h6 class="fw-bold mb-1">::name::</h6>
                                <div class="sf-11 text-muted mb-2"><i class="ti ti-clock me-1"></i>::start_time:: - ::end_time::</div>
                                <div class="d-flex justify-content-between sf-11 mb-2">
                                    <span>Min Hours: <b>::minimum_work_hours::</b></span>
                                    <span>Break: <b>::break_minutes:: min</b></span>
                                </div>
                                <div class="d-flex justify-content-between sf-11 mb-2">
                                    <span>Grace In: <b>::grace_in_minutes:: min</b></span>
                                    <span>Grace Out: <b>::grace_out_minutes:: min</b></span>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-primary skeleton-popup" data-token="{$reqSet['token']}_e_::{$reqSet['act']}::">
                                        <i class="ti ti-edit me-1"></i>Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    HTML;
                    break;
                case 'business_shift_schedule':
                case 'business_shifts_schedule': // alias safeguard
                    $columns = [
                        'id' => ['shift_schedules.id', true],
                        'schedule_id' => ['shift_schedules.schedule_id', true],
                        'name' => ['shift_schedules.name', true],
                        'is_active' => ['shift_schedules.is_active', true],
                        'created_at' => ['shift_schedules.created_at', true],
                    ];
                    $view = <<<HTML
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card h-auto border-0 rounded-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning">Code: ::schedule_id::</span>
                                    <span>::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::</span>
                                </div>
                                <h6 class="fw-bold mb-1">::name::</h6>
                                <div class="sf-11 text-muted mb-3">
                                    <i class="ti ti-calendar-time me-1"></i>Created: ::created_at::
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-primary skeleton-popup" 
                                        data-token="{$reqSet['token']}_e_::{$reqSet['act']}::">
                                        <i class="ti ti-edit me-1"></i>Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    HTML;
                    break;
                case 'business_my_shifts':
                    $columns = [
                        'id' => ['shift_mapping.id', false],
                        'type' => ['shift_mapping.type', true],
                        'ref_id' => ['shift_mapping.ref_id', true],
                        'shift_name' => ['shifts.name AS shift_name', true],
                        'schedule_name' => ['shift_schedules.name AS schedule_name', true],
                        'shift_id' => ['shifts.shift_id', true],
                        'start_time' => ['shifts.start_time', true],
                        'end_time' => ['shifts.end_time', true],
                        'minimum_work_hours' => ['shifts.minimum_work_hours', true],
                        'half_day_hours_limit' => ['shifts.half_day_hours_limit', true],
                        'break_minutes' => ['shifts.break_minutes', true],
                        'grace_in_minutes' => ['shifts.grace_in_minutes', true],
                        'grace_out_minutes' => ['shifts.grace_out_minutes', true],
                        'max_overtime_minutes' => ['shifts.max_overtime_minutes', true],
                        'overtime_eligible' => ['shifts.overtime_eligible', true],
                        'overtime_rate_type' => ['shifts.overtime_rate_type', true],
                        'overtime_rate_value' => ['shifts.overtime_rate_value', true],
                        'auto_overtime_detection' => ['shifts.auto_overtime_detection', true],
                        'overtime_approval_required' => ['shifts.overtime_approval_required', true],
                        'is_cross_day_shift' => ['shifts.is_cross_day_shift', true],
                        'flexible_break' => ['shifts.flexible_break', true],
                        'auto_deduct_break' => ['shifts.auto_deduct_break', true],
                        'allow_multiple_sessions' => ['shifts.allow_multiple_sessions', true],
                        'allow_inferred_sessions' => ['shifts.allow_inferred_sessions', true],
                        'is_holiday_shift' => ['shifts.is_holiday_shift', true],
                        'is_week_off_shift' => ['shifts.is_week_off_shift', true],
                        'is_active' => ['shifts.is_active', true],
                        'created_at' => ['shift_mapping.created_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'shifts', 'on' => ['shift_mapping.ref_id', 'shifts.shift_id']],
                        ['type' => 'left', 'table' => 'shift_schedules', 'on' => ['shift_mapping.ref_id', 'shift_schedules.schedule_id']],
                    ];
                    $conditions = [
                        ['column' => 'shift_mapping.is_active', 'operator' => '=', 'value' => '1'],
                        ['column' => 'shift_mapping.user_id', 'operator' => '=', 'value' => Auth::user()->user_id ?? ''],
                        ['column' => 'shift_mapping.type', 'operator' => '=', 'value' => 'shift'],
                    ];
                    $view = <<<HTML
                    <div class="col-xl-6 col-lg-6 col-md-6 mb-4">
                        <div class="card h-auto border-0 rounded-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-warning">Shift</span>
                                    <span>::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0">::shift_name::</h6>
                                    <span class="badge bg-primary ms-2">::shift_id::</span>
                                </div>
                                <div class="sf-11 text-muted mb-2">
                                    <i class="ti ti-clock me-1"></i>::start_time:: - ::end_time::
                                </div>
                                <div class="row g-2 sf-11 mb-2">
                                    <div class="col-6"><b class="text-muted">Min Hours:</b> <span>::minimum_work_hours::</span></div>
                                    <div class="col-6"><b class="text-muted">Half-day:</b> <span>::IF(half_day_hours_limit > 0, ::half_day_hours_limit::, -)::</span></div>
                                    <div class="col-6"><b class="text-muted">Break:</b> <span>::break_minutes:: min</span></div>
                                    <div class="col-6"><b class="text-muted">Grace In:</b> <span>::grace_in_minutes:: min</span></div>
                                    <div class="col-6"><b class="text-muted">Grace Out:</b> <span>::grace_out_minutes:: min</span></div>
                                    <div class="col-6"><b class="text-muted">Max OT:</b> <span>::max_overtime_minutes:: min</span></div>
                                </div>
                                <div class="sf-11 text-muted mb-2">
                                    <b class="text-muted">Overtime:</b>
                                    ::IF(overtime_eligible = 1, 
                                        <span class="badge bg-success ms-1">Eligible</span>
                                        <span class="ms-2 badge bg-warning">::overtime_rate_type:: ::overtime_rate_value::</span>, 
                                        <span class="badge bg-warning ms-1">Not Eligible</span>
                                    )::
                                </div>
                                <div class="d-flex flex-wrap gap-2 sf-11 mb-2">
                                    ::IF(is_cross_day_shift = 1, <span class="badge bg-info">Cross-day</span>, <span></span>)::
                                    ::IF(allow_multiple_sessions = 1, <span class="badge bg-success">Multiple Sessions</span>, <span></span>)::
                                    ::IF(auto_overtime_detection = 1, <span class="badge bg-success">Auto OT Detection</span>, <span></span>)::
                                    ::IF(overtime_approval_required = 1, <span class="badge bg-warning">OT Approval Required</span>, <span></span>)::
                                    ::IF(is_holiday_shift = 1, <span class="badge bg-info">Holiday Shift</span>, <span></span>)::
                                </div>
                                <div class="row g-2 sf-11 mb-2">
                                    <div class="col-6"><b class="text-muted">Dynamic Break:</b> <span>::IF(flexible_break = 1, Yes, No)::</span></div>
                                    <div class="col-6"><b class="text-muted">Auto-deduct Break:</b> <span>::IF(auto_deduct_break = 1, Yes, No)::</span></div>
                                    <div class="col-6"><b class="text-muted">Inferred Sessions:</b> <span>::IF(allow_inferred_sessions = 1, Yes, No)::</span></div>
                                    <div class="col-6"><b class="text-muted">Week-off Shift:</b> <span>::IF(is_week_off_shift = 1, Yes, No)::</span></div>
                                </div>
                                <div class="sf-11 text-muted">
                                    <i class="ti ti-calendar-time me-1"></i>Assigned On: ::created_at::
                                </div>
                            </div>
                        </div>
                    </div>
                    HTML;
                    break;
                case 'business_my_schedules':
                    $columns = [
                        'id' => ['shift_mapping.id', false],
                        'type' => ['shift_mapping.type', true],
                        'ref_id' => ['shift_mapping.ref_id', true],
                        'schedule_name' => ['shift_schedules.name AS schedule_name', true],
                        'pattern' => ['shift_schedules.pattern', true],
                        'is_active' => ['shift_schedules.is_active', true],
                        'created_at' => ['shift_mapping.created_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'shift_schedules', 'on' => ['shift_mapping.ref_id', 'shift_schedules.schedule_id']],
                    ];
                    $conditions = [
                        ['column' => 'shift_mapping.is_active', 'operator' => '=', 'value' => '1'],
                        ['column' => 'shift_mapping.user_id', 'operator' => '=', 'value' => Auth::user()->user_id ?? ''],
                        ['column' => 'shift_mapping.type', 'operator' => '=', 'value' => 'schedule'],
                    ];
                    $view = <<<HTML
                    <div class="col-xl-6 col-lg-6 col-md-6 mb-4">
                        <div class="card h-auto border-0 rounded-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-warning">Schedule</span>
                                    <span>::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::</span>
                                </div>
                                <h6 class="fw-bold mb-2">::schedule_name::</h6>
                                <div class="sf-11 text-muted mb-2">::pattern::</div>
                                <div class="sf-11 text-muted">
                                    <i class="ti ti-calendar-time me-1"></i>Assigned On: ::created_at::
                                </div>
                            </div>
                        </div>
                    </div>
                    HTML;
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'view' => $view];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = CardHelper::generateResponse($set, $businessId);
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
            // Generate and return response using CardHelper
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve card data.', 500);
        }
    }
}
