<?php
namespace App\Http\Controllers\System\Business\SmartPresence;
use App\Facades\{BusinessDB, Data, Developer, Select, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for SmartPresence entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new SmartPresence entities.
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
                case 'business_smart_geo_location':
                    if (!($mapsApi = env('GOOGLE_MAPS_API'))) throw new Exception('Google Maps API key is missing in environment configuration.');
                    $popup = ['form' => 'builder', 'labelType' => 'floating', 'fields' => [
                        ['type' => 'select', 'name' => 'company_id', 'label' => 'Select Company', 'options' => Select::options('companies', 'array', ['company_id' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ['type' => 'text', 'name' => 'name', 'label' => 'Location Name', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'name', 'maxlength' => '255']],
                        ['type' => 'raw', 'html' => '<div data-smart-map data-maps-api="' . htmlspecialchars($mapsApi) . '" data-size="100%*300px" data-radius="100" data-radius-capture="#radius-input" data-name="pin_coordinates"></div>'],
                        ['type' => 'number', 'name' => 'radius', 'label' => 'Radius (meters)', 'value' => '100', 'id' => 'radius-input', 'required' => true, 'col' => '4', 'attr' => ['min' => '1', 'step' => '1']],
                        ['type' => 'switch', 'name' => 'in_radius', 'label' => 'Require Presence Within Radius', 'col_class' => 'd-flex align-items-center', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes']],
                        ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                    ], 'type' => 'modal', 'size' => 'modal-lg', 'position' => 'end', 'label' => '<i class="fa-regular fa-map-location-dot me-1"></i> Add Business Location', 'short_label' => 'Add Location', 'button' => 'Save Location', 'script' => 'window.general.select();window.presence.smartMap();'];
                    break;
                case 'business_smart_enroll_face':
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '<input type="hidden" name="save_token" value="'.$token.'">
                            <div class="float-input-control mb-3">
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
                                                data-target-age=".captured_age">
                                            </div>
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
                        'script' => 'window.general.select();window.presence.smartFace();'
                    ];
                break;
                case 'business_smart_attendance':
                        $user_id = Skeleton::authUser()->user_id;
                        $record = BusinessDB::table('smart_mapping AS sm')
                            ->leftJoin('smart_geo_locations AS sgl', 'sm.geo_location_id', '=', 'sgl.geo_location_id')
                            ->leftJoin('smart_face_enroll AS sfe', 'sm.face_enroll_id', '=', 'sfe.face_enroll_id')
                            ->leftJoin('users AS u', 'sm.user_id', '=', 'u.user_id')
                            ->select(
                                'sm.geo_location_id','sm.face_enroll_id','u.first_name',
                                'sfe.embedding','sgl.pin_coordinates','sgl.radius','sgl.in_radius'
                            )
                            ->where('sm.user_id', $user_id)
                            ->whereNull('sm.deleted_at')
                            ->where('sm.is_active', true)
                            ->where(fn($q) => $q->where('sgl.is_active', true)->orWhereNull('sgl.is_active'))
                            ->where(fn($q) => $q->where('sfe.is_active', true)->orWhereNull('sfe.is_active'))
                            ->first();

                        $method = '';
                        if ($record && $record->geo_location_id && $record->face_enroll_id) {
                            $method = 'geo-face';
                        } elseif ($record && $record->geo_location_id && !$record->face_enroll_id) {
                            $method = 'geo';
                        }

                        if (!$method) {
                            $popup = [
                                'form' => 'custom',
                                'content' => ' <div class="text-center">
                                    <img src="' . asset('errors/empty-popup.svg') . '" alt="Explore More" class="img-fluid mb-3" style="max-width:200px;">
                                    <h2 class="h4 fw-bold">Geo location or geo-face is not assigned to you, contact admin.</h2>
                                    <div class="mt-3">
                                        <a href="#" class="btn btn-outline-secondary rounded-pill me-2 px-3" data-bs-dismiss="modal">Close</a>
                                        <a href="' . url('/dashboard') . '" class="btn btn-primary rounded-pill px-3">Explore Dashboard</a>
                                    </div>
                                </div>',
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => '<i class="fa-regular fa-user me-1"></i> Smart Match',
                                'short_label' => 'Smart Match',
                                'button' => 'Run Matching',
                                'footer' => 'hide',
                                'script' => 'window.general.select();'
                        ];
                            break;
                        }

                        $data = ['user' => ['name' => $record->first_name ?? 'Unknown User','image' => '']];
                        if ($record->face_enroll_id) {
                            $data['face'] = [
                                'strict-mode' => true,'accuracy' => '60','attempts' => '5','re-take' => true,
                                'embedding' => json_decode($record->embedding, true)
                            ];
                        }
                        if ($record->geo_location_id) {
                            $data['location'] = [
                                'strict-mode' => true,'coordinates' => $record->pin_coordinates ?? '0,0',
                                'radius' => $record->radius ?? '1000','in-radius' => (bool)($record->in_radius ?? true),
                                'locate-me' => true,'size' => '100%*200px'
                            ];
                        }
                        $jsonData = json_encode($data, true);
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '
                            <input type="hidden" name="save_token" value="'.$token.'">
                            <input type="hidden" name="method" value="'.$method.'">
                            <div id="smart-match" data-container-size="100%*200px" data-name="smart_match" 
                                data-text="Face Scan" data-maps-api="'.env('GOOGLE_MAPS_API').'"></div>
                            <div id="result-notes"></div>
                            <div class="d-flex gap-2 align-items-center justify-content-center mt-2">
                                <h6>Please Select Mode</h6>
                                <button 
                                    type="button"
                                    class="btn text-white btn-rounded"
                                    data-toggle="multiple"
                                    data-name="punch"
                                    data-text=\'["Check-In"=>"#21e63eff", "Check-Out"=>"#c9321fff", "Overtime-In"=>"#f40d90ff", "Overtime-Out"=>"#3509f6ff"]\'>
                                </button>
                            </div>
                        ',
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-user me-1"></i> Smart Match',
                        'short_label' => 'Smart Match',
                        'button' => 'Run Matching',
                        'footer' => 'hide',
                        'script' => 'window.general.select();
                                    window.presence.smartFace();
                                    window.general.files();
                                    window.general.toggle();
                                    window.presence.smartMatch('.$jsonData.', "smart-match", "#result-notes");'
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
