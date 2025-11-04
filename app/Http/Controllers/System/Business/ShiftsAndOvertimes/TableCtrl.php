<?php
namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
/**
 * Controller for handling AJAX table data requests in the ShiftManagement module.
 */
class TableCtrl extends Controller
{
    /**
     * Handles AJAX requests for table data processing.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed table data or error response
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
            Developer::info($reqSet);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or lacks required configuration.', 400);
            }
            // Set view to table and parse filters
            $reqSet['view'] = 'table';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? [],
                'dateRange' => $filters['dateRange'] ?? [],
                'columns' => $filters['columns'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 10],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $title = 'Data Retrieved';
            $message = 'ShiftManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_ot_requests':
                    $reqSet['table'] = 'overtime_requests';
                    $reqSet['actions'] = 'caved';
                    $reqSet['system'] = 'business';
                    $reqSet['token'] = Skeleton::skeletonToken('business_ot_requests');
                    $reqSet['act'] = 'id';
                    $columns = [
                        'id' => ['overtime_requests.id', false],
                        'ot_request_id' => ['overtime_requests.ot_request_id', true],
                        'shift_name' => ['shifts.name AS shift_name', true],
                        'start_datetime' => ['overtime_requests.start_datetime', true],
                        'end_datetime' => ['overtime_requests.end_datetime', true],
                        'total_minutes' => ['overtime_requests.total_minutes', true],
                        'reason' => ['overtime_requests.reason', true],
                        'approval_status' => ['overtime_requests.approval_status', true],
                        'rejection_reason' => ['overtime_requests.rejection_reason', true],
                        'decision_by' => ['overtime_requests.decision_by', true],
                        'decision_by_name' => ['decider.username AS decision_by_name', false],
                        'decision_at' => ['overtime_requests.decision_at', true],
                        'created_at' => ['overtime_requests.created_at', true],
                        'updated_at' => ['overtime_requests.updated_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'users', 'on' => [['overtime_requests.user_id', 'users.user_id']]],
                        ['type' => 'left', 'table' => 'shifts', 'on' => [['overtime_requests.shift_id', 'shifts.shift_id']]],
                        ['type' => 'left', 'table' => 'users AS decider', 'on' => [['overtime_requests.decision_by', 'decider.user_id']]],
                    ];
                    $conditions = [
                        ['column' => 'overtime_requests.user_id', 'operator' => '=', 'value' => Auth::user()->user_id ?? ''],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'ot_request_id', 'view' => '<span class="badge bg-primary">::ot_request_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'approval_status', 'view' => '<span class="px-2 py-1 rounded-pill ::IF(approval_status = "approved", text-success, IF(approval_status = "rejected", text-danger, IF(approval_status = "cancelled", text-secondary, text-warning)))::">::approval_status::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_datetime', 'view' => '<code>::start_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_datetime', 'view' => '<code>::end_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'total_minutes', 'view' => '::IF(total_minutes IS NULL OR total_minutes = 0, -, <strong>::total_minutes:: min</strong>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'decision_by', 'view' => '::IF(decision_by_name IS NULL OR decision_by_name = "", ::decision_by::, ::decision_by_name::)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'decision_at', 'view' => '::IF(decision_at IS NULL, -, <small class="text-muted">::decision_at::</small>)::', 'renderHtml' => true],
                    ];
                    $title = 'OT Requests Retrieved';
                    $message = 'Overtime requests retrieved successfully.';
                    break;
                case 'business_ot_approve':
                    $reqSet['table'] = 'overtime_requests';
                    $reqSet['actions'] = 'cved';
                    $reqSet['system'] = 'business';
                    $reqSet['token'] = Skeleton::skeletonToken('business_ot_approve');
                    $reqSet['act'] = 'id';
                    $columns = [
                        'id' => ['overtime_requests.id', false],
                        'ot_request_id' => ['overtime_requests.ot_request_id', true],
                        'username' => ['users.username', true],
                        'shift_name' => ['shifts.name AS shift_name', true],
                        'start_datetime' => ['overtime_requests.start_datetime', true],
                        'end_datetime' => ['overtime_requests.end_datetime', true],
                        'total_minutes' => ['overtime_requests.total_minutes', true],
                        'reason' => ['overtime_requests.reason', true],
                        'approval_status' => ['overtime_requests.approval_status', true],
                        'rejection_reason' => ['overtime_requests.rejection_reason', true],
                        'decision_by' => ['overtime_requests.decision_by', true],
                        'decision_by_name' => ['decider.username AS decision_by_name', false],
                        'decision_at' => ['overtime_requests.decision_at', true],
                        'created_at' => ['overtime_requests.created_at', true],
                        'updated_at' => ['overtime_requests.updated_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'users', 'on' => [['overtime_requests.user_id', 'users.user_id']]],
                        ['type' => 'left', 'table' => 'shifts', 'on' => [['overtime_requests.shift_id', 'shifts.shift_id']]],
                        ['type' => 'left', 'table' => 'users AS decider', 'on' => [['overtime_requests.decision_by', 'decider.user_id']]],
                    ];
                    $conditions = [];
                    $custom = [
                        ['type' => 'modify', 'column' => 'ot_request_id', 'view' => '<span class="badge bg-primary">::ot_request_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'approval_status', 'view' => '<span class="px-2 py-1 rounded-pill ::IF(approval_status = "approved", text-success, IF(approval_status = "rejected", text-danger, IF(approval_status = "cancelled", text-secondary, text-warning)))::">::approval_status::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_datetime', 'view' => '<code>::start_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_datetime', 'view' => '<code>::end_datetime::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'total_minutes', 'view' => '::IF(total_minutes IS NULL OR total_minutes = 0, -, <strong>::total_minutes:: min</strong>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'decision_by', 'view' => '::IF(decision_by_name IS NULL OR decision_by_name = "", ::decision_by::, ::decision_by_name::)::', 'renderHtml' => true],
                    ];
                    $title = 'OT Approvals Retrieved';
                    $message = 'Overtime requests for approval retrieved successfully.';
                    break;
                case 'business_my_shifts':
                    $reqSet['table'] = 'shift_mapping';
                    $reqSet['actions'] = '';
                    $columns = [
                        'id' => ['shift_mapping.id', false],
                        'type' => ['shift_mapping.type', true],
                        'ref_id' => ['shift_mapping.ref_id', false],
                        'assigned_name' => ['::IF(shift_mapping.type = "shift", shifts.name, shift_schedules.name) AS assigned_name', false],
                        'shift_id' => ['shifts.shift_id', true],
                        'name' => ['shifts.name', true],
                        'start_time' => ['shifts.start_time', true],
                        'end_time' => ['shifts.end_time', true],
                        'minimum_work_hours' => ['shifts.minimum_work_hours', true],
                        'half_day_hours_limit' => ['shifts.half_day_hours_limit', true],
                        'grace_in_minutes' => ['shifts.grace_in_minutes', true],
                        'grace_out_minutes' => ['shifts.grace_out_minutes', true],
                        'auto_mark_absent_if_no_checkin' => ['shifts.auto_mark_absent_if_no_checkin', true],
                        'break_minutes' => ['shifts.break_minutes', true],
                        'flexible_break' => ['shifts.flexible_break', true],
                        'auto_deduct_break' => ['shifts.auto_deduct_break', true],
                        'max_breaks_per_shift' => ['shifts.max_breaks_per_shift', true],
                        'minimum_break_gap_minutes' => ['shifts.minimum_break_gap_minutes', true],
                        'is_flexible_shift' => ['shifts.is_flexible_shift', true],
                        'inferred_session_max_hours' => ['shifts.inferred_session_max_hours', true],
                        'max_session_gap_minutes' => ['shifts.max_session_gap_minutes', true],
                        'allow_multiple_logins' => ['shifts.allow_multiple_logins', true],
                        'allow_multiple_sessions' => ['shifts.allow_multiple_sessions', true],
                        'allow_inferred_sessions' => ['shifts.allow_inferred_sessions', true],
                        'allow_remote_checkin' => ['shifts.allow_remote_checkin', true],
                        'overtime_allowed' => ['shifts.overtime_allowed', true],
                        'auto_calculate_overtime' => ['shifts.auto_calculate_overtime', true],
                        'overtime_rate_type' => ['shifts.overtime_rate_type', true],
                        'overtime_rate_value' => ['shifts.overtime_rate_value', true],
                        'overtime_needs_approval' => ['shifts.overtime_needs_approval', true],
                        'overtime_grace_minutes' => ['shifts.overtime_grace_minutes', true],
                        'minimum_overtime_minutes' => ['shifts.minimum_overtime_minutes', true],
                        'maximum_overtime_minutes' => ['shifts.maximum_overtime_minutes', true],
                        'holiday_overtime_multiplier' => ['shifts.holiday_overtime_multiplier', true],
                        'weekly_off_overtime_multiplier' => ['shifts.weekly_off_overtime_multiplier', true],
                        'is_holiday_shift' => ['shifts.is_holiday_shift', true],
                        'is_week_off' => ['shifts.is_week_off', true],
                        'shift_color_code' => ['shifts.shift_color_code', true],
                        'remarks' => ['shifts.remarks', true],
                        'version' => ['shifts.version', true],
                        'is_active' => ['shifts.is_active', true],
                        'created_at' => ['shift_mapping.created_at', true],
                        'updated_at' => ['shifts.updated_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'shifts', 'on' => [['shift_mapping.ref_id', 'shifts.shift_id']]],
                        ['type' => 'left', 'table' => 'shift_schedules', 'on' => [['shift_mapping.ref_id', 'shift_schedules.schedule_id']]],
                    ];
                    $conditions = [
                        ['column' => 'shift_mapping.is_active', 'operator' => '=', 'value' => '1'],
                        ['column' => 'shift_mapping.user_id', 'operator' => '=', 'value' => Auth::user()->user_id ?? ''],
                        ['column' => 'shift_mapping.type', 'operator' => '=', 'value' => 'shift'],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'shift_id', 'view' => '<span class="badge bg-primary">::shift_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_time', 'view' => '<span class="text-info">::start_time::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_time', 'view' => '<span class="text-info">::end_time::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'minimum_work_hours', 'view' => '<code>::minimum_work_hours:: hrs</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'half_day_hours_limit', 'view' => '::IF(half_day_hours_limit > 0, <code>::half_day_hours_limit:: hrs</code>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'grace_in_minutes', 'view' => '::IF(grace_in_minutes > 0, <span class="badge bg-warning">::grace_in_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'grace_out_minutes', 'view' => '::IF(grace_out_minutes > 0, <span class="badge bg-warning">::grace_out_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'auto_mark_absent_if_no_checkin', 'view' => '::IF(auto_mark_absent_if_no_checkin = 1, <span class="badge bg-warning">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'break_minutes', 'view' => '::IF(break_minutes > 0, <span class="badge bg-info">::break_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'flexible_break', 'view' => '::IF(flexible_break = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'auto_deduct_break', 'view' => '::IF(auto_deduct_break = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'max_breaks_per_shift', 'view' => '::IF(max_breaks_per_shift > 0, <span class="badge bg-info">::max_breaks_per_shift::</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'minimum_break_gap_minutes', 'view' => '::IF(minimum_break_gap_minutes > 0, <span class="badge bg-info">::minimum_break_gap_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_flexible_shift', 'view' => '::IF(is_flexible_shift = 1, <span class="badge bg-info">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'inferred_session_max_hours', 'view' => '::IF(inferred_session_max_hours > 0, <code>::inferred_session_max_hours:: hrs</code>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'max_session_gap_minutes', 'view' => '::IF(max_session_gap_minutes > 0, <span class="badge bg-warning">::max_session_gap_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        // Add similar modify views for all remaining boolean/numeric fields...
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'created_at', 'view' => '<small class="text-muted">::created_at::</small>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'updated_at', 'view' => '<small class="text-muted">::updated_at::</small>', 'renderHtml' => true],
                    ];
                    $title = 'My Shifts Retrieved';
                    $message = 'Assigned shifts retrieved successfully.';
                    break;
                case 'business_my_schedules':
                    $reqSet['table'] = 'shift_mapping';
                    $reqSet['actions'] = '';
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
                        ['type' => 'left', 'table' => 'shift_schedules', 'on' => [['shift_mapping.ref_id', 'shift_schedules.schedule_id']]],
                    ];
                    $conditions = [
                        ['column' => 'shift_mapping.is_active', 'operator' => '=', 'value' => '1'],
                        ['column' => 'shift_mapping.user_id', 'operator' => '=', 'value' => Auth::user()->user_id ?? ''],
                        ['column' => 'shift_mapping.type', 'operator' => '=', 'value' => 'schedule'],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'pattern', 'view' => '<span class="text-muted">::pattern::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                    ];
                    $title = 'My Schedules Retrieved';
                    $message = 'Assigned schedules retrieved successfully.';
                    break;
                case 'business_shifts':
                    // Define columns and joins for shifts data
                    $columns = [
                        'id' => ['shifts.id', true],
                        'shift_id' => ['shifts.shift_id', true],
                        'scope_id' => ['shifts.scope_id', true],
                        'name' => ['shifts.name', true],
                        'start_time' => ['shifts.start_time', true],
                        'end_time' => ['shifts.end_time', true],
                        'is_cross_day_shift' => ['shifts.is_cross_day_shift', true],
                        'minimum_work_hours' => ['shifts.minimum_work_hours', true],
                        'half_day_hours_limit' => ['shifts.half_day_hours_limit', true],
                        'grace_in_minutes' => ['shifts.grace_in_minutes', true],
                        'grace_out_minutes' => ['shifts.grace_out_minutes', true],
                        'auto_mark_absent_if_no_checkin' => ['shifts.auto_mark_absent_if_no_checkin', true],
                        'break_minutes' => ['shifts.break_minutes', true],
                        'flexible_break' => ['shifts.flexible_break', true],
                        'auto_deduct_break' => ['shifts.auto_deduct_break', true],
                        'max_breaks_per_shift' => ['shifts.max_breaks_per_shift', true],
                        'minimum_break_gap_minutes' => ['shifts.minimum_break_gap_minutes', true],
                        'allow_multiple_logins' => ['shifts.allow_multiple_logins', true],
                        'auto_end_if_missing_checkout' => ['shifts.auto_end_if_missing_checkout', true],
                        'max_auto_end_hours' => ['shifts.max_auto_end_hours', true],
                        'max_gap_between_logins' => ['shifts.max_gap_between_logins', true],
                        'allow_remote_checkin' => ['shifts.allow_remote_checkin', true],
                        'overtime_allowed' => ['shifts.overtime_allowed', true],
                        'auto_calculate_overtime' => ['shifts.auto_calculate_overtime', true],
                        'overtime_rate_type' => ['shifts.overtime_rate_type', true],
                        'overtime_rate_value' => ['shifts.overtime_rate_value', true],
                        'overtime_needs_approval' => ['shifts.overtime_needs_approval', true],
                        'overtime_grace_minutes' => ['shifts.overtime_grace_minutes', true],
                        'minimum_overtime_minutes' => ['shifts.minimum_overtime_minutes', true],
                        'maximum_overtime_minutes' => ['shifts.maximum_overtime_minutes', true],
                        'holiday_overtime_multiplier' => ['shifts.holiday_overtime_multiplier', true],
                        'weekly_off_overtime_multiplier' => ['shifts.weekly_off_overtime_multiplier', true],
                        'is_flexible_shift' => ['shifts.is_flexible_shift', true],
                        'is_holiday_shift' => ['shifts.is_holiday_shift', true],
                        'is_week_off' => ['shifts.is_week_off', true],
                        'effective_from_date' => ['shifts.effective_from_date', true],
                        'effective_to_date' => ['shifts.effective_to_date', true],
                        'shift_color_code' => ['shifts.shift_color_code', true],
                        'remarks' => ['shifts.remarks', true],
                        'status' => ['shifts.status', true],
                        'created_by' => ['shifts.created_by', true],
                        'updated_by' => ['shifts.updated_by', true],
                        'deleted_at' => ['shifts.deleted_at', true],
                        'restored_at' => ['shifts.restored_at', true],
                        'delete_on' => ['shifts.delete_on', true],
                        'version' => ['shifts.version', true],
                        'created_at' => ['shifts.created_at', true],
                        'updated_at' => ['shifts.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'shift_id', 'view' => '<span class="badge bg-primary">::shift_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_time', 'view' => '<span class="text-info">::start_time::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_time', 'view' => '<span class="text-info">::end_time::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'minimum_work_hours', 'view' => '<code>::minimum_work_hours:: hrs</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'grace_in_minutes', 'view' => '::IF(grace_in_minutes > 0, <span class="badge bg-warning">::grace_in_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'grace_out_minutes', 'view' => '::IF(grace_out_minutes > 0, <span class="badge bg-warning">::grace_out_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'break_minutes', 'view' => '::IF(break_minutes > 0, <span class="badge bg-info">::break_minutes:: min</span>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'half_day_hours_limit', 'view' => '::IF(half_day_hours_limit > 0, <code>::half_day_hours_limit:: hrs</code>, <span class="text-muted">-</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_cross_day_shift', 'view' => '::IF(is_cross_day_shift = 1, <span class="badge bg-info">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'flexible_break', 'view' => '::IF(flexible_break = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'auto_deduct_break', 'view' => '::IF(auto_deduct_break = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'overtime_allowed', 'view' => '::IF(overtime_allowed = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'auto_calculate_overtime', 'view' => '::IF(auto_calculate_overtime = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'overtime_needs_approval', 'view' => '::IF(overtime_needs_approval = 1, <span class="badge bg-warning">Required</span>, <span class="badge bg-success">Not Required</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'overtime_rate_value', 'view' => '<code>::overtime_rate_value::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'overtime_rate_type', 'view' => '<span class="badge bg-secondary">::overtime_rate_type::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_holiday_shift', 'view' => '::IF(is_holiday_shift = 1, <span class="badge bg-info">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_week_off', 'view' => '::IF(is_week_off = 1, <span class="badge bg-info">Yes</span>, <span class="badge bg-light text-dark">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'status', 'view' => '::IF(status = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'created_at', 'view' => '<small class="text-muted">::created_at::</small>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'updated_at', 'view' => '<small class="text-muted">::updated_at::</small>', 'renderHtml' => true],
                    ];
                    $title = 'Shifts Retrieved';
                    $message = 'Shifts retrieved successfully.';
                    break;
                case 'business_shift_schedule':
                    $columns = [
                        'id' => ['shift_schedules.id', true],
                        'schedule_id' => ['shift_schedules.schedule_id', true],
                        'name' => ['shift_schedules.name', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                    ];
                    break;
                case 'business_shift_assignments':
                    $id = isset($reqSet['id']) ? $reqSet['id'] : '';
                    if ($id === 'users') {
                        $reqSet['table'] = 'users';
                        // Only allow checkbox selection in users view (no delete here)
                        $reqSet['actions'] = 'c';
                        $reqSet['system'] = 'business';
                        $reqSet['token'] = Skeleton::skeletonToken('business_shift_assignments');
                        $reqSet['act'] = 'user_id';
                        $columns = [
                            'id' => ['users.id', false],
                            'user_id' => ['users.user_id', false],
                            'first_name' => ['users.first_name', false],
                            'last_name' => ['users.last_name', false],
                            'profile' => ['users.profile', false],
                            'user_info' => ['users.user_id AS user_info', true],
                            'role' => ['roles.name AS role', false],
                            'email' => ['users.email', true],
                            'username' => ['users.username', true],
                            'created_at' => ['users.created_at', true],
                        ];
                        $defaultImg = asset('default/preview-square.svg');
                        $updateToken = Skeleton::skeletonToken('business_shift_assignments') . '_e_';
                        $custom = [
                            [
                                'type' => 'modify',
                                'column' => 'user_info',
                                'view' => '
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md avatar-rounded rounded-circle">
                                                ::IF(profile IS NOT NULL AND profile != \'\',
                                                    <img src="::~\App\Services\FileService->getFile(::profile::)~::" alt="::first_name::" class="img-fluid rounded-circle">,
                                                    <img src="' . $defaultImg . '" alt="::first_name::" class="img-fluid rounded-circle">)::
                                        </div>
                                        <div class="ms-2">
                                            <div class="sf-12 fw-medium">::first_name:: ::last_name::</div>
                                            <div class="d-flex justify-content-start align-items-center sf-10"><span class="fw-normal">::IF(role != \'\', ::role::, No Role Assigned)::</span></div>
                                        </div>
                                    </div>',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'addon',
                                'column' => 'assign',
                                'view' => '<button class="btn btn-sm btn-secondary skeleton-popup" data-token="' . $updateToken . '::id::" data-loading-text="Loading...">Assign Agasddssin</button>',
                                'renderHtml' => true
                            ],
                        ];
                        $joins = [
                            ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                            ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                        ];
                    } else {
                        $reqSet['table'] = 'shift_mapping';
                        $reqSet['actions'] = 'cd';
                        $reqSet['system'] = 'business';
                        $reqSet['token'] = Skeleton::skeletonToken('business_shift_assignments');
                        $reqSet['act'] = 'id';
                        $columns = [
                            'id' => ['shift_mapping.id', false],
                            'user_id' => ['users.user_id', false],
                            'first_name' => ['users.first_name', false],
                            'last_name' => ['users.last_name', false],
                            'profile' => ['users.profile', false],
                            'role' => ['roles.name AS role', false],
                            'user_info' => ['users.user_id AS user_info', true],
                            'type' => ['shift_mapping.type', true],
                            'shift_name' => ['shifts.name AS shift_name', false],
                            'schedule_name' => ['shift_schedules.name AS schedule_name', false],
                            'name' => ['shift_schedules.name AS name', true],
                            'start_date_time' => ['shift_mapping.start_date_time', true],
                            'end_date_time' => ['shift_mapping.end_date_time', true],
                            'created_at' => ['shift_mapping.created_at', true],
                        ];
                        $defaultImg = asset('default/preview-square.svg');
                        $updateToken = Skeleton::skeletonToken('business_shift_assignments') . '_e_';
                        $custom = [
                            [
                                'type' => 'modify',
                                'column' => 'user_info',
                                'view' => '
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md avatar-rounded rounded-circle border border-primary">
                                                ::IF(profile IS NOT NULL AND profile != \'\',
                                                    <img src="::~\App\Services\FileService->getFile(::profile::)~::" alt="::first_name::" class="img-fluid rounded-circle">,
                                                    <img src="' . $defaultImg . '" alt="::first_name::" class="img-fluid rounded-circle">)::
                                        </div>
                                        <div class="ms-2">
                                            <div class="sf-12 fw-medium">::first_name:: ::last_name::</div>
                                            <div class="d-flex justify-content-start align-items-center sf-10"><span class="fw-normal">::IF(role != \'\', ::role::, No Role Assigned)::</span></div>
                                        </div>
                                    </div>',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'addon',
                                'column' => 'names',
                                'view' => '::IF(shift_name IS NOT NULL, ::shift_name::, ::schedule_name::)::',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'modify',
                                'column' => 'type',
                                'view' => '::IF(type = "shift", <span class="badge bg-primary">Shift</span>, <span class="badge bg-info">Schedule</span>)::',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'modify',
                                'column' => 'start_date_time',
                                'view' => '<span class="text-success">::start_date_time::</span>',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'modify',
                                'column' => 'end_date_time',
                                'view' => '<span class="text-danger">::end_date_time::</span>',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'modify',
                                'column' => 'created_at',
                                'view' => '<small class="text-muted">::created_at::</small>',
                                'renderHtml' => true
                            ],
                            [
                                'type' => 'addon',
                                'column' => 'assign',
                                'view' => '<button class="btn btn-sm btn-secondary skeleton-popup" data-token="' . $updateToken . '::id::" data-loading-text="Loading...">Assign Again</button>',
                                'renderHtml' => true
                            ],
                        ];
                        $joins = [
                            ['type' => 'left', 'table' => 'users', 'on' => [['shift_mapping.user_id', 'users.user_id']]],
                            ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                            ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                            ['type' => 'left', 'table' => 'shifts', 'on' => [['shift_mapping.ref_id', 'shifts.shift_id']]],
                            ['type' => 'left', 'table' => 'shift_schedules', 'on' => [['shift_mapping.ref_id', 'shift_schedules.schedule_id']]],
                        ];
                        $conditions = [
                            ['column' => 'shift_mapping.is_active', 'operator' => '=', 'value' => '1'],
                        ];
                    }
                    $title = 'Shift Assignments Retrieved';
                    $message = 'Shift assignments retrieved successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Prepare for set
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'custom' => $custom];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = TableHelper::generateResponse($set, $businessId);
            // Generate and return response using TableHelper
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve table data.', 500);
        }
    }
}
