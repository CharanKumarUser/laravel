<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton, Select, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};
/**
 * Controller for rendering the edit form for CompanyManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing CompanyManagement entities.
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
            $result = Data::fetch($reqSet['system'], $reqSet['table'], [['column'=>$reqSet['act'], 'value'=> $reqSet['id']]]);
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
                    case 'business_companies':
                        $type = $request->id ?? null;
                        if ($type == 'changelogo') {
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'hidden', 'col_class' => 'my-0', 'name' => 'form_type', 'value' => $type, 'class' => ['mb-0']],
                                    ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Company Logo" data-name="logo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . ($data && isset($data->logo) && $data->logo ? FileManager::getFile($data->logo) : asset('default/preview-square.svg')) . '"></div>', 'col' => '12'],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-md',
                                'position' => 'end',
                                'label' => '<i class="fa-solid fa-image me-1"></i> Change Logo',
                                'short_label' => 'Upload or update the company logo',
                                'button' => 'Update Logo',
                                'script' => 'window.general.select();window.general.files();'
                            ];
                        }
                        elseif ($type == 'changebanner') {
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'hidden', 'col_class' => 'my-0', 'name' => 'form_type', 'value' => $type, 'class' => ['mb-0']],
                                    ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="cover" data-label="Company Banner" data-name="banner" data-crop-size="400:150" data-target="#profile-photo-input" data-recommended-size="600px x 200px" data-file-size="2" data-src="' . ($data && isset($data->banner) && $data->banner ? FileManager::getFile($data->banner) : asset('default/preview-square.svg')) . '"></div>', 'col' => '12'],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => '<i class="fa-solid fa-icons me-1"></i> Edit Banner',
                                'short_label' => 'Choose or update the company Banner',
                                'button' => 'Update Banner',
                                'script' => 'window.general.select();window.general.files();'
                            ];
                        }
                        elseif ($type == 'editdetails') {
                            // Handle company edit
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'form_type', 'value' => $type, 'class'=>['mb-0']],
                                    ['type' => 'text', 'name' => 'name', 'label' => 'Company Name', 'value' => $data->name, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                                    ['type' => 'text', 'name' => 'legal_name', 'label' => 'Legal Name', 'value' => $data->legal_name, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                                    ['type' => 'text', 'name' => 'industry', 'label' => 'Industry', 'value' => $data->industry, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '100']],
                                    ['type' => 'select', 'name' => 'type', 'label' => 'Company Type', 'value' => $data->type, 'options' => ['Private Limited' => 'Private Limited', 'Public Limited' => 'Public Limited', 'Partnership' => 'Partnership', 'Sole Proprietorship' => 'Sole Proprietorship', 'LLP' => 'LLP'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                                    ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $data->email, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                                    ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'value' => $data->phone, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '50']],
                                    ['type' => 'url', 'name' => 'website', 'label' => 'Website', 'value' => $data->website, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                                    ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' =>    'dropdown', 'data-value' => $data->is_active]],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => '<i class="fa-solid fa-building me-1"></i> Edit Company',
                                'short_label' => 'Update company information and settings',
                                'button' => 'Update Company',
                                'script' => 'window.general.select();'
                            ];
                        }elseif($type == 'editaddress'){
                             $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'form_type', 'value' => $type, 'class'=>['mb-0']],
                                    ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $data->city, 'required' => false, 'col' => '3', 'attr' => ['maxlength' => '100']],
                                    ['type' => 'text', 'name' => 'state', 'label' => 'State', 'value' => $data->state, 'required' => false, 'col' => '3', 'attr' => ['maxlength' => '100']],
                                    ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'value' => $data->country, 'required' => false, 'col' => '3', 'attr' => ['maxlength' => '100']],
                                    ['type' => 'text', 'name' => 'pincode', 'label' => 'Pincode', 'value' => $data->pincode, 'required' => false, 'col' => '3', 'attr' => ['maxlength' => '20']],
                                    ['type' => 'textarea', 'name' => 'address_line1', 'label' => 'Address Line 1', 'value' => $data->address_line1, 'required' => false, 'col' => '12', 'attr' => ['maxlength' => '150']],
                                    ['type' => 'textarea', 'name' => 'address_line2', 'label' => 'Address Line 2', 'value' => $data->address_line2, 'required' => false, 'col' => '12', 'attr' => ['maxlength' => '150']],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => '<i class="fa-solid fa-building me-1"></i> Edit Address',
                                'short_label' => 'Update company Address',
                                'button' => 'Update Company',
                                'script' => 'window.general.select();'
                            ];
                        } else if ($type == 'sociallinks') {
                        $socialLinks = [];
                        if (!empty($data->social_links)) {
                            $decoded = json_decode($data->social_links, true);
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
                            <input type="hidden" name="form_type" value="' . $type . '">
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

                        } else {
                            return ResponseHelper::emptyPopup();
                        }
                        break;

                case 'business_company_holidays':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => '<div class="file-upload-container"data-file="image"data-file-crop="profile"data-label="Holiday image (Optional)" data-name="image" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px"data-file-size="2"data-src="' . ($data && isset($data->image) && $data->image ? FileManager::getFile($data->image) : asset('default/preview-square.svg')) . '"></div>', 'col' => '12',],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Holiday Name', 'value' => $data->name, 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '150']],
                            ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'value' => $data->start_date, 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'value' => $data->end_date, 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'select', 'name' => 'recurring_type', 'label' => 'Recurring Type', 'options' => ['none' => 'None', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->recurring_type]],
                            ['type' => 'select', 'name' => 'recurring_day', 'label' => 'Recurring Day',  'options' => ['' => 'Select Day', 'sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'], 'required' => false, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->recurring_day,]],
                            ['type' => 'select', 'name' => 'recurring_week', 'label' => 'Recurring Week', 'value' => $data->recurring_week, 'options' => ['' => 'Select Week','all'=>'All', 'first' => 'First', 'second' => 'Second', 'third' => 'Third', 'fourth' => 'Fourth', 'last' => 'Last'], 'required' => false, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->recurring_week,]],
                            ['type' => 'color', 'name' => 'color', 'label' => 'Background Color (Optional)', 'required' => false, 'value' => $data->color, 'col' => '4', 'attr' => ['placeholder' => '#FFFFFF']],
                            ['type' => 'color', 'name' => 'text-color', 'label' => 'Text Color (Optional)', 'required' => false, 'value' => $data->text_color, 'col' => '4', 'attr' => ['placeholder' => '#000000']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'value' => $data->description, 'required' => false, 'col' => '12', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar me-1"></i> Edit Holiday',
                        'short_label' => 'Modify holiday details and scheduling',
                        'button' => 'Update Holiday',
                        'script' => ' window.general.select(); window.general.validateForm();window.general.files();window.skeleton.datePicker();
                            console.log("Hello");
                            const type = document.querySelector("[name=\'recurring_type\']");
                            const day = document.querySelector("[name=\'recurring_day\']");
                            const week = document.querySelector("[name=\'recurring_week\']");

                            function toggleRecurring() {
                                const disable = type.value === "none";
                                [day, week].forEach(el => {
                                    el.value = disable ? "" : el.value;
                                    el.readOnly = disable;   // make readonly
                                });
                                window.general?.select();
                            }

                            toggleRecurring();
                            type.addEventListener("change", toggleRecurring);'
                    ];
                    break;
                case 'business_company_policies':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'company_id', 'value' => $request->id ?? '', 'class'=>['mb-0']],
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'value' => $data->sno, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Policy Name', 'value' => $data->name, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'value' => $data->description, 'required' => false, 'col' => '12', 'attr' => ['maxlength' => '500', 'rows' => '4']],
                            ['type' => 'text', 'name' => 'category', 'label' => 'Category', 'value' => $data->category, 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '100']],
                            ['type' => 'date', 'name' => 'effective_date', 'label' => 'Effective Date','value' => $data->effective_date, 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'date', 'name' => 'expiry_date', 'label' => 'Expiry Date', 'value' => $data->expiry_date, 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date','data-date-picker-allow' => 'future']],
                            ['type' => 'text', 'name' => 'version', 'label' => 'Version', 'value' => $data->version, 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '20']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-file-lines me-1"></i> Update Policy',
                        'short_label' => 'Fill out this form to update a company policy',
                        'button' => 'Update Policy',
                        'script' => 'window.general.select();window.general.validateForm();window.general.files();window.skeleton.datePicker();'
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
                case 'CompanyManagement_entities':
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
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit CompanyManagement Entities',
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
            return response()->json(['token' => $token,'type' => $popup['type'],'size' => $popup['size'],'position' => $popup['position'],'label' => $popup['label'],'short_label' => $popup['short_label'],'content' => $content,'script' => $popup['script'],'button_class' => $popup['button_class'] ?? '','button' => $popup['button'] ?? '','footer' => $popup['footer'] ?? '','header' => $popup['header'] ?? '','validate' => $reqSet['validate'] ?? '0','hold_popup' => $holdPopup,'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}   