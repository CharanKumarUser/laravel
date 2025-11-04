<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{BusinessDB, Data, Developer, Random, Skeleton, Profile, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX card data requests in the LeavesAndRequests module with clean UI.
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
            $columns = $conditions = $joins = $custom = [];
            $user_id = Skeleton::authUser()->user_id;
            $view = '';
            $title = 'Success';
            $message = 'LeavesAndRequests card data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_request_approve':
                case 'business_requests':
                    $user_id = Skeleton::authUser()->user_id;
                    $columns = [
                        'id' => ['requests.id', true],
                        'request_id' => ['requests.request_id', true],
                        'request_type' => ['requests.request_type', true],
                        'user_id' => ['requests.user_id', true],
                        'company' => ['companies.name AS company', true],
                        'name' => ['request_types.name', true],
                        'start_datetime' => ['requests.start_datetime', true],
                        'end_datetime' => ['requests.end_datetime', true],
                        'subject' => ['requests.subject', true],
                        'reason' => ['requests.reason', true],
                        'notes'  => ['requests.notes', true],
                        'approval_status' => ['requests.approval_status', true],
                        'decision_by' => ['requests.decision_by', true],
                        'decision_at' => ['requests.decision_at', true],
                        'created_by' => ['requests.created_by', true],
                        'updated_by' => ['requests.updated_by', true],
                        'created_at' => ['requests.created_at', true],
                        'updated_at' => ['requests.updated_at', true],
                    ];
                     if($reqSet['key'] == 'business_requests'){
                        $conditions = [
                            ['column' => 'requests.user_id', 'operator' => '=', 'value' => Skeleton::authUser()->user_id],
                        ];
                    }
                    if($reqSet['key'] == 'business_request_approve'){
                        $scopeIds = Scope::userChildScopes();
                        $conditions = [
                            ['column' => 'users.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                        ];
                    }
                    $conditions = [
                        ['column' => 'requests.tag_to', 'operator' => '=', 'value' => $user_id],
                    ];


                    $joins = [
                        ['type' => 'left', 'table' => 'users', 'on' => [['requests.user_id', 'users.user_id']]],
                        ['type' => 'left', 'table' => 'request_types', 'on' => [['requests.request_type_id', 'request_types.request_type_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['users.company_id', 'companies.company_id']]],
                    ];
                    $view = '
                    <div class="col-xl-12 col-sm-12 sf-12">
                        <div class="card shadow-none">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                ::~\App\Http\Helpers\ProfileHelper->userProfile(::user_id::, ["flex","lg"], ["company", "role", "scope"], 1)~::
                                <div>
                                    <div>';
                            if ($reqSet['key'] == 'business_request_approve') {
                                $view .= '          
                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-id="::request_id::" data-token="' . $reqSet['token'] . '_e_::' . $reqSet['act'] . '::">
                                            <i class="ti ti-edit text-primary"></i>
                                        </a><a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::">
                                            <i class="ti ti-trash text-danger"></i>
                                        </a>              
                                    ';
                            }
                            else if($reqSet['key'] == 'business_requests'){
                                 $view .= '             
                                    ::IF(approval_status = \'pending\', <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-id="::request_id::" data-token="' . $reqSet['token'] . '_e_::' . $reqSet['act'] . '::">
                                            <i class="ti ti-edit text-primary"></i>
                                        </a><a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::">
                                            <i class="ti ti-trash text-danger"></i>
                                        </a>)::            
                                    ';
                            }
                            $view .= '
                                    </div>
                                </div>
                        </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex flex-column flex-md-row justify-content-between mb-1">
                                        <h6 class="fw-bold text-primary mb-1 text-nowrap me-md-2">
                                            ::request_type:: - <span class="badge rounded-pill bg-light border text-black">::name::</span>
                                        </h6>
                                        <span class="mt-1 mt-md-0">
                                            <strong>Duration: </strong>::start_datetime:: to ::end_datetime::
                                        </span>
                                    </div>
                                    <div><b>subject:</b> ::subject::</div>
                                    <p class="mb-2 mt-2">&nbsp;&nbsp;&nbsp;&nbsp; ::reason::</p>
                                    ::IF(notes IS NOT NULL, <div class="bg-secondary-transparent p-2 rounded"><span class="d-flex align-items-center"><i class="ti ti-info-circle me-1 sf-20"></i> Note From Approver</span><p class="mb-0 mt-2">&nbsp;&nbsp;&nbsp;&nbsp; ::notes::</p></div>)::
                                </div>
                                <div class="row border-top rounded p-3 mb-0 pb-0 g-1">
                                    <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                        <p class="mb-1 text-muted small"><strong>Request status</strong></p>
                                        <p class="mb-0">
                                            ::IF(approval_status = "approved",
                                                <span class="badge bg-success rounded-pill">Approved</span>,
                                                ::IF(approval_status = \'approved\', <span class="badge bg-sucess text-white rounded-pill">Low</span>)::ELSEIF(approval_status = \'pending\', <span class="badge bg-warning text-white rounded-pill">Pending</span>)::ELSE(<span class="badge bg-danger text-white rounded-pill">Rejected</span>):: 
                                        </p>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-sm-6">
                                        <p class="mb-1 text-muted small"><strong>Approved / Rejected By</strong></p>
                                        <div class="d-flex align-items-center">
                                            <span class="fs-12">
                                               ::~\App\Http\Helpers\ProfileHelper->userProfile(::decision_by::, ["flex","xs","sf-7", "sf-7"], ["company","role","scope"], 0)~::
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-sm-6">
                                        <p class="mb-1 text-muted small"><strong>Requested At</strong></p>
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
                    $title = 'Entities Retrieved';
                    $message = 'LeavesAndRequests entity card data retrieved successfully.';
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
            // Generate and return response using TableHelper
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