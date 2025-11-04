<?php
namespace App\Http\Controllers\System\Central\ScopeManagement;
use App\Facades\{Data, Developer, Random, Scope, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Hash, Validator};
/**
 * Controller for saving new ScopeManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new ScopeManagement entity data based on validated input.
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
                        'sno' => 'required|numeric|unique:scopes,sno',
                        'code' => 'required|string|max:50|unique:scopes,code',
                        'scope_name' => 'required|string|max:100',
                        'group' => 'required|string|max:50',
                        'parent_id' => 'nullable|string|exists:scopes,scope_id',
                        'description' => 'nullable|string|max:255',
                        'background' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                        'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                        'allow_form' => 'required|boolean',
                        'is_active' => 'required|boolean',
                        'scope_schema' => 'nullable|string'
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $scope_id = Random::unique(6, 'SCP');
                    $validated['scope_id'] = $scope_id;
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
                    $store = false;
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                    $validated['created_at'] = $validated['updated_at'] = now();
                    $result = Data::insert(Skeleton::authUser('system'), $reqSet['table'], $validated, $reqSet['key']);
                    $scopes = Scope::getScopePaths('all', null, true);
                    // Refresh scope paths cache
                    $reloadCard = true;
                    $reloadCard = true;
                    $script = "window.skeleton.tree('scope-tree-container', $scopes, '".$validated['scope_id']."', true);window.general.tooltip();";
                    $title = 'Scope Added';
                    $message = 'Scope configuration added successfully.';
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
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
            }
            // Insert data into the database
            if ($store) {
                $result = Data::insert('central', $reqSet['table'], $validated, $reqSet['key']);
            }
            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'script' => $script, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
