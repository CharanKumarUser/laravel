<?php

namespace App\Http\Controllers\System\Business\SmartPresence;

use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for handling AJAX table data requests in the SmartPresence module.
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
            $message = 'SmartPresence data retrieved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_smart_geo_location':
                    $columns = [
                        'id'              => ['smart_geo_locations.id', true],
                        'company'        => ['companies.name AS company', true],
                        'geo_location_id' => ['smart_geo_locations.geo_location_id', true],
                        'location'            => ['smart_geo_locations.name AS location', true],
                    ];
                    $joins = [
                        ['type' => 'left', 'table' => 'companies', 'on' => [['smart_geo_locations.company_id', 'companies.company_id']]]
                    ];
                    $custom = [
                        ['type' => 'modify', 'column' => 'company', 'view' => '<span class="badge bg-purple">::company::</span>', 'renderHtml' => true]
                    ];

                    $title = 'Geo Locations Retrived';
                    $message = 'Profile entity data retrieved successfully.';
                    break;
                case 'business_smart_enroll_face':  
                    $columns = [
                        'id'            => ['smart_face_enroll.id', true],
                        'face_enroll_id'=> ['smart_face_enroll.face_enroll_id', true],
                        'user_id'          => ['smart_face_enroll.user_id', true],
                        'capture'          => ['smart_face_enroll.capture', true],

                    ];
                     $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'capture',
                            'view' => '::IF(capture IS NOT NULL,
                                            <div class="avatar avatar-lg avatar-rounded rounded-circle">
                                            <img src="::~\App\Services\FileService->getFile(::capture::)~::" alt="User Avatar"
                                                class="img-fluid rounded-circle"></div>,
                                            <div class="avatar avatar-lg avatar-rounded rounded-circle">
                                                <img src="' . asset('default/preview-square.svg') . '" alt="User Avatar"class="img-fluid rounded-circle"></div>)::',
                            'renderHtml' => true
                        ],

                    ];
                    $title = 'Entities Retrieved';
                    $message = 'Profile entity data retrieved successfully.';
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
