<?php
namespace App\Http\Controllers\System\Business\SmartPresence;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving updated SmartPresence entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated SmartPresence entity data based on validated input.
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
            $message = 'SmartPresence record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
               case 'business_smart_geo_location':

                    $validator = Validator::make($request->all(), [
                        'company_id'      => 'required|string',
                        'name'            => 'required|string|max:255',
                        'pin_address'     => 'required|string',
                        'pin_coordinates' => 'required|string',
                        'current_address' => 'required|string',
                        'current_coordinates' => 'required|string',
                        'radius'          => 'required|integer|min:1',
                        'in_radius'       => 'nullable',       
                        'is_active'       => 'required|in:0,1',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['in_radius'] = ($request->in_radius === 'on') ? 1 : 0;
                    $validated['geo_location_id'] = Random::uniqueId('GEO', 5, true);

                    $reloadTable = true;
                    $title = 'Entity Added';
                    $message = 'SmartPresence entity configuration added successfully.';
                    break;
                case 'business_smart_enroll_face':
                    // Validation for enroll form
                    $validator = Validator::make($request->all(), [
                        'user_id'   => 'required|exists:users,user_id',
                        'is_active' => 'required|in:0,1',
                    ]);
                     if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();   
                    $validated['embedding'] = $request->user_face_encoding ?? '';
                    $validated['age'] = $request->user_face_age ?? '';
                    $validated['gender'] = $request->gender ?? '';
                    $validated['emotion'] = $request->user_face_emotion ?? '';
                    $fileId = null;
                    if ($request->hasFile('user_face_capture_file')) {
                        $folderKey = 'business_enroll_face';
                        $fileResult = FileManager::saveFile(
                            $request,
                            $folderKey,
                            'user_face_capture_file',
                            'Enrolls',
                            Skeleton::authUser()->business_id,
                            false
                        );

                        if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                            $fileId = $fileResult['data']['file_id'] ?? null;
                        }
                    }

                    if ($fileId !== null) {
                        $validated['capture'] = $fileId;
                    }

                    $reloadTable = true;
                    $title = 'Entity Added';
                    $message = 'SmartPresence entity configuration added successfully.';

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
     * Saves bulk updated SmartPresence entity data based on validated input.
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
            $message = 'SmartPresence records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'SmartPresence_entities':
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
                    $message = 'SmartPresence entities configuration updated successfully.';
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