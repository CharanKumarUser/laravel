<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Data;
use App\Facades\Developer;
use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use App\Http\Helpers\CardHelper;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX card data requests in the BusinessManagement module with clean UI.
 */
class CardCtrl extends Controller
{
    /**
     * Handles AJAX requests for card data processing for modules, sections, and items.
     *
     * @param  Request  $request  HTTP request object containing filters and view settings
     * @param  array  $params  Route parameters (module, section, item, token)
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
            if (! isset($reqSet['key']) || ! isset($reqSet['table'])) {
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
            if (! is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $view = '';
            $title = 'Success';
            $message = 'BusinessManagement card data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'central_business_module_pricings':
                    $columns = [
                        'id' => ['business_module_pricing.id', true],
                        'module_price_id' => ['business_module_pricing.module_price_id', true],
                        'module_id' => ['business_module_pricing.module_id', true],
                        'module_name' => ['business_module_pricing.module_name', true],
                        'price' => ['business_module_pricing.price', true],
                        'description' => ['business_module_pricing.description', true],
                        'is_approved' => ['business_module_pricing.is_approved', true],
                        'created_by' => ['business_module_pricing.created_by', true],
                        'updated_by' => ['business_module_pricing.updated_by', true],
                    ];
                    $permissionsToken = Skeleton::skeletonToken('central_business_module_pricings');
                    $view = '<div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm">
                     <div class="card-header p-2 rounded-3" style="background: ::~\App\Http\Helpers\Helper->colors(\'gradient-light-1\', \'background\')~::">
                        <div class="card-body p-0">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="sf-10">::IF(is_approved = 1, <span class="badge bg-success rounded-pill">Active</span>, <span class="badge bg-danger rounded-pill">Inactive</span>)::</span>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><span class="dropdown-item skeleton-popup" data-token="'.$permissionsToken.'_e_::'.$reqSet['act'].'::"><i class="ti ti-edit me-1"></i>Edit</span></li>
                                            <li><span class="dropdown-item skeleton-popup" data-token="'.$permissionsToken.'_d_::'.$reqSet['act'].'::"><i class="ti ti-trash me-1"></i>Delete</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <span class="avatar avatar-xl avatar-rounded border rounded-circle" style="background: #00b4af; color: #ffffff">
                                        ::~\App\Http\Helpers\Helper->textProfile(::module_name::, 2)~::
                                    </span>
                                </div>
                                <div class="d-flex flex-column justify-content-center align-items-center my-2">
                                    <h6 class="mb-1">::module_name::</h6>
                                    <span class="sf-10 badge bg-light my-2 rounded-pill">Price: ₹ ::price::</span>
                                    <span class="sf-11 text-muted">::~\Illuminate\Support\Str->limit(::description::, 27)~::</span>
                                </div>
                         </div>
                     </div>
                     </div>
                    </div>';
                    break;
                case 'central_businesses':
                    $columns = [
                        'id' => ['businesses.id', true],
                        'business_id' => ['businesses.business_id', true],
                        'name' => ['businesses.name', true],
                        'email' => ['businesses.email', true],
                        'phone' => ['businesses.phone', true],
                        'industry' => ['businesses.industry', true],
                    ];
                    $view = '
                <div class="col-xl-3 col-lg-2 col-md-10 mb-4 mt-3">
                    <a href="/t/business-management/info/::business_id::" class="text-decoration-none text-reset">
                        <div class="card h-100 border-0 rounded-4 mb-0" style="transition: all 0.3s ease; overflow: hidden;">
                            <!-- Top Section -->
                            <div class="card-body p-2 text-center position-relative" style="background: ::~\App\Http\Helpers\Helper->colors(\'gradient-light-1\', \'background\')~::">
                                <div class="w-100 d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-white text-dark px-3 py-1 rounded-pill">ID: ::business_id::</span>
                                    
                                </div>
                                <!-- Avatar & Name -->
                               <div class="text-center">
                                    <span class="avatar avatar-xl avatar-rounded border rounded-circle bg-info" style="background: #00b4af; color: #ffffff">
                                        ::~\App\Http\Helpers\Helper->textProfile(::name::, 2)~::
                                    </span>
                                </div>
                                <h5 class="fw-bold mt-3 mb-1">::name::</h5>
                                <span class="fs-12 mb-2">Industry: ::industry::</span>
                            </div>
                        </div>
                    </a>
                    </div>';
                    $title = 'Business Cards';
                    $message = 'Business card data retrieved successfully.';
                    break;
                case 'central_onboard_business':
                    $columns = [
                        'id' => ['business_onboarding.id', true],
                        'onboarding_id' => ['business_onboarding.onboarding_id', true],
                        'name' => ['business_onboarding.name', true],
                        'email' => ['business_onboarding.email', true],
                        'phone' => ['business_onboarding.phone', true],
                        'admin_first_name' => ['business_onboarding.admin_first_name', true],
                        'admin_last_name' => ['business_onboarding.admin_last_name', true],
                        'industry' => ['business_onboarding.industry', true],
                        'onboarding_stage' => ['business_onboarding.onboarding_stage', true],
                        'is_converted' => ['business_onboarding.is_converted', true],

                    ];
                    $conditions = [
                        ['column' => 'business_onboarding.is_converted', 'operator' => '=', 'value' => 0],
                    ];
                    $view = '
                <div class="col-xl-3 col-lg-2 col-md-10 mb-4 mt-3">
                        <div class="card h-100 border-0 rounded-4 mb-0" style="transition: all 0.3s ease; overflow: hidden;">
                            <!-- Top Section -->
                            <div class="card-body p-2  text-center position-relative" style="background: ::~\App\Http\Helpers\Helper->colors(\'gradient-light-1\', \'background\')~::">
                                <div class="w-100 d-flex justify-content-between align-items-start mb-3">
                                    <span class="sf-10">::IF(onboarding_stage = "active", <span class="badge bg-success rounded-pill">Active</span>, <span class="badge bg-danger rounded-pill">::onboarding_stage::</span>)::</span>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-sm rounded-circle " type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><span class="dropdown-item skeleton-popup" data-token="'.$reqSet['token'].'_e_::'.$reqSet['act'].'::'.'"><i class="ti ti-edit me-1"></i>Edit</span></li>
                                            <li><span class="dropdown-item skeleton-popup text-danger" data-token="'.$reqSet['token'].'_d_::'.$reqSet['act'].'::'.'"><i class="ti ti-trash me-1"></i>Delete</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <!-- Avatar & Name -->
                                <div class="text-center">
                                    <span class="avatar avatar-xl avatar-rounded border rounded-circle" style="background: #00b4af; color: #ffffff">
                                        ::~\App\Http\Helpers\Helper->textProfile(::name::, 2)~::
                                    </span>
                                </div>
                                <h5 class="fw-bold  mt-3 mb-1">::name::</h5>
                                <span class="fs-12 d-block mb-2">Industry: ::industry::</span>
                                <button class="btn btn-sm border border-0 bg-white rounded-pill skeleton-popup sf-10 mb-2" data-token="'.Skeleton::skeletonToken('central_convert_to_business').'_a_::onboarding_id::'.'">
                                    <i class="ti ti-plus me-1"></i>Convert To Business
                                </button>
                            </div>
                        </div>
                    </div>';
                    $title = 'Business Cards';
                    $message = 'Business card data retrieved successfully.';
                    break;
                case 'central_business_plans':
                    $businessId = $reqSet['id'];
                    $columns = [
                        'id' => ['business_plans.id', true],
                        'plan_id' => ['business_plans.plan_id', true],
                        'name' => ['business_plans.name', true],
                        'icon' => ['business_plans.icon', true],
                        'type' => ['business_plans.type', true],
                        'module_pricing_ids' => ['business_plans.module_pricing_ids', true],
                        'description' => ['business_plans.description', true],
                        'features' => ['business_plans.features', true],
                        'amount' => ['business_plans.amount', true],
                        'strike_amount' => ['business_plans.strike_amount', true],
                        'discount' => ['business_plans.discount', true],
                        'tax' => ['business_plans.tax', true],
                        'display_order' => ['business_plans.display_order', true],
                        'landing_visibility' => ['business_plans.landing_visibility', true],
                        'is_approved' => ['business_plans.is_approved', true],
                    ];
                    $joins = [
                        // ['type' => 'left', 'table' => 'user_roles', 'on' => ['users.user_id', 'user_roles.user_id']],
                        // ['type' => 'left', 'table' => 'roles', 'on' => ['user_roles.role_id', 'roles.role_id']],
                    ];
                    $conditions = [
                        // ['column' => 'users.business_id', 'operator' => '=', 'value' => $businessId],
                        // ['column' => 'user_roles.role_id', 'operator' => '=', 'value' => 'ADMIN'],
                    ];
                    $gradients = [
                        'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
                        'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)',
                        'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)',
                        'linear-gradient(135deg, #fccb90 0%, #d57eeb 100%)',
                        'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
                    ];
                    $randomBg = $gradients[array_rand($gradients)];
                    $view = '
                            <div class="col-xl-4 col-lg-2 col-md-10 mb-4 mt-3">
                                <a class="text-decoration-none text-reset">
                                    <div class="card h-100 shadow-lg border-0 rounded-4" style="transition: all 0.3s ease; overflow: hidden;">
                                        <!-- Upper Part with Random Background -->
                                        <div class="p-4 rounded-top-4 d-flex flex-column align-items-center justify-content-center text-white" style="background: '.$randomBg.';">
                                            <!-- Badge and Dropdown in Same Row -->
                                            <div class="w-100 d-flex justify-content-between align-items-start mb-3">  
                                                <div class="dropdown ms-auto">
                                                        <button class="btn btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <span class="dropdown-item skeleton-popup" data-token="'.$reqSet['token'].'_e_::'.$reqSet['act'].'::">
                                                                <i class="ti ti-edit me-1"></i>Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span class="dropdown-item skeleton-popup" data-token="'.$reqSet['token'].'_d_::'.$reqSet['act'].'::">
                                                                <i class="ti ti-trash me-1"></i>Delete
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                            <span class="avatar avatar-xxl avatar-rounded border rounded-circle">
                                                ::IF(profile IS NOT NULL,
                                                <img src="::~\App\Services\FileService->getFile(::profile::)~::" alt="User Avatar"
                                                    class="img-fluid rounded-circle">,
                                                <img src="'.asset('default/preview-square.svg').'" alt="User Avatar"
                                                    class="img-fluid rounded-circle">)::
                                            </span>
                                            <span class="badge bg-white rounded-pill sf-10"
                                                style="color: ::~\App\Http\Helpers\Helper->colors(\'light\', \'color\')~:: !important">::job_title::</span>
                                        </div>
                                            <h5 class="fw-bold text-white mt-3 mb-1">::first_name:: ::last_name::</h5>
                                            <span class="fs-12">User ID: ::user_id::</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-3"> 
                                        <span class="badge bg-success px-3 py-1 fs-12 m-3">Admin</span>
                                        <span class="skeleton-popup m-3" data-token="'.$reqSet['token'].'_v_::'.$reqSet['act'].'::_'.$businessId.'"><i class="ti ti-eye me-1"></i></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                    ';
                    $title = 'Table Retrieved';
                    $message = 'Token configuration data retrieved successfully.';
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
