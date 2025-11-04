<?php
namespace App\Http\Controllers\System\Business\UserManagement;
use App\Facades\{Data, Developer, Scope, Select, Skeleton, FileManager,Notification};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for UserManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new UserManagement entities.
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
            $holdPopup = false;
            $system = ['central' => 'Central', 'business' => 'Business'];
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
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => '6'],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '20', 'data-unique' => Skeleton::skeletonToken('open_roles_unique') . '_u', 'data-unique-msg' => 'This Role is already registered']],
                            // ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '20']],
                            ['type' => 'select', 'name' => 'parent_role_id', 'label' => 'Parent Role', 'options' => ['' => '--- Select Parent Role ---'] +  Select::options('roles', 'array', ['role_id' => 'name']), 'value' => null, 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_system_role', 'label' => 'System Role', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Add Role',
                        'short_label' => 'Define and add your own role for your company.',
                        'button' => 'Add',
                        'script' => 'window.general.select();window.general.unique();window.general.pills();'
                    ];
                    break;
                case 'open_scope_mapping':
                case 'open_um_users':
                    $role= Skeleton::authUser('roles');
                    $role_id=array_key_first($role);
                    $scope_id = ($role_id === 'ADMIN') ? null : Skeleton::authUser()->scope_id;
                    $scopes = Scope::getScopePaths('all', $scope_id, true);
                    $scopeId = isset($reqSet['id']) && !empty($reqSet['id']) ? ($reqSet['id'] === 'company' ? null : $reqSet['id']) : null;
                    $scopeInfo = [];
                    $system = Skeleton::authUser('system');
                    if ($scopeId) {
                        $scopeResult = Data::fetch($system, 'scopes', ['scope_id' => $scopeId]);
                        $scopeInfo = $scopeResult['data'][0] ?? null;
                    }   
                    $commonFields = [
                        ['type' => 'raw', 'html' => '<div class="file-upload-container mt-3" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo"  data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . asset('default/preview-square.svg') . '"></div>', 'col' => '12'],
                        ['type' => 'text', 'name' => 'sno', 'label' => 'Sl. No.', 'required' => true, 'col' => '2', 'attr' => ['data-validate' => 'sno']],
                        ['type' => 'text', 'name' => 'unique_code', 'label' => 'User Code (e.g. EMP001)', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '30', 'data-unique' => Skeleton::skeletonToken('open_um_users_unique_code') . '_u', 'data-unique-msg' => 'This Unique Code is already registered']],
                        ['type' => 'text', 'name' => 'username', 'label' => 'User Name', 'required' => true, 'col' => '3', 'attr' => ['data-validate' => 'username', 'maxlength' => '100', 'data-unique' => Skeleton::skeletonToken('open_um_users_username') . '_u', 'data-unique-msg' => 'This Username is already exists.']],
                        ['type' => 'select', 'name' => 'role_id', 'label' => 'App Role', 'options' => Select::options('roles', 'array', ['role_id' => 'name']), 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                        ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true, 'col' => '3'],
                        ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true, 'col' => '3'],
                        ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'options' => ['male' => 'Male', 'female' => 'Female', 'non_binary' => 'Non-binary', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                        ['type' => 'date', 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'required' => false, 'col' => '3', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                        ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'col' => '3', 'attr' => ['data-validate' => 'email']],
                        ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'required' => false, 'col' => '3', 'attr' => ['data-validate' => 'indian-phone']],
                        ['type' => 'date', 'name' => 'hire_date', 'label' => 'Joining Date', 'required' => false, 'col' => '3', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                        ['type' => 'select', 'name' => 'designation_id', 'label' => 'Designation', 'options' => Select::options('designations', 'array', ['designation_id' => 'name']), 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                    ];
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Add New User' . ($scopeId ? ' to <b>' . $scopeInfo['name'] . '</b>' : ''),
                        'short_label' => 'Add a new user to the system. Roles and settings can be configured later during editing.',
                        'button' => 'Save user',
                        'fields' => $scopeId ? array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100 my-3" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="w-100" id="render-scope-form"></div>', 'col' => '12'],
                        ]) : array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100 my-3" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="w-100" id="render-scope-form"></div>', 'col' => '12'],
                        ]),
                        'script' => 'window.general.select();window.skeleton.datePicker();window.general.validateForm();window.general.unique();window.general.files();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ',["' . $scopeId . '"] , "single", true);
                            const input = document.querySelector("[data-scope]");
                            const wrapper = document.querySelector("#render-scope-form");
                            const current = ' . json_encode($scopeInfo['schema'] ?? "") . ' || "";
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
                                var set = "";
                                    if(current){ set = current; } else { set = active.getAttribute("data-schema"); }
                                    const schema = set || "[]"; const label = active.getAttribute("data-label")?.trim() || ""; const desc = active.getAttribute("data-desc") || "Form based on selected scope"; const id = active.getAttribute("data-id") || `default-${Date.now()}`; const allow = active.getAttribute("data-allow");
                                    renderForm(schema, label, desc, "form_" + id, allow);
                                } else { wrapper.innerHTML = ""; clearInputs(); } initScopeEvents(); observeScopeChange(); }'
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
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
