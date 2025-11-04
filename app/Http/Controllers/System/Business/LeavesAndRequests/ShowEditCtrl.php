<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{Data, Developer, Random, Select, Skeleton, Scope, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};

/**
 * Controller for rendering the edit form for LeaveManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing LeaveManagement entities.
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
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['column'=>[$reqSet['act'], 'value'=> $reqSet['id']]]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            Developer::info('', [$data]);
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
                
                case 'business_request_types':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Type Name', 'required' => true, 'value' => $data->name, 'col' => '12', 'attr' => ['maxlength' => '100']],
                            ['type' => 'switch', 'name' => 'carry_forward', 'label' => 'Carry Forward', 'required' => false, 'col' => '4', 'options' => ['0'=>'No','1'=>'Yes'], 'attr' => array_merge(['class'=>'carry-forward-toggle'], $data->carry_forward == 1 ? ['checked'=>'checked'] : [])],
                            ['type' => 'switch', 'name' => 'is_encashable', 'label' => 'Encashable', 'required' => false, 'col' => '4', 'options' => ['0'=>'No','1'=>'Yes'], 'attr' => array_merge(['class'=>'encash-forward-toggle'], $data->is_encashable == 1 ? ['checked'=>'checked'] : [])],
                            ['type' => 'switch', 'name' => 'is_prorated', 'label' => 'Prorated', 'required' => false, 'col' => '4', 'options' => ['0'=>'No','1'=>'Yes'], 'attr' => array_merge(['class'=>'prorate-forward-toggle'], $data->is_prorated == 1 ? ['checked'=>'checked'] : [])],
                            ['type' => 'number', 'name' => 'max_days_per_year', 'label' => 'Max Days Per Year', 'required' => false, 'value' => $data->max_days_per_year, 'col' => '4', 'col_class' => 'type-category-leave-container', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'number', 'name' => 'consecutive_days', 'label' => 'Consecutive Days Per Month', 'required' => false, 'col' => '4','value' => $data->consecutive_days, 'col_class' => 'type-consecutive-leave-container', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value'=> $data->is_active]],
                            ['type' => 'number', 'name' => 'forward_leaves', 'label' => 'Forwardable Leaves', 'required' => false, 'value' => $data->forward_leaves ?? 0, 'col' => '12', 'col_class' => $data->carry_forward == 1 ? 'forward-leaves-container' : 'forward-leaves-container d-none', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'number', 'name' => 'encash_days', 'label' => 'Encashable Days', 'required' => false, 'value' => $data->encash_days ?? 0, 'col' => '12', 'col_class' => $data->is_encashable == 1 ? 'encash-leaves-container' : 'encash-leaves-container d-none', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'value' => $data->description, 'col' => '12', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-plus me-1"></i> Edit Request Type',
                        'short_label' => 'Update request type details and policies',
                        'button' => 'Update Request Type',
                        'script' => '
                            window.general.select();
                            document.addEventListener("change", e => {
                                const forwardBox = document.querySelector(".forward-leaves-container");
                                const encashBox = document.querySelector(".encash-leaves-container");
                
                                if(e.target.name === "carry_forward" && forwardBox){
                                    (e.target.checked || e.target.value=="1") 
                                        ? forwardBox.classList.remove("d-none") 
                                        : forwardBox.classList.add("d-none");
                                }
                
                                if(e.target.name === "is_encashable" && encashBox){
                                    (e.target.checked || e.target.value=="1") 
                                        ? encashBox.classList.remove("d-none") 
                                        : encashBox.classList.add("d-none");
                                }
                            });
                        '
                    ];
                    break;
               case 'business_requests':
                    // Get leave types and request types separately for filtering
                    $userId=Skeleton::authUser()->user_id;
                    $requestTypeIds = BusinessDB::table('assign_request_types')->where('user_id', $userId)->where('is_active', 1)->pluck('request_type_id')->toArray();
                    $requestTypes = BusinessDB::table('request_types')->whereIn('request_type_id', $requestTypeIds)->pluck('name', 'request_type_id')->toArray();
                    $scopeIds = Scope::getParents(Skeleton::authUser()->scope_id);
                    $tagIds = BusinessDB::table('assign_request_types')->where('user_id', $userId)->pluck('tag_id')->toArray();
                    $tagIds = array_filter(array_unique(explode(',', implode(',', $tagIds))));
                    $tags = BusinessDB::table('users')->whereIn('user_id', $tagIds)->pluck('username', 'user_id')->toArray();
                    $startDate = $endDate = $shortDate = $startTime = $endTime = '';

                    if (!empty($data->request_type)) {
                        $start = !empty($data->start_datetime) ? strtotime($data->start_datetime) : null;
                        $end   = !empty($data->end_datetime) ? strtotime($data->end_datetime) : null;

                        if ($data->request_type === 'full-day') {
                            $startDate = $start ? date('Y-m-d', $start) : '';
                            $endDate   = $end ? date('Y-m-d', $end) : '';
                        } elseif ($data->request_type === 'short-time') {
                            $shortDate = $start ? date('Y-m-d', $start) : '';
                            $startTime = $start ? date('H:i', $start) : '';
                            $endTime   = $end ? date('H:i', $end) : '';
                        }
                    }

                    $popup = [
                    'form' => 'builder',
                    'labelType' => 'floating',
                    'fields' => [
                        ['type' => 'select', 'name' => 'request_type', 'label' => 'Applying For', 'id' => 'request-type-selector', 'options' => ['full-day' => 'Full Day', 'short-time' => 'Short time'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value'=> $data->request_type]],
                        ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Request Type', 'options' => $requestTypes, 'required' => true, 'col' => '6', 'col_class' => 'request-type-leave-container', 'attr' => ['data-select' => 'dropdown','data-value'=> $data->request_type_id]],

                        // Full-day fields
                        ['type' => 'text', 'name' => 'start_datetime', 'label' => 'Start Date', 'id' => 'start-date-field', 'required' => true, 'col' => '6', 'value' => $startDate, 'col_class' => 'request-type-full-day', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'future']],
                        ['type' => 'text', 'name' => 'end_datetime', 'label' => 'End Date', 'id' => 'end-date-field', 'required' => true, 'col' => '6', 'value' => $endDate, 'col_class' => 'request-type-full-day', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'future']],


                        // Short-time fields
                        ['type' => 'text', 'name' => 'short_date', 'label' => 'Date', 'id' => 'short-date-field', 'required' => true, 'col' => '4', 'value' => $shortDate, 'col_class' => 'request-type-short-time', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'future']],
                        ['type' => 'time', 'name' => 'start_time', 'label' => 'Start Time', 'id' => 'start-time-field', 'required' => true, 'col' => '4', 'value' => $startTime, 'col_class' => 'request-type-short-time'],
                        ['type' => 'time', 'name' => 'end_time', 'label' => 'End Time', 'id' => 'end-time-field', 'required' => true, 'col' => '4', 'value' => $endTime, 'col_class' => 'request-type-short-time'],

                        ['type' => 'select', 'name' => 'tag_to', 'label' => 'Tag To', 'options' => $tags, 'required' => true, 'col' => '12', 'col_class' => 'request-type-tag-container', 'attr' => ['data-select' => 'dropdown', 'data-value'=> $data->tag_to]],
                        ['type' => 'text', 'name' => 'subject', 'label' => 'Subject', 'required' => true, 'col' => '12', 'value'=> $data->subject],
                        ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason', 'required' => true, 'col' => '12', 'value'=> $data->reason, 'attr' => ['rows' => '3']],
                    ],
                    'type' => 'modal',
                    'size' => 'modal-lg',
                    'position' => 'end',
                    'label' => '<i class="fa-solid fa-calendar-check me-1"></i> Add Request',
                    'short_label' => 'Submit new request for approval',
                    'button' => 'Save Request',
                    'script' => '
                        window.general.select();
                        window.skeleton.datePicker();
                         $(function() {
                            const $requestTypeSelector = $("#request-type-selector");
                            const $fullDayFields = $(".request-type-full-day");
                            const $shortTimeFields = $(".request-type-short-time");

                            const toggleFields = () => {
                                const type = $requestTypeSelector.val();

                                if (type === "full-day") {
                                    $fullDayFields.show().find("input, select, textarea").attr("required", true);
                                    $shortTimeFields.hide().find("input, select, textarea").removeAttr("required");
                                } else if (type === "short-time") {
                                    $shortTimeFields.show().find("input, select, textarea").attr("required", true);
                                    $fullDayFields.hide().find("input, select, textarea").removeAttr("required");
                                } else {
                                    $fullDayFields.hide().find("input, select, textarea").removeAttr("required");
                                    $shortTimeFields.hide().find("input, select, textarea").removeAttr("required");
                                }
                            };

                            $requestTypeSelector.on("change", toggleFields);
                            toggleFields(); // Run initially
                        });
                    ',
                ];

                break;

                case 'business_request_approve':
                    $approval=Data::fetch($reqSet['system'], 'request_approvals', ['where' => ['request_id' => $request->id]]);
                    $approvalItem = $approval['data'][0] ?? null;
                    $approvalItem = is_array($approvalItem) ? (object) $approvalItem : $approvalItem;
                    Developer::info('request_type Id ',[$data->request_type_id]);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'requestType_id', 'value' => $data->request_type_id ?? '', 'class'=>['mb-0']],
                            ['type' => 'select', 'name' => 'approval_status', 'label' => 'Status', 'options' => ['approved' => 'Approved', 'rejected' => 'Rejected'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->approval_status]],
                            ['type' => 'select', 'name' => 'affect_attendance', 'label' => 'Status', 'options' => ['ignore_deduction' => 'Ignore Deduction', 'deduct_from_working_hour' => 'Deduct From Working Hour', 'adjust_shift' => 'Adjust Shift'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'data-value' => $approvalItem->affect_attendance ?? '']],
                            ['type' => 'textarea', 'name' => 'approval_notes', 'label' => 'Notes', 'value' => $approvalItem->approval_notes ?? '', 'required' => false, 'col' => '12'],
                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'start_datetime', 'value' => $data->start_datetime ?? '', 'class'=>['mb-0']],
                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'end_datetime', 'value' => $data->end_datetime ?? '', 'class'=>['mb-0']],
                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $data->user_id ?? '', 'class'=>['mb-0']],

                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clipboard-check me-1"></i> Edit Request Approval Status',
                        'short_label' => 'Review and update request approval status',
                        'button' => 'Update Approval Status',
                        'script' => 'window.general.select();'
                    ];
                    break;

                case 'business_request_balances':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'user_id', 'label' => 'User', 'value' => $data->user_id, 'options' => Select::options('users', 'array', ['user_id' => 'username'], ['where' => ['is_active' => '1']]), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Leave Type', 'value' => $data->request_type_id, 'options' => Select::options('request_types', 'array', ['request_type_id' => 'name'], ['where' => ['is_active' => '1']]), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'year', 'label' => 'Year', 'value' => $data->year, 'required' => true, 'col' => '6', 'attr' => ['min' => '2020', 'max' => '2030']],
                            ['type' => 'number', 'name' => 'allocated_days', 'label' => 'Allocated Days', 'value' => $data->allocated_days, 'required' => true, 'col' => '6', 'attr' => ['min' => '0', 'max' => '365', 'step' => '0.5']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calculator me-1"></i> Edit Leave Balance',
                        'short_label' => 'Update leave allocation and usage tracking',
                        'button' => 'Update Leave Balance',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'business_assign_request_types':
                    $requestTypes = Select::options('request_types', 'array', ['request_type_id' => 'name'], ['where' => ['is_active' => '1']]);
                    Developer::info('Request Types', [$requestTypes]);
                    $scopes = Scope::getScopePaths('all', Skeleton::authUser()->scope_id ?? null, true);
                    $scopeIds = Scope::userChildScopes();
                    $tags = Select::options('users', 'array', ['user_id'=>'first_name'], ['whereIn' => ['scope_id' => $scopeIds]]);
                    Developer::info('scopeIds', [$scopeIds]);
                    $users = Select::options('users', 'array', ['user_id'=>'first_name'], ['whereIn' => ['scope_id' => $scopeIds]]);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            [
                                'type' => 'raw',
                                'html' => '
                                    <label class="text-primary mt-3 ms-1 sf-12">Scope</label>
                                    <div class="path-dropdown w-100 disabled" data-path-id="scope-paths" data-path-name="scope_id" style="pointer-events: none;>
                                        <input type="hidden" name="scope_id" data-scope value="'.$data->scope_id.'" disabled>
                                        <div class="path-trigger" data-placeholder="Select Scope">Select an option</div>
                                        <div class="path-dropdown-menu" data-scope-area></div>
                                    </div>
                                ',
                                'col' => '12',
                            ],
                            ['type' => 'text', 'name' => 'user_name', 'label' => 'User', 'class' => ['update-users-select'], 'options' => $users, 'required' => false, 'col' => '6','value' => $tags[$data->user_id] ?? 'Unknown','attr' => ['readonly' => true]],
                            ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Request Type', 'options' =>  $requestTypes, 'required' => true, 'col' => '6', 'col_class' => 'request-type-leave-container', 'attr' => ['data-select' => 'dropdown','data-value' => $data->request_type_id]],
                            ['type' => 'select', 'name' => 'tag_id', 'label' => 'Tag To', 'options' => $tags, 'col' => '6', 'class' => ['h-auto'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple', 'data-value' => explode(',', $data->tag_id) ?? []]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown'],'value' => $data->is_active ?? '0'],
                            ['type' => 'hidden','name' => 'user_id', 'value' => $data->user_id ?? null],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg', 
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-tasks me-1"></i> Assign Request Types',
                        'short_label' => 'Define Request Types to the Users',
                        'button' => 'Assign Request Types',
                        'script' => 'window.general.select();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ', ["' . $data->scope_id . '"], "single", true);'
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
                case 'LeaveManagement_entities':
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
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit LeaveManagement Entities',
                        'short_label' => 'Update multiple entity configurations at once',
                        'button' => 'Update Entities',
                        'script' => 'window.skeleton.select();window.skeleton.unique();'
                    ];
                    break;

                case 'business_leave_types':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'type_category', 'label' => 'Type Category', 'options' => ['' => 'Keep Current', 'leave' => 'Leave', 'request' => 'Request'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'max_days_per_year', 'label' => 'Max Days Per Year', 'required' => false, 'col' => '6', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'number', 'name' => 'default_duration_minutes', 'label' => 'Default Duration (Minutes)', 'required' => false, 'col' => '6', 'attr' => ['min' => '0', 'max' => '1440']],
                            ['type' => 'select', 'name' => 'affect_attendance', 'label' => 'Affect Attendance', 'options' => ['' => 'Keep Current', 'ignore_deduction' => 'Ignore Deduction', 'deduct_from_working_hours' => 'Deduct from Working Hours', 'adjust_shift' => 'Adjust Shift'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['' => 'Keep Current', '1' => 'Active', '0' => 'Inactive'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-plus me-1"></i> Bulk Edit Request Types',
                        'short_label' => 'Update multiple request type policies simultaneously',
                        'button' => 'Update Request Types',
                        'script' => 'window.general.select();'
                    ];
                    break;

                case 'business_leave_requests':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'request_type', 'label' => 'Request Category', 'options' => ['' => 'Keep Current', 'leave' => 'Leave Request', 'general' => 'General Request'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'request_type_name', 'label' => 'Request Type Name', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => false, 'col' => '6', 'attr' => []],
                            ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => false, 'col' => '6', 'attr' => []],
                            ['type' => 'time', 'name' => 'start_time', 'label' => 'Start Time', 'required' => false, 'col' => '6', 'attr' => []],
                            ['type' => 'time', 'name' => 'end_time', 'label' => 'End Time', 'required' => false, 'col' => '6', 'attr' => []],
                            ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason', 'required' => false, 'col' => '6', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-check me-1"></i> Bulk Edit Requests',
                        'short_label' => 'Update multiple requests in batch',
                        'button' => 'Update Requests',
                        'script' => 'window.general.select();'
                    ];
                    break;

                case 'business_leave_approve':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'approval_status', 'label' => 'Approval Status', 'options' => ['' => 'Keep Current', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'rejection_reason', 'label' => 'Rejection Reason', 'required' => false, 'col' => '6', 'attr' => ['rows' => '3', 'placeholder' => 'Enter rejection reason if applicable...']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-clipboard-check me-1"></i> Bulk Edit Approvals',
                        'short_label' => 'Process multiple request approvals together',
                        'button' => 'Update Approvals',
                        'script' => 'window.general.select();'
                    ];
                    break;

                case 'business_leave_balances':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'number', 'name' => 'allocated_days', 'label' => 'Allocated Days', 'required' => false, 'col' => '6', 'attr' => ['min' => '0', 'max' => '365', 'step' => '0.5']],

                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calculator me-1"></i> Bulk Edit Leave Balances',
                        'short_label' => 'Update multiple leave balance allocations',
                        'button' => 'Update Leave Balances',
                        'script' => 'window.general.select();'
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