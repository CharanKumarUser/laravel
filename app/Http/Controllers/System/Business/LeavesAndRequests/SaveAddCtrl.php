<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{Data, Developer, Random, Skeleton, BusinessDB, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Services\Data\DataService;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};

/**
 * Controller for saving new LeaveManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new LeaveManagement entity data based on validated input.
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
            $message = 'LeaveManagement record added successfully.';
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
                    $validated['consecutive_days'] = $validated['consecutive_days'] ?? 0;
                    $validated['encash_days'] = $validated['encash_days'] ?? 0;
                    $validated['request_type_id'] = Random::unique(6, 'RTP', true);
                    $reloadTable = true;
                    $title = 'Request Type Added';
                    $message = 'Request type added successfully.';
                    break;
            case 'business_assign_request_types':
                Developer::info('entered into save add of business_assign_request_types ');
                $validator = Validator::make($request->all(), [
                    'scope_id' => 'required|string|max:50',
                    'user_id' => 'nullable|array',
                    'user_id.*' => 'string|max:50',
                    'request_type_id' => 'required|array|min:1',
                    'request_type_id.*' => 'string|max:50',
                    'tag_id' => 'required|array|min:1',
                    'tag_id.*' => 'string|max:100',
                    'is_active' => 'required|in:0,1',
                ]);

                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }
                $validated = $validator->validated();
                $userIds = $validated['user_id'] ?? [null];
                $requestTypeIds = $validated['request_type_id'];
                $tagIds = implode(',', $validated['tag_id']);
                $rowsToInsert = [];
                $assignIdMap = [];
                $currentUserId = Skeleton::authUser()->user_id ?? null;
                $now = now();
                foreach ($userIds as $userId) {
                    foreach ($requestTypeIds as $requestTypeId) {
                        $assignId = Random::unique(6, 'ASN', true);
                        $rowsToInsert[] = [
                            'assign_id' => $assignId,
                            'request_type_id' => $requestTypeId,
                            'scope_id' => $validated['scope_id'],
                            'user_id' => $userId,
                            'tag_id' => $tagIds,
                            'is_active' => $validated['is_active'],
                            'created_at' => $now,
                            'created_by' => $currentUserId,
                            'updated_at' => $now
                        ];

                        $assignIdMap["{$userId}_{$requestTypeId}"] = $assignId;
                    }
                }
                if (!empty($rowsToInsert)) {
                    DataService::insert($reqSet['system'], 'assign_request_types', $rowsToInsert, $reqSet['key']);
                }
                if (!empty($requestTypeIds)) {
                    $currentYear = date('Y');
                    $balanceInsertRows = [];
                    foreach ($userIds as $userId) {
                        if (empty($userId)) continue;
                        $userInfo = DataService::fetch(
                            $reqSet['system'],
                            'user_info',
                            [['column' => 'user_id', 'operator' => '=', 'value' => $userId]],
                            true
                        );
                        $hireDate = null;
                        if (!empty($userInfo['data'][0])) {
                            $hireDate = is_object($userInfo['data'][0])
                                ? $userInfo['data'][0]->hire_date ?? null
                                : $userInfo['data'][0]['hire_date'] ?? null;
                        }
                        foreach ($requestTypeIds as $requestTypeId) {
                            $typeResponse = DataService::fetch(
                                $reqSet['system'],
                                'request_types',
                                [['column' => 'request_type_id', 'operator' => '=', 'value' => $requestTypeId]],
                                true
                            );
                            $typeData = $typeResponse['data'][0] ?? null;
                            if (!$typeData) continue;

                            $isProrated = is_object($typeData)
                                ? ($typeData->is_prorated ?? 0)
                                : ($typeData['is_prorated'] ?? 0);

                            $maxDaysPerYear = is_object($typeData)
                                ? ($typeData->max_days_per_year ?? 0)
                                : ($typeData['max_days_per_year'] ?? 0);

                            $allocatedDays = $maxDaysPerYear;
                            if ($isProrated && !empty($hireDate)) {
                                $hire = \Carbon\Carbon::parse($hireDate);
                                $endOfYear = \Carbon\Carbon::parse($currentYear . '-12-31');
                                $remainingDays = $hire->diffInDaysFiltered(fn($date) => true, $endOfYear) + 1;
                                $daysInYear = $hire->isLeapYear() ? 366 : 365;
                                $allocatedDays = round(($remainingDays / $daysInYear) * $maxDaysPerYear);
                            }
                            $assignId = $assignIdMap["{$userId}_{$requestTypeId}"] ?? null;
                            $balanceInsertRows[] = [
                                'request_balance_id' => Random::unique(6, 'RBL', true),
                                'assign_id' => $assignId,
                                'user_id' => $userId,
                                'request_type_id' => $requestTypeId,
                                'year' => $currentYear,
                                'allocated_days' => $allocatedDays,
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
                        DataService::insert($reqSet['system'], 'request_balances', $balanceInsertRows, $reqSet['key']);
                    }
                }
                $reloadTable = true;
                $store = false;
                $title = 'Request Type Assigned';
                $message = 'Request type assigned successfully.';
                $result = ['status' => true];
                break;
                case 'business_requests':
                    $validator = Validator::make($request->all(), [
                        'request_type' => 'required|in:full-day,short-time',
                        'request_type_id' => 'required_if:request_type,leave|nullable|string|max:100',
                        'tag_to' => 'required|string',
                        'subject' => 'required|string|max:2000',
                        'reason' => 'required|string',
                        'start_datetime' => 'nullable|string',
                        'end_datetime' => 'nullable|string',
                        'short_date' => 'nullable|string',
                        'start_time' => 'nullable|string',
                        'end_time' => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();
                    $user =Skeleton::authUser();
                    // 🔹 Handle short-time case: merge date + time into start_datetime/end_datetime
                    if ($validated['request_type'] === 'short-time') {
                        if (!empty($validated['short_date']) && !empty($validated['start_time']) && !empty($validated['end_time'])) {
                            $validated['start_datetime'] = $validated['short_date'] . ' ' . $validated['start_time'];
                            $validated['end_datetime']   = $validated['short_date'] . ' ' . $validated['end_time'];
                        } else {
                            return ResponseHelper::moduleError('Validation Error', 'Date and time fields are required for short-time requests.');
                        }
                    }

                    // 🔹 Generate unique request_id automatically
                    $validated['request_id'] = Random::unique(6, 'REQ');
                    $validated['user_id'] = Skeleton::authUser()->user_id;

                    // 🔹 Insert request log
                    $log = [
                        'request_id' => $validated['request_id'],
                        'action' => 'requested',
                        'action_by' => $validated['user_id'],
                        'action_at' => now(),
                    ];

                    $result = Data::insert($reqSet['system'], 'request_logs', $log, $reqSet['key']);
                    if (!$result['status']) {
                        return ResponseHelper::moduleError('Log Record Failed', 'Internal Server Error');
                    }
                    unset($validated['short_date'],$validated['start_time'],$validated['end_time']);
                    $result = Data::insert($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
                    if (!$result['status']) {
                        return ResponseHelper::moduleError('Log Record Failed', 'Internal Server Error');
                    }
                    //  Notification::user(
                    //     businessId: $user->business_id,
                    //     ids: $validated['tag_to'],
                    //     title: 'New Leave Request Submitted',
                    //     message: 'A new leave request has been submitted for your review.',
                    //     category: 'leave',
                    //     type: 'info',
                    //     priority: 'medium',
                    //     medium: 'app,email',
                    //     html: '<span>Hi <b>::base_users_first_name::</b>, you have received a new <b>leave request</b> from <b>' . $user->username . '</b> for your review and approval.</span>',
                    //     image: Skeleton::authUser()->avatar ?? null,
                    //     target: "Company_{$user->company_id}"
                    //  );
                    $store = false;
                    $reloadPage = true;
                    $title = 'Request Added';
                    $message = 'Request added successfully.';
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
                    $usedDays = 0;

                    if (empty($request->ignore_deduction) && $request->ignore_deduction != 'on') {
                        Developer::info("hello");
                        $approvedLeaves = BusinessDB::table('requests')
                            ->where('user_id', $validated['user_id'])
                            ->where('request_type_id', $validated['request_type_id'])
                            ->whereYear('start_datetime', $validated['year'])
                            ->where('approval_status', 'approved')
                            ->get();

                        foreach ($approvedLeaves as $leave) {
                            $start = \Carbon\Carbon::parse($leave->start_datetime);
                            $end   = \Carbon\Carbon::parse($leave->end_datetime);
                            $usedDays += $start->diffInDays($end) + 1;
                        }
                    }

                    $validated['request_balance_id'] = Random::unique(6, 'LBS');
                    $validated['used_days'] = $usedDays;
                    $reloadTable = true;
                    $title = 'Leave Balance Added';
                    $message = 'Leave balance added successfully.';
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
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}