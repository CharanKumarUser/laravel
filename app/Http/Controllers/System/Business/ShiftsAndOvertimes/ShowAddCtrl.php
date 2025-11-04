<?php
namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;
use App\Facades\{BusinessDB, Data, Developer, FileManager, Select, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
use App\Http\Helpers\Helper;
/**
 * Controller for rendering the add form for ShiftManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new ShiftManagement entities.
     *
     * @param Request $request HTTP request object 
     * @param array $params Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize popup configuration and system options
            $popup = [];
            $holdPopup = false;
            $system = ['central' => 'Central', 'business' => 'Business'];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_ot_requests':
                    // Optional shift list for context
                    $shiftsArray  = Select::options('shifts', 'array', ['shift_id' => 'name'], ['where' => ['is_active' => 1]]);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'shift_id', 'label' => 'Shift (optional)', 'col' => '12', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'datetime-local', 'name' => 'start_datetime', 'label' => 'Start Date & Time', 'required' => true, 'col' => '6'],
                            ['type' => 'datetime-local', 'name' => 'end_datetime', 'label' => 'End Date & Time', 'required' => true, 'col' => '6'],
                            ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason (optional)', 'placeholder' => 'Add a short justification', 'col' => '12', 'attr' => ['maxlength' => '500']],
                        ],
                        'type' => 'modal',
                        'size' => 'md',
                        'position' => 'center',
                        'label' => '<i class="fa-solid fa-user-clock me-1"></i> New Overtime Request',
                        'short_label' => 'Submit an overtime request for approval',
                        'button' => 'Submit Request',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'business_shifts':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            [
                                'type' => 'tabs',
                                'tab' => 'nav-pills',
                                'id' => 'shift-tabs',
                                'class' => ['mb-1'],
                                'tabs' => [
                                    [
                                        'title' => 'Basic',
                                        'id' => 'basic',
                                        'fields' => [
                                            ['type' => 'strong', 'label' => 'General Setup'],
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Shift Name', 'required' => true, 'placeholder' => 'Enter shift name', 'col' => '6', 'attr' => ['maxlength' => '100', 'data-validate' => 'name']],
                                            ['type' => 'time', 'name' => 'start_time', 'label' => 'Start Time', 'required' => true, 'col' => '3'],
                                            ['type' => 'time', 'name' => 'end_time', 'label' => 'End Time', 'required' => true, 'col' => '3'],
                                            ['type' => 'number', 'name' => 'grace_in_minutes', 'label' => 'Grace In (Minutes)', 'required' => false, 'col' => '3', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'grace_out_minutes', 'label' => 'Grace Out (Minutes)', 'required' => false, 'col' => '3', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'half_day_hours_limit', 'label' => 'Half Day Hours Limit', 'required' => false, 'col' => '3', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '12']],
                                            ['type' => 'number', 'name' => 'minimum_work_hours', 'label' => 'Minimum Work Hours', 'required' => true, 'col' => '3', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '24']],
                                            ['type' => 'switch', 'name' => 'is_cross_day_shift', 'label' => 'Cross Day Shift', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'auto_mark_absent_if_no_checkin', 'label' => 'Auto Mark Absent if No Check-in', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '5', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'strong', 'label' => 'Break Setup'],
                                            ['type' => 'number', 'name' => 'break_minutes', 'label' => 'Break Duration (Minutes)', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '240']],
                                            ['type' => 'number', 'name' => 'max_breaks_per_shift', 'label' => 'Max Breaks Per Shift', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '10']],
                                            ['type' => 'number', 'name' => 'minimum_break_gap_minutes', 'label' => 'Minimum Break Gap (Minutes)', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'switch', 'name' => 'flexible_break', 'label' => 'Flexible Break', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'auto_deduct_break', 'label' => 'Auto Deduct Break', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'strong', 'label' => 'Shift Type & Status'],
                                            ['type' => 'switch', 'name' => 'is_holiday_shift', 'label' => 'Holiday Shift', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'is_week_off', 'label' => 'Week Off Shift', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'is_flexible_shift', 'label' => 'Flexible Shift', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'required' => false, 'col' => '3', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'attr' => ['data-select' => 'dropdown']],
                                        ]
                                    ],
                                    [
                                        'title' => 'Advanced',
                                        'id' => 'advanced',
                                        'fields' => [
                                            ['type' => 'strong', 'label' => 'Check-in / Check-out Rules'],
                                            ['type' => 'number', 'name' => 'max_auto_end_hours', 'label' => 'Max Auto End Hours', 'required' => false, 'col' => '6', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '24']],
                                            ['type' => 'number', 'name' => 'max_gap_between_logins', 'label' => 'Max Gap Between Logins (Minutes)', 'required' => false, 'col' => '6', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'switch', 'name' => 'allow_multiple_logins', 'label' => 'Allow Multiple Logins', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '6', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'allow_remote_checkin', 'label' => 'Allow Remote Check-in', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '6', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'strong', 'label' => 'Overtime Setup'],
                                            ['type' => 'switch', 'name' => 'overtime_allowed', 'label' => 'Overtime Allowed', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'switch', 'name' => 'auto_calculate_overtime', 'label' => 'Auto Overtime Detection', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes']],
                                            ['type' => 'number', 'name' => 'overtime_grace_minutes', 'label' => 'Overtime Grace (Minutes)', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'minimum_overtime_minutes', 'label' => 'Minimum Overtime (Minutes)', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'number', 'name' => 'maximum_overtime_minutes', 'label' => 'Maximum Overtime (Minutes)', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'select', 'name' => 'overtime_rate_type', 'label' => 'Overtime Rate Type', 'required' => false, 'col' => '4', 'options' => ['fixed_rate' => 'Fixed Rate', 'multiplier' => 'Multiplier'], 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'number', 'name' => 'overtime_rate_value', 'label' => 'Overtime Rate Value', 'required' => false, 'col' => '4', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '10']],
                                            ['type' => 'switch', 'name' => 'overtime_needs_approval', 'label' => 'Overtime Approval Required', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['1' => 'Required', '0' => 'No']],
                                            ['type' => 'strong', 'label' => 'Effective Dates & Display'],
                                            ['type' => 'date', 'name' => 'effective_from_date', 'label' => 'Effective From', 'col' => '4', 'required' => false],
                                            ['type' => 'date', 'name' => 'effective_to_date', 'label' => 'Effective To', 'col' => '4', 'required' => false],
                                            ['type' => 'color', 'name' => 'shift_color_code', 'label' => 'Shift Color Code', 'col' => '4', 'required' => false, 'attr' => ['maxlength' => '10']],
                                            ['type' => 'textarea', 'name' => 'remarks', 'label' => 'Remarks', 'col' => '12', 'required' => false],
                                        ]
                                    ]
                                ],
                                'col' => '12'
                            ]
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clock me-1"></i> Add Shift',
                        'short_label' => 'Create new shift configuration with timing, breaks, and overtime rules',
                        'button' => 'Save Shift',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'business_shift_schedule':
                    $daysOfWeek   = ['sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'];
                    $weeksOfMonth = ['1' => 'Week 1', '2' => 'Week 2', '3' => 'Week 3', '4' => 'Week 4', '5' => 'Week 5'];
                    $monthsOfYear = ['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                    $positions    = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th', 'last' => 'Last'];
                    $shiftsArray  = Select::options('shifts', 'array', ['shift_id' => 'name'], ['where' => ['is_active' => '1']]);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'col' => '12', 'col_class' => (empty($shiftsArray) ? '' : 'd-none'), 'html' => '<div class="alert alert-warning m-0"><strong>No shifts found.</strong> Please create at least one shift before creating a schedule.</div>'],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Schedule Name', 'required' => true, 'placeholder' => 'Enter schedule name', 'col' => '5', 'attr' => ['maxlength' => '100']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Schedule Type', 'id' => 'shift-schedule-type', 'required' => true, 'col' => '5', 'options' => ['single' => 'Single', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'range' => 'Range', 'custom_day' => 'Custom Day', 'custom_week' => 'Custom Week'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple', 'data-value' => ['single']]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'col_class' => 'd-flex align-items-center', 'col' => '2', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'strong', 'col_class' => 'schedule-single-container', 'label' => 'Single Day'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-single-container', 'label' => 'Assign one or more shifts to apply every day. Example: Morning + Afternoon daily.'],
                            ['type' => 'select', 'name' => 'single_pattern', 'label' => 'Select Shifts', 'col_class' => 'schedule-single-container', 'required' => false, 'col' => '12', 'options' => $shiftsArray, 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                            ['type' => 'strong', 'col_class' => 'schedule-daily-container d-none', 'label' => 'Daily Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-daily-container d-none', 'label' => 'Assign specific shifts for each day of the week. Example: Monday - Morning, Friday - Night.'],
                            ['type' => 'repeater', 'name' => 'daily_pattern', 'set' => 'pair', 'col_class' => 'schedule-daily-container mt-0 d-none', 'fields' => [
                                ['type' => 'select', 'name' => 'label', 'label' => 'Day', 'options' => ['' => '-- Select Day --'] + $daysOfWeek, 'required' => false],
                                ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'required' => false]
                            ], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-weekly-container d-none', 'label' => 'Weekly Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-weekly-container d-none', 'label' => 'Assign shifts based on week number in the month. Example: Week 1 - Morning, Week 3 - Night.'],
                            ['type' => 'repeater', 'name' => 'weekly_pattern', 'set' => 'pair', 'col_class' => 'schedule-weekly-container mt-0 d-none', 'fields' => [
                                ['type' => 'select', 'name' => 'label', 'label' => 'Week', 'options' => ['' => '-- Select Week --'] + $weeksOfMonth, 'required' => false],
                                ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'required' => false]
                            ], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-monthly-container d-none', 'label' => 'Monthly Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-monthly-container d-none', 'label' => 'Assign shifts to specific months. Example: January - Morning, July - Night.'],
                            ['type' => 'repeater', 'name' => 'monthly_pattern', 'set' => 'pair', 'col_class' => 'schedule-monthly-container mt-0 d-none', 'fields' => [
                                ['type' => 'select', 'name' => 'label', 'label' => 'Month', 'options' => ['' => '-- Select Month --'] + $monthsOfYear, 'required' => false],
                                ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'required' => false]
                            ], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-range-container d-none', 'label' => 'Date Range'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-range-container d-none', 'label' => 'Assign shifts for a specific date range. Example: 1st Jan - 10th Jan - Morning.'],
                            ['type' => 'repeater', 'name' => 'range_pattern', 'set' => 'array', 'col_class' => 'schedule-range-container mt-0 d-none', 'fields' => [
                                ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => false],
                                ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => false],
                                ['type' => 'select', 'name' => 'shift_id', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'required' => false]
                            ], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-custom-day-container d-none', 'label' => 'Custom Day Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-custom-day-container d-none', 'label' => 'Assign shifts for occurrences like 2nd Saturday or Last Monday.'],
                            ['type' => 'repeater', 'name' => 'custom_day_pattern', 'set' => 'array', 'col_class' => 'schedule-custom-day-container mt-0 d-none', 'fields' => [
                                ['type' => 'select', 'name' => 'on', 'label' => 'On', 'options' => ['' => '-- Select Position --'] + $positions, 'required' => false],
                                ['type' => 'select', 'name' => 'day', 'label' => 'Day', 'options' => ['' => '-- Select Day --'] + $daysOfWeek, 'required' => false],
                                ['type' => 'select', 'name' => 'shift', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'required' => false]
                            ], 'col' => '12']
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-days me-1"></i> Add Shift Schedule',
                        'short_label' => 'Create new recurring or custom shift schedule',
                        'button' => 'Save Schedule',
                        'script' => 'window.general.select();window.general.repeater();$(function () {
                            const $typeSelect = $("#shift-schedule-type");
                            if (!$typeSelect.length) return;
                            const sections = {
                                single: ".schedule-single-container",
                                daily: ".schedule-daily-container",
                                weekly: ".schedule-weekly-container",
                                monthly: ".schedule-monthly-container",
                                range: ".schedule-range-container",
                                custom_day: ".schedule-custom-day-container",
                            };
                            const updateVisibility = () => {
                                $.each(sections, (_, sel) => $(sel).addClass("d-none"));
                                $typeSelect.find("option:selected").each(function () {
                                    const sel = sections[$(this).val()];
                                    if (sel) $(sel).removeClass("d-none");
                                });
                            };
                            $typeSelect.on("change", updateVisibility);
                            updateVisibility();
                        });'
                    ];
                    break;
                case 'business_shift_assignments':
                    // Data prep
                    $userSetArr = Data::query('business', 'users', [
                        'select' => ['users.user_id', 'users.first_name', 'users.last_name', 'users.profile', 'roles.name AS role'],
                        'joins' => [
                            ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                            ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]]
                        ]
                    ]);
                    $shiftSetArr     = Data::fetch('business', 'shifts', [['select' => ['shift_id', 'name'], 'where' => ['is_active' => '1']]]);
                    $scheduleSetArr  = Data::fetch('business', 'shift_schedules', [['select' => ['schedule_id', 'name'], 'where' => ['is_active' => '1']]]);
                    $shiftSet        = array_map(fn($s) => ['id' => $s['shift_id'], 'value' => trim($s['name'])], $shiftSetArr['data'] ?? []);
                    $scheduleSet     = array_map(fn($s) => ['id' => $s['schedule_id'], 'value' => trim($s['name'])], $scheduleSetArr['data'] ?? []);
                    $formattedShifts = json_encode($shiftSet);
                    $formattedSchedules = json_encode($scheduleSet);
                    // Draggable users
                    $defaultAvatar = asset('default/preview-square.svg');
                    $source = '';
                    $target = '';
                    foreach ($userSetArr['data'] as $set) {
                        $avatar = FileManager::getFile($set['profile']);
                        if (empty($avatar) || !preg_match('/^data:image\/[a-z]+;base64,/', $avatar)) $avatar = $defaultAvatar;
                        $source .= '<div data-drag-item data-value="' . $set['user_id'] . '" data-sum="1" class="d-flex align-items-center gap-2 bg-light p-1 border rounded-2 mb-1">
                        <div class="avatar avatar-sm avatar-rounded"><img src="' . $avatar . '" alt="' . $set['first_name'] . ' ' . $set['last_name'] . '" class="img-fluid rounded-circle"></div>
                        <div class="d-flex flex-column w-100"><div class="sf-14 fw-bold">' . $set['first_name'] . ' ' . $set['last_name'] . '</div>
                        <div class="sf-9 text-muted"><b>' . $set['role'] . '</b> | ' . $set['user_id'] . '</div></div></div>';
                    }
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-check me-1"></i>Assign Shifts & Schedules',
                        'short_label' => 'Assign one or more shifts or schedules to selected users and review totals in real-time.',
                        'button' => 'Assign',
                        'fields' => [
                            ['type' => 'text', 'name' => 'shift_ids', 'label' => 'Select Shifts', 'placeholder' => 'Select (Max Allowed: 3)', 'col' => '6', 'attr' => ['data-pills' => 'option', 'data-pills-list' => $formattedShifts, 'data-pills-separator' => ',', 'data-max-tags' => '3']],
                            ['type' => 'text', 'name' => 'schedule_ids', 'label' => 'Select Schedules', 'placeholder' => 'Select (Max Allowed: 3)', 'col' => '6', 'attr' => ['data-pills' => 'option', 'data-pills-list' => $formattedSchedules, 'data-pills-separator' => ',', 'data-max-tags' => '3']],
                            ['type' => 'datetime-local', 'name' => 'start_date_time', 'label' => 'Start Date & Time', 'required' => false, 'col' => '6'],
                            ['type' => 'datetime-local', 'name' => 'end_date_time', 'label' => 'End Date & Time', 'required' => false, 'col' => '6'],
                            ['type' => 'dragger', 'name' => 'user_ids', 'source' => ['html' => $source, 'input_string' => '.area_1_values', 'input_sum' => '.area_1_sum', 'separator' => ',', 'class' => ['drag-area']], 'target' => ['html' => $target, 'input_string' => '.dropped-users-ids', 'input_sum' => '.dropped-users-sum', 'separator' => ',', 'class' => ['drag-area']], 'col' => '12'],
                            ['type' => 'raw', 'col_class' => 'my-0 py-0', 'html' => '<div class="live-summary text-center small mt-2 rounded-3">No selections yet.</div>'],
                            ['type' => 'raw', 'col_class' => 'my-0 py-0', 'html' => '<div class="alert alert-warning mt-2 note-warning rounded-3" style="display:none;">⚠️ To proceed, select at least <b>one shift or schedule</b> and <b>at least one user</b>.</div>'],
                        ],
                        'script' => 'window.general.select();window.general.pills();window.skeleton.drag();function csvCount(sel){
                            let el=document.querySelector(sel);
                            if(!el) return 0;
                            let v=("value" in el ? el.value : el.textContent).trim();
                            return v? v.split(",").filter(Boolean).length : 0;
                        }
                        function updateLiveSummary(){
                            let shifts=csvCount("[name=\'shift_ids\']"),
                                schedules=csvCount("[name=\'schedule_ids\']"),
                                users=csvCount(".dropped-users-ids");
                            const s=n=>n===1?"":"s";
                            let box=document.querySelector(".live-summary");
                            if(box) box.textContent=shifts||schedules||users
                                ?`${shifts} shift${s(shifts)} + ${schedules} schedule${s(schedules)} → ${users} user${s(users)} assigned`
                                :"No selections yet.";
                            let valid=users>0&&(shifts>0||schedules>0);
                            let note=document.querySelector(".note-warning"); if(note) note.style.display=valid?"none":"";
                            let btn=document.querySelector("form [type=\'submit\']"); if(btn) btn.disabled=!valid;
                        }
                        ["change","input","click","dragend"].forEach(ev=>document.addEventListener(ev,updateLiveSummary,{passive:true}));
                        updateLiveSummary();'
                    ];
                    break;
                default:
                    return ResponseHelper::emptyPopup();
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
