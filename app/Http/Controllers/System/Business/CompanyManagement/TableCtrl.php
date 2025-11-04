<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the CompanyManagement module.
 */
class TableCtrl extends Controller
{
    /**
     * Handles AJAX requests for table data processing.
     *
     * @param Request $request HTTP request object containing filters and view settings
     * @param array $params Route parameters (module, section, item, token)
     * @return JsonResponse Processed table data or error response
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
            // Set view to table and parse filters
            $reqSet['view'] = 'table';
            $reqSet['draw'] = (int) $request->input('draw', 1);
            $filters = $request->input('skeleton_filters', []);
            $reqSet['filters'] = [
                'search' => $filters['search'] ?? [],
                'dateRange' => $filters['dateRange'] ?? [],
                'columns' => $filters['columns'] ?? [],
                'sort' => $filters['sort'] ?? [],
                'pagination' => $filters['pagination'] ?? ['page' => 1, 'limit' => 10],
            ];
            // Validate filters format
            if (!is_array($reqSet['filters'])) {
                return ResponseHelper::moduleError('Invalid Filters', 'The filters format is invalid.', 400);
            }
            // Initialize configuration arrays
            $columns = $conditions = $joins = $custom = [];
            $title = 'Data Retrieved';
            $message = 'CompanyManagement data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'business_companies':
                    $columns = [
                        'id' => ['companies.id', true],
                        'company_id' => ['companies.company_id', true],
                        'business_id' => ['companies.business_id', true],
                        'name' => ['companies.name', true],
                        'legal_name' => ['companies.legal_name', true],
                        'industry' => ['companies.industry', true],
                        'type' => ['companies.type', true],
                        'email' => ['companies.email', true],
                        'phone' => ['companies.phone', true],
                        'website' => ['companies.website', true],
                        'city' => ['companies.city', true],
                        'state' => ['companies.state', true],
                        'country' => ['companies.country', true],
                        'is_active' => ['companies.is_active', true],
                        'created_at' => ['companies.created_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'company_id', 'view' => '<span class="badge bg-primary">::company_id::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'email', 'view' => '<a href="mailto:::email::" class="text-primary">::email::</a>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'website', 'view' => '<a href="::website::" target="_blank" class="text-info">::website::</a>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'phone', 'view' => '<code>::phone::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'type', 'view' => '<span class="badge bg-info">::type::</span>', 'renderHtml' => true],
                    ];
                    break;

                case 'business_company_holidays':
                    $columns = [
                        'id' => ['company_holidays.id', true],
                        'holiday_id' => ['company_holidays.holiday_id', true],
                        'company_id' => ['company_holidays.company_id', false],
                        'name' => ['company_holidays.name', true],
                        'description' => ['company_holidays.description', true],
                        'image'   =>  ['company_holidays.image', true],
                        'start_date' => ['company_holidays.start_date', true],
                        'end_date' => ['company_holidays.end_date', true],
                        'recurring_type' => ['company_holidays.recurring_type', true],
                        'recurring_day' => ['company_holidays.recurring_day', true],
                        'recurring_week' => ['company_holidays.recurring_week', true],
                        'is_active' => ['company_holidays.is_active', true],
                        'created_at' => ['company_holidays.created_at', true],
                    ];
                    if(isset($reqSet['id'] ) && !empty($reqSet['id'])){
                        $conditions = [
                            ['column' => 'company_holidays.company_id', 'operator' => 'IN', 'value' => $reqSet['id']],
                        ];
                    }
                    $custom = [
                        ['type' => 'modify', 'column' => 'is_active', 'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'recurring_type', 'view' => '<span class="badge bg-info">::recurring_type::</span>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'start_date', 'view' => '<code>::start_date::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'end_date', 'view' => '<code>::end_date::</code>', 'renderHtml' => true],
                        ['type' => 'modify', 'column' => 'description', 'view' => '<span title="::description::">::description::</span>', 'renderHtml' => true],
                        [
                            'type' => 'modify',
                            'column' => 'image',
                            'view' => '::IF(image IS NOT NULL,
                                            <img src="::~\App\Services\FileService->getFile(::image::)~::" alt="User Avatar"
                                                class="img-fluid rounded-circle">,
                                            <img src="' . asset('default/preview-square.svg') . '" alt="User Avatar"
                                                class="img-fluid rounded-circle">)::',
                            'renderHtml' => true
                        ],

                    ];
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
            $set = ['columns' => $columns, 'joins' => $joins, 'conditions' => $conditions, 'req_set' => $reqSet, 'custom' => $custom];
            $businessId = Skeleton::authUser()->business_id ?? 'central';
            $response = TableHelper::generateResponse($set, $businessId);
            // Generate and return response using TableHelper
            if ($response['status']) {
                return response()->json($response);
            } else {
                return ResponseHelper::moduleError('Data Fetch Failed', $response['message'] ?? 'Something went wrong', 500);
            }
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'Failed to retrieve table data.', 500);
        }
    }
}
