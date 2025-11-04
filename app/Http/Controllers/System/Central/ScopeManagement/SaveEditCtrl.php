<?php
namespace App\Http\Controllers\System\Central\ScopeManagement;
use App\Facades\{Data, Developer, Random, Scope, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving updated ScopeManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated ScopeManagement entity data based on validated input.
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
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = $script = false;
            $validated = [];
            $title = 'Success';
            $message = 'ScopeManagement record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_scopes':
                    $validator = Validator::make($request->all(), [
                        'sno' => 'required|numeric',
                        'code' => 'required|string|max:50',
                        'scope_name' => 'required|string|max:100',
                        'group' => 'required|string|max:50',
                        'parent_id' => 'nullable|string|exists:scopes,scope_id',
                        'description' => 'nullable|string|max:255',
                        'background' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                        'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                        'allow_form' => 'required|boolean',
                        'is_active' => 'required|boolean',
                        'scope_schema' => 'nullable|string',
                        'scope_id' => 'required|string'
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    // Handle custom form schema
                    if ($validated['scope_schema']) {
                        $schema = $request->input('scope_schema');
                        if (!empty($schema)) {
                            json_decode($schema); // Validate JSON
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                return ResponseHelper::moduleError('Validation Error', 'Invalid JSON in scope schema.', 422);
                            }
                            $validated['data'] = $validated['snap'] = $validated['schema'] = $schema;
                        }
                    }
                    $validated['name'] = $validated['scope_name'];
                    unset($validated['scope_schema'], $validated['scope_name']);
                    // Set metadata
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                    $validated['updated_at'] = $validated['updated_at'] = now();
                    $result = Data::update(Skeleton::authUser('system'), $reqSet['table'], $validated, ['scope_id' => $validated['scope_id']], $reqSet['token']);
                    $scopes = Scope::getScopePaths('all', null, true);
                    // Refresh scope paths cache
                    $reloadCard = true;
                    $script = "window.skeleton.tree('scope-tree-container', $scopes, '".$validated['scope_id']."', true);window.general.tooltip();";
                    $title = 'Scope Updated';
                    $message = 'Scope configuration updated successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add metadata if required
            if ($byMeta || $timestampMeta) {
                if ($byMeta) {
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['updated_at'] = now();
                }
            }
            // Update data in the database
            if ($store) {
                $result = Data::update('central', $reqSet['table'], $validated,  [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'script' => $script, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'result' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated ScopeManagement entity data based on validated input.
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
            $byMeta = $timestampMeta = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'ScopeManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'ScopeManagement_entities':
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
                    $message = 'ScopeManagement entities configuration updated successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add metadata if required
            if ($byMeta || $timestampMeta) {
                if ($byMeta) {
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['updated_at'] = now();
                }
            }
            // Update data in the database
            $result = Data::update('central', $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'result' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
