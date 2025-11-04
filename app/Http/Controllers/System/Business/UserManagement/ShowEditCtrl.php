<?php
namespace App\Http\Controllers\System\Business\UserManagement;
use App\Facades\{Data, Developer, Select, Skeleton, FileManager, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Log};
/**
 * Controller for rendering the edit form for UserManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing UserManagement entities.
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
            Developer::info($reqSet);
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            Developer::info($reqSet['id']);

            if ($reqSet['id'] != "user") {
                // Fetch existing data
                $result = Data::fetch($reqSet['system'], $reqSet['table'], [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]]);
                $dataItem = $result['data'][0] ?? null;
                $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
                if (!$data) {
                    return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
                }
            }
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_um_roles':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => '6', 'value' => $data->sno],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'value' => $data->name, 'col' => '6', 'attr' => ['maxlength' => '100', 'readonly' => 'readonly']],
                            ['type' => 'select', 'name' => 'parent_role_id', 'label' => 'Parent Role', 'options' => ['' => '--- Select Parent Role ---'] +  Select::options('roles', 'array', ['role_id' => 'name']), 'value' => null, 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->parent_role_id]],
                            ['type' => 'select', 'name' => 'is_system_role', 'label' => 'System Role', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_system_role]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description',  'value' => $data->description, 'required' => false, 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Update Role',
                        'short_label' => 'Edit and update the details of this role.',
                        'button' => 'Update',
                        'script' => 'window.general.select();window.general.unique();window.general.pills();'
                    ];
                    break;
                case 'open_scope_mapping':
                case 'open_um_users':
                    // Fetch user data with joins
                    $system = Skeleton::authUser('system');
                    $role= Skeleton::authUser('roles');
                    $role_id=array_key_first($role);
                    $scope_id = ($role_id === 'ADMIN') ? null : Skeleton::authUser()->scope_id;
                    $scopes = Scope::getScopePaths('all', $scope_id, true);
                    $scopeId = isset($reqSet['id']) && !empty($reqSet['id']) ? ($reqSet['id'] === 'company' ? null : $reqSet['id']) : null;
                    $scopeInfo = [];
                    if ($scopeId) {
                        $scopeResult = Data::fetch($system, 'scopes', ['where' => ['scope_id' => $scopeId]]);
                        $scopeInfo = $scopeResult['data'][0] ?? [];
                    }
                    $userData = null;
                    if (!empty($data->user_id)) {
                        $result = Data::query($system, 'users', [
                            'select' => [
                                'users.sno',
                                'users.user_id',
                                'user_info.unique_code',
                                'users.username',
                                'users.email',
                                'users.first_name',
                                'users.last_name',
                                'users.profile',
                                'user_roles.role_id',
                                'scope_mapping.scope_id',
                                'user_info.gender',
                                'user_info.date_of_birth',
                                'user_info.phone',
                                'user_info.hire_date',
                                'user_info.job_title',
                                'user_info.department',
                                'scope_data.data',
                            ],
                            'joins' => [
                                [
                                    'type' => 'left',
                                    'table' => 'user_roles',
                                    'on' => [['users.user_id', 'user_roles.user_id']]
                                ],
                                [
                                    'type' => 'left',
                                    'table' => 'user_info',
                                    'on' => [['users.user_id', 'user_info.user_id']]
                                ],
                                [
                                    'type' => 'left',
                                    'table' => 'scope_mapping',
                                    'on' => [['users.user_id', 'scope_mapping.user_id']]
                                ],
                                [
                                    'type' => 'left',
                                    'table' => 'scope_data',
                                    'on' => [['users.user_id', 'scope_data.user_id']]
                                ]
                            ],
                            'where' => ['users.user_id' => $data->user_id]
                        ]);
                        $userData = $result['data'][0] ?? null;
                        $userScopeId = $userData['scope_id'];
                        $scopeId = $reqSet['param'] ?? '';
                    }
                    $commonFields = [
                        ['type' => 'raw', 'html' => '<div class="file-upload-container mt-3" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . ($userData && isset($userData['profile']) && $userData['profile'] ? FileManager::getFile($userData['profile']) : asset('default/preview-square.svg')) . '"></div>', 'col' => '12'],
                        ['type' => 'text', 'name' => 'sno', 'label' => 'Sl. No.', 'required' => true, 'col' => '2', 'value' => $userData['sno'] ?? '', 'attr' => ['data-validate' => 'sno']],
                        ['type' => 'text', 'name' => 'unique_code', 'label' => 'User Code (e.g. EMP001)', 'required' => false, 'col' => '4', 'value' => $userData['unique_code'] ?? '', 'attr' => ['maxlength' => '30', 'data-unique' => Skeleton::skeletonToken('open_um_users_unique_code') . '_u', 'data-unique-msg' => 'This Unique Code is already registered']],
                        ['type' => 'text', 'name' => 'username', 'label' => 'User Name', 'required' => true, 'col' => '3', 'value' => $userData['username'] ?? '', 'attr' => ['data-validate' => 'username', 'maxlength' => '100', 'data-unique' => Skeleton::skeletonToken('open_um_users_username') . '_u', 'data-unique-msg' => 'This Username is already exists.']],
                        ['type' => 'select', 'name' => 'role_id', 'label' => 'App Role', 'options' => Select::options('roles', 'array', ['role_id' => 'name']), 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $userData['role_id'] ?? '']],
                        ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true, 'col' => '3', 'value' => $userData['first_name'] ?? ''],
                        ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true, 'col' => '3', 'value' => $userData['last_name'] ?? ''],
                        ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'options' => ['male' => 'Male', 'female' => 'Female', 'non_binary' => 'Non-binary', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $userData['gender'] ?? '']],
                        ['type' => 'date', 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'required' => false, 'col' => '3', 'value' => $userData['date_of_birth'] ?? '', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                        ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'col' => '4', 'value' => $userData['email'] ?? '', 'attr' =>['data-validate' => 'email']],
                        ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'required' => false, 'col' => '4', 'value' => $userData['phone'] ?? '', 'attr' =>['data-validate' => 'phone']],
                        ['type' => 'date', 'name' => 'hire_date', 'label' => 'Joining Date', 'required' => false, 'col' => '4', 'value' => $userData['hire_date'] ??  '', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                    ];
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Edit User' . ($scopeId && isset($scopeInfo['name']) ? ' in <b>' . htmlspecialchars($scopeInfo['name']) . '</b>' : ''),
                        'short_label' => 'Edit user details and settings.',
                        'button' => 'Update User',
                        'fields' => $scopeId ? array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '<input type="hidden" name="scope_id" value="' . htmlspecialchars($scopeId) . '">', 'col' => '12']
                        ]) : array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100 my-3" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope name="scope_id" value="' . htmlspecialchars($userData['scope_id'] ?? $data->scope_id ?? '') . '"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="w-100" id="render-scope-form"></div>', 'col' => '12'],
                        ]),
                        'script' => 'window.general.select();window.skeleton.datePicker();window.general.validateForm();window.general.unique();window.general.files();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ',["' . $userScopeId . '"] , "single", true);
                            const input = document.querySelector("[data-scope]");
                            const wrapper = document.querySelector("#render-scope-form");
                            const current = ' . json_encode($userData['data']) . ' || "";
                            const clearInputs = () => document.querySelector("input[name=\'scope_data\']")?.remove();
                            const isValidSchema = (schema) => {try {const parsed = JSON.parse(schema);return Array.isArray(parsed) && parsed.length > 0;} catch {return false;}};
                            const renderForm = (schema = "[]", label = "", desc = "", formId = "", allowForm = 0) => {
                                if (!wrapper || !window.skeleton.renderForm) return;clearInputs();wrapper.innerHTML = "";
                                if (!isValidSchema(schema)) {return;}
                                const formName = label ? `Please provide additional information for <b>${label}</b>` : "Please provide additional information";
                                if (allowForm == 1 || allowForm == "1") { window.skeleton.renderForm( "render-scope-form", schema, "6", formName, desc, "floating", "scope_data", formId);}
                            };
                            const initScopeEvents = () => {
                                document.querySelectorAll(".path-item").forEach(item => {
                                    item.addEventListener("click", () => {
                                        const schema = item.getAttribute("data-schema") || "[]"; const label = item.getAttribute("data-label")?.trim() || ""; const desc = item.getAttribute("data-desc") || ""; const id = item.getAttribute("data-id"); const allow = item.getAttribute("data-allow"); input.value = id;
                                        document.querySelectorAll(".path-item").forEach(i => i.classList.remove("path-active"));
                                        item.classList.add("path-active");
                                        renderForm(schema, label, desc, "form_" + id, allow);
                                    });
                                });
                            };
                            const observeScopeChange = () => {
                                new MutationObserver(() => {
                                    const selected = document.querySelector(`.path-item[data-id="${input.value}"]`);
                                    document.querySelectorAll(".path-item").forEach(i => i.classList.remove("path-active"));
                                    if (selected) {
                                        const schema = selected.getAttribute("data-schema") || "[]"; const label = selected.getAttribute("data-label")?.trim() || ""; const desc = selected.getAttribute("data-desc") || ""; const id = selected.getAttribute("data-id") || `default-${Date.now()}`; const allow = selected.getAttribute("data-allow");
                                        selected.classList.add("path-active");
                                        renderForm(schema, label, desc, "form_" + id, allow);
                                    } else {
                                        wrapper.innerHTML = "";
                                        clearInputs();
                                    }
                                }).observe(input, { attributes: true, attributeFilter: ["value"] });
                            };
                            if (input && wrapper) {
                                const active = document.querySelector(".path-item.path-active");
                                if (active) {
                                    const schema = current || active.getAttribute("data-schema"); const label = active.getAttribute("data-label")?.trim() || ""; const desc = active.getAttribute("data-desc") || "Form based on selected scope"; const id = active.getAttribute("data-id") || `default-${Date.now()}`; const allow = active.getAttribute("data-allow");
                                    renderForm(schema, label, desc, "form_" + id, allow);
                                } else { wrapper.innerHTML = ""; clearInputs(); } initScopeEvents(); observeScopeChange(); }'
                    ];
                    break;
                case 'open_um_role_permissions':
                    // Define form for editing role permissions
                    $permissionType = (Skeleton::authUser('role')['role_id']=='DEVELOPER' || Skeleton::authUser('role')['role_id']=='SUPREME' || (Skeleton::authUser('role')['role_id']=='ADMIN' && Skeleton::getUserSystem()!=='central')) ? 'all' : 'self';
                    $permissions = Skeleton::loadPermissions($permissionType, 'all', 'role-id', $reqSet['id']);
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '
                                <input type="hidden" name="save_token" value="' . $reqSet['token'] . '_e_' . $reqSet['id'] . '"> 
                                <div data-permissions-container>
                                    <div id="accordion-permissions" class="accordion"></div>
                                    <input type="hidden" id="permission_ids" name="permission_ids" value="[]">
                                    <div id="errorMessage" class="alert alert-danger d-none"></div>
                                </div>',
                        'type' => 'modal',
                        'size' => 'modal-xl',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Edit Role Permissions',
                        'short_label' => '',
                        'button' => 'Update Permissions',
                        'script' => '
                                window.skeleton.permissions(' . json_encode($permissions, JSON_UNESCAPED_SLASHES) . ');
                            ',
                    ];
                    break;
                case 'open_um_user_permissions':
                    $set = (Skeleton::authUser('role')['role_id']=='DEVELOPER' || Skeleton::authUser('role')['role_id']=='SUPREME' || (Skeleton::authUser('role')['role_id']=='ADMIN' && Skeleton::getUserSystem()!=='central')) ? 'all' : 'self';
                    $permissions = Skeleton::loadPermissions($set, 'all', 'user-id', $reqSet['id']);
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '
                                    <input type="hidden" name="save_token" value="' . $reqSet['token'] . '_e_' . $reqSet['id'] . '">
                                    <div data-permissions-container>
                                        <div id="accordion-permissions" class="accordion"></div>
                                        <input type="hidden" id="permission_ids" name="permission_ids" value="[]">
                                        <div id="errorMessage" class="alert alert-danger d-none"></div>
                                    </div>',
                        'type' => 'modal',
                        'size' => 'modal-xl',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Edit User Permissions',
                        'short_label' => '',
                        'button' => 'Update Permissions',
                        'script' => '
                                    window.general.select();
                                    window.skeleton.permissions(' . json_encode($permissions, JSON_UNESCAPED_SLASHES) . ');
                                ',
                    ];
                    break;
                case 'open_um':
                    $paramParts = explode('_', $reqSet['param']);
                    $type = $paramParts[0] ?? null;
                    $userId = $paramParts[1] ?? null;
                    $dataId = $paramParts[2] ?? null;
                    $system = Skeleton::authUser('system');
                    $userParams = [
                        'select' => ['user_id', 'business_id', 'username', 'email', 'first_name', 'last_name', 'profile', 'cover', 'settings', 'email_verified_at', 'two_factor', 'two_factor_via', 'two_factor_confirmed_at', 'last_password_changed_at', 'last_login_at', 'account_status'],
                        'where' => [['column'=>'user_id', 'value' => $userId]]
                    ];
                    $userResult = Data::query($system, 'users', $userParams, '1');
                    if (!$userResult['status']) {
                        return ResponseHelper::moduleError('User Fetch Failed', $userResult['message'], 400);
                    }
                    $userInfoParams = [
                        'select' => ['unique_code', 'bio', 'gender', 'date_of_birth', 'nationality', 'marital_status', 'alt_email', 'phone', 'alt_phone', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'latitude', 'longitude', 'job_title', 'department', 'hire_date', 'user_type', 'portfolio_url', 'social_links', 'skills', 'education', 'certifications', 'experience', 'emergency_info', 'bank_info', 'onboarding_status', 'onboarding_tasks', 'offboarding_date', 'is_active',],
                        'where' => [['column'=>'user_id', 'value' => $userId]]
                    ];
                    $userInfoResult = Data::query($system, 'user_info', $userInfoParams, '1');
                    $userData = (object) $userResult['data'][0] ?? (object)[];
                    $info =  (object) $userInfoResult['data'][0] ?? (object)[];
                    if ($type == 'main') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'raw', 'col_class'=>'my-0', 'html' => '<div class="file-upload-container mt-3" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . FileManager::getFile($userData->profile) . '"></div>', 'col' => '12'],
                                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'value' => $userData->first_name ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'value' => $userData->last_name ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'username', 'label' => 'User Name', 'value' => $userData->username ?? '', 'required' => true, 'col' => '6', 'attr' => ['readonly' => 'readonly']],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $userData->email ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'value' => $info->phone ?? '', 'col' => '6'],
                                ['type' => 'text', 'name' => 'alt_phone', 'label' => 'Alternate Phone', 'value' => $info->alt_phone ?? '', 'col' => '6'],
                                ['type' => 'textarea', 'name' => 'bio', 'label' => 'Bio', 'value' => $info->bio ?? '', 'col' => '12', 'attr' => ['placeholder' => 'Enter your bio']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-user me-1"></i> Edit Profile',
                            'short_label' => 'Update your profile details',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.general.files();'
                        ];
                    } else if ($type == 'banner') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'raw', 'col_class'=>'my-0', 'html' => '<div class="file-upload-container mt-3" data-file="image" data-file-crop="cover" data-label="Cover Photo" data-name="cover_photo" data-crop-size="400:150" data-target="#profile-photo-input" data-recommended-size="600px x 200px" data-file-size="2" data-src="' . FileManager::getFile($userData->cover) . '"></div>', 'col' => '12'],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-camera me-1"></i> Change Cover Photo',
                            'short_label' => 'Upload a new cover photo',
                            'button' => 'Upload Cover',
                            'script' => 'window.general.select();window.general.files();'
                        ];
                    } else if ($type == 'profilechange') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',  
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . FileManager::getFile($userData->profile) . '"></div>', 'col' => '12'],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-camera me-1"></i> Change Profile Photo',
                            'short_label' => 'Upload a new profile photo',
                            'button' => 'Upload Profile',
                            'script' => 'window.general.select();window.general.files();'
                        ];
                    } else if ($type == 'bio') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'textarea', 'name' => 'bio', 'label' => 'About', 'value' => $info->bio ?? '', 'required' => true, 'col' => '12', 'attr' => ['placeholder' => 'Enter your bio']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'About You',
                            'label' => '<i class="fa-solid fa-address-card"></i> Introduce Yourself',
                            'button' => 'Update',
                            'script' => ''
                        ];
                    } else if ($type == 'skills') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'text', 'name' => 'skills', 'label' => 'Skills', 'value' => $info->skills ?? '', 'required' => false, 'col' => '12', 'class' => ['h-auto'], 'attr' => ['placeholder' => 'Enter your Skills', 'data-pills' => 'normal']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Skills',
                            'label' => '<i class="fa-solid fa-wand-magic-sparkles"></i> Show Off Your Skills',
                            'button' => 'Update',
                            'script' => 'window.general.pills();'
                        ];
                    } else if ($type == 'sociallinks') {
                        $socialLinks = [];
                        if (!empty($info->social_links)) {
                            $decoded = json_decode($info->social_links, true);
                            if (is_array($decoded)) {
                                $socialLinks = array_intersect_key($decoded, array_flip([
                                    'linkedin',
                                    'github',
                                    'youtube',
                                    'facebook',
                                    'instagram',
                                    'x'
                                ]));
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <input type="hidden" name="user_id" value="' . $userId . '">
                            <div class="row p-2 g-3">';
                        foreach (
                            [
                                'facebook' => ['label' => 'Facebook', 'icon' => 'facebook.svg', 'db_key' => 'facebook'],
                                'instagram' => ['label' => 'Instagram', 'icon' => 'instagram.svg', 'db_key' => 'instagram'],
                                'youtube' => ['label' => 'YouTube', 'icon' => 'youtube.svg', 'db_key' => 'youtube'],
                                'x' => ['label' => 'X', 'icon' => 'x.svg', 'db_key' => 'x'],
                                'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin.svg', 'db_key' => 'linkedin'],
                                'github' => ['label' => 'GitHub', 'icon' => 'github.svg', 'db_key' => 'github'],
                            ] as $platform => $data
                        ) {
                            $content .= '
                                <div class="row align-items-center gy-3">
                                    <div class="col-12 col-md-5 d-flex align-items-center gap-3">
                                        <img src="' . asset('social/' . $data['icon']) . '" alt="' . $data['label'] . '"
                                            class="img-fluid rounded-circle" style="width: 30px; height: 30px;">
                                        <div>
                                            <p class="fw-bold mb-1">' . $data['label'] . '</p>
                                            <p class="text-muted small m-0">Integrate your ' . $data['label'] . ' account</p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="float-input-control">
                                            <input type="text" id="' . $platform . '_url" name="' . $platform . '_url"
                                                value="' . htmlspecialchars($socialLinks[$data['db_key']] ?? '') . '" 
                                                class="form-float-input" placeholder="https://">
                                            <label for="' . $platform . '_url" class="form-float-label">' . $data['label'] . '</label>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Online Presence',
                            'label' => '<i class="ti ti-steam"></i> Showcase Your Online Profiles',
                            'button' => 'Update',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'basicinfo') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'tel', 'name' => 'phone', 'label' => 'Phone', 'value' => $info->phone ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $userData->email ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'email']],
                                ['type' => 'tel', 'name' => 'alt_phone', 'label' => 'Alt Phone', 'value' => $info->alt_phone ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'email', 'name' => 'alt_email', 'label' => 'Alt Email', 'value' => $info->alt_email ?? '', 'required' => false, 'col' => '6'],
                                ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer Not to Say'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'date', 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'value' => $info->date_of_birth ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                                ['type' => 'text', 'name' => 'nationality', 'label' => 'Nationality', 'value' => $info->nationality ?? '', 'col' => '6'],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-user me-1"></i> Edit Profile',
                            'short_label' => 'Update your profile details',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'address') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'value' => $info->address_line1 ?? '', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'value' => $info->address_line2 ?? '', 'required' => false, 'col' => '12', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $info->city ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'city']],
                                ['type' => 'text', 'name' => 'state', 'label' => 'State', 'value' => $info->state ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'state']],
                                ['type' => 'text', 'name' => 'postal_code', 'label' => 'Postal Code', 'value' => $info->postal_code ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'pincode']],
                                ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'value' => $info->country ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'country']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-solid fa-location-dot me-1"></i> Update Address',
                            'short_label' => 'Manage your address info',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'educationadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'university', 'label' => 'University / College', 'required' => true, 'col' => '12'],
                                ['type' => 'text', 'name' => 'degree', 'label' => 'Degree', 'required' => true, 'col' => '12'],
                                ['type' => 'date', 'name' => 'start_year', 'label' => 'Start Year', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'date', 'name' => 'end_year', 'label' => 'End Year', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->education ?? ''],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Education',
                            'label' => '<i class="fa-solid fa-graduation-cap"></i> Add Your Education Background',
                            'button' => 'Add',
                            'script' => 'window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'educationedit') {
                        $educationList = [];
                        if (!empty($info->education)) {
                            $decoded = json_decode($info->education, true);
                            if (is_array($decoded)) {
                                $educationList = $decoded;
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <input type="hidden" name="user_id" value="' . $userId . '">
                            <div class="row p-2 g-3">';
                        foreach ($educationList as $index => $edu) {
                            $content .= '
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-bold mb-3">Education Entry #' . ($index + 1) . '</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="university_' . $index . '" name="education[' . $index . '][university]" 
                                                    value="' . htmlspecialchars($edu['university'] ?? '') . '" 
                                                    class="form-float-input" placeholder="University Name">
                                                <label for="university_' . $index . '" class="form-float-label">University</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="degree_' . $index . '" name="education[' . $index . '][degree]" 
                                                    value="' . htmlspecialchars($edu['degree'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Degree">
                                                <label for="degree_' . $index . '" class="form-float-label">Degree</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="start_year_' . $index . '" name="education[' . $index . '][start_year]" 
                                                    value="' . htmlspecialchars($edu['start_year'] ?? '') . '"  data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="start_year_' . $index . '" class="form-float-label">Start Year</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="end_year_' . $index . '" name="education[' . $index . '][end_year]" 
                                                    value="' . htmlspecialchars($edu['end_year'] ?? '') . '" data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="end_year_' . $index . '" class="form-float-label">End Year</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Education History',
                            'label' => '<i class="ti ti-school"></i> Edit Your Education Background',
                            'button' => 'Save Education',
                            'script' => 'window.general.select();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'experienceadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'company', 'label' => 'Company', 'required' => true, 'col' => '12'],
                                ['type' => 'text', 'name' => 'position', 'label' => 'Position', 'required' => true, 'col' => '12'],
                                ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->experience ?? ''],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Experience',
                            'label' => '<i class="fa-solid fa-graduation-cap"></i> Add Your Work Experience',
                            'button' => 'Add',
                            'script' => 'window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'experienceedit') {
                        $experienceList = [];
                        if (!empty($info->experience)) {
                            $decoded = json_decode($info->experience, true);
                            if (is_array($decoded)) {
                                $experienceList = $decoded;
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <input type="hidden" name="user_id" value="' . $userId . '">
                            <div class="row p-2 g-3">';
                        foreach ($experienceList as $index => $exp) {
                            $content .= '
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-bold mb-3">Experience Entry #' . ($index + 1) . '</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="company_' . $index . '" name="experience[' . $index . '][company]" 
                                                    value="' . htmlspecialchars($exp['company'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Company Name">
                                                <label for="company_' . $index . '" class="form-float-label">Company</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="position_' . $index . '" name="experience[' . $index . '][position]" 
                                                    value="' . htmlspecialchars($exp['position'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Job Title">
                                                <label for="position_' . $index . '" class="form-float-label">Position</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="start_date_' . $index . '" name="experience[' . $index . '][start_date]" 
                                                    value="' . htmlspecialchars($edu['start_year'] ?? '') . '"  data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="start_date_' . $index . '" class="form-float-label">Start Date</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="end_date_' . $index . '" name="experience[' . $index . '][end_date]" 
                                                    value="' . htmlspecialchars($edu['end_year'] ?? '') . '" data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="end_date_' . $index . '" class="form-float-label">End Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Work Experience',
                            'label' => '<i class="ti ti-briefcase"></i> Edit Your Work Experience',
                            'button' => 'Save Experience',
                            'script' => 'window.general.select();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'emergency') {
                        $emergencyList = [
                            [
                                'type' => 'Primary',
                                'name' => '',
                                'relation' => '',
                                'phone' => ''
                            ],
                            [
                                'type' => 'Secondary',
                                'name' => '',
                                'relation' => '',
                                'phone' => ''
                            ]
                        ];
                        if (!empty($info->emergency_info)) {
                            $decoded = json_decode($info->emergency_info, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $i => $entry) {
                                    if (isset($emergencyList[$i])) {
                                        $emergencyList[$i] = array_merge($emergencyList[$i], $entry);
                                    }
                                }
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <input type="hidden" name="user_id" value="' . $userId . '">
                            <div class="row p-2 g-3">';
                        foreach ($emergencyList as $index => $contact) {
                            $label = htmlspecialchars($contact['type']);
                            $content .= '
                                <div class="p-1 mb-3">
                                    <h6 class="fw-bold mb-3">' . $label . ' Emergency Contact</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="float-input-control">
                                                <input type="text" id="name_' . $index . '" name="emergency[' . $index . '][name]" 
                                                    value="' . htmlspecialchars($contact['name']) . '" 
                                                    class="form-float-input" placeholder="Full Name">
                                                <label for="name_' . $index . '" class="form-float-label">Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="relation_' . $index . '" name="emergency[' . $index . '][relation]" 
                                                    value="' . htmlspecialchars($contact['relation']) . '" 
                                                    class="form-float-input" placeholder="Relation">
                                                <label for="relation_' . $index . '" class="form-float-label">Relation</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="phone_' . $index . '" name="emergency[' . $index . '][phone]" 
                                                    value="' . htmlspecialchars($contact['phone']) . '"  data-validate = "indian-phone"
                                                    class="form-float-input" placeholder="Phone Number">
                                                <label for="phone_' . $index . '" class="form-float-label">Phone</label>
                                            </div>
                                        </div>
                                        <input type="hidden" name="emergency[' . $index . '][type]" value="' . $label . '">
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Emergency Contacts',
                            'label' => '<i class="ti ti-alert-triangle"></i> Emergency Contact Info',
                            'button' => 'Save Contacts',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'bankadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'bank_name', 'label' => 'Bank Name', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'account_number', 'label' => 'Account Number', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'bank-account']],
                                ['type' => 'text', 'name' => 'ifsc_code', 'label' => 'Ifsc Code', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'ifsc']],
                                ['type' => 'select', 'name' => 'account_type', 'label' => 'Account Type', 'required' => true, 'col' => '6',  'options' => ['savings' => 'Savings', 'checking' => 'Checking'], 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'text', 'name' => 'branch', 'label' => 'Branch', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'city']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->bank_info ?? ''],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Bank Details',
                            'label' => '<i class="fa-solid fa-building-columns"></i> Enter Your Bank Details',
                            'button' => 'Add Bank',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'bankedit') {
                        $bankList = [];
                        if (!empty($info->bank_info)) {
                            $decoded = json_decode($info->bank_info, true);
                            if (is_array($decoded)) {
                                $bankList = $decoded;
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <input type="hidden" name="user_id" value="' . $userId . '">
                            <div class="row p-2 g-3">';
                        foreach ($bankList as $index => $bank) {
                            $content .= '
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-bold mb-3">Bank Detail #' . ($index + 1) . '</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="bank_name_' . $index . '" name="bank[' . $index . '][bank_name]"
                                                    value="' . htmlspecialchars($bank['bank_name'] ?? '') . '"
                                                    class="form-float-input" placeholder="Bank Name">
                                                <label for="bank_name_' . $index . '" class="form-float-label">Bank Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="account_number_' . $index . '" name="bank[' . $index . '][account_number]"
                                                    value="' . htmlspecialchars($bank['account_number'] ?? '') . '"
                                                    class="form-float-input" placeholder="Account Number">
                                                <label for="account_number_' . $index . '" class="form-float-label">Account Number</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="ifsc_code_' . $index . '" name="bank[' . $index . '][ifsc_code]"
                                                    value="' . htmlspecialchars($bank['ifsc_code'] ?? '') . '"
                                                    class="form-float-input" placeholder="IFSC Code">
                                                <label for="ifsc_code_' . $index . '" class="form-float-label">IFSC Code</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                            <select id="account_type_' . $index . '" name="bank[' . $index . '][account_type]" class="form-float-input" data-select="dropdown" data-value="' . ($bank['account_type'] ?? '') . '">
                                                    <option value=""></option>
                                                    <option value="savings">Savings</option>
                                                    <option value="current">Current</option>
                                                </select>
                                                <label for="account_type_' . $index . '" class="form-float-label">Account Type</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="branch_' . $index . '" name="bank[' . $index . '][branch]"
                                                    value="' . htmlspecialchars($bank['branch'] ?? '') . '"
                                                    class="form-float-input" placeholder="Branch">
                                                <label for="branch_' . $index . '" class="form-float-label">Branch</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="city_' . $index . '" name="bank[' . $index . '][city]"
                                                    value="' . htmlspecialchars($bank['city'] ?? '') . '"
                                                    class="form-float-input" placeholder="City">
                                                <label for="city_' . $index . '" class="form-float-label">City</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Bank Info',
                            'label' => '<i class="ti ti-building-bank"></i> Edit Bank Details',
                            'button' => 'Save Bank Info',
                            'script' => 'window.general.select();'
                        ];
                    }else if ($type == 'changePassword') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                ['type' => 'password', 'name' => 'current_password', 'label' => 'Current Password', 'required' => true, 'col' => '12'],
                                ['type' => 'password', 'name' => 'new_password', 'label' => 'New Password', 'required' => true, 'col' => '12'],
                                ['type' => 'password', 'name' => 'new_password_confirmation', 'label' => 'Confirm New Password', 'required' => true, 'col' => '12'],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-lock me-1"></i> Change Password',
                            'short_label' => 'Update your account password',
                            'button' => 'Change Password',
                            'script' => 'window.general.select();window.general.validate();'
                        ];
                    } else if ($type == 'deleteaccount') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'type', 'value' => $type, 'class'=>['mb-0']],
                                ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'user_id', 'value' => $userId, 'class'=>['mb-0']],
                                [
                                    'type' => 'raw',
                                    'html' => '
                                        <div class="w-100 rounded p-2 text-center">
                                            <div class="mb-3">
                                                <i class="fa-solid fa-circle-exclamation fa-2x text-danger"></i>
                                            </div>
                                            <h5 class="text-danger mb-2">Confirm Account Deletion</h5>
                                            <p class="mb-0 text-muted">
                                                Are you absolutely sure you want to delete your account?<br>
                                                <strong>This action is permanent and will erase all your data. It cannot be undone.</strong>
                                            </p>
                                        </div>',
                                    'col' => '12'
                                ],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Delete User',
                            'label' => '<i class="fa-solid fa-trash"></i> Confirm Delete User',
                            'button' => 'Delete',
                            'script' => '',
                        ];
                    }
                    break;
                default:
                    return ResponseHelper::emptyPopup();
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
    /**
     * Renders a popup to confirm bulk update of records.
     *
     * @param Request $request HTTP request object containing input data.
     * @param array $params Route parameters including token.
     * @return JsonResponse Custom UI configuration for the popup or an error message.
     */
    public function bulk(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['system']) || !isset($reqSet['table']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or missing required data.', 400);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id', '')));
            if (empty($ids)) {
                return ResponseHelper::moduleError('Invalid Data', 'No records specified for update.', 400);
            }
            // Fetch records details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]], 'all');
            if (!$result['status'] || empty($result['data'])) {
                return ResponseHelper::moduleError('Records Not Found', $result['message'] ?: 'The requested records were not found.', 404);
            }
            $records = $result['data'];
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            $recordCount = count($records);
            $maxDisplayRecords = 5;
            // Generate accordion for records
            $detailsHtml = sprintf('<div class="alert alert-warning" role="alert"><div class="accordion" id="updateAccordion-%s"><div class="accordion-item border-0"><h2 class="accordion-header p-0 my-0"><button class="accordion-button collapsed p-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-%s" aria-expanded="false" aria-controls="collapse-%s">Confirm Bulk Update of %d Record(s)</button></h2><div id="collapse-%s" class="accordion-collapse collapse" data-bs-parent="#updateAccordion-%s"><div class="accordion-body p-2 bg-light"><div class="accordion" id="updateRecords-%s">', $token, $token, $token, $recordCount, $token, $token, $token);
            if ($recordCount > $maxDisplayRecords) {
                $detailsHtml .= sprintf('<div class="d-flex justify-content-between align-items-center"><div class="text-muted">Updating <b>%d</b> records.</div><button class="btn btn-link btn-sm text-decoration-none text-primary sf-12" type="button" data-bs-toggle="collapse" data-bs-target="#details-%s" aria-expanded="false" aria-controls="details-%s">Details</button></div><div class="collapse mt-2" id="details-%s"><div class="table-responsive" style="max-height: 200px;">', $recordCount, $token, $token, $token);
            }
            $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            $displayRecords = $recordCount > $maxDisplayRecords ? array_slice($records, 0, 5) : $records;
            foreach ($displayRecords as $index => $record) {
                $recordArray = (array)$record;
                $recordId = htmlspecialchars($recordArray[$reqSet['act']] ?? 'N/A');
                $detailsHtml .= sprintf('<tr><td colspan="2"><b>Record %d (ID: %s)</b></td></tr>', $index + 1, $recordId);
                if (empty($recordArray)) {
                    $detailsHtml .= '<tr><td colspan="2" class="text-muted">No displayable details available</td></tr>';
                } else {
                    foreach ($recordArray as $key => $value) {
                        $detailsHtml .= sprintf('<tr><td>%s</td><td><b>%s</b></td></tr>', htmlspecialchars(ucwords(str_replace('_', ' ', $key))), htmlspecialchars($value ?? ''));
                    }
                }
            }
            $detailsHtml .= $recordCount > $maxDisplayRecords ? sprintf('<tr><td colspan="2" class="text-muted">... and %d more records</td></tr></table></div></div>', $recordCount - count($displayRecords)) : '</table>';
            $detailsHtml .= sprintf('</div><div class="mt-2"><i class="sf-10"><span class="text-danger">Note: </span>Only non-unique fields can be updated in bulk. Changes will apply to all %d selected records. Ensure values are valid to avoid data conflicts.</i></div></div></div></div></div></div>', $recordCount);
            // Initialize popup configuration
            $popup = [];
            $detailsHtmlPlacement = 'top';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'UserManagement_entities':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['data' => 'Data', 'unique' => 'Unique', 'select' => 'Select', 'other' => 'Other'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit UserManagement Entities',
                        'short_label' => '',
                        'button' => 'Update Entities',
                        'script' => 'window.skeleton.select();window.skeleton.unique();'
                    ];
                    break;
                default:
                    return ResponseHelper::emptyPopup();
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = '<input type="hidden" name="update_ids" value="' . $request->input('id', '') . '">';
            $content .= $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            $content = $detailsHtmlPlacement === 'top' ? $detailsHtml . $content : $content . $detailsHtml;
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
