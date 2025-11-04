<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{Data, Developer, Random, Skeleton, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the AssetManagement module.
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
            $message = 'AssetManagement data retrieved successfully.';
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
                        'company' => ['companies.name AS company', true],
                        'image' => ['assets.image_url AS image', true],
                        'name' => ['assets.name', true],
                        'assigned_to' => ['asset_assignments.user_id AS assigned_to', true],
                        'category' => ['asset_categories.name AS category', true],
                        'purchase_date' => ['assets.purchase_date', true],
                        'purchase_cost' => ['assets.purchase_cost', true],
                        'warranty_expiry' => ['assets.warranty_expiry', true],
                        'status' => ['assets.status', true],
                        'location' => ['assets.location', true],
                        'vendor_name' => ['assets.vendor_name', true],
                        'vendor_contact' => ['assets.vendor_contact', true],
                        'notes' => ['assets.notes', true],
                        'is_active' => ['assets.is_active', true],
                    ];
                    $custom = [
                         [
                            'type' => 'modify',
                            'column' => 'image',
                            'view' => '
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xl avatar-rounded rounded-circle">
                                  ::IF(image IS NOT NULL, <img src="::~\App\Services\FileService->getFile(::image::)~::" alt="Asset Image" class="img-fluid h-auto w-auto">, <img src="' . asset('default/preview-square.svg') . '" alt="Asset Image" class="img-fluid rounded-circle">)::
                                </div>
                            </div>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'is_active',
                            'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'company',
                            'view' => '<span class="badge bg-purple rounded-pill">::company::</span>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'category',
                            'view' => '::IF(category IS NOT NULL,<span class="badge bg-warning rounded-pill">::category::</span>, <span>N/A</span>)::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'assigned_to',
                            'view' => '<div class = "::IF(status = \'assigned\',d-block,d-none)::">::~\App\Http\Helpers\ProfileHelper->userProfile(::assigned_to::,["flex","lg"],["role","scope"],1)~::</div>
                                       <div class = "::IF(status = \'assigned\',d-none,d-block)::">Not assigned yet</div>',
                            'renderHtml' => true
                        ]
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'asset_categories', 'on' => [['assets.category_id', 'asset_categories.category_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['assets.company_id', 'companies.company_id']]],
                        ['type' => 'left', 'table' => 'asset_assignments', 'on' => [['assets.asset_id', 'asset_assignments.asset_id']]],
                        ['type' => 'left', 'table' => 'users', 'on' => [['asset_assignments.user_id', 'users.user_id']]],
                    ];
                    if ($reqSet['key'] === 'business_my_assets') {
                        unset($columns['document'], $columns['vendor_name'], $columns['vendor_contact'], $columns['purchase_date'], $columns['purchase_cost'], $columns['warranty_expiry'], $columns['is_active'],);
                        $user_id = Skeleton::authUser()->user_id;
                        $assetId = BusinessDB::table('asset_assignments')->where('user_id', $user_id)->whereNull('deleted_at')->pluck('asset_id')->toArray();
                        $reqSet['actions'] = 'v';
                        $conditions = [
                            ['column' => 'assets.asset_id', 'operator' => 'IN', 'value' => $assetId],
                        ];
                    }
                break;
                case 'business_asset_categories':
                    $columns = [
                        'sno' => ['asset_categories.sno', true],
                        'category_id' => ['asset_categories.category_id', false],
                        'name' => ['asset_categories.name', true],
                        'description' => ['asset_categories.description', true],
                        'is_active' => ['asset_categories.is_active', true],
                        'created_by' => ['asset_categories.created_by', true],
                        'updated_by' => ['asset_categories.updated_by', true],
                        'created_at' => ['asset_categories.created_at', true],
                        'updated_at' => ['asset_categories.updated_at', true],
                    ];
                    $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'is_active',
                            'view' => '::IF(is_active = 1, <span class="badge bg-success">Active</span>, <span class="badge bg-danger">Inactive</span>)::',
                            'renderHtml' => true
                        ],
                    ];
                    break;
                case 'business_asset_assignment':
                    $columns = [
                        'assignment_id' => ['asset_assignments.assignment_id', false],
                        'company' => ['companies.name AS company', true],
                        'user' => ['asset_assignments.user_id AS user', true],
                        'asset' => ['assets.name AS asset', true],
                        'assigned_quantity' => ['asset_assignments.quantity AS assigned_quantity', true],
                        'assigned_date' => ['asset_assignments.assigned_date', true],
                        'return' => ['asset_assignments.id AS return', true],
                        'return_date' => ['asset_assignments.return_date', true],
                        'status' => ['asset_assignments.status', true],
                        'notes' => ['asset_assignments.notes', true],
                        'created_by' => ['asset_assignments.created_by', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'assets', 'on' => [['asset_assignments.asset_id', 'assets.asset_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['assets.company_id', 'companies.company_id']]],
                    ];
                    $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'user',
                            'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::user::, ["flex","lg"], ["role", "scope"], 0)~::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'status',
                            'view' => '::IF(status = \'assigned\', <span class="badge bg-info text-white">Assigned</span>)::ELSEIF(status = \'returned\', <span class="badge bg-success text-white">Returned</span>)::ELSEIF(status = \'lost\', <span class="badge bg-danger text-white">Lost</span>)::ELSE(<span class="badge bg-warning text-white">Damaged</span>)::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'company',
                            'view' => '<span class="badge bg-purple rounded-pill text-white">::company::</span>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'created_by',
                            'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::created_by::, ["flex","lg"], ["role", "scope"], 1)~::',
                            'renderHtml' => true
                        ],
                         [
                            'type' => 'modify',
                            'column' => 'asset',
                            'view' => '::asset::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'assigned_quantity',
                            'view' => '::assigned_quantity::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'return',
                            'view' => '<button class="btn btn-sm btn-info skeleton-popup" data-token="'.Skeleton::skeletonToken('business_return_assets').'_e_::assignment_id::">Return Asset</button>',
                            'renderHtml' => true
                        ],
                    ];
                    break;
               
                case 'business_asset_maintenance':
                    $columns = [
                        'maintenance_id' => ['asset_maintenance.maintenance_id', false],
                        'company' => ['companies.name AS company', true],
                        'asset' => ['assets.name AS asset', true],
                        'maintenance_type' => ['asset_maintenance.maintenance_type', true],
                        'description' => ['asset_maintenance.description', true],
                        'maintenance_date' => ['asset_maintenance.maintenance_date', true],
                        'cost' => ['asset_maintenance.cost', true],
                        'vendor_name' => ['asset_maintenance.vendor_name', true],
                        'vendor_contact' => ['asset_maintenance.vendor_contact', true],
                        'next_due_date' => ['asset_maintenance.next_due_date', true],
                        'status' => ['asset_maintenance.status', true],
                        'created_by' => ['asset_maintenance.created_by', true],
                        'created_at' => ['asset_maintenance.created_at', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'assets', 'on' => [['asset_maintenance.asset_id', 'assets.asset_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['assets.company_id', 'companies.company_id']]]
                    ];
                    $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'maintenance_type',
                            'view' => '::IF(maintenance_type = \'repair\', <span class="badge bg-danger text-white">Repair</span>)::ELSEIF(maintenance_type = \'service\', <span class="badge bg-info text-white">Service</span>)::ELSE(<span class="badge bg-success text-white">Inspection</span>)::',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'status',
                            'view' => '::IF(status = \'scheduled\', <span class="badge bg-info text-white">Scheduled</span>)::ELSEIF(status = \'completed\', <span class="badge bg-success text-white">Completed</span>)::ELSE(<span class="badge bg-warning text-white">Pending</span>)::',
                            'renderHtml' => true
                        ], 
                        [
                            'type' => 'modify',
                            'column' => 'company',
                            'view' => '<span class="badge bg-purple rounded-pill">::company::</span>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'asset',
                            'view' => '<span class="badge bg-primary rounded-pill">::asset::</span>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'created_by',
                            'view' => '::~\App\Http\Helpers\ProfileHelper->userProfile(::created_by::, ["flex","lg"], ["role", "scope"], 1)~::',
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
