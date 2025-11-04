<?php
namespace App\Http\Controllers\System\Business\ScopeManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{CardHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX card data requests in the ScopeManagement module with clean UI.
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
            $message = 'ScopeManagement card data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_scopes':
                    $scopeIds = Scope::userChildScopes(true);
                    $columns = [
                        'id' => ['scopes.id', true],
                        'scope_id' => ['scopes.scope_id', true],
                        'sno' => ['scopes.sno', true],
                        'code' => ['scopes.code', true],
                        'name' => ['scopes.name', true],
                        'description' => ['scopes.description', true],
                        'background' => ['scopes.background', true],
                        'color' => ['scopes.color', true],
                        'is_active' => ['scopes.is_active', true],
                    ];
                    $conditions = [
                        ['column' => 'scopes.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                    ];
                    $view = '<div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span>::sno::</span>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><span class="dropdown-item skeleton-popup" data-token="' . $reqSet['token'] . '_e_::' . $reqSet['act'] . '::"><i class="ti ti-edit me-1"></i>Edit</span></li>
                                            <li><span class="dropdown-item skeleton-popup" data-token="' . $reqSet['token'] . '_d_::' . $reqSet['act'] . '::"><i class="ti ti-trash me-1"></i>Delete</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <span class="avatar avatar-xl avatar-rounded border rounded-circle" style="background: ::background::; color: ::color::">
                                        ::~\App\Http\Helpers\Helper->textProfile(::name::, 2)~::
                                    </span>
                                </div>
                                <div class="d-flex flex-column justify-content-center align-items-center my-2">
                                <h6 class="mb-1">::name::</h6>
                                <span class="sf-11 badge bg-light rounded-pill">Code : ::code::</span>
                                <span class="sf-11 text-muted">::~\Illuminate\Support\Str->limit(::description::, 27)~::</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-3">
                                <span class="skeleton-popup" data-token="' . $reqSet['token'] . '_v_::' . $reqSet['act'] . '::"><i class="ti ti-eye me-1"></i></span>
                                    <span class="d-inline-flex align-items-center">
                                        ::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">In Active</span>)::
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>';
                    break;
                case 'open_scope_view':
                    $scopeIds = explode('-', $reqSet['id']);
                    $columns = [
                        'id' => ['users.id', true],
                        'business_id' => ['users.business_id', true],
                        'user_id' => ['users.user_id', true],
                        'role' => ['roles.name AS role', true],
                        'first_name' => ['users.first_name', true],
                        'last_name' => ['users.last_name', true],
                        'scope' => ['scopes.name AS scope', true],
                        'group' => ['scopes.group', true],
                        'profile' => ['users.profile', true],
                        'code' => ['user_info.unique_code AS code', true],
                        'email' => ['users.email', true],
                        'phone' => ['user_info.phone', true],
                        'job_title' => ['user_info.job_title', true],
                        'hire_date' => ['user_info.hire_date', true],
                        'username' => ['users.username', true],
                        'permissions' => ['users.id AS permissions', true],
                        'account_status' => ['users.account_status', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'user_roles', 'on' => ['users.user_id', 'user_roles.user_id']],
                        ['type' => 'left', 'table' => 'roles', 'on' => ['user_roles.role_id', 'roles.role_id']],
                        ['type' => 'left', 'table' => 'scope_mapping', 'on' => ['users.user_id', 'scope_mapping.user_id']],
                        ['type' => 'left', 'table' => 'user_info', 'on' => ['users.user_id', 'user_info.user_id']],
                        ['type' => 'left', 'table' => 'scopes', 'on' => ['scope_mapping.scope_id', 'scopes.scope_id']],
                    ];
                    $conditions = [
                        ['column' => 'scope_mapping.scope_id', 'operator' => 'IN', 'value' => $scopeIds],
                    ];
                    $view = '<div class="col-xl-4 col-lg-4 col-md-4">
                            <div class="card">
                                <div class="card-header p-2"
                                    style="background: ::~\App\Http\Helpers\Helper->colors(\'gradient-light-1\', \'background\')~::">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-white rounded-pill sf-10 text-dark d-flex justify-content-center align-items-center">::code::</span>
                                        <span class="d-inline-flex align-items-center">::IF(account_status = active, <span><i
                                                    class="ti ti-discount-check-filled text-success ms-1"></i></span>,<span><i
                                                    class="ti ti-discount-check-filled text-danger ms-1"></i></span>)::</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="avatar avatar-xxl avatar-rounded border rounded-circle">
                                            ::IF(profile IS NOT NULL,
                                            <img src="::~\App\Services\FileService->getFile(::profile::)~::" alt="User Avatar"
                                                class="img-fluid rounded-circle">,
                                            <img src="' . asset('default/preview-square.svg') . '" alt="User Avatar"
                                                class="img-fluid rounded-circle">)::
                                        </span>
                                        <h6 class="d-flex align-items-center justify-content-center mb-1 fw-bold mb-1">::first_name::
                                            ::last_name::</h6>
                                        <span class="badge bg-white rounded-pill sf-10"
                                            style="color: ::~\App\Http\Helpers\Helper->colors(\'light\', \'color\')~:: !important">::job_title::</span>
                                    </div>
                                </div>
                                <div class="card-body p-2 h-100">
                                <div class="d-flex justify-content-between align-items-center px-2">
                                <div><span class="sf-12">Role</span></div>
                                <div><b class="sf-12">::role::</b></div>
                                </div>
                                    <div class="d-flex justify-content-between align-items-center rounded my-2 px-2">
                                                            ::IF(group IS NOT NULL, <div class=" sf-12">::group::</div>
                                    <div><b class="sf-12">::scope::</b></div>, <div><b class="sf-12">::group::</b></div>)::
                                    </div>
                                    <div class="d-flex flex-column px-2">
                                        <p class="text-dark d-inline-flex align-items-center mb-2">
                                            <i class="ti ti-mail-forward text-gray-5 me-2"></i>
                                            ::email::
                                        </p>
                                        <p class="text-dark d-inline-flex align-items-center mb-2">
                                            <i class="ti ti-phone text-gray-5 me-2"></i>
                                            ::phone::
                                        </p>
                                    </div>
                                    <div class="row g-2 border-top mt-1 pb-0 mb-0">
                                        <div class="col-sm-6">
                                            <div><a href="tel:::phone::" class="btn  btn-sm btn-outline-secondary w-100"><i class="ti ti-phone-call me-1"></i>Call </a></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div><a href="' . url('/') . '/t/user-management/page/::user_id::" class="btn btn-sm btn-outline-primary w-100">View More</a></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
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
