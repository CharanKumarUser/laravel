<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{Data, Developer, Random, Skeleton, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX card data requests in the AssetManagement module with clean UI.
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
            $columns = $conditions = $joins = $view = [];
            $view = '';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_assets':
                case 'business_my_assets':

                    $columns = [
                        'sno' => ['assets.sno', true],
                        'asset_id' => ['assets.asset_id', false],
                        'company_id' => ['assets.company_id', true],
                        'image' => ['assets.image_url AS image', true],
                        'name' => ['assets.name', true],
                        'user_id' => ['asset_assignments.user_id', true],
                        'company' => ['companies.name AS company', true],
                        'category' => ['asset_categories.name AS category', true],
                        'purchase_date' => ['assets.purchase_date', true],
                        'purchase_cost' => ['assets.purchase_cost', true],
                        'warranty_expiry' => ['assets.warranty_expiry', true],
                        'allow_repair_request' => ['assets.allow_repair_request', true],
                        'status' => ['assets.status', true],
                        'location' => ['assets.location', true],
                        'vendor_name' => ['assets.vendor_name', true],
                        'vendor_contact' => ['assets.vendor_contact', true],
                        'notes' => ['assets.notes', true],
                        'is_active' => ['assets.is_active', true],
                        'created_by' => ['assets.created_by', true],
                        'updated_by' => ['assets.updated_by', true],
                        'created_at' => ['assets.created_at', true],
                        'updated_at' => ['assets.updated_at', true],
                    ];
                    
                    $joins = [
                        ['type' => 'left', 'table' => 'asset_categories', 'on' => [['assets.category_id', 'asset_categories.category_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['assets.company_id', 'companies.company_id']]],
                        ['type' => 'left', 'table' => 'asset_assignments', 'on' => [['assets.asset_id', 'asset_assignments.asset_id']]],
                        ['type' => 'left', 'table' => 'users', 'on' => [['asset_assignments.user_id', 'users.user_id']]],
                    ];
                    if ($reqSet['key'] === 'business_my_assets') {
                        unset($columns['document'], $columns['vendor_name'], $columns['vendor_contact'], $columns['purchase_date'], $columns['purchase_cost'], $columns['warranty_expiry'], $columns['is_active']);
                        $user_id = Skeleton::authUser()->user_id;
                        $assetId = BusinessDB::table('asset_assignments')->where('user_id', $user_id)->whereNull('deleted_at')->pluck('asset_id')->toArray();
                        $reqSet['actions'] = 'v';
                        $conditions = [
                            ['column' => 'assets.asset_id', 'operator' => 'IN', 'value' => $assetId],
                            ['column' => 'assets.status', 'operator' => '=', 'value' => 'assigned'],
                        ];
                    }
                    $tokenForRequest = Skeleton::skeletonToken('business_asset_maintenance_request');
                    
                    if ($reqSet['key'] === 'business_my_assets') {
                        $view = '
                        <div class="col-xl-6 col-sm-12 sf-12">
                            <div class="card shadow-sm border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex gap-2 mb-1">
                                            <div>
                                                <div class="d-flex gap-3">
                                                    <h5>::name::</h5>::IF(is_active = \'1\', <span class="badge rounded-pill bg-success">Active</span>)::ELSEIF(is_active = \'0\', <span class="badge rounded-pill bg-danger">Inactive</span>)::
                                                </div> 
                                                <div class="d-flex align-items-center">
                                                    <span>company: ::IF(company IS NOT NULL,<span>::company::</span>, <span>No Company</span>)::</span>
                                                    <span class="mx-2">|</span>
                                                    <span class="sf-11">sno: <b>2</b></span>
                                                    <span class="mx-2">|</span>
                                                    <span>category: <span class="badge rounded-pill bg-light text-dark">::category::</span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 mb-3 mb-md-0">
                                            <div class="asset-image-container text-center rounded">
                                                ::IF(image IS NOT NULL,
                                                    <img src="::~\App\Services\FileService->getFile(::image::)~::" alt="Asset Image"
                                                    class="img-fluid rounded object-fit-cover w-100" style="height: 200px;">,
                                                    <img src="' . asset('default/preview-square.svg') . '" alt="Asset Image"
                                                            class="img-fluid rounded object-fit-cover w-100" style="height: 200px; aspect: ratio 2px / 3px;">)::
                                            </div>
                                        </div>
                                        <div class="col-lg-8 col-md-8 mb-3 mb-md-0">
                                            <div class="row">
                                                <div class="col-lg-12 col-md-12">
                                                    <div class="h-100 d-flex flex-column">
                                                        <div>
                                                            <h6 class="mb-3 fw-semibold border-bottom pb-2">Basic</h6>
                                                        </div>
                                                        <div class="asset-details flex-grow-1">
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>Location</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0">::location::</p>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-currency-dollar me-1"></i>Cost</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(purchase_cost IS NOT NULL,::purchase_cost:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12 mb-md-0">
                                                <div class="bg-light rounded p-2 h-100">
                                                    <h6 class="mb-2 fw-semibold">Notes</h6>
                                                    <p class="mb-0">::notes::</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer border-top">
                                    <div class="row rounded d-flex justify-content-between">
                                        <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                            <p class="mb-1 fw-bold small"><i class="ti ti-calendar-plus me-1"></i> Created on: </p>
                                            <p class="mb-0">::created_at::</p>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                            <p class="mb-1 fw-bold small"><i class="ti ti-clock-edit me-1"></i> Last Updated on: </p>
                                            <p class="mb-0">::updated_at::</p>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0 ::IF(allow_repair_request = \'1\', d-block, d-none)::">
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-primary w-100 skeleton-popup"
                                                 data-token="' . $tokenForRequest . '_a_::asset_id::" 
                                            >
                                                <i class="ti ti-wrench me-1"></i> Request Maintenance
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    else{
                        $view = '
                        <div class="col-xl-12 col-sm-12 sf-12">
                            <div class="card shadow-sm border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex gap-2 mb-1">
                                            <div>
                                                <div class="d-flex gap-2 mb-1">
                                                    <h5>::name::</h5>::IF(is_active = \'1\', <span class="badge rounded-pill bg-success">Active</span>)::ELSEIF(is_active = \'0\', <span class="badge rounded-pill bg-danger">Inactive</span>)::
                                                </div> 
                                                <div class="d-flex align-items-center">
                                                    <span>::IF(company IS NOT NULL,<span>::company::</span>, <span>No Company</span>)::</span>
                                                    <span class="mx-1">|</span>
                                                    <span class="sf-11">sno: <b>2</b></span>
                                                    <span class="mx-1">|</span>
                                                    <span>category: <span class="badge rounded-pill bg-light text-dark">::IF(category IS NOT NULL, ::category:: , No Category)::</span></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <a class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_e_::' . $reqSet['act'] . '::">
                                                <i class="ti ti-edit text-primary"></i>
                                            </a><a class="btn btn-icon btn-sm skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::">
                                                <i class="ti ti-trash text-danger"></i>
                                            </a> 
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 mb-3 mb-md-0">
                                            <div class="asset-image-container text-center rounded">
                                                ::IF(image IS NOT NULL,
                                                    <img src="::~\App\Services\FileService->getFile(::image::)~::" alt="Asset Image"
                                                    class="img-fluid rounded object-fit-cover w-100" style="height: 200px;">,
                                                    <img src="' . asset('default/preview-square.svg') . '" alt="Asset Image"
                                                            class="img-fluid rounded object-fit-cover w-100" style="height: 200px; aspect: ratio 2px / 3px;">)::
                                            </div>
                                        </div>
                                        <div class="col-lg-8 col-md-8 mb-3 mb-md-0">
                                            <div class="row">
                                                <div class="col-lg-6 col-md-6">
                                                    <div class="h-100 d-flex flex-column">
                                                        <div>
                                                            <h6 class="mb-3 fw-semibold border-bottom pb-2">Basic</h6>
                                                        </div>
                                                        <div class="asset-details flex-grow-1">
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-map-pin me-1"></i>Location</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0">::location::</p>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-calendar-event me-1"></i>Purchase Date</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(purchase_date IS NOT NULL,::purchase_date:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-currency-dollar me-1"></i>Cost</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(purchase_cost IS NOT NULL,::purchase_cost:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6">
                                                    <div class="h-100 d-flex flex-column">
                                                        <h6 class="mb-3 fw-semibold border-bottom pb-2">Other Details</h6>

                                                        <div class="vendor-details flex-grow-1">
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-building-store me-1"></i>Vendor Name</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(vendor_name IS NOT NULL,::vendor_name:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-phone me-1"></i>Contact</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(vendor_contact IS NOT NULL,::vendor_contact:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-2">
                                                                <div class="col-auto">
                                                                    <span class="text-muted mb-1"><i class="ti ti-shield-check me-1"></i>Warranty Expiry</span>
                                                                </div>
                                                                <div class="col text-end">
                                                                    <p class="fw-medium mb-0"> ::IF(warranty_expiry IS NOT NULL, ::warranty_expiry:: , Not-Mentioned)::</p>
                                                                </div>
                                                            </div>
                                                        
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12 mb-md-0">
                                                <div class="bg-light rounded p-2 h-100">
                                                    <h6 class="mb-2 fw-semibold">Notes</h6>
                                                    <p class="mb-0">::IF(notes IS NOT NULL, ::notes:: , No notes provided)::</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer border-top">
                                    <div class="row rounded d-flex justify-content-between">
                                        <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                            <p class="mb-1 fw-bold small"><i class="ti ti-clipboard-check me-1"></i> status</p>
                                            <p class="mb-0">
                                                ::IF(status = \'assigned\', <span class="badge bg-success text-white rounded-pill">Assigned</span>)::ELSEIF(status = \'under_maintenance\', <span class="badge bg-warning text-white rounded-pill">Under Maintenance</span>)::ELSEIF(status = \'available\', <span class="badge bg-purple text-white rounded-pill">Available</span>)::ELSE(<span class="badge bg-danger text-white rounded-pill">Retired</span>):: 
                                            </p>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0">
                                            <p class="mb-1 fw-bold small"><i class="ti ti-user-check me-1"></i> Assaigned To: </p>
                                            <div class = "::IF(status = \'assigned\',d-block,d-none)::">::~\App\Http\Helpers\ProfileHelper->userProfile(::user_id::,["flex","xs"],["role","scope"],1)~::</div>
                                            <div class = "::IF(status = \'assigned\',d-none,d-block)::">Not assigned yet</div>
                                        </div>
                                         <div class="col-xl-3 col-md-6 col-sm-6 mb-2 mb-xl-0 ::IF(allow_repair_request = \'1\', d-block, d-none)::">
                                       
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-primary w-100 skeleton-popup"
                                                data-token="' . $tokenForRequest . '_a_::asset_id::"
                                            >
                                                <i class="ti ti-wrench me-1"></i> Request Maintenance
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
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