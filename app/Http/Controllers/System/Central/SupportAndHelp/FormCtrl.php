<?php
namespace App\Http\Controllers\System\Central\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new SupportAndHelp entities.
 */
class FormCtrl extends Controller
{
    /**
     * Saves new SupportAndHelp entity data based on validated input.
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
            Developer::info($reqSet);
            // Initialize variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'SupportAndHelp data saved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'SupportAndHelp_entities':
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
                    $message = 'SupportAndHelp entity configuration added successfully.';
                    break;
                case 'business_support_faqs':

                     $validator = Validator::make($request->all(), [
                        'faq_id'     => 'nullable|string|max:50',
                        'question'   => 'required|string|max:255',
                        'answer'     => 'required|string|max:1000',
                        'category'   => 'required|string|max:100',
                        'tags'       => 'nullable|string|max:255',
                        'is_public'  => 'required|boolean',
                        'is_active'  => 'required|boolean',
                        'company_id' => 'required',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();

                    // Auto-generate faq_id if not provided
                    if (empty($validated['faq_id'])) {
                        $validated['faq_id'] = Random::unique(8, 'FAQ');
                    }

                    $companyId = $request->input('company_id');
                    $company = Data::fetch('business', 'companies', ['company_id' => $companyId], 'business_support_faqs');

                    if (
                        $company && isset($company['status']) && $company['status'] === true &&
                        isset($company['data']) && is_array($company['data']) && count($company['data']) > 0
                    ) {
                        $companyData = $company['data'][0];
                        if (isset($companyData['business_id'])) {
                            $validated['business_id'] = $companyData['business_id'];
                        }
                    }
                    $validated['user_id'] = Skeleton::authUser()->user_id;
                    $validated['company_id'] = $companyId;

                    $reloadTable = true;
                    $title   = 'FAQ Added';
                    $message = 'Support FAQ created successfully.';
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
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] ?? '' : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}