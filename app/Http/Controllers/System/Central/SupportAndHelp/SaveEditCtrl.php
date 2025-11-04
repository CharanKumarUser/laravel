<?php
namespace App\Http\Controllers\System\Central\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton, CentralDB, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving updated SupportAndHelp entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated SupportAndHelp entity data based on validated input.
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
            $message = 'SupportAndHelp record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'business_support_tickets':
                $validator = Validator::make($request->all(), [
                    'issue_scope'      => 'nullable|in:Support,Developer',
                    'issue_category'   => 'nullable|in:bug,feature,support',
                    'issue_priority'   => 'nullable|in:Low,Medium,High,Critical',
                    'issue_status'     => 'nullable|in:Open,In Progress,Resolved,Closed,Reopened',
                    'reported_by'      => 'nullable|string|max:100',
                    'assigned_to'      => 'nullable|string|max:100',
                    'resolution_notes' => 'nullable|string|max:1000',
                    'is_active'        => 'nullable|boolean',
                ]);

                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }
                $validated = $validator->validated();
                if ($validated['issue_scope'] == 'External') {
                    $exists = BusinessDB::table('supports')
                        ->where($reqSet['act'], $reqSet['id'])
                        ->exists();
                    if ($exists) {
                        $supportData['issue_status'] = $validated['issue_status'];
                        $result = Data::update('business', $reqSet['table'], $supportData,  [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]], $reqSet['key']);
                        if (!$result['status']) {
                            return ResponseHelper::moduleError('Internal Server Error', 'Please try again later');
                        }
                    }
                }
                $reloadTable = true;
                $reloadCard  = true;
                $title       = 'Support Ticket Updated';
                $message     = 'Ticket has been updated successfully.';
                break;
                case 'business_support_faqs':
                    $validator = Validator::make($request->all(), [
                        'question'   => 'nullable|string|max:255',
                        'answer'     => 'nullable|string|max:1000',
                        'category'   => 'nullable|string|max:100',
                        'tags'       => 'nullable|string|max:255',
                        'is_public'  => 'nullable|boolean',
                        'is_active'  => 'nullable|boolean',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'FAQ Updated';
                    $message = 'Support FAQ updated successfully.';
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
            $result = Data::update($reqSet['system'], $reqSet['table'], $validated,  [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated SupportAndHelp entity data based on validated input.
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
            $message = 'SupportAndHelp records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'SupportAndHelp_entities':
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
                    $message = 'SupportAndHelp entities configuration updated successfully.';
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