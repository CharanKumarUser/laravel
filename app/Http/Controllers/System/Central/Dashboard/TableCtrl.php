<?php
namespace App\Http\Controllers\System\Central\Dashboard;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the Dashboard module.
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
            $message = 'Dashboard data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'Dashboard_entities':
                    $columns = [
                        'id' => ['entities.id', true],
                        'name' => ['entities.name', true],
                        'type' => ['entities.type', true],
                        'status' => ['entities.status', true],
                        'created_at' => ['entities.created_at', true],
                        'updated_at' => ['entities.updated_at', true],
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'status', 'view' => '::((status = 1) ~ <span class="badge bg-success">Active</span> || <span class="badge bg-danger">Inactive</span>)::', 'renderHtml' => true],
                    ];
                    $title = 'Entities Retrieved';
                    $message = 'Dashboard entity data retrieved successfully.';
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
