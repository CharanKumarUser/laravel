<?php
namespace App\Http\Controllers\System\Central\Developer;
use App\Facades\{Data, Developer, FileManager, Random, Skeleton, Helper};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new developer entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new developer entity data based on validated input.
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
            $byMeta = $timestampMeta = true;
            $reloadTable = $reloadCard = false;
            $validated = [];
            $title = 'Success';
            $message = 'Record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle token configuration creation
                case 'central_skeleton_tokens':
                    // Validate token-related fields
                    $validator = Validator::make($request->all(), [
                        'key' => 'required|string|regex:/^[a-z_]{3,100}$/|max:100',
                        'module' => 'nullable|string|max:100',
                        'system' => 'required|in:central,business,open,lander',
                        'type' => 'required|in:data,unique,select,other',
                        'table' => 'required|string|regex:/^[a-z_]{3,100}$/|max:100',
                        'column' => 'required|string|max:150',
                        'value' => 'required|string|max:150',
                        'act' => 'required|string|max:150',
                        'validate' => 'required|boolean',
                        'actions' => 'nullable|array|in:c,v,e,d'
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['actions'] = isset($validated['actions']) ? implode('', $validated['actions']) : null;
                    $reloadTable = true;
                    $title = 'Token Added';
                    $message = 'Token configuration added successfully.';
                    break;
                case 'central_skeleton_dropdowns':
                    // Validate token-related fields
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|regex:/^[a-z_]{3,100}$/|max:100',
                        'pairs' => 'nullable|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'Dropdown Added';
                    $message = 'Dropdown configuration added successfully.';
                    break;
                case 'central_skeleton_restrictions':
                    // Validate token-related fields
                    $validator = Validator::make($request->all(), [
                        'type' => 'required|string|max:100',
                        'value' => 'nullable|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'Restrcition record Added';
                    $message = 'Restrcition configuration added successfully.';
                break;
                // Handle module configuration creation
                case 'central_skeleton_modules':
                    // Validate module-related fields
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'system' => 'required|in:central,business,open',
                        'in_view' => 'required|in:admin,user,open',
                        'order' => 'required|integer|min:0',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Trim string fields
                    foreach (['name', 'display', 'icon', 'system'] as $field) {
                        if (isset($validated[$field]) && is_string($validated[$field])) {
                            $validated[$field] = trim($validated[$field]);
                        }
                    }
                    $validated['module_id'] = Random::unique(4, 'MOD');
                    $reloadTable = true;
                    $title = 'Module Added';
                    $message = 'Module configuration added successfully.';
                    break;
                // Handle section configuration creation
                case 'central_skeleton_sections':
                    // Validate section-related fields
                    $validator = Validator::make($request->all(), [
                        'module_id' => 'required|string',
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'order' => 'required|integer|min:0',
                        'in_view' => 'required|in:admin,user,open',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Trim string fields
                    foreach (['name', 'display', 'icon', 'system'] as $field) {
                        if (isset($validated[$field]) && is_string($validated[$field])) {
                            $validated[$field] = trim($validated[$field]);
                        }
                    }
                    $validated['section_id'] = Random::unique(4, 'SEC');
                    $reloadTable = true;
                    $title = 'Section Added';
                    $message = 'Section configuration added successfully.';
                    break;
                // Handle item configuration creation
                case 'central_skeleton_items':
                    // Validate item-related fields
                    $validator = Validator::make($request->all(), [
                        'section_id' => 'required|string',
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'order' => 'required|integer|min:0',
                        'in_view' => 'required|in:admin,user,open',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Trim string fields
                    foreach (['name', 'display', 'icon', 'system', 'section_id'] as $field) {
                        if (isset($validated[$field]) && is_string($validated[$field])) {
                            $validated[$field] = trim($validated[$field]);
                        }
                    }
                    $validated['item_id'] = Random::unique(4, 'ITM');
                    $reloadTable = true;
                    $title = 'Item Added';
                    $message = 'Item configuration added successfully.';
                    break;
                // Handle custom permission creation
                case 'central_skeleton_custom_permissions':
                    // Validate custom permission-related fields
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'description' => 'nullable|string|max:255',
                        'is_approved' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['permission_id'] = Random::unique(4, 'PRC');
                    $reloadTable = true;
                    $title = 'Custom Permission Added';
                    $message = 'Custom permission configuration added successfully.';
                    break;
                // Handle folder configuration creation
                case 'central_skeleton_folders':
                    // Validate folder-related fields
                    $validator = Validator::make($request->all(), [
                        'key'              => 'required|string|max:100',
                        'name'             => 'required|string|max:100',
                        'parent_folder_id' => 'nullable|string',
                        'system'           => 'nullable|string|max:100',
                        'is_approved'      => 'nullable|boolean',
                        'description'      => 'nullable|string|max:255',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $normalizedName = strtolower(preg_replace('/\s+/', '-', trim($validated['name'])));
                    $validated['path'] = '\\' . $normalizedName;
                    // Check for circular nesting and build full path if parent exists
                    if (!empty($validated['parent_folder_id']) && $validated['parent_folder_id'] != 'Empty') {
                        if (FileManager::isCircularReference($validated['parent_folder_id'], null)) {
                            return ResponseHelper::moduleError('Validation Error', 'Circular reference detected in folder hierarchy.');
                        }
                        $folderPaths = FileManager::getFolderPaths();
                        if (isset($folderPaths[$validated['parent_folder_id']])) {
                            $validated['path'] = $folderPaths[$validated['parent_folder_id']] . '\\' . $normalizedName;
                        } else {
                            return ResponseHelper::moduleError('Validation Error', 'Invalid parent folder ID.');
                        }
                    }
                    // Generate unique folder ID
                    $validated['folder_id'] = Random::unique(4, 'FLD');
                    // Flags for UI or flow control
                    $reloadTable = true;
                    $title = 'Folder Key Added';
                    $message = 'Folder configuration added successfully.';
                    break;
                // Handle folder permission creation
                case 'central_folder_permissions':
                    // Validate folder permission-related fields
                    $validator = Validator::make($request->all(), [
                        'folder_id' => 'required|string',
                        'permissions' => 'nullable|array',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['permission_type'] = isset($validated['permissions']) ? implode(',', $validated['permissions']) : null;
                    $reloadTable = true;
                    $title = 'Folder Permissions Added';
                    $message = 'Folder permissions added successfully.';
                    break;
                // Handle file extension configuration creation
                case 'central_file_extensions':
                    // Validate file extension-related fields
                    $validator = Validator::make($request->all(), [
                        'extension' => 'required|string|max:50',
                        'icon_path' => 'required|string|max:100',
                        'mime_type' => 'nullable|string|max:100',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['extension_id'] = Random::unique(4, 'EXT');
                    $reloadTable = true;
                    $title = 'File Extension Added';
                    $message = 'File extension configuration added successfully.';
                    break;
                case 'central_skeleton_templates':
                    // Validate file extension-related fields
                    $validator = Validator::make($request->all(), [
                        'key' => 'required|string|max:50',
                        'type' => 'required|string|max:100',
                        'name' => 'required|string|max:100',
                        'purpose' => 'required|string|max:250',
                        'subject' => 'required|string|max:250',
                        'mailer' => 'required|string|max:250',
                        'from_name' => 'required|string|max:250',
                        'from_address' => 'required|string|max:250',
                        'placeholders' => 'required|string|max:250',
                        'content' => 'required',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Ensure content is stored as a JSON string
                    if (is_array($validated['content']) || is_object($validated['content'])) {
                        $validated['content'] = json_encode($validated['content']);
                    }
                    $validated['content'] = json_encode(json_decode($validated['content'], true));
                    $reloadTable = $reqSet['id'];
                    $title = 'Template Added';
                    $message = 'Template added successfully.';
                    break;
                case 'central_business_schemas':
                    $validator = Validator::make($request->all(), [
                        'module' => 'required|string',
                        'table' => 'required|string|max:100',
                        'operation' => 'required|string|max:100',
                        'schema' => 'required|string',
                        'depends_on_modules' => 'nullable|array',
                        'depends_on_tables' => 'nullable|array',
                        'execution_order' => 'required|integer|min:0',
                        'is_approved' => 'required|boolean',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Trim input strings
                    foreach (['module', 'table', 'operation'] as $field) {
                        if (isset($validated[$field]) && is_string($validated[$field])) {
                            $validated[$field] = trim($validated[$field]);
                        }
                    }
                    $validated['depends_on_modules'] = isset($validated['depends_on_modules']) ? implode(',', $validated['depends_on_modules']) : null;
                    $validated['depends_on_tables'] = isset($validated['depends_on_tables']) ? implode(',', $validated['depends_on_tables']) : null;
                    // Generate schema_id and snapshot
                    $validated['schema_id'] = Random::unique(7, 'BSM');
                    // Ensure SQL schema is stored safely (JSON stringify for consistency)
                    try {
                        $validated['schema'] = trim($validated['schema']);
                        $validated['snap'] = json_encode(['sql' => $validated['schema']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } catch (Exception $e) {
                        return ResponseHelper::moduleError('Invalid schema', 'Failed to encode schema snapshot');
                    }
                    $reloadTable = true;
                    $title = 'Schema Added';
                    $message = 'Schema configuration added successfully.';
                    break;
                // Handle invalid configuration keys
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
            $result = Data::insert('central', $reqSet['table'], $validated, $reqSet['key']);
            // Return response based on creation success
            return response()->json([
                'status' => $result['status'],
                'reload_table' => $reloadTable,
                'reload_card' => $reloadCard,
                'token' => $reqSet['token'],
                'affected' => $result['status'] ? $result['id'] : '-',
                'title' => $result['status'] ? $title : 'Failed',
                'message' => $result['status'] ? $message : $result['message']
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
