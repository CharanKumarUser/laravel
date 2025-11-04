<?php
namespace App\Http\Controllers\System\Business\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX card data requests in the SupportAndHelp module with clean UI.
 */
class CardCtrl extends Controller
{
    /**
     * Handles AJAX requests for card data processing for modules, sections, and items.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed card data or error response
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token and validate configuration
            $reqSet = Skeleton::resolveToken($token);
            Developer::info($reqSet);
            if (!isset($reqSet['key']) || !isset($reqSet['table'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or lacks required configuration.', 400);
            }
            // Set view to card and parse filters
            $reqSet['view'] = 'card';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? '',
                'dateRange' => $filters['dateRange'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 12],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $view = [];
            $view = '';
            $title = 'Success';
            $message = 'SupportAndHelp card data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_support_tickets':
                case 'business_support_my_tickets':
                    $columns = [
                        'id'               => ['supports.id', true],
                        'support_id'       => ['supports.support_id', true],
                        'business_id'      => ['supports.business_id', true],
                        'company_id'       => ['supports.company_id', true],
                        'user_id'          => ['supports.user_id', true],
                        'subject'          => ['supports.subject', true],
                        'description'      => ['supports.description', true],
                        'issue_scope'      => ['supports.issue_scope', true],
                        'issue_category'   => ['supports.issue_category', true],
                        'issue_priority'   => ['supports.issue_priority', true],
                        'issue_status'     => ['supports.issue_status', true],
                        'reported_by'      => ['supports.reported_by', true],
                        'assigned_to'      => ['supports.assigned_to', true],
                        'parent_support_id' => ['supports.parent_support_id', true],
                        'reported_at'      => ['supports.reported_at', true],
                        'resolved_at'      => ['supports.resolved_at', true],
                        'resolution_notes' => ['supports.resolution_notes', true],
                        'attachment_path'  => ['supports.attachment_path', true],
                        'is_private_note'  => ['supports.is_private_note', true],
                        'is_active'        => ['supports.is_active', true],
                        'created_by'       => ['supports.created_by', true],
                        'updated_by'       => ['supports.updated_by', true],
                        'created_at'       => ['supports.created_at', true],
                        'updated_at'       => ['supports.updated_at', true],
                    ];
                    if($reqSet['key'] === 'business_support_my_tickets'){
                        $conditions = [
                            ['column' => 'supports.user_id', 'operator' => '=', 'value' => Skeleton::authUser()->user_id],
                        ];
                    }
                   
                   $view = '
                    <div class="col-xl-12 sf-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                             <div class="d-flex align-items-center">
                                    ::~\App\Http\Helpers\ProfileHelper->userProfile(::user_id::, ["flex","lg"], ["company","role", "scope"], 1)~::
                                </div>';         
                            if ($reqSet['key'] !== 'business_support_my_tickets') {
                                $view .= '
                                        <div>
                                            <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token']. '_e_::' . $reqSet['act'] . '::">
                                                <i class="ti ti-edit text-primary"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::">
                                                <i class="ti ti-trash text-danger"></i>
                                            </a>
                                        </div>';
                            }

                            $view .= '
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="fw-bold text-primary mb-1 text-nowrap me-2">::issue_category::</h6>
                                        <div><span class="fw-bold">Issue At: </span><span class="badge bg-light rounded-pill border border-1 text-dark">::issue_scope::</span></div>
                                    </div>
                                    <span><b class="me-1">subject:</b>::IF(issue_priority = "Low",<span class="text-success">::subject::</span>)::ELSEIF(issue_priority = \'Medium\', <span class="text-info">::subject::</span>)::ELSEIF(issue_priority = \'High\', <span class="text-warning">::subject::</span>)::ELSE(<span class="text-danger">::subject::</span>)::</span>
                                    <p class="mb-2 mt-2">&nbsp;&nbsp;&nbsp;&nbsp; ::description::</p>
                                    ::IF(resolution_notes IS NOT NULL, <div class="bg-secondary-transparent p-2 rounded"><span class="d-flex align-items-center"><i class="ti ti-info-circle me-1 sf-20"></i>Resolution Notes: </span><p class="mb-0 mt-2">&nbsp;&nbsp;&nbsp;&nbsp; ::resolution_notes::</p></div>)::
                                </div>
                                <div class="row border-top rounded p-3 mb-0 pb-0 g-1">
                                    <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                        <p class="mb-1 text-muted small"><strong>Issue Status</strong></p>
                                        <p class="mb-0">
                                            ::IF(issue_status = "Open",<span class="badge bg-danger rounded-pill">Open</span>)::ELSEIF(issue_status = \'In Progress\', <span class="badge bg-warning text-white rounded-pill">In Progress</span>)::ELSEIF(issue_status = \'Resolved\', <span class="badge bg-success text-white rounded-pill">Resolved</span>)::ELSEIF(issue_status = \'Reopened\',<span class="badge bg-danger text-white rounded-pill">Reopened</span>)::ELSE(<span class="badge bg-purple text-white rounded-pill">Closed</span>)::
                                        </p>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-sm-6">
                                        <p class="mb-1 text-muted small"><strong>Reported by</strong></p>
                                        <div class="d-flex align-items-center">
                                            <span class="fs-12">
                                                ::~\App\Http\Helpers\ProfileHelper->userProfile(::reported_by::, ["flex","xs"], ["company","role", "scope"], 1)~::
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-sm-6">
                                        <p class="mb-1 text-muted small"><strong>Raised At</strong></p>
                                        <p class="mb-0 d-flex align-items-center">
                                            <i class="ti ti-calendar-plus me-2"></i>
                                            <span>::IF(created_at IS NOT NULL,::created_at::, N/A)::</span>
                                        </p>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-sm-6">
                                        <p class="mb-1 text-muted small"><strong>Last Updated</strong></p>
                                        <p class="mb-0"><i class="ti ti-calendar-plus me-2"></i>::IF(updated_at IS NOT NULL,::updated_at::, Not Mentioned)::</p>
                                    </div>
                                </div>  
                            </div>
                        </div>
                    </div>';
                    $title = 'supports Retrieved';
                    $message = 'Support entity card data retrieved successfully.';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Prepare for set
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'view' => $view];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = CardHelper::generateResponse($set, $businessId);
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve card data.', 500);
        }
    }
}