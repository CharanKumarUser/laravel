<?php
namespace App\Http\Controllers\System\Business\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{TableHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for handling AJAX table data requests in the SupportAndHelp module.
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
            $message = 'SupportAndHelp data retrieved successfully.';
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
                        'user'             => ['supports.user_id AS user', true],
                        'subject'          => ['supports.subject', true],
                        'description'      => ['supports.description', true],
                        'issue_scope'      => ['supports.issue_scope', true],
                        'issue_category'   => ['supports.issue_category', true],
                        'issue_priority'   => ['supports.issue_priority', true],
                        'issue_status'     => ['supports.issue_status', true],
                        'reported_by'      => ['supports.reported_by', true],
                        'assigned_to'      => ['supports.assigned_to', true],
                        'parent_support_id'=> ['supports.parent_support_id', true],
                        'reported_at'      => ['supports.reported_at', true],
                        'resolved_at'      => ['supports.resolved_at', true],
                        'resolution_notes' => ['supports.resolution_notes', true],
                        'attachment_path'  => ['supports.attachment_path', true],
                        'is_private_note'  => ['supports.is_private_note', true],
                        'is_active'        => ['supports.is_active', true],
                        'created_at'       => ['supports.created_at', true],
                        'updated_at'       => ['supports.updated_at', true],
                    ];

                    $custom = [
                        
                        [
                            'type'   => 'modify',
                            'column' => 'user',
                            'view'   => '::~\App\Http\Helpers\ProfileHelper->userProfile(::user::, ["flex","lg"], ["company","role", "scope"], 1)~::',
                            'renderHtml' => true
                        ],
                        [
                            'type'   => 'modify',
                            'column' => 'reported_by',
                            'view'   => '::~\App\Http\Helpers\ProfileHelper->userProfile(::reported_by::, ["flex","lg"], ["company","role", "scope"], 1)~::',
                            'renderHtml' => true
                        ],
                    ];
                    if($reqSet['key'] === 'business_support_my_tickets'){
                        $conditions = [
                            ['column' => 'supports.user_id', 'operator' => '=', 'value' => Skeleton::authUser()->user_id],
                        ];
                    }
                    
                break;
                case 'business_support_faqs':
                    $columns = [
                        'id'          => ['support_faqs.id', true],
                        'faq_id'      => ['support_faqs.faq_id', true],
                        'business_id' => ['support_faqs.business_id', true],
                        'company_id'  => ['support_faqs.company_id', true],
                        'user_id'     => ['support_faqs.user_id', true],
                        'question'    => ['support_faqs.question', true],
                        'answer'      => ['support_faqs.answer', true],
                        'category'    => ['support_faqs.category', true],
                        'tags'        => ['support_faqs.tags', true],
                        'is_public'   => ['support_faqs.is_public', true],
                        'is_active'   => ['support_faqs.is_active', true],
                        'created_by'  => ['support_faqs.created_by', true],
                        'updated_by'  => ['support_faqs.updated_by', true],
                    ];

                    $custom = [
                        [
                            'type' => 'modify',
                            'column' => 'is_public',
                            'view' => '<span class="px-2 py-1 rounded-pill ::IF(is_public = 1, bg-info, bg-secondary)::">::IF(is_public = 1, Public, Private)::</span>',
                            'renderHtml' => true
                        ],
                        [
                            'type' => 'modify',
                            'column' => 'is_active',
                            'view' => '<span class="px-2 py-1 rounded-pill ::IF(is_active = 1, bg-success, bg-danger)::">::IF(is_active = 1, Active, Inactive)::</span>',
                            'renderHtml' => true
                        ],
                    ];

                    $title = 'Support FAQs Retrieved';
                    $message = 'Support FAQs data retrieved successfully.';
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
