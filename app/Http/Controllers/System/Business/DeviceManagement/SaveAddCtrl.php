<?php

namespace App\Http\Controllers\System\Business\DeviceManagement;

use App\Facades\{BusinessDB, CentralDB, Data, Developer, Random, Scope, Skeleton, Select};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for saving new DeviceManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new DeviceManagement entity data based on validated input.
     *
     * @param  Request  $request  HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (! $token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'DeviceManagement record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_devices':
                    $validator = Validator::make($request->all(), [
                        'company_id' => 'required|string',
                        'serial_number' => 'nullable|string|max:50',
                        'name' => 'required|string|max:100',
                        'location' => 'nullable|string|max:100',
                        'ip' => 'nullable|ip',
                        'port' => 'nullable|integer|min:1|max:65535',
                        'mac_address' => 'nullable|string|max:17',
                        'is_active' => 'required|in:0,1',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    // Check for existence in both databases
                    $existInCentral = CentralDB::table('business_devices')->where('serial_number', $validated['serial_number'])->exists();
                    $existInBusiness = BusinessDB::table('devices')->where('serial_number', $validated['serial_number'])->exists();
                    if ($existInCentral && $existInBusiness) {
                        return ResponseHelper::moduleError('Duplicate Entry', 'The device already exists in the system', 409);
                    }
                    // Prepare data
                    $validated['device_id'] = 'DEV'.Random::unique(9, 'DEV');
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                    $validated['created_at'] = $validated['updated_at'] = now();
                    $result = [];
                    $affectedId = null;
                    // Insert into CentralDB if not exists
                    if (! $existInCentral) {
                        CentralDB::transaction(function () use ($validated) {
                            $data = $validated;
                            $data['business_id'] = Skeleton::authUser()->business_id;
                            unset($data['company_id'], $data['location']);
                            CentralDB::table('business_devices')->insert($data);
                        });
                        $result = [
                            'status' => true,
                            'id' => $affectedId,
                        ];
                    }
                    // Insert into BusinessDB if not exists
                    if (! $existInBusiness) {
                        BusinessDB::transaction(function () use ($validated, &$affectedId) {
                            $data = $validated;
                            $affectedId = BusinessDB::table('devices')->insertGetId($data);
                        });
                        $result = [
                            'status' => true,
                            'id' => $affectedId,
                        ];
                    }
                    $store = false;
                    $reloadTable = true;
                    $title = 'Device Added';
                    $message = 'Device added successfully to the system.';
                    break;
                case 'business_device_users':
                    $validator = Validator::make($request->all(), [
                        'device_id' => 'required|string|max:30',
                        'user_id' => 'required|string|max:30',
                        'name' => 'required|string|max:100',
                        'password' => 'required|string|max:50',
                        'privilege' => 'nullable|integer|min:0|max:14',
                        'card_number' => 'nullable|string|max:50',
                        'group_id' => 'nullable|integer|min:1',
                        'time_zone' => 'nullable|string|max:16',
                        'expires' => 'nullable|integer|in:0,1',
                        'start_datetime' => 'nullable|date_format:Y-m-d\TH:i',
                        'end_datetime' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:start_datetime',
                        'valid_count' => 'nullable|integer|min:0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $exists = BusinessDB::table('device_users')
                        ->where('device_id', $validated['device_id'])
                        ->where('device_user_id', $validated['user_id'])
                        ->exists();
                    if ($exists) {
                        return ResponseHelper::moduleError(
                            'Duplicate Entry',
                            'This user already exists on the selected device.',
                            409
                        );
                    }
                    $params = [
                        'PIN' => $validated['user_id'],            // map device_id → PIN
                        'Name' => $validated['name'],
                        'Passwd' => $validated['password'],
                        'Pri' => $validated['privilege'] ?? 0,
                        'Card' => $validated['card_number'] ?? '',
                        'Grp' => $validated['group_id'] ?? 1,
                        'TZ' => $validated['time_zone'] ?? '0',
                        'Expires' => $validated['expires'] ?? 0,
                        'StartDatetime' => $validated['start_datetime'] ?? '',
                        'EndDatetime' => $validated['end_datetime'] ?? '',
                        'ValidCount' => $validated['valid_count'] ?? 0,
                    ];
                    $validated = [];
                    $validated = [
                        'command_id' => Random::uniqueId('CMD', 5, true),
                        'device_id' => $request->input('device_id'),
                        'name' => 'ADD USER',
                        'command' => 'DATA USER',
                        'params' => json_encode($params, JSON_UNESCAPED_SLASHES),
                        'status' => 'EXECUTED',
                    ];
                    $reqSet['table'] = 'device_commands';
                    $reloadTable = true;
                    $title = 'Device User Added';
                    $message = 'Device user added successfully to the system.';
                    break;
                case 'business_load_device_users':
                    $validator = Validator::make($request->all(), [
                        'device_id' => 'required|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $validated = [
                        'command_id' => Random::uniqueId('CMD', 5, true),
                        'device_id' => $request->input('device_id'),
                        'name' => 'Load Users',
                        'command' => 'CHECK',
                        'params' => '{}',
                        'status' => 'PENDING',
                    ];
                    $result = Data::upsert($reqSet['system'], $reqSet['table'], $validated, [['column' => 'device_id', 'value' => 'DIV0001']]);
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
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
