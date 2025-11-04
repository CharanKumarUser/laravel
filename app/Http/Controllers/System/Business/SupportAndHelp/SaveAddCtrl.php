<?php
namespace App\Http\Controllers\System\Business\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new SupportAndHelp entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new SupportAndHelp entity data based on validated input.
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
            $message = 'SupportAndHelp record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'business_support_tickets':
                case 'business_support_my_tickets':
                    $validator = Validator::make($request->all(), [
                        'attachment_path'=> 'nullable|file|image|max:2048',
                        'issue_category' =>'required|string',
                        'subject'        => 'required|string',
                        'description'    => 'required|string'
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();
                    $fileId = null;
                    if ($request->hasFile('attachment_path')) {
                        $folderKey = 'business_support';
                        $fileResult = FileManager::saveFile(
                            $request,
                            $folderKey,
                            'attachment_path',
                            'Tickekts',
                            Skeleton::authUser()->business_id,
                            false
                        );
                        if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                            $fileId = $fileResult['data']['file_id'] ?? null;
                        }
                    }

                    if ($fileId) {
                        $validated['attachment_path'] = $fileId;
                    }
                    $validated['support_id'] = Random::unique(5, 'TIK', true);
                    $validated['business_id'] = Skeleton::authUser()->business_id;
                    $validated['company_id'] = Skeleton::authUser()->company_id;
                    $validated['user_id'] = Skeleton::authUser()->user_id;
                    $validated['reported_by'] = Skeleton::authUser()->user_id;
                    $reloadPage = true;
                    $title   = 'Support Ticket Added';
                    $message = 'Support ticket Raised successfully.';
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