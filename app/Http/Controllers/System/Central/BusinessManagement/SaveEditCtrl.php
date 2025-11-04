<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Data;
use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for saving updated BusinessManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated BusinessManagement entity data based on validated input.
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
            $message = 'BusinessManagement record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'central_business_devices':
                    $validator = Validator::make($request->all(), [
                        'business_id' => 'required|string|max:30',
                        'serial_number' => 'required|string|max:100',
                        'name' => 'required|string|max:150',
                        'ip' => 'required|ip',
                        'port' => 'required|integer|min:1|max:65535',
                        'is_approved' => 'required|in:1,0',
                        'is_active' => 'required|in:1,0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $dataSet = Data::fetch($reqSet['system'], $reqSet['table'], [$reqSet['act'] => $reqSet['id']]);
                    $dataItem = $dataSet['data'][0] ?? null;
                    $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
                    if ($data->is_approved != $validated['is_approved']) {
                        $result = Data::update($data->business_id, 'devices', ['is_approved' => $validated['is_approved']], ['device_id' => $data->device_id], $reqSet['key']);
                    }
                    $title = 'Device Updated';
                    $message = 'Business device updated successfully.';
                    break;
                case 'central_business_module_pricings':
                    $validator = Validator::make($request->all(), [
                        'module_id' => 'required|string|max:30',
                        'dependent_module_ids' => 'nullable|array',
                        'price' => 'required|numeric|min:0',
                        'description' => 'required|string',
                        'is_approved' => 'nullable|in:1,0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $moduleData = Data::get('central', 'skeleton_modules', ['where' => ['module_id' => $validated['module_id']]], '1');
                    $validated['module_name'] = $moduleData['data'][0]['name'];
                    $validated['dependent_module_ids'] = isset($validated['dependent_module_ids']) ? implode(',', $validated['dependent_module_ids']) : null;
                    $reloadCard = true;
                    $title = 'Module pricing updated.';
                    $message = 'Business module pricing updated successfully.';
                    break;
                case 'central_business_plans':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:150',
                        'icon' => 'nullable|string|max:255',
                        'duration_type' => 'required|string',
                        'duration_value' => 'required|numeric|min:0',
                        'type' => 'required|in:fixed,custom',
                        'module_pricing_ids' => 'required|string',
                        'amount' => 'required|numeric|min:0',
                        'discount' => 'nullable|numeric|min:0|max:100',
                        'total_amount' => 'nullable|numeric|min:0',
                        'description' => 'nullable|string',
                        'features' => 'nullable|string',
                        'tax' => 'nullable|string',
                        'display_order' => 'nullable|integer|min:0',
                        'landing_visibility' => 'required|in:1,0',
                        'is_approved' => 'required|in:1,0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $duration = [$validated['duration_type'] => $validated['duration_value']];
                    $validated['duration'] = json_encode($duration);
                    unset($validated['duration_type'], $validated['duration_value']);
                    $reloadTable = true;
                    $title = 'Plan Added';
                    $message = 'Business plan created successfully.';
                    break;
                case 'central_onboard_business':
                    $validator = Validator::make($request->all(), [
                        // Admin Details
                        'admin_first_name' => 'required|string|max:100',
                        'admin_last_name' => 'nullable|string|max:100',
                        'admin_email' => 'required|email|max:100',
                        'admin_phone' => 'nullable|string|max:20',
                        'admin_password' => 'required|string|max:255',
                        // Organization Details
                        'name' => 'required|string|max:100',
                        'legal_name' => 'required|string|max:100',
                        'email' => 'required|email|max:100',
                        'phone' => 'nullable|string|max:15',
                        'plan_id' => 'nullable|string',
                        'industry' => 'nullable|string|max:50',
                        'business_size' => 'nullable|string|in:micro,small,medium,large',
                        'business_type' => 'nullable|string|max:50',
                        'no_of_employees' => 'nullable|integer|min:0',
                        'registration_no' => 'nullable|string|max:50',
                        'tax_id' => 'nullable|string|max:50',
                        'website' => 'nullable|url|max:255',
                        // Business Address
                        'address_line1' => 'nullable|string|max:255',
                        'address_line2' => 'nullable|string|max:255',
                        'city' => 'nullable|string|max:100',
                        'state' => 'nullable|string|max:100',
                        'country' => 'nullable|string|max:100',
                        'pincode' => 'nullable|string|max:10',
                        // HR Contact
                        'hr_contact_email' => 'nullable|email|max:100',
                        'hr_contact_phone' => 'nullable|string|max:15',
                        // Billing & Status
                        'billing_status' => 'nullable|string',
                        'payment_method' => 'nullable|string',
                        'payment_status' => 'nullable|string',
                        'paid_on' => 'nullable|date',
                        'onboarding_stage' => 'required|string',
                        'status' => 'nullable|string',
                        'device_count' => 'nullable|integer|min:0',
                        'device_code' => 'nullable|string|max:20',
                        'device_check' => 'nullable|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['admin_password'] = Hash::make($validated['admin_password']);
                    $reloadCard = true;
                    $title = 'Success';
                    $message = 'Onborded Successfully';
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
                $result = Data::update('central', $reqSet['table'], $validated, [$reqSet['act'] => $reqSet['id']], $reqSet['key']);
            }

            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }

    /**
     * Saves bulk updated BusinessManagement entity data based on validated input.
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
            $message = 'BusinessManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'BusinessManagement_entities':
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
                    $message = 'BusinessManagement entities configuration updated successfully.';
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
                $result = Data::update('central', $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
            }

            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
