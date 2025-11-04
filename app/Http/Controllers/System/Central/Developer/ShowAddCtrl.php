<?php
namespace App\Http\Controllers\System\Central\Developer;
use App\Facades\{FileManager, Select, Developer, Skeleton, Helper};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for developer entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new developer entities.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize popup configuration and system options
            $popup = [];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle token configuration form
                case 'central_skeleton_tokens':
                    // Define form fields for adding a new token
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'key', 'label' => 'Key', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'key', 'maxlength' => '100', 'data-unique' => Skeleton::skeletonToken('central_skeleton_tokens_unique') . '_u', 'data-unique-msg' => 'This key is already registered']],
                            ['type' => 'select', 'name' => 'system', 'label' => 'System', 'options' => ['central' => 'Central', 'business' => 'Business', 'open' => 'Open', 'lander' => 'Lander'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-target' => Skeleton::skeletonToken('central_skeleton_tokens_module') . '_s']],
                            ['type' => 'select', 'name' => 'module', 'label' => 'Module', 'options' => Select::options('skeleton_modules', 'array', ['name' => 'name']), 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-source' => Skeleton::skeletonToken('central_skeleton_tokens_module') . '_s']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['data' => 'Data', 'unique' => 'Unique', 'select' => 'Select', 'other' => 'Other'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'table', 'label' => 'Table', 'required' => true, 'col' => '4', 'attr' => ['data-validate' => 'key', 'maxlength' => '100']],
                            ['type' => 'text', 'name' => 'column', 'label' => 'Column', 'required' => true, 'col' => '4'],
                            ['type' => 'text', 'name' => 'value', 'label' => 'Value', 'required' => true, 'col' => '4'],
                            ['type' => 'select', 'name' => 'validate', 'label' => 'Validate', 'options' => ['0' => 'No', '1' => 'Yes'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'act', 'label' => 'Action Column', 'required' => true, 'col' => '4'],
                            ['type' => 'select', 'name' => 'actions', 'label' => 'Actions', 'options' => ['c' => 'Checkbox', 'v' => 'View', 'e' => 'Edit', 'd' => 'Delete'], 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Token',
                        'short_label' => '',
                        'button' => 'Save Token',
                        'script' => 'window.general.select();window.general.unique();window.general.pills();'
                    ];
                    break;
                case 'central_skeleton_dropdowns':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'key', 'maxlength' => '100', 'data-unique' => Skeleton::skeletonToken('central_skeleton_dropdowns_unique') . '_u', 'data-unique-msg' => 'This name is already registered']],
                            ['type' => 'repeater', 'name' => 'pairs', 'set' => 'pair', 'fields' => [['type' => 'text', 'name' => 'label', 'label' => 'Value', 'placeholder' => 'Value', 'required' => true],['type' => 'text', 'name' => 'value', 'label' => 'Display', 'placeholder' => 'Display', 'required' => true]], 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Dropdown',
                        'short_label' => '',
                        'button' => 'Add Dropdown',
                        'script' => 'window.general.select();window.general.unique();window.general.repeater();'
                    ];
                    break;
                case 'central_skeleton_restrictions':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'type', 'label' => 'Type', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'value', 'label' => 'Value', 'col' => '12', 'attr' => ['data-pills' => 'normal']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Dropdown',
                        'short_label' => '',
                        'button' => 'Add Dropdown',
                        'script' => 'window.general.select();window.general.unique();window.general.repeater();window.general.pills();'
                    ];
                break;
                // Handle module configuration form
                case 'central_skeleton_modules':
                    // Define form fields for adding a new module
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'module', 'data-unique' => Skeleton::skeletonToken('central_skeleton_module_unique') . '_u', 'data-unique-msg' => 'This key is already registered']],
                            ['type' => 'text', 'name' => 'display', 'label' => 'Display Name', 'required' => false, 'col' => '6'],
                            ['type' => 'text', 'name' => 'icon', 'label' => 'Icon', 'required' => true, 'col' => '4'],
                            ['type' => 'number', 'name' => 'order', 'label' => 'Order', 'required' => true, 'col' => '4'],
                            ['type' => 'select', 'name' => 'system', 'label' => 'System', 'options' => ['central' => 'Central', 'business' => 'Business', 'open' => 'Open'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'in_view', 'label' => 'In View', 'options' => ['admin' => 'Admin', 'user' => 'User', 'open' => 'Open'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approve', '0' => 'Reject'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_navigable', 'label' => 'Is Navigable', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Module',
                        'short_label' => '',
                        'button' => 'Save Module',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle section configuration form
                case 'central_skeleton_sections':
                    // Define form fields for adding a new section
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'module']],
                            ['type' => 'text', 'name' => 'display', 'label' => 'Display Name', 'required' => false, 'col' => '6'],
                            ['type' => 'text', 'name' => 'icon', 'label' => 'Icon', 'required' => true, 'col' => '4'],
                            ['type' => 'number', 'name' => 'order', 'label' => 'Order', 'required' => true, 'col' => '4'],
                            ['type' => 'select', 'name' => 'in_view', 'label' => 'In View', 'options' => ['admin' => 'Admin', 'user' => 'User', 'open' => 'Open'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'module_id', 'label' => 'Module', 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name']), 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Active', '0' => 'Deactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_navigable', 'label' => 'Is Navigable', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Section',
                        'short_label' => '',
                        'button' => 'Save Section',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle item configuration form
                case 'central_skeleton_items':
                    // Define form fields for adding a new item
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'module']],
                            ['type' => 'text', 'name' => 'display', 'label' => 'Display Name', 'required' => false, 'col' => '6'],
                            ['type' => 'text', 'name' => 'icon', 'label' => 'Icon', 'required' => true, 'col' => '4'],
                            ['type' => 'number', 'name' => 'order', 'label' => 'Order', 'required' => true, 'col' => '4'],
                            ['type' => 'select', 'name' => 'in_view', 'label' => 'In View', 'options' => ['admin' => 'Admin', 'user' => 'User', 'open' => 'Open'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'module_id', 'label' => 'Module', 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-target' => Skeleton::skeletonToken('central_skeleton_item_sections_select') . '_s']],
                            ['type' => 'select', 'name' => 'section_id', 'label' => 'Section', 'options' => [], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-source' => Skeleton::skeletonToken('central_skeleton_item_sections_select') . '_s']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Active', '0' => 'Deactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_navigable', 'label' => 'Is Navigable', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Skeleton Item',
                        'short_label' => '',
                        'button' => 'Save Item',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle custom permission configuration form
                case 'central_skeleton_custom_permissions':
                    // Define form fields for adding a new custom permission
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'permission', 'data-unique-msg' => 'This key is already registered']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12'],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Active', '0' => 'Deactive'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Custom Permission',
                        'short_label' => '',
                        'button' => 'Save Permission',
                        'script' => 'window.general.select();'
                    ];
                    break;
                // Handle role permission assignment form
                case 'central_skeleton_role_permissions':
                    // Define form for assigning permissions to roles
                    $permissions = Skeleton::loadPermissions('self', 'USR0001', 'role');
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '<div data-permissions-container>
                                            <div id="accordion-permissions" class="accordion"></div>
                                                <input type="hidden" id="permission_ids" name="permission_ids" value="[]">
                                            <div id="errorMessage" class="alert alert-danger d-none"></div>
                                        </div>',
                        'type' => 'modal',
                        'size' => 'modal-xl',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Assign Permissions',
                        'short_label' => '',
                        'button' => 'Save Permissions',
                        'script' => 'window.skeleton.permissions(' . json_encode($permissions, JSON_UNESCAPED_SLASHES) . ');'
                    ];
                    break;
                // Handle folder configuration form
                case 'central_skeleton_folders':
                    // Define form fields for adding a new folder
                    $folderPaths = FileManager::getFolderPaths();
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'key', 'label' => 'Key', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'key', 'data-unique' => Skeleton::skeletonToken('central_skeleton_folder_unique') . '_u', 'data-unique-msg' => 'This key is already registered']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'module']],
                            [
                                'type' => 'select',
                                'name' => 'parent_folder_id',
                                'label' => 'Parent Folder',
                                'options' => ['' => 'None'] + $folderPaths,
                                'required' => false,
                                'col' => '12',
                                'attr' => ['data-select' => 'dropdown']
                            ],
                            ['type' => 'select', 'name' => 'system', 'label' => 'System', 'options' => ['central' => 'Central', 'business' => 'Business', 'lander' => 'Lander'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approve', '0' => 'Reject'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Folder Key',
                        'short_label' => '',
                        'button' => 'Add Folder',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle folder permission configuration form
                case 'central_folder_permissions':
                    // Define form fields for adding folder permissions
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'folder_id', 'label' => 'Folder', 'options' => Select::options('skeleton_folders', 'array', ['folder_id' => 'name']), 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'permissions', 'label' => 'Permissions', 'options' => ['view' => 'View', 'edit' => 'Edit'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Permission',
                        'short_label' => '',
                        'button' => 'Add Permission',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle file extension configuration form
                case 'central_file_extensions':
                    // Define form fields for adding a new file extension
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'extension', 'label' => 'Extension', 'required' => true, 'col' => '12'],
                            ['type' => 'text', 'name' => 'icon_path', 'label' => 'Icon Path', 'required' => true, 'col' => '12'],
                            ['type' => 'text', 'name' => 'mime_type', 'label' => 'Mime Type', 'required' => true, 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Extension',
                        'short_label' => '',
                        'button' => 'Add Extension',
                        'script' => 'window.general.select();window.general.unique();'
                    ];
                    break;
                // Handle templates configuration form
                case 'central_skeleton_templates':
                    $html = $script = $mdlSize = '';
                    $fields = [
                        ['type' => 'text', 'name' => 'key', 'label' => 'Key', 'required' => true, 'col' => '4', 'attr' => ['data-validate' => 'key', 'maxlength' => '100', 'data-unique' => Skeleton::skeletonToken('central_skeleton_template_unique') . '_u', 'data-unique-msg' => 'This key is already registered']],
                        ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '4'],
                        ['type' => 'text', 'name' => 'purpose', 'label' => 'Purpose', 'required' => true, 'col' => '4'],
                        ['type' => 'text', 'name' => 'subject', 'label' => 'Subject', 'required' => true, 'col' => '8'],
                        ['type' => 'select', 'name' => 'mailer', 'label' => 'Mailer', 'options' => ['info' => 'Info', 'alert' => 'Alert', 'billing' => 'Billing'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                        ['type' => 'text', 'name' => 'from_name', 'label' => 'From name', 'required' => true, 'col' => '3'],
                        ['type' => 'text', 'name' => 'from_address', 'label' => 'From Address', 'required' => true, 'col' => '3'],
                        ['type' => 'text', 'name' => 'placeholders', 'label' => 'Placeholders', 'class' => ['h-auto'], 'required' => false, 'col' => '6', 'attr' => ['data-pills' => '']],
                    ];
                    if ($reqSet['id'] == 'email') {
                        $mdlSize = 'modal-xl';
                        $fields[] = ['type' => 'hidden', 'name' => 'type', 'label' => 'Type', 'value' => 'email'];
                        $html = PopupHelper::generateBuildForm($token, $fields, 'floating');
                        $html .= '<div data-template-id="for-email-template"></div>';
                        $script = 'window.general.pills();window.skeleton.template("email", "for-email-template", "", "");';
                    } else {
                        $mdlSize = 'modal-lg';
                        $fields[] = ['type' => 'hidden', 'name' => 'type', 'label' => 'Type', 'value' => 'whatsapp'];
                        $html = PopupHelper::generateBuildForm($token, $fields, 'floating');
                        $html .= '<div data-editor-id="for-whatsapp-template" name="content"></div>';
                        $script = 'window.general.pills();window.skeleton.editor("for-whatsapp-template", "", "");';
                    }
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => $html,
                        'type' => 'modal',
                        'size' => $mdlSize ?: 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Template',
                        'short_label' => '',
                        'button' => 'Add Template',
                        'script' => $script . 'window.general.unique();window.general.select()'
                    ];
                    break;
                case 'central_business_schemas':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'module', 'label' => 'Module', 'options' => ['' => 'None'] + Select::options('skeleton_modules', 'array', ['name' => 'name'], ['where' => ['system' => ['in' => ['business', 'open']]]]), 'col' => '4', 'required' => true, 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'table', 'label' => 'Table Name', 'required' => true, 'col' => '5'],
                            ['type' => 'select', 'name' => 'operation', 'label' => 'Schema Type', 'options' => ['create' => 'Create', 'alter' => 'Alter', 'drop' => 'Drop', 'index' => 'Index'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'raw', 'html' => '<div class="mt-3 mb-1" data-code="sql" data-code-input="schema" data-code-value=""></div>', 'col' => '12'],
                            ['type' => 'select', 'name' => 'depends_on_modules', 'label' => 'Depends on Modules', 'options' => Select::options('business_schemas', 'array', ['module' => 'module']), 'col' => '6', 'class' => ['h-auto'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                            ['type' => 'select', 'name' => 'depends_on_tables', 'label' => 'Depends on Tables', 'options' => Select::options('business_schemas', 'array', ['table' => 'table']), 'col' => '6', 'class' => ['h-auto'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                            ['type' => 'number', 'name' => 'execution_order', 'label' => 'Execution Order', 'col' => '6', 'required' => true],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-database me-1"></i> Add Business Schema',
                        'short_label' => 'Define SQL Schema Structure for Business Module Tables',
                        'button' => 'Save Schema',
                        'script' => 'window.general.select();window.skeleton.code();'
                    ];
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json([
                'token' => $token,
                'type' => $popup['type'],
                'size' => $popup['size'],
                'position' => $popup['position'],
                'label' => $popup['label'],
                'short_label' => $popup['short_label'],
                'content' => $content,
                'script' => $popup['script'],
                'button_class' => $popup['button_class'] ?? '',
                'button' => $popup['button'] ?? '',
                'footer' => $popup['footer'] ?? '',
                'header' => $popup['header'] ?? '',
                'validate' => $reqSet['validate'] ?? '0',
                'status' => true,
                'title' => 'Form Generated',
                'message' => 'Add form for ' . $reqSet['key'] . ' generated successfully.'
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
