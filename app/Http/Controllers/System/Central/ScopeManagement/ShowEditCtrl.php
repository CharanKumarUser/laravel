<?php
namespace App\Http\Controllers\System\Central\ScopeManagement;
use App\Facades\{Data, Developer, Random, Scope, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};

/**
 * Controller for rendering the edit form for ScopeManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing ScopeManagement entities.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = Data::fetch($reqSet['system'], $reqSet['table'],  [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            if (!$data) {
                return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
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
                case 'open_scopes':
                $scopes = Scope::getScopePaths('all', null, true);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'number', 'name' => 'sno', 'label' => 'SN', 'required' => true, 'value' => $data->sno, 'col' => '2', 'attr' => ['maxlength' => '50', 'data-unique' => Skeleton::skeletonToken('open_scope_sno') . '_u', 'data-unique-msg' => 'This Sno is already Provided']],
                            ['type' => 'text', 'name' => 'code', 'label' => 'Code', 'required' => true, 'value' => $data->code, 'col' => '3', 'attr' => ['maxlength' => '50', 'data-unique' => Skeleton::skeletonToken('open_scopes_code') . '_u', 'data-unique-msg' => 'This Code is already registered']],
                            ['type' => 'text', 'name' => 'scope_name', 'label' => 'Scope Name', 'required' => true, 'value' => $data->name, 'col' => '4'],
                            ['type' => 'text', 'name' => 'group', 'label' => 'Group', 'required' => true, 'value' => $data->group, 'col' => '3'],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'value' => $data->description, 'col' => '12', 'attr' => ['rows' => '3']],
                            ['type' => 'color', 'name' => 'background', 'label' => 'Background Color', 'required' => false, 'value' => $data->background, 'col' => '3', 'attr' => ['placeholder' => '#FFFFFF']],
                            ['type' => 'color', 'name' => 'color', 'label' => 'Text Color', 'required' => false, 'value' => $data->color, 'col' => '3', 'attr' => ['placeholder' => '#000000']],
                            ['type' => 'select', 'name' => 'allow_form', 'label' => 'Allow Visibility', 'options' => ['1' => 'Allow', '0' => 'Hide'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->allow_form]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Active', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                            ['type' => 'raw', 'html' => '
                                    <label class="text-primary mt-3 ms-1 sf-12">Parent ID</label>
                                    <div class="path-dropdown w-100" data-path-id="scope-paths" data-path-name="parent_id">
                                    <input type="hidden" data-scope name="parent_id">
                                        <div class="path-trigger" data-placeholder="Select Root Scope">Select an option</div>
                                        <div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="p-2 rounded bg-light w-100"><input type="hidden" name="scope_id" value="'.$data->scope_id.'">
                                        <h5>Custom Form for This Scope <small class="sf-10">(Optional)</small></h5>
                                        <p class="text-muted sf-11 my-2">
                                            <strong>Note:</strong> Define a custom form structure specific to this scope. The order in which you arrange the form fields here 
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

                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-diagram-project me-1"></i> Edit Scope',
                        'short_label' => 'Edit scope details to shape your company hierarchy.',
                        'button' => 'Update Scope',
                        'script' => "window.general.select();window.general.unique();window.skeleton.path('scope-paths', " . $scopes . ", [\"$data->parent_id\"], 'single');window.skeleton.formBuilder('scope-form', $data->schema);
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
                            setTimeout(() => { wrapper.innerHTML = ''; wrapper.removeAttribute('class'); clearWrapperInputs(); window.skeleton.formBuilder('scope-form', $data->schema); hideShimmer(); }, 1000);
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
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0','hold_popup' => $holdPopup, 'status' => true]);
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
                case 'ScopeManagement_entities':
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
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit ScopeManagement Entities',
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
            return response()->json(['token' => $token,'type' => $popup['type'],'size' => $popup['size'],'position' => $popup['position'],'label' => $popup['label'],'content' => $content,'script' => $popup['script'],'button_class' => $popup['button_class'] ?? '','button' => $popup['button'] ?? '','footer' => $popup['footer'] ?? '','header' => $popup['header'] ?? '','validate' => $reqSet['validate'] ?? '0','hold_popup' => $holdPopup,'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}