<?php
namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;
use App\Facades\{BusinessDB, Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new ShiftManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new ShiftManagement entity data based on validated input.
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
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'ShiftManagement record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_ot_requests':
                    $validator = Validator::make($request->all(), [
                        'shift_id'        => 'nullable|string|max:30',
                        'start_datetime'  => 'required|date',
                        'end_datetime'    => 'required|date|after:start_datetime',
                        'reason'          => 'nullable|string|max:500',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['ot_request_id'] = Random::unique(6, 'OTR');
                    $validated['user_id'] = Skeleton::authUser()->user_id;
                    // compute total_minutes (optional)
                    try {
                        $start = new \DateTime($validated['start_datetime']);
                        $end   = new \DateTime($validated['end_datetime']);
                        $validated['total_minutes'] = max(0, (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60));
                    } catch (\Throwable $e) {
                        $validated['total_minutes'] = null;
                    }
                    $reqSet['system'] = 'business';
                    $reqSet['table'] = 'overtime_requests';
                    $reloadTable = true;
                    $title = 'OT Request Submitted';
                    $message = 'Your overtime request has been submitted.';
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
                    $title = 'Shift Added';
                    $message = 'Shift configuration added successfully.';
                    break;
                case 'business_shift_schedule':
                    $validator = Validator::make($request->all(), [
                        'name'                  => 'required|string|max:100',
                        'is_active'             => 'nullable',
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
                    $patternKeys = [
                        'single'      => 'single_pattern',
                        'daily'       => 'daily_pattern',
                        'weekly'      => 'weekly_pattern',
                        'monthly'     => 'monthly_pattern',
                        'range'       => 'range_pattern',
                        'custom_day'  => 'custom_day_pattern',
                    ];
                    $pattern = [];
                    foreach ($patternKeys as $key => $field) {
                        if (!empty($validated[$field])) {
                            $pattern[$key] = is_array($validated[$field])
                                ? $validated[$field]
                                : json_decode($validated[$field], true);
                        }
                        unset($validated[$field]);
                    }
                    $validated['schedule_id'] = Random::unique(6, 'SCHE');
                    $validated['pattern'] = json_encode($pattern);
                    $reloadTable = $reloadCard = true;
                    $title = 'Schedule Added';
                    $message = 'Schedule configuration added successfully.';
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
                    $user_ids     = $parseIds($request->user_ids);
                    $shift_ids    = $parseIds($request->shift_ids);
                    $schedule_ids = $parseIds($request->schedule_ids);
                    if (empty($user_ids) || (empty($shift_ids) && empty($schedule_ids))) {
                        return ResponseHelper::moduleError('Validation Error', 'At least one user and one shift/schedule is required.');
                    }
                    $created_by = Skeleton::authUser()->user_id;
                    $timestamp  = now();
                    $start_date_time = $request->start_date_time ?: null;
                    $end_date_time = $request->end_date_time ?: null;
                    // Build all assignments in one collection
                    $assignments = collect([
                        ['type' => 'shift',    'ids' => $shift_ids],
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
                                    'user_id'    => $user_id,
                                    'type'       => $group['type'],
                                    'ref_id'     => $ref_id,
                                    'start_date_time' => $start_date_time,
                                    'end_date_time' => $end_date_time,
                                    'is_active'  => 1,
                                    'created_by' => $created_by,
                                    'created_at' => $timestamp,
                                    'updated_at' => $timestamp,
                                ];
                            });
                        });
                    })->all();
                    $result   = ['status' => false];
                    if (!empty($assignments)) {
                        BusinessDB::table('shift_mapping')->insert($assignments);
                        $result   = ['status' => true];
                    }
                    $shiftCount    = count($shift_ids) * count($user_ids);
                    $scheduleCount = count($schedule_ids) * count($user_ids);
                    $userCount     = count($user_ids);
                    $store   = false;
                    $reloadTable = true;
                    $title   = "Assignments Completed";
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
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
                // Insert data into the database
                $result = Data::insert($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
            }
            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] ?? '' : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
