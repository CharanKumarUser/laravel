<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{Data, Developer, Random, Skeleton};
use App\Services\Data\DataService;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};

/**
 * Controller for saving updated LeaveManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated LeaveManagement entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            Developer::info('save token',[$token]);
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            Developer::info('',[$reqSet['id']]);
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'LeaveManagement record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_request_types':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'description' => 'nullable|string',
                        'max_days_per_year' => 'nullable|integer|min:0|max:365',
                        'is_active' => 'required|in:0,1',
                        'forward_leaves' => 'nullable|integer|min:0|max:365',
                        'encash_days' => 'nullable|integer|min:0|max:365',
                        'consecutive_days' => 'nullable|integer|min:0|max:365',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['carry_forward'] = $request->has('carry_forward') ? 1 : 0;
                    $validated['is_encashable'] = $request->has('is_encashable') ? 1 : 0;
                    $validated['is_prorated'] = $request->has('is_prorated') ? 1 : 0;
                    $validated['forward_leaves'] = $validated['forward_leaves'] ?? 0;
                    $validated['encash_days'] = $validated['encash_days'] ?? 0;
                    $validated['consecutive_days'] = $validated['consecutive_days'] ?? 0;
                    $reloadTable = true;
                    $title = 'Request Type Updated';
                    $message = 'Request type updated successfully.';
                    break;
                case 'business_requests':
                    $validator = Validator::make($request->all(), [
                        'request_type' => 'required|in:full-day,short-time',
                        'request_type_id' => 'required_if:request_type,leave|nullable|string|max:100',
                        'start_datetime' => 'required|string',
                        'end_datetime' => 'required|string',
                        'subject'   => 'required|string|max:2000',
                        'reason' => 'required|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();

                    $validated['user_id'] = Skeleton::authUser()->user_id;
                    $log = [];
                    $log = [
                        'request_id' => $reqSet['id'],
                        'action' => 'modified',
                        'action_by' => $validated['user_id'],
                        'action_at' => now(),
                    ];
                    $log = Data::insert($reqSet['system'], 'request_logs', $log, '');
                    if(!$log['status']){
                        return ResponseHelper::moduleError('Log Record Failed', 'Internal Server Error');
                    }
                    $reloadTable = true;
                    $title = 'Request Added';
                    $message = 'Request added successfully.';
                    break;

                case 'business_request_approve':
                    $validator = Validator::make($request->all(), [
                        'request_id'        => 'required|string',
                        'approval_status'   => 'required|in:pending,approved,rejected',
                        'affect_attendance' => 'required|string',
                        'approval_notes'    => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();
                    $validated['approver_id'] = Skeleton::authUser()->user_id;
                    $validated['approval_level'] = '1';
                    $validated['approved_at'] = now();

                    $isApproved = Data::fetch($reqSet['system'], 'request_approvals', ['where' => [$reqSet['act'] => $reqSet['id']]]);
                    $approval = $isApproved['status'] && count($isApproved['data']) > 0
                        ? Data::update($reqSet['system'], 'request_approvals', $validated, [$reqSet['act'] => $reqSet['id']])
                        : Data::insert($reqSet['system'], 'request_approvals', $validated);

                    if (!$approval['status']) {
                        return ResponseHelper::moduleError('Failed to Update Status', 'Internal Server Error');
                    }

                    $req = [
                        'approval_status' => $validated['approval_status'],
                        'notes'           => $validated['approval_notes'],
                        'decision_by'     => $validated['approver_id'],
                        'decision_at'     => now(),
                    ];
                    $result = Data::update($reqSet['system'], 'requests', $req, ['request_id' => $validated['request_id']], $reqSet['key']);
                    if (!$result['status']) {
                        return ResponseHelper::moduleError('Failed to update request status', 'Internal Server Error');
                    }

                    $requestData = Data::fetch($reqSet['system'], 'requests', ['where' => [$reqSet['act'] => $reqSet['id']]]);
                    if (count($requestData['data']) > 0 && $validated['approval_status'] === 'approved' && $requestData['data'][0]['request_type'] === 'full-day') {
                        $userId = $requestData['data'][0]['user_id'] ?? '';
                        $typeId = $requestData['data'][0]['request_type_id'] ?? '';
                        $start  = \Carbon\Carbon::parse($requestData['data'][0]['start_datetime']);
                        $end    = \Carbon\Carbon::parse($requestData['data'][0]['end_datetime']);
                        $days   = $start->diffInDays($end) > 1 ? $start->diffInDays($end) : 1;

                        $balance = Data::fetch($reqSet['system'], 'request_balances', ['where' =>[
                            'user_id'        => $userId,
                            'request_type_id'=> $typeId,
                            'year'           => now()->year,
                        ]]);

                        if ($balance['status'] && count($balance['data']) > 0) {
                            $row = $balance['data'][0];
                            $updateResult = Data::update($reqSet['system'], 'request_balances', [
                                'used_days'  => (int) $row['used_days'] + $days,
                                'updated_by' => $validated['approver_id'],
                            ], ['id' => $row['id']]);

                            if (!$updateResult['status']) {
                                return ResponseHelper::moduleError('Failed to update leave balance', 'Internal Server Error');
                            }
                        }
                    }

                    $log = Data::insert($reqSet['system'], 'request_logs', [
                        'request_id' => $validated['request_id'],
                        'action'     => $validated['approval_status'],
                        'action_by'  => $validated['approver_id'],
                        'action_at'  => $validated['approved_at'],
                        'notes'      => $validated['approval_notes'],
                    ]);

                    if (!$log['status']) {
                        return ResponseHelper::moduleError('Log Record Failed', 'Internal Server Error');
                    }

                    $store = false;
                    $reloadPage = true;
                    $title = 'Request Approval Status Updated';
                    $message = 'Request approval status updated successfully.';
                break;

                case 'business_request_balances':
                    $validator = Validator::make($request->all(), [
                        'user_id' => 'required|string|max:30',
                        'request_type_id' => 'required|string|max:100',
                        'year' => 'required|integer|min:2020|max:2030',
                        'allocated_days' => 'required|numeric|min:0|max:365',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $is_there=Data::fetch($reqSet['system'], 'request_balances', ['user_id' => $validated['user_id'], 'request_type_id'=> $validated['request_type_id'], 'year'=> $validated['year']]);
                    if($is_there['status'] && is_array($is_there['data'])){
                        unset($validated['user_id'], $validated['request_type_id'], $validated['year']);
                    }
                    $reloadTable = true;
                    $title = 'Request Balance Updated';
                    $message = 'Request balance updated successfully.';
                    break;
                case 'business_assign_request_types':
                    Developer::info('entered into save logic',$request->all());
                    $validator = Validator::make($request->all(), [
                        'scope_id' => 'required|string|max:50',
                        'user_id' => 'string|max:50',
                        'request_type_id' => 'string|max:50',
                        'is_active' => 'required|in:0,1',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                
                    $validated = $validator->validated();
                    $userId = $validated['user_id'];
                    $currentUserId = Skeleton::authUser()->user_id ?? null;
                    $now = now();
                    $requestTypeId = $validated['request_type_id'];
                    Developer::info('',[$requestTypeId]);
                    // -------------------------
                    // Insert into request_balances
                    // -------------------------
                    if (!empty($requestTypeId)) {
                        // Async fetch using correct syntax
                        $requestTypesResponse = DataService::fetch(
                            $reqSet['system'],
                            'request_types',
                            [
                                'select' => ['request_type_id', 'max_days_per_year'],
                                'request_type_id' => [
                                    'operator' => '=',
                                    'value' => $requestTypeId,  
                                ],
                            ],
                            true
                        );
                        Developer::info('request_type',$requestTypesResponse);
                        $requestTypeId = $validated['request_type_id']; 
                        $requestTypesData = $requestTypesResponse['data'] ?? [];

                        // Filter only matching request type(s)
                        $matchedRequestTypes = array_filter($requestTypesData, function($item) use ($requestTypeId) {
                            $id = is_object($item) ? $item->request_type_id : $item['request_type_id'];
                            return in_array($id, (array)$requestTypeId); 
                        });

                        // Re-index array (optional)
                        $matchedRequestTypes = array_values($matchedRequestTypes);
                        $currentRequestType = DataService::fetch($reqSet['system'], $reqSet['table'], ['select' => ['request_type_id'], $reqSet['act'] => ['operator' => '=', 'value' => $reqSet['id']]], true);
                        Developer::info('currentRequestType',$currentRequestType);
                        Developer::info('request_type', $matchedRequestTypes);

                        $currentYear = date('Y');
                        $balanceInsertRows = [];
                
                        foreach ($matchedRequestTypes as $type) {
                            $requestTypeId = is_object($type) ? $type->request_type_id : $type['request_type_id'];
                            $allocatedDays = is_object($type) ? $type->max_days_per_year : $type['max_days_per_year'];
            
                            // ✅ Check if balance already exists
                            $existingBalance = DataService::fetch(
                                $reqSet['system'], 
                                'request_balances',
                                [
                                    'select' => ['request_balance_id'],
                                    'user_id' => ['operator' => '=', 'value' => $validated['user_id']],
                                    'request_type_id' => ['operator' => '=', 'value' => $requestTypeId],
                                    'year' => ['operator' => '=', 'value' => $currentYear],
                                ],
                                true
                            );
            
                            if (empty($existingBalance['data'])) {
                                $balanceInsertRows[] = [
                                    'request_balance_id' => Random::unique(6, 'RBL', true),
                                    'user_id' => $validated['user_id'],
                                    'request_type_id' => $requestTypeId,
                                    'year' => $currentYear,
                                    'allocated_days' => $allocatedDays ?? 0,
                                    'used_days' => null,
                                    'created_by' => $currentUserId,
                                    'updated_by' => null,
                                    'deleted_at' => null,
                                    'restored_at' => null,
                                    'delete_on' => null,
                                    'version' => null,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }
                
                        if (!empty($balanceInsertRows)) {
                            DataService::update($reqSet['system'], 'request_balances', $balanceInsertRows, ['request_type_id' => $currentRequestType,'user_id' => $validated['user_id']]);
                        }
                    }
                    $reloadTable = true;
                    $title = 'Request Type Assigned';
                    $message = 'Request type assigned successfully.';
                    $result = ['status' => true];
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
                $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [$reqSet['act'] => $reqSet['id']], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated LeaveManagement entity data based on validated input.
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
            $message = 'LeaveManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'LeaveManagement_entities':
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
                    $message = 'LeaveManagement entities configuration updated successfully.';
                    break;

                case 'business_leave_types':
                    $validator = Validator::make($request->all(), [
                        'type_category' => 'nullable|in:leave,request',
                        'max_days_per_year' => 'nullable|integer|min:0|max:365',
                        'default_duration_minutes' => 'nullable|integer|min:0|max:1440',
                        'affect_attendance' => 'nullable|in:ignore_deduction,deduct_from_working_hours,adjust_shift',
                        'is_active' => 'nullable|in:0,1',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = array_filter($validator->validated());

                    if (empty($validated)) {
                        return ResponseHelper::moduleError('No Data', 'No fields provided for update.');
                    }

                    $reloadTable = true;
                    $title = 'Request Types Updated';
                    $message = 'Request types updated successfully.';
                    break;

                case 'business_leave_requests':
                    $validator = Validator::make($request->all(), [
                        'request_type' => 'nullable|in:leave,general',
                        'request_type_name' => 'nullable|string|max:100',
                        'start_date' => 'nullable|date',
                        'end_date' => 'nullable|date|after_or_equal:start_date',
                        'start_time' => 'nullable',
                        'end_time' => 'nullable',
                        'reason' => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = array_filter($validator->validated());

                    if (empty($validated)) {
                        return ResponseHelper::moduleError('No Data', 'No fields provided for update.');
                    }

                    // Process datetime fields if date/time fields are being updated
                    if (isset($validated['start_date']) || isset($validated['end_date']) || isset($validated['start_time']) || isset($validated['end_time'])) {
                        // Get current record data to process datetime updates
                        $currentRecords = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]]], 'all');
                        if ($currentRecords['status'] && count($currentRecords['data']) > 0) {
                            foreach ($currentRecords['data'] as $record) {
                                $requestType = $record['request_type'] ?? 'leave';

                                if ($requestType === 'leave') {
                                    // For leave requests, process date fields
                                    if (isset($validated['start_date']) || isset($validated['end_date'])) {
                                        $startDate = isset($validated['start_date']) ? $validated['start_date'] : \Carbon\Carbon::parse($record['start_datetime'])->format('Y-m-d');
                                        $endDate = isset($validated['end_date']) ? $validated['end_date'] : \Carbon\Carbon::parse($record['end_datetime'])->format('Y-m-d');

                                        $startDateObj = \Carbon\Carbon::parse($startDate);
                                        $endDateObj = \Carbon\Carbon::parse($endDate);
                                        $totalDays = $startDateObj->diffInDays($endDateObj) + 1;

                                        $updateData = [
                                            'start_datetime' => $startDateObj->startOfDay()->format('Y-m-d H:i:s'),
                                            'end_datetime' => $endDateObj->endOfDay()->format('Y-m-d H:i:s'),
                                            'total_days' => (int) $totalDays,
                                            'duration_minutes' => 0
                                        ];

                                        Data::update(
                                            $reqSet['system'],
                                            $reqSet['table'],
                                            $updateData,
                                            [$reqSet['act'] => $record[$reqSet['act']]],
                                            $reqSet['key']
                                        );
                                    }
                                } else {
                                    // For general requests, process time fields
                                    if (isset($validated['start_time']) || isset($validated['end_time'])) {
                                        $today = \Carbon\Carbon::today();
                                        $startTime = isset($validated['start_time']) ? $validated['start_time'] : \Carbon\Carbon::parse($record['start_datetime'])->format('H:i');
                                        $endTime = isset($validated['end_time']) ? $validated['end_time'] : \Carbon\Carbon::parse($record['end_datetime'])->format('H:i');

                                        $startDateTime = $today->copy()->setTimeFromTimeString($startTime);
                                        $endDateTime = $today->copy()->setTimeFromTimeString($endTime);
                                        $durationMinutes = $startDateTime->diffInMinutes($endDateTime);

                                        $updateData = [
                                            'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
                                            'end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
                                            'total_days' => null,
                                            'duration_minutes' => $durationMinutes
                                        ];

                                        Data::update(
                                            $reqSet['system'],
                                            $reqSet['table'],
                                            $updateData,
                                            [$reqSet['act'] => $record[$reqSet['act']]],
                                            $reqSet['key']
                                        );
                                    }
                                }
                            }
                        }

                        // Remove date/time fields from validated as they're processed above
                        unset($validated['start_date'], $validated['end_date'], $validated['start_time'], $validated['end_time']);
                    }

                    $reloadTable = true;
                    $title = 'Requests Updated';
                    $message = 'Requests updated successfully.';
                    break;

                case 'business_leave_balances':
                    $validator = Validator::make($request->all(), [
                        'allocated_days' => 'nullable|numeric|min:0|max:365',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = array_filter($validator->validated());

                    if (empty($validated)) {
                        return ResponseHelper::moduleError('No Data', 'No fields provided for update.');
                    }

                    $reloadTable = true;
                    $title = 'Leave Balances Updated';
                    $message = 'Leave balances updated successfully.';
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
                $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}