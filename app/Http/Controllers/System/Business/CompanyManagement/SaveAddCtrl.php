<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new CompanyManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new CompanyManagement entity data based on validated input.
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
            $message = 'CompanyManagement record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'CompanyManagement_entities':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|regex:/^[a-z_]{3,100}$/|max:100',
                        'type' => 'required|in:data,unique,select,other',
                        'status' => 'required|in:active,inactive',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['entity_id'] = Random::unique(6, 'ENT');
                    $reloadTable = true;
                    $title = 'Entity Added';
                    $message = 'CompanyManagement entity configuration added successfully.';
                    break;
            case 'business_companies':
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|max:150',
                    'legal_name' => 'nullable|string|max:150',
                    'industry' => 'nullable|string|max:100',
                    'type' => 'nullable|string|max:50',
                    'email' => 'nullable|email|max:150',
                    'phone' => 'nullable|string|max:50',
                    'website' => 'nullable|url|max:150',
                    'address_line1' => 'nullable|string|max:150',
                    'address_line2' => 'nullable|string|max:150',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'country' => 'nullable|string|max:100',
                    'pincode' => 'nullable|string|max:20',
                    'is_active' => 'required|in:0,1',
                ]);
                $validated = $validator->validated();
                $existing = BusinessDB::table('companies')
                    ->where('business_id', Skeleton::authUser()->business_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
                    ->exists();

                if ($existing) {
                    return ResponseHelper::moduleError('Validation Error', 'Company name already exists.');
                }
                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }

                $validated['company_id'] = Random::uniqueId('COM', 5, true);
                $validated['business_id'] = Skeleton::authUser()->business_id;

                // Store the company record
                $result = Data::insert($reqSet['system'], 'companies', $validated, $reqSet['key']);
                if (!$result['status']) {
                    throw new Exception('Failed to create scope user: ' . ($result['message'] ?? 'Unknown error'), 400);
                }
                // Prepare scope data
                $scope = [
                    'sno' => Random::userId(),
                    'scope_id' => $validated['company_id'],
                    'code' => $validated['company_id'],
                    'name' => $validated['name'],
                    'group' => 'company',
                    'parent_id' => $validated['business_id'],
                    'background' =>'#00b4af',
                    'color' =>'#ffffff',
                    'is_active' => $validated['is_active'],
                    'created_by' => Skeleton::authUser()->user_id,
                ];

                // Store the scope record
                $result = Data::insert($reqSet['system'], 'scopes', $scope, $reqSet['key']);
                if (!$result['status']) {
                    throw new Exception('Failed to create scope user: ' . ($result['message'] ?? 'Unknown error'), 400);
                }
                $store = false;
                $reloadPage = true;
                $title = 'Company Added';
                $message = 'Company added successfully to the system.';
            break;

                case 'business_company_holidays':
                    $validator = Validator::make($request->all(), [
                        'images'   => 'nullable|file|image|max:2048',
                        'company_id'      => 'required|string|max:30',
                        'color'           => 'nullable|string',
                        'text_color'           => 'nullable|string',
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
                        'company_id' => $reqSet['id']
                    ]);

                    if (!$companyExists['status'] || empty($companyExists['data'])) {
                        return ResponseHelper::moduleError('Invalid Company', 'Company ID does not exist. Please select a valid company.');
                    }

                    $validated = $validator->validated();
                    $validated['holiday_id'] = Random::unique(7, 'HOL', true);

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

                    if ($fileId) {
                        $validated['image'] = $fileId;
                    }

                    $reloadPage = true;
                    $title = 'Holiday Added';
                    $message = 'Company holiday added successfully to the system.';
                    break;
                case 'business_company_policies':
                $validator = Validator::make($request->all(), [
                    'sno'             => 'required|string|max:30',
                    'company_id'      => 'required|string|max:30',
                    'name'            => 'required|string|max:150',
                    'description'     => 'nullable|string|max:500',
                    'category'        => 'nullable|string|max:100',
                    'effective_date'  => 'nullable|date',
                    'expiry_date'     => 'nullable|date|after_or_equal:effective_date',
                    'version'         => 'nullable|string|max:20',
                    'is_active'       => 'required|in:0,1',
                ]);

                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }
                $validated = $validator->validated();
                $validated['policy_id'] = Random::unique(7, 'POL', true);

                $reloadCard = true;
                $title = 'Policy Added';
                $message = 'Company policy added successfully to the system.';
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
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] ?? '' : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}