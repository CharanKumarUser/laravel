<?php

namespace App\Http\Controllers\System\Central\Developer;

use App\Facades\{FileManager, Data, Developer, Skeleton, Database, Helper};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};

/**
 * Controller for saving updated developer entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated developer entity data based on validated input.
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
            $byMeta = $timestampMeta = true;
            $reloadTable = $reloadCard = false;
            $validated = [];
            $title = 'Success';
            $message = 'Record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle token configuration updates
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
                    $title = 'Token Updated';
                    $message = 'Token configuration updated successfully.';
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
                    $title = 'Dropdown updated';
                    $message = 'Dropdown configuration updated successfully.';
                    break;
                // Handle module configuration updates
                case 'central_skeleton_modules':
                    // Validate module-related fields
                    // Create controllers, blades, or permissions if requested
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'system' => 'required|in:central,business,open',
                        'in_view' => 'required|in:admin,user,open',
                        'order' => 'required|integer|min:0',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                        'controllers' => 'nullable',
                        'blades' => 'nullable',
                        'permissions' => 'nullable',
                        'module_id' => 'nullable|string',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Trim string fields
                    foreach (['name', 'display', 'icon', 'system', 'module_id'] as $field) {
                        if (isset($validated[$field]) && is_string($validated[$field])) {
                            $validated[$field] = trim($validated[$field]);
                        }
                    }
                    $moduleId = $validated['module_id'] ?? '';
                    $system = $validated['system'] ?? '';
                    if ($validated['controllers'] ?? false) {
                        Developer::generateStructure('controller', 'module', $moduleId, $system);
                    }
                    if ($validated['blades'] ?? false) {
                        Developer::generateStructure('blade', 'module', $moduleId, $system);
                    }
                    if ($validated['permissions'] ?? false) {
                        Developer::generateStructure('permission', 'module', $moduleId, $system);
                    }
                    // Remove unwanted fields
                    unset($validated['controllers'], $validated['blades'], $validated['permissions']);
                    $reloadTable = true;
                    $title = 'Module Updated';
                    $message = 'Module configuration updated successfully.';
                    break;
                // Handle section configuration updates
                case 'central_skeleton_sections':
                    // Validate section-related fields
                    // Create controllers, blades, or permissions if requested
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'order' => 'required|integer|min:0',
                        'in_view' => 'required|in:admin,user,open',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                        'blades' => 'nullable',
                        'permissions' => 'nullable',
                        'section_id' => 'nullable|string',
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
                    if ($validated['blades'] ?? false) {
                        Developer::generateStructure('blade', 'section', $validated['section_id']);
                    }
                    if ($validated['permissions'] ?? false) {
                        Developer::generateStructure('permission', 'section', $validated['section_id']);
                    }
                    // Remove unwanted fields
                    unset($validated['blades'], $validated['permissions']);
                    $reloadTable = true;
                    $title = 'Section Updated';
                    $message = 'Section configuration updated successfully.';
                    break;
                // Handle item configuration updates
                case 'central_skeleton_items':
                    // Validate item-related fields
                    // Create controllers, blades, or permissions if requested
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:100',
                        'display' => 'nullable|string|max:100',
                        'icon' => 'nullable|string|max:100',
                        'order' => 'required|integer|min:0',
                        'in_view' => 'required|in:admin,user,open',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                        'blades' => 'nullable',
                        'permissions' => 'nullable',
                        'item_id' => 'nullable|string',
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
                    if ($validated['blades'] ?? false) {
                        Developer::generateStructure('blade', 'item', $validated['item_id']);
                    }
                    if ($validated['permissions'] ?? false) {
                        Developer::generateStructure('permission', 'item', $validated['item_id']);
                    }
                    // Remove unwanted fields
                    unset($validated['blades'], $validated['permissions']);
                    $reloadTable = true;
                    $title = 'Item Updated';
                    $message = 'Item configuration updated successfully.';
                    break;
                // Handle permission configuration updates
                case 'central_skeleton_permissions':
                    // Validate permission-related fields
                    $validator = Validator::make($request->all(), [
                        'is_approved' => 'required|boolean',
                        'description' => 'nullable|string|max:255',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'Permission Updated';
                    $message = 'Permission configuration updated successfully.';
                    break;
                // Handle custom permission configuration updates
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
                    $reloadTable = true;
                    $title = 'Custom Permission Updated';
                    $message = 'Custom permission configuration updated successfully.';
                    break;
                // Handle role permission updates
                case 'central_skeleton_role_permissions':
                    // Validate role permission-related fields
                    $validator = Validator::make($request->all(), [
                        'permission_ids' => 'required|json',
                        'business_id'    => 'nullable|string',
                        'role_id'        => 'nullable|string',
                    ]);
                    Developer::alert('this is the testing data',['the test of the test'=>$request->all()]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Decode permission_ids JSON
                    $permissionIds = json_decode($validated['permission_ids'], true);
                    if (!is_array($permissionIds)) {
                        return ResponseHelper::moduleError('Invalid Data', 'Permission IDs must be a valid JSON array.');
                    }
                    // Fallback to route param if role_id not passed in form
                    $roleId = trim($validated['role_id'] ?? $reqSet['id']);
                    // Determine business context
                    $businessId = $validated['business_id'] ?? null;
                    $isCentral = strtoupper($businessId) === 'CENTRAL' || empty($businessId);
                    if ($isCentral) {
                        Skeleton::managePermissions('role', $roleId, $permissionIds, null);
                    } else {
                        Skeleton::managePermissions('role', $roleId, $permissionIds, $businessId);
                    }
                    return response()->json([
                        'status'       => true,
                        'title'        => 'Role Permissions Updated',
                        'message'      => 'Role permissions updated successfully.',
                        'reload_table' => true,
                        'reload_card'  => false,
                        'token'        => $reqSet['token'],
                    ]);
                    // Handle user permission updates
                case 'central_skeleton_user_permissions':
                    // Validate user permission-related fields
                    $validator = Validator::make($request->all(), [
                        'permission_ids' => 'required|json',
                        'business_id' => 'nullable',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $permissionIds = json_decode($validated['permission_ids'], true);
                    if (!is_array($permissionIds)) {
                        return ResponseHelper::moduleError('Invalid Data', 'Permission IDs must be a valid JSON array.');
                    }
                    $userId = trim($reqSet['id']);
                    if ($validated['business_id'] != '' || $validated['business_id'] != 'CENTRAL') {
                        Skeleton::managePermissions('user', $userId, $permissionIds, $validated['business_id']);
                    } else {
                        Skeleton::managePermissions('user', $userId, $permissionIds, null);
                    }
                    return response()->json([
                        'status' => true,
                        'title' => 'User Permissions Updated',
                        'message' => 'User permissions updated successfully.',
                        'reload_table' => true,
                        'reload_card' => false,
                        'token' => $reqSet['token'],
                    ]);
                    break;
                // Handle folder configuration updates
                case 'central_skeleton_folders':
                    // Validate folder-related fields
                    $validator = Validator::make($request->all(), [
                        'name'             => 'required|string|max:100',
                        'parent_folder_id' => 'nullable|string',
                        'system'             => 'nullable|string|max:100',
                        'is_approved'      => 'nullable|boolean',
                        'description'      => 'nullable|string|max:255',
                        'folder_id'        => 'required|string|exists:skeleton_folders,folder_id',
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
                    // Flags for UI or flow control
                    $reloadTable = true;
                    $title = 'Folder Updated';
                    $message = 'Folder configuration updated successfully.';
                    break;
                // Handle folder permission updates
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
                    $title = 'Folder Permissions Updated';
                    $message = 'Folder permissions updated successfully.';
                    break;
                // Handle file extension configuration updates
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
                    $reloadTable = true;
                    $title = 'File Extension Updated';
                    $message = 'File extension configuration updated successfully.';
                    break;
                case 'central_skeleton_templates':
                    // Validate file extension-related fields
                    $validator = Validator::make($request->all(), [
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
                    $reloadTable = $validated['type'];
                    $title = 'Template Updated';
                    $message = 'Template updated successfully.';
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
                    // Ensure SQL schema is stored safely (JSON stringify for consistency)
                    try {
                        $validated['schema'] = trim($validated['schema']);
                        $validated['snap'] = json_encode(['sql' => $validated['schema']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } catch (Exception $e) {
                        return ResponseHelper::moduleError('Invalid schema', 'Failed to encode schema snapshot');
                    }
                    $reloadTable = true;
                    $title = 'Schema Updated';
                    $message = 'Schema configuration updated successfully.';
                    break;
                // Handle invalid configuration keys
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                  *
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
            $affected = Data::update('central', $reqSet['table'], $validated, [['column'=>$reqSet['act'], 'value' => $reqSet['id']]], $reqSet['key']);
            // Return response based on update success
            return response()->json([
                'status' => $affected > 0,
                'reload_table' => $reloadTable,
                'reload_card' => $reloadCard,
                'token' => $reqSet['token'],
                'affected' => $affected,
                'title' => $affected > 0 ? $title : 'Failed',
                'message' => $affected > 0 ? $message : 'No changes were made.'
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated developer entity data based on validated input.
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
            $ids = explode('@', $request->input('update_ids', ''));
            if (empty($ids)) {
                return response()->json(['status' => false, 'title' => 'Invalid Data', 'message' => 'No valid IDs provided for deletion.']);
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = true;
            $reloadTable = $reloadCard = $reloadPage = $hold_popup = false;
            $validated = [];
            $title = 'Success';
            $message = 'Record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle module configuration updates
                case 'central_skeleton_modules':
                    // Validate module-related fields
                    // Create controllers, blades, or permissions if requested
                    $validator = Validator::make($request->all(), [
                        'system' => 'required|in:central,business,open',
                        'order' => 'required|integer|min:0',
                        'is_navigable' => 'required|boolean',
                        'is_approved' => 'required|boolean',
                        'controllers' => 'nullable',
                        'blades' => 'nullable',
                        'permissions' => 'nullable',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $moduleId = $validated['module_id'] ?? '';
                    $system = $validated['system'] ?? '';
                    if ($validated['controllers'] ?? false) {
                        Developer::generateStructure('controller', 'module', $moduleId, $system);
                    }
                    if ($validated['blades'] ?? false) {
                        Developer::generateStructure('blade', 'module', $moduleId, $system);
                    }
                    if ($validated['permissions'] ?? false) {
                        Developer::generateStructure('permission', 'module', $moduleId, $system);
                    }
                    // Remove unwanted fields
                    unset($validated['controllers'], $validated['blades'], $validated['permissions']);
                    $reloadTable = true;
                    $title = 'Module Updated';
                    $message = 'Module configuration updated successfully.';
                    break;
                // Handle invalid configuration keys
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                  *
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
            $affected = Data::update('central', $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
            // Return response based on update success
            return response()->json(['status' => $affected > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'hold_popup' => $hold_popup, 'token' => $reqSet['token'], 'affected' => $affected, 'title' => $affected > 0 ? $title : 'Failed', 'message' => $affected > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
