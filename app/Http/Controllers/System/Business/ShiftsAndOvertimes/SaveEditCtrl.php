<?php

namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;

use App\Facades\{BusinessDB, Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};

/**
 * Controller for saving updated ShiftManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated ShiftManagement entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'ShiftManagement record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_ot_requests':
                    $validator = Validator::make($request->all(), [
                        'shift_id'       => 'nullable|string|max:30',
                        'request_title'  => 'required|string|max:150',
                        'start_datetime' => 'required|date',
                        'end_datetime'   => 'required|date|after:start_datetime',
                        'reason'         => 'nullable|string|max:500',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Recalculate derived fields
                    $start = \Carbon\Carbon::parse($validated['start_datetime']);
                    $end = \Carbon\Carbon::parse($validated['end_datetime']);
                    $validated['total_minutes'] = $end->diffInMinutes($start);
                    $validated['total_hours'] = round($validated['total_minutes'] / 60, 2);
                    $validated['request_date'] = $start->toDateString();
                    // Preserve status if not changing explicitly
                    unset($validated['approval_status']);
                    $reloadTable = true;
                    $title = 'Overtime Request Updated';
                    $message = 'Your overtime request has been updated successfully.';
                    break;
                case 'business_ot_approve':
                    // single-record approve/reject/cancel
                    $validator = Validator::make($request->all(), [
                        'approval_status'  => 'required|in:approved,rejected,cancelled,pending',
                        'rejection_reason' => 'nullable|string|max:500',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $action = $request->input('approval_status');
                    $remarks = $request->input('rejection_reason');
                    if ($action === 'rejected' && empty($remarks)) {
                        return ResponseHelper::moduleError('Validation Error', 'Rejection reason is required when status is Rejected.');
                    }
                    $now = now();
                    // Update requests table for single id
                    $update = [
                        'approval_status' => $action,
                        'rejection_reason' => $action === 'rejected' ? $remarks : null,
                        'decision_by' => Skeleton::authUser()->user_id,
                        'decision_at' => $now,
                        'updated_at' => $now,
                    ];
                    $affected = BusinessDB::table('overtime_requests')
                        ->where($reqSet['act'], $reqSet['id'])
                        ->update($update);
                    // Insert approvals backup
                    BusinessDB::table('overtime_approvals')->insert([
                        'approval_id' => Random::unique(6, 'OTA'),
                        'ot_request_id' => $reqSet['id'],
                        'approver_id' => Skeleton::authUser()->user_id,
                        'action' => $action,
                        'remarks' => $remarks,
                        'action_date' => $now,
                        'created_by' => Skeleton::authUser()->user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $store = false;
                    $result = (int) $affected;
                    $reloadTable = true;
                    $title = 'OT Updated';
                    $message = 'Overtime request updated successfully.';
                    break;
                case 'business_shifts':
                    $validator = Validator::make($request->all(), [
                        // Basic Shift Info
                        'name'                      => 'required|string|max:100',
                        'start_time'                 => 'required|string',
                        'end_time'                   => 'required|string',
                        'minimum_work_hours'         => 'required|numeric|min:0|max:24',
                        'half_day_hours_limit'       => 'nullable|numeric|min:0|max:12',
                        'grace_in_minutes'           => 'nullable|integer|min:0|max:120',
                        'grace_out_minutes'          => 'nullable|integer|min:0|max:120',
                        'auto_mark_absent_if_no_checkin' => 'nullable',
                        // Break & Rest
                        'break_minutes'              => 'nullable|integer|min:0|max:240',
                        'flexible_break'             => 'nullable',
                        'auto_deduct_break'          => 'nullable',
                        'max_breaks_per_shift'       => 'nullable|integer|min:0|max:10',
                        'minimum_break_gap_minutes'  => 'nullable|integer|min:0|max:480',
                        // Check-in / Check-out rules
                        'allow_multiple_logins'      => 'nullable',
                        'allow_remote_checkin'       => 'nullable',
                        'max_auto_end_hours'         => 'nullable|numeric|min:0|max:24',
                        'max_gap_between_logins'     => 'nullable|integer|min:0|max:480',
                        // Overtime & Extra Hours
                        'overtime_allowed'           => 'nullable',
                        'auto_calculate_overtime'    => 'nullable',
                        'overtime_grace_minutes'     => 'nullable|integer|min:0|max:120',
                        'minimum_overtime_minutes'   => 'nullable|integer|min:0|max:480',
                        'maximum_overtime_minutes'   => 'nullable|integer|min:0|max:480',
                        'overtime_rate_type'         => 'nullable|in:fixed_rate,multiplier',
                        'overtime_rate_value'        => 'nullable|numeric|min:0|max:10',
                        'overtime_needs_approval'    => 'nullable',
                        // Shift Type & Status
                        'is_holiday_shift'           => 'nullable',
                        'is_week_off'                => 'nullable',
                        'is_flexible_shift'          => 'nullable',
                        'active_status'              => 'nullable',
                        // Display & Notes
                        'effective_from_date'       => 'nullable|date',
                        'effective_to_date'         => 'nullable|date',
                        'shift_color_code'          => 'nullable|string|max:10',
                        'remarks'                   => 'nullable|string',
                        // Audit / Version (optional, can be system-generated)
                        'version'                    => 'nullable|string|max:10',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Auto-generate unique shift ID
                    $validated['shift_id'] = Random::unique(6, 'SHFT');
                    // Handle boolean fields (0/1)
                    $booleanFields = [
                        'flexible_break',
                        'auto_deduct_break',
                        'allow_multiple_logins',
                        'allow_remote_checkin',
                        'overtime_allowed',
                        'auto_calculate_overtime',
                        'overtime_needs_approval',
                        'is_holiday_shift',
                        'is_week_off',
                        'is_flexible_shift',
                        'status',
                        'auto_mark_absent_if_no_checkin'
                    ];
                    foreach ($booleanFields as $field) {
                        $validated[$field] = in_array(strtolower((string)($request->input($field, 0))), ['1', 'true', 'on', 'yes', 'enabled'], true) ? 1 : 0;
                    }
                    // Default values for numeric/nullable fields
                    $validated = array_merge([
                        'grace_in_minutes'           => 0,
                        'grace_out_minutes'          => 0,
                        'break_minutes'              => 0,
                        'max_breaks_per_shift'       => 0,
                        'minimum_break_gap_minutes'  => 0,
                        'max_auto_end_hours'         => 0,
                        'max_gap_between_logins'     => 0,
                        'minimum_work_hours'         => 0,
                        'half_day_hours_limit'       => 0,
                        'overtime_grace_minutes'     => 0,
                        'minimum_overtime_minutes'   => 0,
                        'maximum_overtime_minutes'   => 0,
                        'overtime_rate_value'        => 1.50,
                        'overtime_rate_type'         => 'multiplier',
                    ], $validated);
                    $reloadTable = $reloadCard = true;
                    $title   = 'Shift Updated';
                    $message = 'Shift configuration updated successfully.';
                    break;
                case 'business_shift_schedule':
                    $validator = Validator::make($request->all(), [
                        'name'                  => 'required|string|max:100',
                        'is_active'             => 'nullable',
                        'type'                  => 'array',
                        'single_pattern'        => 'nullable|array',
                        'daily_pattern'         => 'nullable|string',
                        'weekly_pattern'        => 'nullable|string',
                        'monthly_pattern'       => 'nullable|string',
                        'range_pattern'         => 'nullable|string',
                        'custom_day_pattern'    => 'nullable|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Map form field names to pattern keys
                    $patternKeys = [
                        'single'     => 'single_pattern',
                        'daily'      => 'daily_pattern',
                        'weekly'     => 'weekly_pattern',
                        'monthly'    => 'monthly_pattern',
                        'range'      => 'range_pattern',
                        'custom_day' => 'custom_day_pattern',
                    ];
                    $pattern = [];
                    foreach ($patternKeys as $typeKey => $fieldName) {
                        // Only include if this type is selected
                        if (!empty($validated['type']) && in_array($typeKey, $validated['type'])) {
                            if (!empty($validated[$fieldName])) {
                                $pattern[$typeKey] = is_array($validated[$fieldName])
                                    ? $validated[$fieldName]
                                    : json_decode($validated[$fieldName], true);
                            } else {
                                $pattern[$typeKey] = [];
                            }
                        }
                        unset($validated[$fieldName]); // Remove raw input
                    }
                    $validated['pattern'] = json_encode($pattern);
                    unset($validated['type']);
                    $reloadTable = $reloadCard = true;
                    $title = 'Schedule updated';
                    $message = 'Schedule configuration updated successfully.';
                    break;
                case 'business_shift_assignments':
                    $validator = Validator::make($request->all(), [
                        'shift_ids'        => 'nullable|string|max:100',
                        'schedule_ids'     => 'nullable|string|max:100',
                        'user_ids'         => 'required|string',
                        'start_date_time'  => 'nullable|date',
                        'end_date_time'    => 'nullable|date|after_or_equal:start_date_time',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    // Helper to clean & split comma-separated IDs
                    $parseIds = fn($ids) => collect(explode(',', (string) $ids))->map(fn($id) => trim($id))->filter()->unique()->values()->all();
                    $user_ids = $parseIds($request->user_ids);
                    $shift_ids = $parseIds($request->shift_ids);
                    $schedule_ids = $parseIds($request->schedule_ids);

                    if (empty($user_ids) || (empty($shift_ids) && empty($schedule_ids))) {
                        return ResponseHelper::moduleError('Validation Error', 'At least one user and one shift/schedule is required.');
                    }

                    $created_by = Skeleton::authUser()->user_id;
                    $timestamp = now();
                    $start_date_time = $request->start_date_time ?: null;
                    $end_date_time = $request->end_date_time ?: null;

                    // Build all assignments in one collection - EXACTLY like ShowAdd
                    $assignments = collect([
                        ['type' => 'shift', 'ids' => $shift_ids],
                        ['type' => 'schedule', 'ids' => $schedule_ids],
                    ])->flatMap(function ($group) use ($user_ids, $created_by, $timestamp, $start_date_time, $end_date_time) {
                        return collect($user_ids)->flatMap(function ($user_id) use ($group, $created_by, $timestamp, $start_date_time, $end_date_time) {
                            return collect($group['ids'])->map(function ($ref_id) use ($user_id, $group, $created_by, $timestamp, $start_date_time, $end_date_time) {
                                // Deactivate old mapping
                                BusinessDB::table('shift_mapping')
                                    ->where('user_id', $user_id)
                                    ->where('type', $group['type'])
                                    ->where('ref_id', $ref_id)
                                    ->update(['is_active' => 0, 'deleted_at' => $timestamp, 'updated_at' => $timestamp]);
                                // Return new active assignment
                                return [
                                    'user_id' => $user_id,
                                    'type' => $group['type'],
                                    'ref_id' => $ref_id,
                                    'start_date_time' => $start_date_time,
                                    'end_date_time' => $end_date_time,
                                    'is_active' => 1,
                                    'created_by' => $created_by,
                                    'created_at' => $timestamp,
                                    'updated_at' => $timestamp,
                                ];
                            });
                        });
                    })->all();

                    $result = ['status' => false];
                    if (!empty($assignments)) {
                        BusinessDB::table('shift_mapping')->insert($assignments);
                        $result = ['status' => true];
                    }

                    $shiftCount = count($shift_ids) * count($user_ids);
                    $scheduleCount = count($schedule_ids) * count($user_ids);
                    $userCount = count($user_ids);
                    $store = false;
                    $reloadTable = $reloadCard = true;

                    $title = "Assignments Updated";
                    $message = "{$shiftCount} shift(s) and {$scheduleCount} schedule(s) assigned to {$userCount} user(s) successfully.";
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            if ($store) {
                if ($byMeta) {
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['updated_at'] = now();
                }
                // Update data in the database
                $result = Data::update($reqSet['system'], $reqSet['table'], $validated,  [['column' => $reqSet['act'], 'value' => $reqSet['id']]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated ShiftManagement entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function bulk(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Split update_ids into individual IDs
            $ids = array_filter(explode('@', $request->input('update_ids', '')));
            if (empty($ids)) {
                return response()->json(['status' => false, 'title' => 'Invalid Data', 'message' => 'No valid IDs provided for update.']);
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'ShiftManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'ShiftManagement_entities':
                    $validator = Validator::make($request->all(), [
                        'type' => 'required|in:data,unique,select,other',
                        'status' => 'required|in:active,inactive',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'Entities Updated';
                    $message = 'ShiftManagement entities configuration updated successfully.';
                    break;
                case 'business_shift_assignments':
                    // Handle delete operation for shift assignments
                    $result = BusinessDB::table('shift_mapping')
                        ->whereIn('id', $ids)
                        ->update([
                            'is_active' => 0,
                            'deleted_at' => now(),
                            'updated_at' => now()
                        ]);
                    $reloadTable = true;
                    $title = 'Assignments Deleted';
                    $message = 'Selected shift assignments have been deleted successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            if ($store) {
                if ($reqSet['key'] === 'business_shift_assignments') {
                    // Delete operation already handled in switch case
                    // $result is already set
                } else {
                    if ($byMeta) {
                        $validated['updated_by'] = Skeleton::authUser()->user_id;
                    }
                    if ($timestampMeta) {
                        $validated['updated_at'] = now();
                    }
                    // Update data in the database
                    $result = Data::update('central', $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
                }
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
