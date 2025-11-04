<?php
namespace App\Http\Controllers\System\Business\ShiftsAndOvertimes;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, Select};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};
use App\Http\Helpers\Helper;
/**
 * Controller for rendering the edit form for ShiftManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing ShiftManagement entities.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = Data::fetch($reqSet['system'], $reqSet['table'], [['column' => $reqSet['act'], 'value' => $reqSet['id']]]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            if (!$data) {
                return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
            }
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_ot_approve':
                    // Single-record approval modal (approve/reject/cancel) with conditional rejection reason
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'approval_status', 'label' => 'Status', 'value' => $data->approval_status ?? 'pending', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'id' => 'ot-approval-status-selector']],
                            ['type' => 'textarea', 'name' => 'rejection_reason', 'label' => 'Rejection Reason', 'value' => $data->rejection_reason ?? '', 'required' => false, 'col' => '12', 'col_class' => (($data->approval_status ?? 'pending') === 'rejected' ? 'ot-approval-rejected-container' : 'ot-approval-rejected-container d-none'), 'attr' => ['rows' => '3', 'placeholder' => 'Enter rejection reason if applicable...', 'id' => 'ot-rejection-reason-field']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clipboard-check me-1"></i> Edit OT Approval Status',
                        'short_label' => 'Review and update overtime request status',
                        'button' => 'Update Approval Status',
                        'script' => 'window.general.select();
                        $(function(){
                            var expected = "' . ($data->approval_status ?? 'pending') . '";
                            var $doc = $(document);
                            var getSelect = function(){
                                var $s = $("#ot-approval-status-selector");
                                if ($s.length) return $s;
                                return $("select[name=approval_status]");
                            };
                            var updateVisibility = function(){
                                var $s = getSelect();
                                var $box = $(".ot-approval-rejected-container");
                                var $field = $("#ot-rejection-reason-field");
                                if (!$s.length) return;
                                var val = String($s.val() || "");
                                if (val === "rejected") {
                                    $box.removeClass("d-none");
                                    $field.attr("required", "required");
                                } else {
                                    $box.addClass("d-none");
                                    $field.removeAttr("required");
                                    // Clear any previously entered rejection reason when not rejected
                                    try { $field.val("").trigger("change"); } catch(e) {}
                                }
                            };
                            var init = function(){
                                var $s = getSelect();
                                if (!$s.length) return;
                                if ($s.val() !== expected) {
                                    $s.val(expected).trigger("change.select2");
                                }
                                // Bind robustly on both id and name selectors
                                $doc.off("change.otapproval", "#ot-approval-status-selector").on("change.otapproval", "#ot-approval-status-selector", updateVisibility);
                                $doc.off("change.otapprovalName", "select[name=approval_status]").on("change.otapprovalName", "select[name=approval_status]", updateVisibility);
                                $doc.off("select2:select.otapproval", "#ot-approval-status-selector").on("select2:select.otapproval", "#ot-approval-status-selector", updateVisibility);
                                $doc.off("select2:select.otapprovalName", "select[name=approval_status]").on("select2:select.otapprovalName", "select[name=approval_status]", updateVisibility);
                                updateVisibility();
                            };
                            // Try several times in case Select2 initializes late
                            setTimeout(init, 0);
                            setTimeout(init, 150);
                            setTimeout(init, 300);
                            setTimeout(init, 600);
                            // Also poll a few times as a last resort
                            var tries = 0;
                            var iv = setInterval(function(){
                                tries++;
                                init();
                                if (tries >= 5) clearInterval(iv);
                            }, 250);
                        });'
                    ];
                    break;
                case 'business_ot_requests':
                    $shiftsArray = Select::options('shifts', 'array', ['shift_id' => 'name'], ['where' => ['is_active' => 1]]);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'shift_id', 'label' => 'Related Shift (Optional)', 'options' => ['' => '-- Select Shift --'] + $shiftsArray, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->shift_id ?? '']],
                            ['type' => 'datetime-local', 'name' => 'start_datetime', 'label' => 'Start Date & Time', 'value' => $data->start_datetime ?? '', 'required' => true, 'col' => '6'],
                            ['type' => 'datetime-local', 'name' => 'end_datetime', 'label' => 'End Date & Time', 'value' => $data->end_datetime ?? '', 'required' => true, 'col' => '6'],
                            ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason for Overtime', 'value' => $data->reason ?? '', 'required' => true, 'rows' => '3', 'col' => '12', 'attr' => ['maxlength' => '500']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clock me-1"></i> Edit Overtime Request',
                        'short_label' => 'Modify your overtime request details',
                        'button' => 'Update Request',
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
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Shift Name', 'value' => $data->name ?? '', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                                            ['type' => 'time', 'name' => 'start_time', 'label' => 'Start Time', 'value' => $data->start_time ?? '', 'required' => true, 'col' => '3'],
                                            ['type' => 'time', 'name' => 'end_time', 'label' => 'End Time', 'value' => $data->end_time ?? '', 'required' => true, 'col' => '3'],
                                            ['type' => 'number', 'name' => 'grace_in_minutes', 'label' => 'Grace In (Minutes)', 'value' => $data->grace_in_minutes ?? 0, 'col' => '3', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'grace_out_minutes', 'label' => 'Grace Out (Minutes)', 'value' => $data->grace_out_minutes ?? 0, 'col' => '3', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'half_day_hours_limit', 'label' => 'Half Day Hours Limit', 'value' => $data->half_day_hours_limit ?? 0, 'col' => '3', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '12']],
                                            ['type' => 'number', 'name' => 'minimum_work_hours', 'label' => 'Minimum Work Hours', 'value' => $data->minimum_work_hours ?? 0, 'required' => true, 'col' => '3', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '24']],
                                            ['type' => 'switch', 'name' => 'is_cross_day_shift', 'label' => 'Cross Day Shift', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->is_cross_day_shift ?? 0],
                                            ['type' => 'switch', 'name' => 'auto_mark_absent_if_no_checkin', 'label' => 'Auto Mark Absent If No Check-in', 'col_class' => 'd-flex align-items-center', 'col' => '5', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->auto_mark_absent_if_no_checkin ?? 0],
                                            ['type' => 'strong', 'label' => 'Break Setup'],
                                            ['type' => 'number', 'name' => 'break_minutes', 'label' => 'Break Minutes', 'value' => $data->break_minutes ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '240']],
                                            ['type' => 'number', 'name' => 'max_breaks_per_shift', 'label' => 'Max Breaks Per Shift', 'value' => $data->max_breaks_per_shift ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '10']],
                                            ['type' => 'number', 'name' => 'minimum_break_gap_minutes', 'label' => 'Minimum Break Gap (Minutes)', 'value' => $data->minimum_break_gap_minutes ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'switch', 'name' => 'flexible_break', 'label' => 'Flexible Break', 'col_class' => 'd-flex align-items-center', 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->flexible_break ?? 0],
                                            ['type' => 'switch', 'name' => 'auto_deduct_break', 'label' => 'Auto Deduct Break', 'col_class' => 'd-flex align-items-center', 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->auto_deduct_break ?? 0],
                                            ['type' => 'strong', 'label' => 'Shift Type & Status'],
                                            ['type' => 'switch', 'name' => 'is_holiday_shift', 'label' => 'Holiday Shift', 'col_class' => 'd-flex align-items-center', 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->is_holiday_shift ?? 0],
                                            ['type' => 'switch', 'name' => 'is_week_off', 'label' => 'Week Off Shift', 'col_class' => 'd-flex align-items-center', 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->is_week_off ?? 0],
                                            ['type' => 'switch', 'name' => 'is_flexible_shift', 'label' => 'Flexible Shift', 'col_class' => 'd-flex align-items-center', 'col' => '3', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->is_flexible_shift ?? 0],
                                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'col' => '3', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->status ?? 1]],
                                        ]
                                    ],
                                    [
                                        'title' => 'Advanced',
                                        'id' => 'advanced',
                                        'fields' => [
                                            ['type' => 'strong', 'label' => 'Check-in / Check-out Rules'],
                                            ['type' => 'number', 'name' => 'max_auto_end_hours', 'label' => 'Max Auto End Hours', 'value' => $data->max_auto_end_hours ?? 0, 'required' => false, 'col' => '6', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '24']],
                                            ['type' => 'number', 'name' => 'max_gap_between_logins', 'label' => 'Max Gap Between Logins (Minutes)', 'value' => $data->max_gap_between_logins ?? 0, 'required' => false, 'col' => '6', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'switch', 'name' => 'allow_multiple_logins', 'label' => 'Allow Multiple Logins', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '6', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->allow_multiple_logins ?? 0],
                                            ['type' => 'switch', 'name' => 'allow_remote_checkin', 'label' => 'Allow Remote Check-in', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '6', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->allow_remote_checkin ?? 0],
                                            ['type' => 'strong', 'label' => 'Overtime Setup'],
                                            ['type' => 'switch', 'name' => 'overtime_allowed', 'label' => 'Overtime Allowed', 'col_class' => 'd-flex align-items-center', 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->overtime_allowed ?? 0],
                                            ['type' => 'switch', 'name' => 'auto_calculate_overtime', 'label' => 'Auto Overtime Detection', 'col_class' => 'd-flex align-items-center', 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'value' => $data->auto_calculate_overtime ?? 0],
                                            ['type' => 'number', 'name' => 'overtime_grace_minutes', 'label' => 'Overtime Grace (Minutes)', 'value' => $data->overtime_grace_minutes ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '120']],
                                            ['type' => 'number', 'name' => 'minimum_overtime_minutes', 'label' => 'Min Overtime (Minutes)', 'value' => $data->minimum_overtime_minutes ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'number', 'name' => 'maximum_overtime_minutes', 'label' => 'Max Overtime (Minutes)', 'value' => $data->maximum_overtime_minutes ?? 0, 'col' => '4', 'attr' => ['min' => '0', 'max' => '480']],
                                            ['type' => 'select', 'name' => 'overtime_rate_type', 'label' => 'Overtime Rate Type', 'value' => $data->overtime_rate_type ?? 'multiplier', 'col' => '4', 'options' => ['fixed_rate' => 'Fixed Rate', 'multiplier' => 'Multiplier'], 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'number', 'name' => 'overtime_rate_value', 'label' => 'Overtime Rate Value', 'value' => $data->overtime_rate_value ?? 1.50, 'col' => '4', 'attr' => ['step' => '0.01', 'min' => '0', 'max' => '10']],
                                            ['type' => 'switch', 'name' => 'overtime_needs_approval', 'label' => 'Overtime Approval Required', 'col_class' => 'd-flex align-items-center', 'col' => '4', 'options' => ['1' => 'Required', '0' => 'No'], 'value' => $data->overtime_needs_approval ?? 1],
                                            ['type' => 'strong', 'label' => 'Effective Dates & Display'],
                                            ['type' => 'date', 'name' => 'effective_from_date', 'label' => 'Effective From', 'value' => $data->effective_from_date ?? '', 'col' => '4', 'required' => false],
                                            ['type' => 'date', 'name' => 'effective_to_date', 'label' => 'Effective To', 'value' => $data->effective_to_date ?? '', 'col' => '4', 'required' => false],
                                            ['type' => 'color', 'name' => 'shift_color_code', 'label' => 'Shift Color Code', 'value' => $data->shift_color_code ?? '', 'col' => '4', 'required' => false, 'attr' => ['maxlength' => '10']],
                                            ['type' => 'textarea', 'name' => 'remarks', 'label' => 'Remarks', 'value' => $data->remarks ?? '', 'col' => '12', 'required' => false],
                                        ]
                                    ]
                                ],
                                'col' => '12'
                            ]
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clock me-1"></i> Edit Shift',
                        'short_label' => 'Modify shift configuration with timing and rules',
                        'button' => 'Update Shift',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'business_shift_schedule':
                    $daysOfWeek   = ['sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'];
                    $weeksOfMonth = ['1' => 'Week 1', '2' => 'Week 2', '3' => 'Week 3', '4' => 'Week 4', '5' => 'Week 5'];
                    $monthsOfYear = ['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                    $positions    = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th', 'last' => 'Last'];
                    $shiftsArray  = Select::options('shifts', 'array', ['shift_id' => 'name'], ['where' => ['is_active' => 1]]);
                    $patternData  = json_decode($data->pattern ?? '{}', true);
                    $patterns = [];
                    foreach (['single', 'daily', 'weekly', 'monthly', 'range', 'custom_day', 'custom_week'] as $t) {
                        $patterns[$t] = !empty($patternData[$t])
                            ? ($t === 'single' ? $patternData[$t] : json_encode($patternData[$t]))
                            : ($t === 'single' ? [] : '{}');
                    }
                    $showClass = fn($t, $extra = '') => (empty($patternData[$t]) ? 'd-none' : '') . ($extra ? " $extra" : '');
                    $popup = [
                        'form'       => 'builder',
                        'labelType'  => 'floating',
                        'fields'     => [
                            ['type' => 'raw', 'col' => '12', 'col_class' => (empty($shiftsArray) ? '' : 'd-none'), 'html' => '<div class="alert alert-warning m-0"><strong>No shifts found.</strong> Please create at least one shift before creating a schedule.</div>'],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Schedule Name', 'value' => $data->name ?? '', 'required' => true, 'placeholder' => 'Enter schedule name', 'col' => '5', 'attr' => ['maxlength' => '100']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Schedule Type', 'id' => 'shift-schedule-type', 'required' => true, 'col' => '5', 'options' => ['single' => 'Single', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'range' => 'Range', 'custom_day' => 'Custom Day', 'custom_week' => 'Custom Week'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple', 'data-value' => array_keys(array_filter($patternData))]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'col_class' => 'd-flex align-items-center', 'col' => '2', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active ?? 1]],
                            ['type' => 'strong', 'col_class' => 'schedule-single-container', 'label' => 'Single Day'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-single-container', 'label' => 'Assign one or more shifts to apply every day. Example: Morning + Afternoon daily.'],
                            ['type' => 'select', 'name' => 'single_pattern', 'label' => 'Select Shifts', 'col_class' => 'schedule-single-container', 'required' => false, 'col' => '12', 'options' => $shiftsArray, 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple', 'data-value' => $patterns['single']]],
                            ['type' => 'strong', 'col_class' => 'schedule-daily-container ' . $showClass('daily'), 'label' => 'Daily Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-daily-container ' . $showClass('daily'), 'label' => 'Assign specific shifts for each day of the week.'],
                            ['type' => 'repeater', 'name' => 'daily_pattern', 'set' => 'pair', 'value' => $patterns['daily'], 'col_class' => 'schedule-daily-container mt-0 ' . $showClass('daily'), 'fields' => [['type' => 'select', 'name' => 'label', 'label' => 'Day', 'options' => ['' => '-- Select Day --'] + $daysOfWeek], ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray]], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-weekly-container ' . $showClass('weekly'), 'label' => 'Weekly Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-weekly-container ' . $showClass('weekly'), 'label' => 'Assign shifts based on week number in the month.'],
                            ['type' => 'repeater', 'name' => 'weekly_pattern', 'set' => 'pair', 'value' => $patterns['weekly'], 'col_class' => 'schedule-weekly-container mt-0 ' . $showClass('weekly'), 'fields' => [['type' => 'select', 'name' => 'label', 'label' => 'Week', 'options' => ['' => '-- Select Week --'] + $weeksOfMonth], ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray]], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-monthly-container ' . $showClass('monthly'), 'label' => 'Monthly Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-monthly-container ' . $showClass('monthly'), 'label' => 'Assign shifts to specific months.'],
                            ['type' => 'repeater', 'name' => 'monthly_pattern', 'set' => 'pair', 'value' => $patterns['monthly'], 'col_class' => 'schedule-monthly-container mt-0 ' . $showClass('monthly'), 'fields' => [['type' => 'select', 'name' => 'label', 'label' => 'Month', 'options' => ['' => '-- Select Month --'] + $monthsOfYear], ['type' => 'select', 'name' => 'value', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray]], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-range-container ' . $showClass('range'), 'label' => 'Date Range'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-range-container ' . $showClass('range'), 'label' => 'Assign shifts for a specific date range.'],
                            ['type' => 'repeater', 'name' => 'range_pattern', 'set' => 'array', 'value' => $patterns['range'], 'col_class' => 'schedule-range-container mt-0 ' . $showClass('range'), 'fields' => [['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date'], ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date'], ['type' => 'select', 'name' => 'shift_id', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray]], 'col' => '12'],
                            ['type' => 'strong', 'col_class' => 'schedule-custom-day-container ' . $showClass('custom_day'), 'label' => 'Custom Day Pattern'],
                            ['type' => 'small', 'tag_class' => 'sf-10', 'col_class' => 'mt-0 schedule-custom-day-container ' . $showClass('custom_day'), 'label' => 'Assign shifts for occurrences like 2nd Saturday or Last Monday.'],
                            ['type' => 'repeater', 'name' => 'custom_day_pattern', 'set' => 'array', 'value' => $patterns['custom_day'], 'col_class' => 'schedule-custom-day-container mt-0 ' . $showClass('custom_day'), 'fields' => [['type' => 'select', 'name' => 'on', 'label' => 'On', 'options' => ['' => '-- Select Position --'] + $positions], ['type' => 'select', 'name' => 'day', 'label' => 'Day', 'options' => ['' => '-- Select Day --'] + $daysOfWeek], ['type' => 'select', 'name' => 'shift', 'label' => 'Shift', 'options' => ['' => '-- Select Shift --'] + $shiftsArray]], 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-days me-1"></i> Add Schedule',
                        'short_label' => 'Create new recurring or custom schedule',
                        'button' => 'Save Schedule',
                        'script' => 'window.general.select();window.general.repeater();
                        $(function(){
                            const sections={single:".schedule-single-container",daily:".schedule-daily-container",weekly:".schedule-weekly-container",monthly:".schedule-monthly-container",range:".schedule-range-container",custom_day:".schedule-custom-day-container"};
                            const $type=$("#shift-schedule-type");
                            const update=()=>{$.each(sections,(_,sel)=>$(sel).addClass("d-none"));$type.find("option:selected").each(function(){const sel=sections[$(this).val()];if(sel) $(sel).removeClass("d-none");});};
                            $type.on("change",update);update();
                        });'
                    ];
                    break;
                case 'business_shift_assignments':
                    // Determine the target user for editing assignments
                    // If editing a specific mapping record, prefer that record's user_id; fallback to token id
                    $targetUserId = (string)($data->user_id ?? $reqSet['id']);
                    // Get current assignments for the target user
                    $currentAssignments = Data::fetch('business', 'shift_mapping', [
                        'columns' => ['type', 'ref_id'],
                        'where' => [
                            'user_id' => $targetUserId,
                            'is_active' => 1
                        ]
                    ]);
                    // Separate current shifts and schedules
                    $currentShifts = [];
                    $currentSchedules = [];
                    if (!empty($currentAssignments['data'])) {
                        foreach ($currentAssignments['data'] as $assignment) {
                            if ($assignment['type'] === 'shift') {
                                $currentShifts[] = $assignment['ref_id'];
                            } elseif ($assignment['type'] === 'schedule') {
                                $currentSchedules[] = $assignment['ref_id'];
                            }
                        }
                    }
                    // If no current assignments, log for debugging
                    if (empty($currentShifts) && empty($currentSchedules)) {
                        \Log::info('No current assignments found for user ' . $reqSet['id']);
                    }
                    // Log current assignments for debugging
                    \Log::info('Current assignments for user ' . $reqSet['id'] . ': ' . count($currentShifts) . ' shifts, ' . count($currentSchedules) . ' schedules');
                    // Data prep - EXACTLY like ShowAdd
                    $userSetArr = Data::fetch('business', 'users', [
                        'columns' => ['users.user_id', 'users.first_name', 'users.last_name', 'users.profile', 'roles.name AS role'],
                        'joins' => [
                            ['type' => 'left', 'table' => 'user_roles', 'on' => ['users.user_id', 'user_roles.user_id']],
                            ['type' => 'left', 'table' => 'roles', 'on' => ['user_roles.role_id', 'roles.role_id']]
                        ]
                    ]);
                    $shiftSetArr     = Data::fetch('business', 'shifts', ['columns' => ['shift_id', 'name'], 'where' => ['is_active' => 1]]);
                    $scheduleSetArr  = Data::fetch('business', 'shift_schedules', ['columns' => ['schedule_id', 'name'], 'where' => ['is_active' => 1]]);
                    $shiftSet        = array_map(fn($s) => ['id' => $s['shift_id'], 'value' => trim($s['name'])], $shiftSetArr['data'] ?? []);
                    $scheduleSet     = array_map(fn($s) => ['id' => $s['schedule_id'], 'value' => trim($s['name'])], $scheduleSetArr['data'] ?? []);
                    $formattedShifts = json_encode($shiftSet);
                    $formattedSchedules = json_encode($scheduleSet);
                    // Draggable users - EXACTLY like ShowAdd
                    $defaultAvatar = asset('default/preview-square.svg');
                    $source = '';
                    $target = '';
                    foreach ($userSetArr['data'] as $set) {
                        // Skip target user in source list (they're already in target)
                        if ((string)$set['user_id'] === $targetUserId) {
                            continue;
                        }
                        $avatar = FileManager::getFile($set['profile']);
                        if (empty($avatar) || !preg_match('/^data:image\/[a-z]+;base64,/', $avatar)) $avatar = $defaultAvatar;
                        $source .= '<div data-drag-item data-value="' . $set['user_id'] . '" data-sum="1" class="d-flex align-items-center gap-2 bg-light p-1 border rounded-2 mb-1">
                        <div class="avatar avatar-sm avatar-rounded"><img src="' . $avatar . '" alt="' . $set['first_name'] . ' ' . $set['last_name'] . '" class="img-fluid rounded-circle"></div>
                        <div class="d-flex flex-column w-100"><div class="sf-14 fw-bold">' . $set['first_name'] . ' ' . $set['last_name'] . '</div>
                        <div class="sf-9 text-muted"><b>' . $set['role'] . '</b> | ' . $set['user_id'] . '</div></div></div>';
                    }
                    // Pre-populate target with current user
                    $currentUser = null;
                    foreach ($userSetArr['data'] as $set) {
                        if ((string)$set['user_id'] === $targetUserId) {
                            $currentUser = $set;
                            break;
                        }
                    }
                    if ($currentUser) {
                        $avatar = FileManager::getFile($currentUser['profile']);
                        if (empty($avatar) || !preg_match('/^data:image\/[a-z]+;base64,/', $avatar)) $avatar = $defaultAvatar;
                        $target = '<div data-drag-item data-value="' . $currentUser['user_id'] . '" data-sum="1" class="d-flex align-items-center gap-2 bg-light p-1 border rounded-2 mb-1">
                        <div class="avatar avatar-sm avatar-rounded"><img src="' . $avatar . '" alt="' . $currentUser['first_name'] . ' ' . $currentUser['last_name'] . '" class="img-fluid rounded-circle"></div>
                        <div class="d-flex flex-column w-100"><div class="sf-14 fw-bold">' . $currentUser['first_name'] . ' ' . $currentUser['last_name'] . '</div>
                        <div class="sf-9 text-muted"><b>' . $currentUser['role'] . '</b> | ' . $currentUser['user_id'] . '</div></div></div>';
                    }
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-check me-1"></i>Edit Assignments',
                        'short_label' => 'Update shift and schedule assignments for this user and review totals in real-time.',
                        'button' => 'Update Assignments',
                        'fields' => [
                            ['type' => 'text', 'name' => 'shift_ids', 'label' => 'Select Shifts', 'placeholder' => 'Select (Max Allowed: 3)', 'value' => implode(',', $currentShifts), 'col' => '6', 'attr' => ['data-pills' => 'option', 'data-pills-list' => $formattedShifts, 'data-pills-separator' => ',', 'data-max-tags' => '3']],
                            ['type' => 'text', 'name' => 'schedule_ids', 'label' => 'Select Schedules', 'placeholder' => 'Select (Max Allowed: 3)', 'value' => implode(',', $currentSchedules), 'col' => '6', 'attr' => ['data-pills' => 'option', 'data-pills-list' => $formattedSchedules, 'data-pills-separator' => ',', 'data-max-tags' => '3']],
                            // Prefill datetime fields from the loaded record if present
                            ['type' => 'datetime-local', 'name' => 'start_date_time', 'label' => 'Start Date & Time', 'value' => ($data->start_date_time ?? ''), 'required' => false, 'col' => '6'],
                            ['type' => 'datetime-local', 'name' => 'end_date_time', 'label' => 'End Date & Time', 'value' => ($data->end_date_time ?? ''), 'required' => false, 'col' => '6'],
                            ['type' => 'dragger', 'name' => 'user_ids', 'source' => ['html' => $source, 'input_string' => '.area_1_values', 'input_sum' => '.area_1_sum', 'separator' => ',', 'class' => ['drag-area']], 'target' => ['html' => $target, 'input_string' => '.dropped-users-ids', 'input_sum' => '.dropped-users-sum', 'separator' => ',', 'class' => ['drag-area']], 'col' => '12'],
                            ['type' => 'raw', 'col_class' => 'my-0 py-0', 'html' => '<div class="live-summary text-center small mt-2 rounded-3">Loading current assignments...</div>'],
                            ['type' => 'raw', 'col_class' => 'my-0 py-0', 'html' => '<div class="alert alert-warning mt-2 note-warning rounded-3" style="display:none;">⚠️ To proceed, select at least <b>one shift or schedule</b> and <b>at least one user</b>.</div>'],
                        ],
                        'script' => 'window.general.select();window.general.pills();window.skeleton.drag();
                        // Debug and manually set values if needed
                        setTimeout(() => {
                            const shiftInput = document.querySelector("[name=\'shift_ids\']");
                            const scheduleInput = document.querySelector("[name=\'schedule_ids\']");
                            console.log("Debug - Shift input:", shiftInput);
                            console.log("Debug - Shift input value:", shiftInput ? shiftInput.value : "not found");
                            console.log("Debug - Shift input dataset:", shiftInput ? shiftInput.dataset : "not found");
                            console.log("Debug - Schedule input:", scheduleInput);
                            console.log("Debug - Schedule input value:", scheduleInput ? scheduleInput.value : "not found");
                            console.log("Debug - Schedule input dataset:", scheduleInput ? scheduleInput.dataset : "not found");
                            // Check if Tagify is initialized and manually set values if needed
                            if (shiftInput && shiftInput.tagify) {
                                console.log("Debug - Shift Tagify value:", shiftInput.tagify.value);
                                if (shiftInput.value && shiftInput.tagify.value.length === 0) {
                                    console.log("Debug - Manually setting shift values:", shiftInput.value);
                                    const values = shiftInput.value.split(",").filter(Boolean);
                                    shiftInput.tagify.addTags(values);
                                }
                            }
                            if (scheduleInput && scheduleInput.tagify) {
                                console.log("Debug - Schedule Tagify value:", scheduleInput.tagify.value);
                                if (scheduleInput.value && scheduleInput.tagify.value.length === 0) {
                                    console.log("Debug - Manually setting schedule values:", scheduleInput.value);
                                    const values = scheduleInput.value.split(",").filter(Boolean);
                                    scheduleInput.tagify.addTags(values);
                                }
                            }
                        }, 1000);
                        function csvCount(sel){
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
    /**
     * Renders a popup to confirm bulk update of records.
     *
     * @param Request $request HTTP request object containing input data.
     * @param array $params Route parameters including token.
     * @return JsonResponse Custom UI configuration for the popup or an error message.
     */
    public function bulk(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['system']) || !isset($reqSet['table']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or missing required data.', 400);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id', '')));
            if (empty($ids)) {
                return ResponseHelper::moduleError('Invalid Data', 'No records specified for update.', 400);
            }
            // Fetch records details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]], 'all');
            if (!$result['status'] || empty($result['data'])) {
                return ResponseHelper::moduleError('Records Not Found', $result['message'] ?: 'The requested records were not found.', 404);
            }
            $records = $result['data'];
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            $recordCount = count($records);
            $maxDisplayRecords = 5;
            // Generate accordion for records
            $detailsHtml = sprintf('<div class="alert alert-warning" role="alert"><div class="accordion" id="updateAccordion-%s"><div class="accordion-item border-0"><h2 class="accordion-header p-0 my-0"><button class="accordion-button collapsed p-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-%s" aria-expanded="false" aria-controls="collapse-%s">Confirm Bulk Update of %d Record(s)</button></h2><div id="collapse-%s" class="accordion-collapse collapse" data-bs-parent="#updateAccordion-%s"><div class="accordion-body p-2 bg-light"><div class="accordion" id="updateRecords-%s">', $token, $token, $token, $recordCount, $token, $token, $token);
            if ($recordCount > $maxDisplayRecords) {
                $detailsHtml .= sprintf('<div class="d-flex justify-content-between align-items-center"><div class="text-muted">Updating <b>%d</b> records.</div><button class="btn btn-link btn-sm text-decoration-none text-primary sf-12" type="button" data-bs-toggle="collapse" data-bs-target="#details-%s" aria-expanded="false" aria-controls="details-%s">Details</button></div><div class="collapse mt-2" id="details-%s"><div class="table-responsive" style="max-height: 200px;">', $recordCount, $token, $token, $token);
            }
            $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            $displayRecords = $recordCount > $maxDisplayRecords ? array_slice($records, 0, 5) : $records;
            foreach ($displayRecords as $index => $record) {
                $recordArray = (array)$record;
                $recordId = htmlspecialchars($recordArray[$reqSet['act']] ?? 'N/A');
                $detailsHtml .= sprintf('<tr><td colspan="2"><b>Record %d (ID: %s)</b></td></tr>', $index + 1, $recordId);
                if (empty($recordArray)) {
                    $detailsHtml .= '<tr><td colspan="2" class="text-muted">No displayable details available</td></tr>';
                } else {
                    foreach ($recordArray as $key => $value) {
                        $detailsHtml .= sprintf('<tr><td>%s</td><td><b>%s</b></td></tr>', htmlspecialchars(ucwords(str_replace('_', ' ', $key))), htmlspecialchars($value ?? ''));
                    }
                }
            }
            $detailsHtml .= $recordCount > $maxDisplayRecords ? sprintf('<tr><td colspan="2" class="text-muted">... and %d more records</td></tr></table></div></div>', $recordCount - count($displayRecords)) : '</table>';
            $detailsHtml .= sprintf('</div><div class="mt-2"><i class="sf-10"><span class="text-danger">Note: </span>Only non-unique fields can be updated in bulk. Changes will apply to all %d selected records. Ensure values are valid to avoid data conflicts.</i></div></div></div></div></div></div>', $recordCount);
            // Initialize popup configuration
            $popup = [];
            $detailsHtmlPlacement = 'top';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'ShiftManagement_entities':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['data' => 'Data', 'unique' => 'Unique', 'select' => 'Select', 'other' => 'Other'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit ShiftManagement Entities',
                        'short_label' => '',
                        'button' => 'Update Entities',
                        'script' => 'window.skeleton.select();window.skeleton.unique();'
                    ];
                    break;
                case 'business_shift_assignments':
                    // Handle delete confirmation for shift assignments
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'hidden', 'name' => 'confirm_delete', 'value' => '1'],
                        ],
                        'type' => 'modal',
                        'size' => 'md',
                        'position' => 'center',
                        'label' => '<i class="fa-solid fa-trash-can me-1 text-danger"></i> Confirm Delete Shift Assignments',
                        'short_label' => '',
                        'button' => 'Delete Assignments',
                        'button_class' => 'btn-danger',
                        'script' => ''
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
            $content = '<input type="hidden" name="update_ids" value="' . $request->input('id', '') . '">';
            $content .= $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            $content = $detailsHtmlPlacement === 'top' ? $detailsHtml . $content : $content . $detailsHtml;
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
