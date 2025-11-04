<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Business;
use App\Facades\CentralDB;
use App\Facades\Data;
use App\Facades\FileManager;
use App\Facades\Random;
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
 * Controller for saving new BusinessManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new BusinessManagement entity data based on validated input.
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
            $message = 'BusinessManagement record added successfully.';
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
                    // Assign a unique device ID
                    $validated['device_id'] = Random::unique(9, 'DEV');
                    $devices = Data::fetch($validated['business_id'], 'devices', ['serial_number'=> $validated['serial_number']], '1');
                    if(!empty($devices['data'])){
                        return ResponseHelper::moduleError('Validation Error', 'This Serial Number already exist');
                    }
                    $deviceData=[
                        'device_id' => $validated['device_id'],
                        'serial_number' => $validated['serial_number'],
                        'name' => $validated['name'],
                        'ip' => $validated['ip'],
                        'port' => $validated['port'],
                        'is_approved' => $validated['is_approved'],
                        'is_active' => $validated['is_active'],
                    ];
                    $businessDevice=Data::insert($validated['business_id'], 'devices', $deviceData, $reqSet['key']);
                    if(!$businessDevice['status']){
                        return ResponseHelper::moduleError('Internal Server Error', 'Please try again');
                    }
                    $reloadTable = true;
                    $title = 'Device Added';
                    $message = 'Business device created successfully.';
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
                    $validated['module_price_id'] = Random::unique(4, 'BMP');
                    $validated['dependent_module_ids'] = isset($validated['dependent_module_ids']) ? implode(',', $validated['dependent_module_ids']) : null;
                    $reloadCard = true;
                    $title = 'Module Pricing Added';
                    $message = 'Business module pricing created successfully.';
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
                    $validated['plan_id'] = Random::unique(9, 'PLN');
                    $reloadTable = true;
                    $title = 'Plan Added';
                    $message = 'Business plan created successfully.';
                    break;
                case 'central_businesses':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'legal_name' => 'required|string|max:100',
                        'registration_no' => 'required|string|max:50',
                        'tax_id' => 'required|string|max:50',
                        'license_key' => 'required|string|max:255',
                        'industry' => 'required|string|max:100',
                        'type' => 'nullable|string|max:100',
                        'founded_date' => 'required|date',
                        'business_size' => 'nullable|in:small,medium,large',
                        'logo' => 'nullable|string|max:255',
                        'email' => 'required|email|max:100',
                        'phone' => 'required|string|max:20',
                        'hr_contact_email' => 'required|email|max:100',
                        'hr_contact_phone' => 'required|string|max:20',
                        'website' => 'nullable|string|max:255',
                        'address' => 'required|string|max:1000',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $system = Skeleton::getUserSystem();
                    $businessId = Skeleton::authUser()->business_id;
                    $fileId = null;
                    if ($request->hasFile('business_logo')) {
                        $folderKey = $system.'_profiles';
                        $fileResult = FileManager::saveFile($request, $folderKey, 'business_logo', 'business_logo', $businessId, false);
                        if (! $fileResult['status']) {
                            throw new Exception('Failed to upload profile photo: '.($fileResult['message'] ?? 'Unknown error'), 400);
                        }
                        $fileId = $fileResult['data']['file_id'];
                    }
                    $validated = $validator->validated();
                    $validated['business_logo'] = $fileId;
                    $validated['business_id'] = Random::unique(4, 'BIZ');
                    $reloadPage = 'business_id';
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
                    $validated['onboarding_id'] = Random::uniqueId('ONB', 7);
                    $validated['admin_password'] = Hash::make($validated['admin_password']);
                    $reloadTable = true;
                    $reloadCard = true;
                    $title = 'Success';
                    $message = 'Onborded Successfully';
                    break;
                case 'central_convert_to_business':
                    try {
                        $validator = Validator::make($request->all(), [
                            'onboarding_id' => 'required|string',
                            'plan_id' => 'nullable|string|max:100',
                            'start_date' => 'nullable|date',
                            'end_date' => 'nullable|date',
                            'status' => 'nullable|string|in:active,inactive,cancelled,expired',
                            'auto_renew' => 'nullable|in:0,1',
                        ]);
                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }
                        $validated = $validator->validated();
                        $validated['subscription_id'] = Random::uniqueId('SUB', 6);
                        $onboardingId = $validated['onboarding_id'];
                        $onboardingData = CentralDB::table('business_onboarding')
                            ->where('onboarding_id', $onboardingId)
                            ->first();
                        if (! $onboardingData) {
                            return ResponseHelper::moduleError('Not Found', 'No onboarding record found for the given ID.');
                        }
                        // Prepare data for businesses table
                        $businessData = [
                            'business_id' => $onboardingData->device_code,
                            'onboarding_id' => $onboardingId,
                            'subscription_id' => $validated['subscription_id'],
                            'admin_first_name' => $onboardingData->admin_first_name,
                            'admin_last_name' => $onboardingData->admin_last_name,
                            'admin_email' => $onboardingData->admin_email,
                            'admin_phone' => $onboardingData->admin_phone,
                            'admin_password' => $onboardingData->admin_password,
                            'name' => $onboardingData->name,
                            'legal_name' => $onboardingData->legal_name,
                            'industry' => $onboardingData->industry,
                            'type' => $onboardingData->business_type,
                            'registration_no' => $onboardingData->registration_no,
                            'tax_id' => $onboardingData->tax_id,
                            'email' => $onboardingData->email,
                            'phone' => $onboardingData->phone,
                            'website' => $onboardingData->website,
                            'address_line1' => $onboardingData->address_line1,
                            'address_line2' => $onboardingData->address_line2,
                            'city' => $onboardingData->city,
                            'state' => $onboardingData->state,
                            'country' => $onboardingData->country,
                            'pincode' => $onboardingData->pincode,
                            'business_size' => $onboardingData->business_size,
                            'no_of_employees' => $onboardingData->no_of_employees,
                            'hr_contact_email' => $onboardingData->hr_contact_email,
                            'hr_contact_phone' => $onboardingData->hr_contact_phone,
                            'reseller_id' => $onboardingData->reseller_id,
                            'status' => 'active',
                            'is_active' => 1,
                            'created_by' => Skeleton::authUser()->user_id,
                            'created_at' => now(),
                        ];
                        // Insert into businesses table
                        $result = Data::insert('central', 'businesses', $businessData, $reqSet['key']);
                        if (! $result['status']) {
                            throw new Exception('Failed to create subscription record.');
                        }
                        // Insert into business_subscriptions table
                        $validated['business_id'] = $onboardingData->device_code;
                        $validated['created_at'] = now();
                        $validated['updated_at'] = now();
                        unset($validated['onboarding_id']);
                        $subscriptionResult = Data::insert('central', 'business_subscriptions', $validated, $reqSet['key']);
                        if (! $subscriptionResult['status']) {
                            throw new Exception('Failed to create subscription record.');
                        }
                        $update = ['is_converted' => 1];
                        $affected = Data::update('central', 'business_onboarding', $update, ['onboarding_id' => ['operator' => '=', 'value' => $onboardingId]], $reqSet['key']);
                        if (! $affected['status']) {
                            throw new Exception('Failed to update Onbording Business record.');
                        }
                        $reqSet['token'] = Skeleton::skeletonToken('central_onboard_business');

                        return response()->json([
                            'status' => true,
                            'reload_page' => true,
                            'hold_popup' => $holdPopup,
                            'token' => $reqSet['token'],
                            'affected' => $result['data']['id'],
                            'title' => $title,
                            'message' => $message,
                        ]);
                    } catch (\Exception $e) {
                        return ResponseHelper::moduleError('Operation Failed', 'An error occurred while processing the request: '.$e->getMessage());
                    }
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
                $result = Data::insert('central', $reqSet['table'], $validated, $reqSet['key']);
            }

            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
