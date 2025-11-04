<?php
namespace App\Http\Controllers\System\Business\ScopeManagement;
use App\Facades\{Data, Skeleton, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};
/**
 * Controller for rendering the view form for ScopeManagement entities.
 */
class ViewCtrl extends Controller
{
    /**
     * Columns to exclude from the details table globally.
     *
     * @var array
     */
    protected $excludedColumns = ['id', 'unique_id', 'content', 'delete_on', 'restored_at', 'password', 'deleted_at', 'deleted_on'];
    /**
     * Renders a popup form for viewing ScopeManagement entities.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = $data = '';
            if(!$reqSet['id'] == "scope") {
                $result = Data::fetch($reqSet['system'], $reqSet['table'], [$reqSet['act'] => $reqSet['id']]);
                $data = $result['data'][0] ?? null;
                if (!$data) {
                return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
            }
            }
            
            
            // Initialize popup configuration
            $popup = [];
            $holdPopup = $script = false;
            $title = 'View Form Loaded';
            $message = 'View form loaded successfully.';
            $allowDefault = false;
            $detailsHtml = '';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_scopes_toggle_btn':
                $set = Scope::getScopePaths('all', null, true);
                $highlight = $reqSet['param'] ?? '';
                $content = Scope::renderPath($set, $highlight);
                    $popup = [
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => 'Scope Menu',
                        'form' => 'custom',
                        'labelType' => 'above',
                        'content' => $content,
                        'button' => 'Close',
                        'button_class' => 'd-none',
                        'footer' => true,
                        'header' => true,
                        'script' => 'window.general.tooltip();'
                    ];
                    $title = 'View Entity Form';
                    $message = 'ScopeManagement entity view form loaded successfully.';
                    break;
                // Handle invalid configuration keys
                default:
                    $detailsHtml = '';
                    if ($allowDefault) {
                        $excludedColumns = property_exists($this, 'excludedColumns') ? $this->excludedColumns : [];
                        $filteredRecord = array_diff_key((array) $data, array_flip($excludedColumns));
                        $detailsHtml = '<div class="table-responsive"><table class="table table-sm table-borderless table-striped table-hover mb-0"><thead><tr class="bg-light"><th>Field</th><th>Value</th></tr></thead><tbody>';
                        if (!empty($filteredRecord)) {
                            foreach ($filteredRecord as $key => $value) {
                                $detailsHtml .= '<tr><td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td><td><b>' . htmlspecialchars($value ?? '') . '</b></td></tr>';
                            }
                        } else {
                            $detailsHtml .= '<tr><td colspan="2">No displayable details available</td></tr>';
                        }
                        $detailsHtml .= '</tbody></table></div>';
                    } else {
                        $detailsHtml = '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100 h-100 p-3"><img src="' . asset('errors/empty.svg') . '" alt="No Details Available" class="img-fluid mb-2" style="max-width: 150px;"><h3 class="h5 mb-2 fw-bold">No Details Available</h3><p class="text-muted mb-2" style="max-width: 400px;">No displayable details are available for this record.</p><div class="d-flex flex-wrap justify-content-center gap-2 mt-2"><button type="button" class="btn btn-outline-primary btn-sm px-4 rounded-pill" data-bs-dismiss="offcanvas">View Another Entry</button></div></div>';
                    }
                    $popup = ['type' => 'offcanvas', 'size' => '', 'position' => 'end', 'label' => 'Record Details', 'form' => 'builder', 'labelType' => 'above', 'content' => $detailsHtml, 'button' => 'View', 'button_class' => 'd-none'];
                    $title = 'View Record';
                    $message = 'Record details loaded successfully.';
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'content' => $popup['content'], 'script' => $popup['script'] ?? '', 'button_class' => $popup['button_class'] ?? 'd-none', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'status' => true, 'hold_popup' => $holdPopup]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}