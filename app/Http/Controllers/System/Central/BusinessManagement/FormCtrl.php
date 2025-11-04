<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\{Adms, Business, Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Controller for saving new BusinessManagement entities.
 */
class FormCtrl extends Controller
{
    /**
     * Saves new BusinessManagement entity data based on validated input.
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
            $message = 'BusinessManagement data saved successfully.';

            /****************************************************************************************************
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'central_business_devices':
                    switch ($request->input('form_type')) {
                        case 'updateStatus':
                            $validator = Validator::make($request->all(), [
                                'business_id' => 'required|string',
                                'device_id' => 'required|string',
                                'is_active' => 'required|in:0,1'
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $result = Data::update($validated['business_id'], 'devices', ['is_active' => $validated['is_active']], ['device_id' => $validated['device_id']]);
                            if (!$result['status']) {
                                return ResponseHelper::moduleError('Internal Server Error', 'Please try again Later');
                            }
                            $centralDevice = Data::update('central', $reqSet['table'], ['is_active' => $validated['is_active']], ['device_id' => $validated['device_id']]);
                            if (!$centralDevice['status']) {
                                return ResponseHelper::moduleError('Internal Server Error', 'Please try again Later');
                            }
                            $reloadPage = true;
                            $title = $validated['is_active'] ? 'Device Activated' : 'Device Deactivated';
                            $message = $validated['is_active'] ? 'Device Activated successfully.' : 'Device Deactivated successfully.';
                            return response()->json([
                                'status' => $result['status'],
                                'reload_table' => $reloadTable,
                                'reload_card' => $reloadCard,
                                'reload_page' => $reloadPage,
                                'hold_popup' => $holdPopup,
                                'token' => $reqSet['token'],
                                'message' => $result['status'] ? $message : $result['message']
                            ]);
                            break;

                        case 'settings':
                            $validator = Validator::make($request->all(), [
                                'business_id' => 'required|string',
                                'device_id' => 'required|string',
                                'Stamp' => 'required|string',
                                'ATTLOGStamp' => 'required|string',
                                'OpStamp' => 'required|string',
                                'OPERLOGStamp' => 'required|string',
                                'PhotoStamp' => 'required|string',
                                'ATTPHOTOStamp' => 'required|string',
                                'ErrorDelay' => 'required|numeric',
                                'Delay' => 'required|numeric',
                                'TransTimes' => 'required|string',
                                'TransInterval' => 'required|numeric',
                                'TransFlag' => 'required|string',
                                'Realtime' => 'required|numeric',
                                'TimeOut' => 'required|numeric',
                                'TimeZone' => 'required|string',
                                'Encrypt' => 'required|in:0,1',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $settingsJson = json_encode(
                                collect($validated)->except(['business_id', 'device_id'])->toArray()
                            );
                            $result = Data::update($validated['business_id'], 'devices', ['settings_json' => $settingsJson], ['device_id' => $validated['device_id']]);
                            if (!$result['status']) {
                                return ResponseHelper::moduleError('Internal Server Error', 'Please try again Later');
                            }
                            $centralDevice = Data::update('central', $reqSet['table'], ['settings_json' => $settingsJson], ['device_id' => $validated['device_id']]);
                            if (!$centralDevice['status']) {
                                return ResponseHelper::moduleError('Internal Server Error', 'Please try again Later');
                            }
                            $reloadPage = true;
                            $title = 'Device Settings Updated';
                            $message = 'Device Settings Updated successfully.';
                            return response()->json([
                                'status' => $result['status'],
                                'reload_table' => $reloadTable,
                                'reload_card' => $reloadCard,
                                'reload_page' => $reloadPage,
                                'hold_popup' => $holdPopup,
                                'token' => $reqSet['token'],
                                'message' => $result['status'] ? $message : $result['message']
                            ]);
                            break;

                        case 'commands':
                            $command = $request->input('command') ?? '';
                            if ($command == '') {
                                return ResponseHelper::moduleError('Internal Server Error', 'Please try again Later');
                            }
                            try {
                                $result = Adms::command(
                                    serialNumber: $request->input('serial_number'),
                                    businessId: $request->input('business_id'),
                                    name: $command,
                                    params: $request->except(['serial_number', 'business_id', 'command']),
                                    resp: true
                                );
                                return response()->json($result);
                            }catch (InvalidArgumentException $e) {
                                return ResponseHelper::moduleError('Invalid Request', $e->getMessage(), 400);
                            } catch (Exception $e) {
                                return ResponseHelper::moduleError('Internal Server Error', $e->getMessage());
                            }
                            break;

                        default:
                            return ResponseHelper::moduleError('Invalid Form Type', 'The form type is not supported.', 400);
                    }
                    break;

                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             ****************************************************************************************************/

            // Apply metadata if store is true
            if ($store) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
                // Insert data
                $result = Data::insert('central', $reqSet['table'], $validated);
            }

            // Generate response
            return response()->json([
                'status' => $result['status'],
                'reload_table' => $reloadTable,
                'reload_card' => $reloadCard,
                'reload_page' => $reloadPage,
                'hold_popup' => $holdPopup,
                'token' => $reqSet['token'],
                'affected' => $result['status'] ? $result['id'] : '-',
                'title' => $result['status'] ? $title : 'Failed',
                'message' => $result['status'] ? $message : $result['message']
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}