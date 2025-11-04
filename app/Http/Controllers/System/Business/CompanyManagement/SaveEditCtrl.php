<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving updated CompanyManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated CompanyManagement entity data based on validated input.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'CompanyManagement record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'business_companies':
                    if($request->form_type === 'changelogo'){
                       $validator = Validator::make($request->all(), [
                            'logo' => 'nullable|file|image|max:2048'
                        ]);
                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }
                        $validated = $validator->validated();
                        $fileId = null;
                        if ($request->hasFile('logo')) {
                            $folderKey = 'business_company_profile';
                            $fileResult = FileManager::saveFile(
                                $request,
                                $folderKey,
                                'logo',
                                'Company Profile',
                                Skeleton::authUser()->business_id,
                                false
                            );

                            if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                                $fileId = $fileResult['data']['file_id'] ?? null;
                            }
                        }
                        if ($fileId !== null) {
                            $validated['logo'] = $fileId;
                        }
                    }
                    elseif($request->form_type === 'changebanner'){
                       $validator = Validator::make($request->all(), [
                            'banner' => 'nullable|file|image|max:2048'
                        ]);
                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }
                        $validated = $validator->validated();
                        $fileId = null;
                        if ($request->hasFile('banner')) {
                            $folderKey = 'business_company_profile';
                            $fileResult = FileManager::saveFile(
                                $request,
                                $folderKey,
                                'banner',
                                'Company Profile',
                                Skeleton::authUser()->business_id,
                                false
                            );

                            if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                                $fileId = $fileResult['data']['file_id'] ?? null;
                            }
                        }

                        if ($fileId !== null) {
                            $validated['banner'] = $fileId;
                        }
                    }
                    elseif($request->form_type === 'editdetails'){
                       $validator = Validator::make($request->all(), [
                            'name' => 'required|string|max:150',
                            'legal_name' => 'nullable|string|max:150',
                            'industry' => 'nullable|string|max:100',
                            'type' => 'nullable|string|max:50',
                            'email' => 'nullable|email|max:150',
                            'phone' => 'nullable|string|max:50',
                            'website' => 'nullable|url|max:150',
                            'is_active' => 'required|in:0,1',
                        ]);
                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }
                        $validated = $validator->validated();
                    }
                    elseif($request->form_type === 'editaddress'){
                       $validator = Validator::make($request->all(), [
                            'address_line1' => 'nullable|string|max:150',
                            'address_line2' => 'nullable|string|max:150',
                            'city' => 'nullable|string|max:100',
                            'state' => 'nullable|string|max:100',
                            'country' => 'nullable|string|max:100',
                            'pincode' => 'nullable|string|max:20',
                        ]);
                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }
                        $validated = $validator->validated();
                    }else if ($request->form_type === 'sociallinks') {
                        $allowedPlatforms = ['linkedin', 'github', 'youtube', 'facebook', 'instagram', 'x'];
                        $socialLinks = [];
                        foreach ($allowedPlatforms as $platform) {
                            $inputKey = $platform . '_url';
                            $url = trim($request->input($inputKey));
                            if (!empty($url)) {
                                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                                    return response()->json([
                                        'status' => 'error',
                                        'message' => "Invalid URL for {$platform}.",
                                    ], 422);
                                }
                                $socialLinks[$platform] = $url;
                            }
                        }
                        $validated = [
                            'social_links' => json_encode($socialLinks),
                        ];
                        $reloadPage = true;
                        $title = 'Social Links Updated';
                        $message = 'Your social links have been updated successfully.';
                        
                    } 
                    else{
                        return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
                    }
                    $reloadPage = true;
                    $title = 'Company Updated';
                    $message = 'Company information updated successfully.';
                    break;

                case 'business_company_holidays':
                    $validator = Validator::make($request->all(), [
                        'images'          => 'nullable|file|image|max:2048',
                        'color'           => 'nullable|string',
                        'text_color'      => 'nullable|string',
                        'name'            => 'required|string|max:150',
                        'description'     => 'nullable|string',
                        'start_date'      => 'nullable|date',
                        'end_date'        => 'nullable|date|after_or_equal:start_date',
                        'recurring_type'  => 'required|in:none,weekly,monthly,yearly',
                        'recurring_day'   => 'nullable|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
                        'recurring_week'  => 'nullable|in:all,first,second,third,fourth,last',
                        'is_active'       => 'required|in:0,1',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    // ✅ Check company exists
                    $companyExists = Data::fetch($reqSet['system'], 'companies', [
                        'company_id' => $request->input('company_id')
                    ]);

                    if (!$companyExists['status'] || empty($companyExists['data'])) {
                        return ResponseHelper::moduleError('Invalid Company', 'Company ID does not exist. Please select a valid company.');
                    }

                    $validated = $validator->validated();
                    $validated['holiday_id'] = Random::unique(7, 'HOL');

                    // ✅ Handle holiday photo upload using FileManager
                    $fileId = null;
                    if ($request->hasFile('image')) {
                        $folderKey = 'business_holidays';
                        $fileResult = FileManager::saveFile(
                            $request,
                            $folderKey,
                            'image',
                            'Holidays',
                            Skeleton::authUser()->business_id,
                            false
                        );

                        if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                            $fileId = $fileResult['data']['file_id'] ?? null;
                        }
                    }

                    if ($fileId !== null) {
                        $validated['image'] = $fileId;
                    }

                    $reloadPage = true;
                    $title = 'Holiday Updated';
                    $message = 'Company holiday Updated successfully to the system.';
                    break;
                case 'business_company_policies':
                    $validator = Validator::make($request->all(), [
                        'sno'             => 'required|string|max:30',
                        'company_id'      => 'required|string|max:30',
                        'name'            => 'required|string|max:150',
                        'description'     => 'nullable|string|max:500',
                        'category'        => 'nullable|string|max:100',
                        'effective_date'  => 'required|date',
                        'expiry_date'     => 'nullable|date|after_or_equal:effective_date',
                        'version'         => 'nullable|string|max:20',
                        'is_active'       => 'required|in:0,1',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadCard = true;
                    $title = 'Policy Updated';
                    $message = 'Company policy updated successfully to the Company.';
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
     * Saves bulk updated CompanyManagement entity data based on validated input.
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
            $message = 'CompanyManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'CompanyManagement_entities':
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
                    $message = 'CompanyManagement entities configuration updated successfully.';
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
            $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}