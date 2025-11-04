<?php
namespace App\Http\Controllers\System\Business\SmartPresence;
use App\Facades\{Data, Developer, Random, Skeleton, Select, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};
/**
 * Controller for rendering the edit form for SmartPresence entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing SmartPresence entities.
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
                case 'business_smart_geo_location':
                     if (!($mapsApi = env('GOOGLE_MAPS_API'))) throw new Exception('Google Maps API key is missing in environment configuration.');
                    $popup = ['form'=>'builder','labelType'=>'floating','fields'=>[
                        ['type'=>'select','name'=>'company_id','label'=>'Select Company','options'=>Select::options('companies','array',['company_id'=>'name']),'required'=>true,'col'=>'6','attr'=>['data-select'=>'dropdown', 'data-value'=>$data->company_id]],
                        ['type'=>'text','name'=>'name','label'=>'Location Name','required'=>true,'col'=>'6', 'value'=>$data->name, 'attr'=>['data-validate'=>'name','maxlength'=>'255','data-unique'=>Skeleton::skeletonToken('SmartPresence_entities_unique').'_u','data-unique-msg'=>'This location name is already in use']],
                        ['type'=>'raw','html'=>'<div data-smart-map data-maps-api="'.htmlspecialchars($mapsApi).'" data-size="100%*300px" data-radius="100" data-radius-capture="#radius-input" data-name="pin_coordinates" data-coordinates='.$data->pin_coordinates.'></div>'],
                        ['type'=>'number','name'=>'radius','label'=>'Radius (meters)','value'=>'100','id'=>'radius-input', 'value'=>$data->radius, 'required'=>true,'col'=>'4','attr'=>['min'=>'1','step'=>'1']],
                        ['type'=>'switch','name'=>'in_radius','label'=>'Require Presence Within Radius','col_class'=>'d-flex align-items-center','required'=>false,'col'=>'4','options'=>['0'=>'No','1'=>'Yes']],
                        ['type'=>'select','name'=>'is_active','label'=>'Status','options'=>['1'=>'Active','0'=>'Inactive'],'required'=>true,'col'=>'4','attr'=>['data-select'=>'dropdown', 'data-value'=>$data->is_active]],
                    ],'type'=>'modal','size'=>'modal-lg','position'=>'end','label'=>'<i class="fa-regular fa-map-location-dot me-1"></i> Add Business Location','short_label'=>'Add Location','button'=>'Save Location','script'=>'window.general.select();window.presence.smartMap();'];
                    break;

                case 'business_smart_enroll_face':

                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '<input type="hidden" name="save_token" value="'.$token.'">
                            <div class="float-input-control mb-3">
                                <select id="userSelect" name="user_id" class="form-float-input" required data-select="dropdown">
                                    ' . Select::users([], 'user:role', 'html') . '
                                </select>
                                <label for="userSelect" class="form-float-label">Select User<span class="text-danger ms-1">*</span></label>
                            </div>
                            <div class="float-input-control mb-3">
                                <select id="statusSelect" name="is_active" class="form-float-input" required data-select="dropdown">
                                    <option value="1">Active</option>
                                    <option value="0">Inctive</option>
                                </select>
                                <label for="statusSelect" class="form-float-label">Status<span class="text-danger ms-1">*</span></label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                            <div data-smart-face-enroll 
                                                data-name="user_face" 
                                                data-target-controls=".controls_area" 
                                                data-target-instruction=".instruction_area" 
                                                data-target-emotion=".captured_emotion" 
                                                data-target-gender=".captured_gender" 
                                                data-target-age=".captured_age"
                                                data-capture-datauri-value="'. FileManager::getFile($data->capture) .'"
                                                data-emotion-value="'.$data->emotion.'"
                                                data-gender-value="'.$data->gender.'"
                                                data-age-value="'.$data->age.'"></div>
                                                
                                </div>
                                <div class="col-md-6">
                                <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                    <div class="control-center w-100 p-2">
                                            <div class="controls_area mb-3" aria-label="Face enrollment controls"></div>
                                            <div class="instruction_area mb-3 text-center text-muted" role="status" aria-live="polite">
                                                Click Start to begin enrollment. <small class="text-muted d-block mt-1">Ensure good lighting and center your face.</small>
                                            </div>
                                            <div class="progress mb-3" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-3"><label class="text-success">Detected Emotion:</label><div class="captured_emotion">Unknown</div></div>
                                            <div class="d-flex justify-content-between align-items-center mb-3"><label class="text-info">Detected Gender</label><div class="captured_gender">Unknown</div></div>
                                            <div class="d-flex justify-content-between align-items-center mb-3"><label class="text-primary">Approximate Age</label><div class="captured_age">Unknown</div></div>
                                    </div>
                                    </div>
                                </div>
                            </div>',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Enroll User Face',
                        'short_label' => 'Enroll Face',
                        'button' => 'Save Enrollment',
                        'script' => 'window.general.select();window.presence.smartFace();window.general.files();'
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
                case 'SmartPresence_entities':
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
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit SmartPresence Entities',
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