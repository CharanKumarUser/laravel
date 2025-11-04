<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX card data requests in the CompanyManagement module with clean UI.
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
            $view = '';
            $title = 'Success';
            $message = 'CompanyManagement card data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_company_policies':
                    $columns = [
                        'id'         => ['company_policies.id', true],
                        'sno'        => ['company_policies.sno', true],
                        'company_id' => ['company_policies.company_id', true],
                        'policy_id'  => ['company_policies.policy_id', true],
                        'name'       => ['company_policies.name', true],
                        'category'   => ['company_policies.category', true],
                        'description'  => ['company_policies.description', true],
                        'effective_date'  => ['company_policies.effective_date', true],
                        'expiry_date'   => ['company_policies.expiry_date', true],
                        'is_active'  => ['company_policies.is_active', true],
                        'created_at' => ['company_policies.created_at', true],
                        'updated_at' => ['company_policies.updated_at', true],
                    ];
                    if($reqSet['id'] !== ''){
                        $conditions = [
                            ['column' => 'company_policies.company_id', 'operator' => '=', 'value' => $reqSet['id']],
                        ];
                    }
                    $view = '
                        <div class="col-xl-12 sf-12">
                            <div class="card shadow-none">
                                <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <div>
                                        <div class="d-flex">
                                            <h5 class="text-info fw-medium me-2 sf-15">::name::</h5>
                                            <div class="d-flex align-items-center ">::IF(is_active = \'1\', <span class="badge rounded-pill bg-success">Active</span>)::ELSEIF(is_active = \'0\', <span class="badge rounded-pill bg-danger">Inactive</span>)::</div> 
                                        </div>
                                        <div class="d-flex align-items-center mt-2">
                                            <span class="sf-11">sno: <b>::sno::</b></span>
                                            ::IF(category IS NOT NULL,<span class="mx-2">|</span><span class="sf-11">category: <b>::category::</b></span>, )::
                                        </div>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_e_::' .$reqSet['act'] . '::" data-id="::company_id::">
                                            <i class="ti ti-edit text-primary"></i>
                                        </a>
                                        <a href="javascript:void(0);" class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::">
                                            <i class="ti ti-trash text-danger"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div>
                                        <div>
                                            <p class="mb-3">&nbsp;&nbsp;&nbsp;&nbsp; ::description::</p>
                                        </div>
                                        <div class="row border-top rounded p-3 mb-0 pb-0">
                                            <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                                <p class="mb-1 text-muted small"><strong> Created At </strong></p>
                                                <p class="mb-0 d-flex align-items-center">
                                                    <i class="ti ti-calendar-plus me-2"></i>
                                                    <span>::IF(created_at IS NOT NULL,::created_at::, N/A)::</span>
                                                </p>
                                            </div>
                                            <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                                <p class="mb-1 text-muted small"><strong>Effective Date</strong></p>
                                                <p class="mb-0 d-flex align-items-center">
                                                    <i class="ti ti-calendar-bolt me-2"></i>
                                                    <span>::IF(effective_date IS NOT NULL,::effective_date::, Not Mentioned)::</span>
                                                </p>
                                            </div>
                                            <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                                <p class="mb-1 text-muted small"><strong>Expiry Date</strong></p>
                                                <p class="mb-0 d-flex align-items-center">
                                                    <i class="ti ti-calendar-off me-2"></i>
                                                    <span>::IF(expiry_date IS NOT NULL,::expiry_date::, Not Mentioned)::</span>
                                                </p>
                                            </div>
                                            <div class="col-xl-3 col-md-6 col-sm-6">
                                                <p class="mb-1 text-muted small"><strong>Last Updated</strong></p>
                                                <p class="mb-0 d-flex align-items-center">
                                                    <i class="ti ti-calendar-plus me-2"></i>
                                                    <span>::IF(updated_at IS NOT NULL,::updated_at::, Not Mentioned)::</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';    
                    $title = 'Entities Retrieved';
                    $message = 'CompanyManagement entity card data retrieved successfully.';
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
