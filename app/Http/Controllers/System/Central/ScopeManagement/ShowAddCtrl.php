<?php
namespace App\Http\Controllers\System\Central\ScopeManagement;
use App\Facades\{Data, Developer, Scope, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for ScopeManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new ScopeManagement entities.
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
                case 'open_scopes':
                    $scopes = Scope::getScopePaths('all', null, true);
                    $parentId = isset($reqSet['id']) && !empty($reqSet['id']) ? ($reqSet['id'] === 'company' ? null : $reqSet['id']) : null;
                    $scopeInfo = [];
                    $system = Skeleton::authUser('system');
                    if ($parentId) {
                        $parentResult = Data::fetch($system, 'scopes', ['where' => ['scope_id' => $parentId]]);
                        $scopeInfo = $parentResult['data'][0] ?? null;
                    }
                    $commonFields = [
                        ['type' => 'number', 'name' => 'sno', 'label' => 'SN', 'required' => true, 'col' => '2', 'attr' => ['maxlength' => '50', 'data-unique' => Skeleton::skeletonToken('open_scope_sno') . '_u', 'data-unique-msg' => 'This Sno is already Provided']],
                        ['type' => 'text', 'name' => 'code', 'label' => 'Code', 'required' => true, 'col' => '3', 'attr' => ['maxlength' => '50', 'data-unique' => Skeleton::skeletonToken('open_scopes_code') . '_u', 'data-unique-msg' => 'This Code is already registered']],
                        ['type' => 'text', 'name' => 'scope_name', 'label' => 'Scope Name', 'required' => true, 'col' => '4'],
                        ['type' => 'text', 'name' => 'group', 'label' => 'Group', 'required' => true, 'col' => '3'],
                        ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'attr' => ['rows' => '3']],
                        ['type' => 'color', 'name' => 'background', 'label' => 'Background Color', 'required' => false, 'value' => '#00B4AF', 'col' => '3', 'attr' => ['placeholder' => '#FFFFFF']],
                        ['type' => 'color', 'name' => 'color', 'label' => 'Text Color', 'required' => false, 'value' => '#FFFFFF', 'col' => '3', 'attr' => ['placeholder' => '#000000']],
                        ['type' => 'select', 'name' => 'allow_form', 'label' => 'Allow Visibility', 'options' => ['1' => 'Allow', '0' => 'Hide'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                        ['type' => 'select', 'name' => 'is_active', 'label' => 'Active', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                    ];
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => $parentId ? array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '<input type="hidden" name="parent_id" value="' . $parentId . '">', 'col' => '12'],
                            ['type' => 'raw', 'html' => '
                                <div class="p-2 rounded bg-light w-100 mt-3">
                                    <h5>Custom Form for This Scope <small class="sf-10">(Optional)</small></h5>
                                    <p class="text-muted sf-11 my-2">
                                        <strong>Note:</strong> Define a custom form structure specific to this scope. 
                                        If visibility is enabled, it will be displayed when adding users. 
                                        The order in which you arrange the form fields here 
                                        will be maintained when the form is shown to end users.<br>
                                        <strong class="text-danger">Global Data Access:</strong> Form data entered here can be accessed globally
                                        based on your configuration.
                                    </p>
                                    <div class="form-builder-wrapper"
                                        data-form-builder-id="scope-form"
                                        data-form-builder-name="scope_schema"
                                        data-form-builder-fields="text|number|textarea|select|date">
                                    </div>
                                </div>', 'col' => '12']
                        ]) : array_merge($commonFields, [
                            ['type' => 'raw', 'html' => '
                                <div class="path-dropdown w-100 my-3" data-path-id="scope-paths" data-path-name="parent_id">
                                    <input type="hidden" data-scope name="parent_id">
                                    <div class="path-trigger" data-placeholder="Select Root Scope">Select an option</div>
                                    <div class="path-dropdown-menu" data-scope-area></div>
                                </div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '
                                <div class="p-2 rounded bg-light w-100">
                                    <h5>Custom Form for This Scope <small class="sf-10">(Optional)</small></h5>
                                    <p class="text-muted sf-11 my-2">
                                        <strong>Note:</strong> Define a custom form structure specific to this scope. 
                                        If visibility is enabled, it will be displayed when adding users. 
                                        The order in which you arrange the form fields here 
                                        will be maintained when the form is shown to end users.<br>
                                        <strong class="text-danger">Global Data Access:</strong> Form data entered here can be accessed globally
                                        based on your configuration.
                                    </p>
                                    <div class="form-builder-wrapper"
                                        data-form-builder-id="scope-form"
                                        data-form-builder-name="scope_schema"
                                        data-form-builder-fields="text|number|textarea|select|date">
                                    </div>
                                </div>', 'col' => '12']
                        ]),
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-diagram-project me-1"></i>'. ($parentId ? 'Add Scope under <b>' . $scopeInfo['name'] . '</b>' : 'Add Scope'),
                        'short_label' => 'Enter scope details to shape your company hierarchy.',
                        'button' => 'Add Scope',
                        'script' => "window.general.select();window.general.unique();window.skeleton.path('scope-paths', " . $scopes . ", [], 'single', true);window.skeleton.formBuilder('scope-form', null);
                        const input = document.querySelector('[data-scope]');
                        const wrapper = document.querySelector('[data-form-builder-id=\"scope-form\"]');
                        const showShimmer = () => {
                            if (!wrapper) return;wrapper.innerHTML = '';wrapper.classList.add('form-shimmer');
                        };
                        const hideShimmer = () => {
                            wrapper?.classList.remove('form-shimmer');
                        };
                        const clearWrapperInputs = () => {
                            const inputField = document.querySelector('input[name=\"scope_schema\"]');
                            if (inputField) inputField.remove();
                        };
                        const reinitializeFormBuilder = () => {
                            if (!wrapper || !window.skeleton.formBuilder) {return;}showShimmer();
                        clearWrapperInputs();
                            setTimeout(() => { wrapper.innerHTML = ''; wrapper.removeAttribute('class'); clearWrapperInputs(); window.skeleton.formBuilder('scope-form', null); hideShimmer(); }, 1000);
                        };
                        if (input && wrapper) {
                            const observer = new MutationObserver(() => {clearWrapperInputs();reinitializeFormBuilder();});
                            observer.observe(input, { attributes: true, attributeFilter: ['value'] });
                        }"
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
