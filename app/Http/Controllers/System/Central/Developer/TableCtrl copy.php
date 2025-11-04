<?php
namespace App\Http\Controllers\System\Central\Developer;
use App\Facades\{Data, Developer, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the central system.
 */
class TableCtrl extends Controller
{
    /**
     * Handles AJAX requests for table data processing.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed table data or error response
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (empty($token)) {
                Developer::warning('TableCtrl: No token provided', [
                    'params' => $params,
                    'request' => $request->except(['password', 'token'])
                ]);
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token and validate configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                Developer::warning('TableCtrl: Invalid token configuration', [
                    'token' => $token,
                    'reqSet' => $reqSet
                ]);
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or lacks required configuration.', 400);
            }
            // Set view to table and parse filters
            $reqSet['view'] = 'table';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? [],
                'dateRange' => $filters['dateRange'] ?? [],
                'columns' => $filters['columns'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 10],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                Developer::warning('TableCtrl: Invalid filters format', [
                    'filters' => $reqSet['filters'],
                    'token' => $token
                ]);
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle token configuration table data
                case 'central_skeleton_tokens':
                    // Define columns for token data
                    $columns = [
                        'id' => ['skeleton_tokens.id', true],
                        'key' => ['skeleton_tokens.key', true],
                        'module' => ['skeleton_tokens.module', true],
                        'system' => ['skeleton_tokens.system', true],
                        'type' => ['skeleton_tokens.type', true],
                        'table' => ['skeleton_tokens.table', true],
                        'column' => ['skeleton_tokens.column', true],
                        'value' => ['skeleton_tokens.value', true],
                        'act' => ['skeleton_tokens.act', true],
                        'action' => ['skeleton_tokens.actions AS action', true],
                        'updated_at' => ['skeleton_tokens.updated_at', true],
                    ];
                    $title = 'Tokens Retrieved';
                    $message = 'Token configuration data retrieved successfully.';
                    $custom = [
                        ['type' => 'modify', 'column' => 'key', 'view' => '<span class="badge bg-secondary">::key::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'system', 'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'open\', <span class="badge bg-warning">Open</span>)::ELSEIF(system = \'lander\', <span class="badge bg-danger">Lander</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'type', 'view' => '::IF(type = \'data\', <span class="badge bg-info">Data</span>)::ELSEIF(type = \'select\', <span class="badge bg-success">Select</span>)::ELSEIF(type = \'unique\', <span class="badge bg-warning">Unique</span>)::ELSEIF(system = \'others\', <span class="badge bg-primary">Other</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true]
                    ];
                    break;
                case 'central_skeleton_dropdowns':
                    // Define columns for token data
                    $columns = [
                        'id' => ['skeleton_dropdowns.id', true],
                        'name' => ['skeleton_dropdowns.name', true],
                        'pairs' => ['skeleton_dropdowns.pairs', true],
                        'updated_at' => ['skeleton_dropdowns.updated_at', true],
                    ];
                    $title = 'Dropdowns Retrieved';
                    $message = 'Dropdowns configuration data retrieved successfully.';
                    break;
                // Handle module configuration table data
                case 'central_skeleton_modules':
                    // Define columns and customizations for module data
                    $columns = [
                        'id' => ['skeleton_modules.id', true],
                        'module_id' => ['skeleton_modules.module_id', true],
                        'system' => ['skeleton_modules.system', true],
                        'name' => ['skeleton_modules.name', true],
                        'icon' => ['skeleton_modules.icon', true],
                        'order' => ['skeleton_modules.order', true],
                        'approval' => ['skeleton_modules.is_approved AS approval', true],
                        'navigable' => ['skeleton_modules.is_navigable AS navigable', true],
                        'created_at' => ['skeleton_modules.created_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'navigable', 'view' => '::IF(navigable = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'system', 'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'open\', <span class="badge bg-warning">Open</span>)::ELSEIF(system = \'sort\', <span class="badge bg-primary">Sort</span>)::ELSEIF(system = \'legal\', <span class="badge bg-danger">Legal</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true]
                    ];
                    $title = 'Modules Retrieved';
                    $message = 'Module configuration data retrieved successfully.';
                    break;
                // Handle section configuration table data
                case 'central_skeleton_sections':
                    // Define columns, joins, and customizations for section data
                    $columns = [
                        'id' => ['skeleton_sections.id', true],
                        'section_id' => ['skeleton_sections.section_id', true],
                        'module' => ['skeleton_modules.name AS module', true],
                        'system' => ['skeleton_modules.system', true],
                        'name' => ['skeleton_sections.name', true],
                        'icon' => ['skeleton_sections.icon', true],
                        'order' => ['skeleton_sections.order', true],
                        'approval' => ['skeleton_sections.is_approved AS approval', true],
                        'navigable' => ['skeleton_sections.is_navigable AS navigable', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'skeleton_modules', 'on' => [['skeleton_sections.module_id', 'skeleton_modules.module_id']]],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'navigable', 'view' => '::IF(navigable = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'system', 'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'open\', <span class="badge bg-warning">Open</span>)::ELSEIF(system = \'sort\', <span class="badge bg-primary">Sort</span>)::ELSEIF(system = \'legal\', <span class="badge bg-danger">Legal</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true]
                    ];
                    $title = 'Sections Retrieved';
                    $message = 'Section configuration data retrieved successfully.';
                    break;
                // Handle item configuration table data
                case 'central_skeleton_items':
                    // Define columns, joins, and customizations for item data
                    $columns = [
                        'id' => ['skeleton_items.id', true],
                        'item_id' => ['skeleton_items.item_id', true],
                        'module' => ['skeleton_modules.name AS module', true],
                        'section' => ['skeleton_sections.name AS section', true],
                        'system' => ['skeleton_modules.system', true],
                        'name' => ['skeleton_items.name', true],
                        'icon' => ['skeleton_items.icon', true],
                        'order' => ['skeleton_items.order', true],
                        'approval' => ['skeleton_items.is_approved AS approval', true],
                        'navigable' => ['skeleton_items.is_navigable AS navigable', true]
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'skeleton_sections', 'on' => [['skeleton_items.section_id', 'skeleton_sections.section_id']]],
                        ['type' => 'left', 'table' => 'skeleton_modules', 'on' => [['skeleton_sections.module_id', 'skeleton_modules.module_id']]],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'navigable', 'view' => '::IF(navigable = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'system', 'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'open\', <span class="badge bg-warning">Open</span>)::ELSEIF(system = \'sort\', <span class="badge bg-primary">Sort</span>)::ELSEIF(system = \'legal\', <span class="badge bg-danger">Legal</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true]
                    ];
                    break;
                // Handle permission configuration table data
                case 'central_skeleton_permissions':
                    // Define columns and customizations for permission data
                    $columns = [
                        'id' => ['permissions.id', true],
                        'permission_id' => ['permissions.permission_id', true],
                        'name' => ['permissions.name', true],
                        'description' => ['permissions.description', true],
                        'approval' => ['permissions.is_approved AS approval', true],
                        'is_skeleton' => ['permissions.is_skeleton', true],
                        'updated_at' => ['permissions.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_skeleton', 'view' => '::IF(is_skeleton = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true]
                    ];
                    break;
                // Handle custom permission configuration table data
                case 'central_skeleton_custom_permissions':
                    // Define columns, conditions, and customizations for custom permission data
                    $columns = [
                        'id' => ['permissions.id', true],
                        'permission_id' => ['permissions.permission_id', true],
                        'name' => ['permissions.name', true],
                        'description' => ['permissions.description', true],
                        'approval' => ['permissions.is_approved AS approval', true],
                        'is_skeleton' => ['permissions.is_skeleton', true],
                        'updated_at' => ['permissions.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'name', 'view' => '<span class="badge bg-primary">::name::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_skeleton', 'view' => '::IF(is_skeleton = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true]
                    ];
                    $conditions = [
                        ['column' => 'permissions.is_skeleton', 'operator' => '=', 'value' => 0],
                    ];
                    $title = 'Custom Permissions Retrieved';
                    $message = 'Custom permission configuration data retrieved successfully.';
                    break;
                // Handle role permission configuration table data
                case 'central_skeleton_role_permissions':
                    // Define columns and customizations for role permission data
                    $columns = [
                        'id' => ['roles.id', true],
                        'role_id' => ['roles.role_id', true],
                        'name' => ['roles.name', true],
                        'description' => ['roles.description', true],
                        'parent_role_id' => ['roles.parent_role_id', true],
                        'is_system_role' => ['roles.is_system_role', true],
                        'status' => ['roles.is_system_role AS status', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'name', 'view' => '<span class="badge bg-info">::name::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'status', 'view' => '::IF(status = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">In-Active</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_system_role', 'view' => '::IF(is_system_role = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::', 'renderHtml' => true]
                    ];
                    $title = 'Role Permissions Retrieved';
                    $message = 'Role permission configuration data retrieved successfully.';
                    break;
                // Handle user permission configuration table data
                case 'central_skeleton_user_permissions':
                    // Define columns and joins for user permission data
                    $columns = [
                        'user_id' => ['users.user_id', true],
                        'first_name' => ['users.first_name', true],
                        'role' => ['roles.name AS role', true],
                        'email' => ['users.email', true],
                        'username' => ['users.username', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                        ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                    ];
                    $title = 'User Permissions Retrieved';
                    $message = 'User permission configuration data retrieved successfully.';
                    break;
                // Handle folder configuration table data
                case 'central_skeleton_folders':
                    // Define columns and customizations for folder data
                    $columns = [
                        'id' => ['skeleton_folders.id', true],
                        'folder_id' => ['skeleton_folders.folder_id', true],
                        'key' => ['skeleton_folders.key', true],
                        'name' => ['skeleton_folders.name', true],
                        'system' => ['skeleton_folders.system', true],
                        'path' => ['skeleton_folders.path', true],
                        'description' => ['skeleton_folders.description', true],
                        'approval' => ['skeleton_folders.is_approved AS approval', true],
                        'created_by' => ['skeleton_folders.created_by', true],
                        'updated_at' => ['skeleton_folders.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'approval', 'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>, <span class="badge bg-danger">Rejected</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'system', 'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'lander\', <span class="badge bg-secondary">Lander</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::', 'renderHtml' => true]
                    ];
                    $title = 'Folders Retrieved';
                    $message = 'Folder configuration data retrieved successfully.';
                    break;
                // Handle file extension configuration table data
                case 'central_file_extensions':
                    // Define columns and customizations for file extension data
                    $columns = [
                        'id' => ['file_extensions.id', true],
                        'extension_id' => ['file_extensions.extension_id', true],
                        'extension' => ['file_extensions.extension', true],
                        'icon_path' => ['file_extensions.icon_path', true],
                        'mime_type' => ['file_extensions.mime_type', true],
                        'created_by' => ['file_extensions.created_by', true],
                        'updated_by' => ['file_extensions.updated_by', true],
                        'deleted_on' => ['file_extensions.deleted_on', true],
                        'restored_at' => ['file_extensions.restored_at', true],
                        'created_at' => ['file_extensions.created_at', true],
                        'updated_at' => ['file_extensions.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'extension', 'view' => '<span class="badge bg-info">::extension::</span>', 'renderHtml' => true]
                    ];
                    $title = 'File Extensions Retrieved';
                    $message = 'File extension configuration data retrieved successfully.';
                    break;
                // Handle skeleton_templates table data
                case 'central_skeleton_templates':
                    // Define columns and customizations for skeleton_templates data
                    $columns = [
                        'id' => ['skeleton_templates.id', true],
                        'key' => ['skeleton_templates.key', true],
                        'name' => ['skeleton_templates.name', true],
                        'type' => ['skeleton_templates.type', true],
                        'purpose' => ['skeleton_templates.purpose', true],
                        'subject' => ['skeleton_templates.subject', true],
                        'placeholders' => ['skeleton_templates.placeholders', true],
                        'description' => ['skeleton_templates.description', true],
                        'created_by' => ['skeleton_templates.created_by', true],
                        'updated_at' => ['skeleton_templates.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'type', 'view' => '<span class="badge bg-info">::type::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'purpose', 'view' => '<span class="badge bg-secondary">::purpose::</span>', 'renderHtml' => true]
                    ];
                    $conditions = [
                        ['column' => 'skeleton_templates.type', 'operator' => '=', 'value' => $reqSet['id']],
                    ];
                    $title = 'Skeleton Templates Retrieved';
                    $message = 'Skeleton template configuration data retrieved successfully.';
                    break;
                case 'central_business_schemas':
                    $columns = [
                        'id' => ['business_schemas.id', true],
                        'schema_id' => ['business_schemas.schema_id', true],
                        'module' => ['business_schemas.module', true],
                        'table' => ['business_schemas.table', true],
                        'operation' => ['business_schemas.operation', true],
                        'order' => ['business_schemas.execution_order AS order', true],
                        'depended_modules' => ['business_schemas.depends_on_modules AS depended_modules', true],
                        'depended_tables' => ['business_schemas.depends_on_tables AS depended_tables', true],
                        'is_approved' => ['business_schemas.is_approved', true],
                        'created_by' => ['business_schemas.created_by', true],
                        'updated_by' => ['business_schemas.updated_by', true],
                        'updated_at' => ['business_schemas.updated_at', true],
                    ];
                    $title = 'Schema Entries Retrieved';
                    $message = 'Business schema definitions retrieved successfully.';
                    $custom = [
                        ['type' => 'modify', 'column' => 'operation', 'view' => '::IF(operation = \'create\', <span class="badge bg-success">Create</span>)::ELSEIF(operation = \'alter\', <span class="badge bg-warning">Alter</span>)::ELSEIF(operation = \'modify\', <span class="badge bg-info">Modify</span>)::ELSE(<span class="badge bg-secondary">Other</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_approved', 'view' => '::IF(is_approved = 1, <span class="badge bg-success">Approved</span>)::ELSE(<span class="badge bg-danger">Pending</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'schema', 'view' => '<code class="d-block text-truncate" style="max-width:300px;">::schema::</code>', 'renderHtml' => true],
                    ];
                    break;
                case 'central_business_schema_progress':
                    $columns = [
                        'id' => ['business_schema_progress.id', true],
                        'business_id' => ['business_schema_progress.business_id', true],
                        'module' => ['business_schema_progress.module', true],
                        'table' => ['business_schema_progress.table', true],
                        'status' => ['business_schema_progress.status', true],
                        'message' => ['business_schema_progress.message', true],
                        'updated_by' => ['business_schema_progress.updated_by', true],
                        'updated_at' => ['business_schema_progress.updated_at', true],
                    ];
                    $title = 'Schema Progress Retrieved';
                    $message = 'Schema execution progress for business retrieved successfully.';
                    $custom = [
                        ['type' => 'modify', 'column' => 'status', 'view' => '::IF(status = \'success\', <span class="badge bg-success">Success</span>)::ELSEIF(status = \'error\', <span class="badge bg-danger">Error</span>)::ELSE(<span class="badge bg-secondary">Pending</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'message', 'view' => '<span class="d-block text-truncate" style="max-width:300px;">::message::</span>', 'renderHtml' => true],
                    ];
                    break;
                // Handle invalid configuration keys
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Prepare for set
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'custom' => $custom];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = TableHelper::generateResponse($set, $businessId);
            // Generate and return response using TableHelper
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
        } catch (Exception $e) {
            Developer::error('TableCtrl: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'token' => $token ?? 'unknown',
                'request' => $request->except(['password', 'token'])
            ]);
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve table data.', 500);
        }
    }
}
