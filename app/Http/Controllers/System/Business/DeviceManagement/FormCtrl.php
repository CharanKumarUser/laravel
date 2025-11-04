<?php
namespace App\Http\Controllers\System\Business\DeviceManagement;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new DeviceManagement entities.
 */
class FormCtrl extends Controller
{
    /**
     * Saves new DeviceManagement entity data based on validated input.
     *
     * @param Request $request HTTP request with form data and token
     * @return JsonResponse Success or error message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'DeviceManagement data saved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'DeviceManagement_entities':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'type' => 'required|in:data,unique,select,other',
                        'status' => 'required|in:active,inactive',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $validated['entity_id'] = Random::unique(6, 'ENT');
                    $title = 'Entity Added';
                    $message = 'DeviceManagement entity configuration added successfully.';
                    break;
                case 'business_device_settings':
                    $rules = [
                        'device_id' => 'required|string|max:30',
                        'trans_stamp' => 'nullable|string|max:50',
                        'attlog_stamp' => 'nullable|string|max:50',
                        'op_stamp' => 'nullable|string|max:50',
                        'operlog_stamp' => 'nullable|string|max:50',
                        'photo_stamp' => 'nullable|string|max:50',
                        'attphoto_stamp' => 'nullable|string|max:50',
                        'error_delay' => 'nullable|integer|min:0',
                        'delay' => 'nullable|integer|min:0',
                        'trans_times' => 'nullable|string|max:50',
                        'trans_interval' => 'nullable|integer|min:0',
                        'trans_flag' => 'nullable|string|max:20',
                        'realtime' => 'nullable|in:0,1',
                        'timeout' => 'nullable|integer|min:0',
                        'timezone' => 'nullable|integer',
                        'encrypt' => 'nullable|in:0,1',
                        'memory_alert' => 'nullable|in:0,1',
                        'memory_threshold' => 'nullable|integer|min:0',
                        'memory_interval' => 'nullable|integer|min:0',
                        'attlog_alert' => 'nullable|in:0,1',
                        'attlog_threshold' => 'nullable|integer|min:0',
                        'attlog_interval' => 'nullable|integer|min:0',
                        'auto_remove_logs' => 'nullable|in:0,1',
                        'auto_remove_age' => 'nullable|integer|min:0',
                        'auto_remove_threshold' => 'nullable|integer|min:0',
                    ];
                    $input = $request->all();
                    // Normalize checkboxes to 0/1
                    foreach (['realtime','encrypt','memory_alert','attlog_alert','auto_remove_logs'] as $flagField) {
                        $input[$flagField] = $request->has($flagField) ? '1' : '0';
                    }
                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $title = 'Settings Saved';
                    $message = 'Device settings saved successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
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
            // Insert data
            $result = Data::insert($reqSet['system'], $reqSet['table'], $validated);
            }
            // Generate response
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}