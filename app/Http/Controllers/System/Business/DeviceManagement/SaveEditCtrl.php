<?php
namespace App\Http\Controllers\System\Business\DeviceManagement;
use App\Facades\BusinessDB;
use App\Facades\CentralDB;
use App\Facades\Data;
use App\Facades\Random;
use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
/**
 * Controller for saving updated DeviceManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated DeviceManagement entity data based on validated input.
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
            if (! isset($reqSet['key']) || ! isset($reqSet['act']) || ! isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'DeviceManagement record updated successfully.';
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
                    // Check for existing device in either DB
                    $centralDevice = CentralDB::table('business_devices')->where('serial_number', $validated['serial_number'])->first();
                    $businessDevice = BusinessDB::table('devices')->where('serial_number', $validated['serial_number'])->first();
                    if ($centralDevice && $businessDevice) {
                        return ResponseHelper::moduleError('Duplicate Entry', 'The device already exists in the system', 409);
                    }
                    // Determine device_id
                    if ($centralDevice) {
                        $validated['device_id'] = $centralDevice->device_id;
                    } elseif ($businessDevice) {
                        $validated['device_id'] = $businessDevice->device_id;
                    } else {
                        $validated['device_id'] = 'DEV'.Random::unique(9, 'DEV');
                    }
                    // Add audit fields
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                    $validated['updated_at'] = now();
                    $validated['created_by'] = $validated['created_at'] = now();
                    $upsertKey = $reqSet['act'] ?? 'device_id';
                    $affectedId = $validated['device_id'];
                    // Upsert into CentralDB
                    CentralDB::transaction(function () use ($validated, $upsertKey) {
                        $data = $validated;
                        $data['business_id'] = Skeleton::authUser()->business_id;
                        unset($data['company_id'], $data['location']);
                        CentralDB::table('business_devices')->upsert(
                            [$data],
                            [$upsertKey],
                            ['serial_number', 'name', 'ip', 'port', 'mac_address', 'is_active', 'updated_at', 'created_by', 'business_id']
                        );
                    });
                    // Upsert into BusinessDB
                    BusinessDB::transaction(function () use ($validated, $upsertKey) {
                        $data = $validated;
                        BusinessDB::table('devices')->upsert(
                            [$data],
                            [$upsertKey],
                            ['serial_number', 'name', 'location', 'ip', 'port', 'mac_address', 'is_active', 'updated_at', 'created_by']
                        );
                    });
                    $result = ['status' => true, 'id' => $affectedId];
                    $title = 'Device Saved';
                    $message = 'Device added or updated successfully.';
                    $store = false;
                    $reloadTable = true;
                    $title = 'Device Updated';
                    $message = 'Device updated successfully to the system.';
                    break;
                case 'business_device_users':
                    $validator = Validator::make($request->all(), [
                        'device_user_id' => 'required|string|max:50',
                        'device_id' => 'required|string|max:30',
                        'name' => 'required|string|max:100',
                        'privilege' => 'nullable|integer|min:0',
                        'password' => 'required|string|max:50',
                        'card_number' => 'nullable|string|max:50',
                        'group_id' => 'nullable|integer|min:1',
                        'time_zone' => 'nullable|string|max:16',
                        'expires' => 'nullable|string|max:11',
                        'start_datetime' => 'nullable|string|max:50',
                        'end_datetime' => 'nullable|string|max:50',
                        'valid_count' => 'nullable|integer|min:0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Disable meta updated_by for device_users table (column doesn't exist)
                    $byMeta = false;
                    $reloadTable = true;
                    $title = 'Device User Updated';
                    $message = 'Device user updated successfully.';
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
                $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [['column' => $reqSet['act'], 'value' => $reqSet['id']]], $reqSet['key']);
            }
            // Normalize response
            $status = is_array($result) ? ($result['status'] ?? false) : (bool) $result;
            $affectedRows = is_array($result) ? ($result['data']['affected_rows'] ?? 0) : (int) $result;
            $finalMessage = $status && $affectedRows > 0 ? $message : ($status ? 'No changes were made.' : 'Update failed.');
            $finalTitle = $status && $affectedRows > 0 ? $title : ($status ? 'No Changes' : 'Failed');
            return response()->json([
                'status' => $status,
                'reload_table' => $reloadTable,
                'reload_card' => $reloadCard,
                'reload_page' => $reloadPage,
                'hold_popup' => $holdPopup,
                'token' => $reqSet['token'],
                'affected' => $affectedRows,
                'title' => $finalTitle,
                'message' => $finalMessage,
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated DeviceManagement entity data based on validated input.
     *
     * @param  Request  $request  HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function bulk(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (! $token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['key']) || ! isset($reqSet['act'])) {
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
            $message = 'DeviceManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'DeviceManagement_entities':
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
                    $message = 'DeviceManagement entities configuration updated successfully.';
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
