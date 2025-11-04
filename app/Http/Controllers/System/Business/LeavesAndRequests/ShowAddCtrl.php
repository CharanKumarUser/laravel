<?php

namespace App\Http\Controllers\System\Business\LeavesAndRequests;

use App\Facades\{Data, Developer, Random, Select, Skeleton, Scope};
use App\Services\Data\DataService;
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Controller for rendering the add form for LeaveManagement entities.
 */

class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new LeaveManagement entities.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize popup configuration and system options
            $popup = [];
            $holdPopup = false;
            $system = ['central' => 'Central', 'business' => 'Business'];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_request_types':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Type Name', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '100']],
                            ['type' => 'switch', 'name' => 'carry_forward', 'label' => 'Carry Forward', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'attr' => ['class' => 'carry-forward-toggle']],
                            ['type' => 'switch', 'name' => 'is_encashable', 'label' => 'Encashable', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'attr' => ['class' => 'encash-forward-toggle']],
                            ['type' => 'switch', 'name' => 'is_prorated', 'label' => 'Prorated', 'required' => false, 'col' => '4', 'options' => ['0' => 'No', '1' => 'Yes'], 'attr' => ['class' => 'prorate-forward-toggle']],
                            ['type' => 'number', 'name' => 'max_days_per_year', 'label' => 'Max Days Per Year', 'required' => false, 'col' => '4', 'col_class' => 'type-category-leave-container', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'number', 'name' => 'consecutive_days', 'label' => 'Consecutive Days Per Month', 'required' => false, 'col' => '4', 'col_class' => 'type-consecutive-leave-container', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'forward_leaves', 'label' => 'Forwardable Leaves', 'required' => false, 'col' => '12', 'col_class' => 'forward-leaves-container d-none', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'number', 'name' => 'encash_days', 'label' => 'Encashable Days', 'required' => false, 'col' => '12', 'col_class' => 'encash-leaves-container d-none', 'attr' => ['min' => '0', 'max' => '365']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-list me-1"></i> Add Request Type',
                        'short_label' => 'Define new request categories and policies',
                        'button' => 'Save Request Type',
                        'script' => '
                            window.general.select();
                            document.addEventListener("change", e => {
                                const forwardBox = document.querySelector(".forward-leaves-container");
                                const encashBox = document.querySelector(".encash-leaves-container");

                                if(e.target.name === "carry_forward" && forwardBox){
                                    (e.target.checked || e.target.value=="1") 
                                        ? forwardBox.classList.remove("d-none") 
                                        : forwardBox.classList.add("d-none");
                                }

                                if(e.target.name === "is_encashable" && encashBox){
                                    (e.target.checked || e.target.value=="1") 
                                        ? encashBox.classList.remove("d-none") 
                                        : encashBox.classList.add("d-none");
                                }
                            });
                        '
                    ];
                    break;
                case 'business_assign_request_types':
                    $requestTypes = Select::options('request_types', 'array', ['request_type_id' => 'name'], ['where' => ['is_active' => '1']]);
                    $scopes = Scope::getScopePaths('all', Skeleton::authUser()->scope_id ?? null, true);
                    $scopeIds = Scope::userChildScopes();
                    Developer::info($scopeIds);
                    $tags = Select::options('users', 'array', ['user_id'=>'first_name'], ['whereIn' => ['scope_id' => $scopeIds]]);
                    $token = Skeleton::skeletonToken('business_assign_request_types') . '_s';
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'raw', 'html' => '<div class="path-dropdown w-100" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope data-source="' . $token . '" data-select-trigger=".update-users-select" data-set="scope" name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                                ['type' => 'select', 'name' => 'user_id', 'label' => 'User', 'class' => ['update-users-select'], 'options' => [''], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dynamic', 'multiple'=>'multiple']],
                                ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Request Type', 'options' =>  $requestTypes, 'required' => true, 'col' => '6', 'col_class' => 'request-type-leave-container', 'attr' => ['data-select' => 'dropdown','multiple' => 'multiple']],
                                ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'select', 'name' => 'tag_id', 'label' => 'Tag To', 'options' =>  $tags, 'required' => true, 'col' => '6', 'col_class' => 'request-type-tag-container', 'attr' => ['data-select' => 'dropdown','multiple' => 'multiple']],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-solid fa-tasks me-1"></i> Assign Request Types',
                            'short_label' => 'Define Request Types to the Users',
                            'button' => 'Assign Request Types',
                            'script' => 'window.general.select();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ',[] , "single", true);
                            
                            
                            '
                        ];
                        break;
                case 'business_requests':
                    // Get leave types and request types separately for filtering
                    $requestTypes = Select::options('request_types', 'array', ['request_type_id' => 'name'], ['where' => ['is_active' => '1']]);
                    $scopeIds = Scope::getParents(Skeleton::authUser()->scope_id);
                    $userId = Skeleton::authUser()->user_id;
                    Developer::info($userId);
                    $tagged_userIds = Dataservice::query('business','assign_request_types',[
                        'select' => ['tag_id'],
                        'where' => [
                            ['column' => 'user_id', 'operator' => '=', 'value'=> $userId]
                        ]
                    ]);
                    $tagIds = [];
                    if (!empty($tagged_userIds['data'])) {
                        $tagIds = array_column($tagged_userIds['data'], 'tag_id');
                    }
                    Developer::info('grouped array of tags',$tagIds);
                    $tags = [];
                    if (!empty($tagIds)) {
                        $tags = Select::options(
                            'users',
                            'array',
                            ['user_id' => 'first_name'],
                            [
                                'whereIn' => ['user_id' => $tagIds]
                            ]
                        );
                    }
                    Developer::info('all user ids',$tagged_userIds);
                    Developer::info('user id tags',$tags);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'request_type', 'label' => 'Applying For', 'id' => 'request-type-selector', 'options' => ['full-day' => 'Full Day', 'short-time' => 'Short time'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Request Type', 'options' =>  $requestTypes, 'required' => true, 'col' => '6', 'col_class' => 'request-type-leave-container', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'date', 'name' => 'start_datetime', 'label' => 'Start Date & Time', 'id' => 'start-field', 'required' => true, 'col' => '4', 'col_class' => 'request-type-general-container', 'attr' => []],
                            ['type' => 'date', 'name' => 'end_datetime', 'label' => 'End Date & Time', 'id' => 'end-field', 'required' => true, 'col' => '4', 'col_class' => 'request-type-general-container', 'attr' => []],
                            ['type' => 'select', 'name' => 'tag_ids', 'label' => 'Tag To', 'options' =>  $tags, 'required' => true, 'col' => '4', 'col_class' => 'request-type-tag-container', 'attr' => ['data-select' => 'dropdown','multiple' => 'multiple']],
                            ['type' => 'text', 'name' => 'subject', 'label' => 'Subject', 'required' => true, 'col' => '12',],
                            ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason', 'required' => true, 'col' => '12', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar-check me-1"></i> Add Request',
                        'short_label' => 'Submit new request for approval',
                        'button' => 'Save Request',
                        'script' => 'window.general.select();$(function () {
                            const $requestTypeSelector = $("#request-type-selector");
                            const $startField = $("#start-field");
                            const $endField = $("#end-field");
                            if (!$requestTypeSelector.length) return;
                            const updateFieldTypes = () => {
                                const selectedValue = $requestTypeSelector.val();
                                console.log(selectedValue);
                                if (selectedValue === "full-day") {
                                    $startField.attr("type", "date").attr("label", "Start Date");
                                    $endField.attr("type", "date").attr("label", "End Date");
                                } else if (selectedValue === "short-time") {
                                     $startField.attr("type", "datetime-local").attr("label", "Start Time");
                                    $endField.attr("type", "datetime-local").attr("label", "End Time");
                                }
                            };
                            $requestTypeSelector.on("change", updateFieldTypes);
                            updateFieldTypes();
                        });'

                    ];
                    break;

                case 'business_request_balances':
                    $requestTypes = Select::options('request_types', 'array', ['request_type_id' => 'name'], ['where' => ['is_active' => '1']]);
                    $scopes = Scope::getScopePaths('all', null, true);
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope data-source="' . $token . '" data-select-trigger=".update-users-select" data-set="scope" name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '12'],
                            ['type' => 'select', 'name' => 'user_id', 'label' => 'User', 'class' => ['update-users-select'], 'options' => Select::options('users', 'array', ['user_id' => 'username']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dynamic']],
                            ['type' => 'select', 'name' => 'request_type_id', 'label' => 'Request Type', 'options' => $requestTypes, 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'year', 'label' => 'Year', 'required' => true, 'col' => '6', 'attr' => ['min' => '2020', 'max' => '2030']],
                            ['type' => 'number', 'name' => 'allocated_days', 'label' => 'Allocated Days', 'required' => true, 'col' => '6', 'attr' => ['min' => '0', 'max' => '365', 'step' => '0.5']],
                            ['type' => 'switch', 'name' => 'ignore_deduction', 'label' => 'Ignore previous deductions', 'required' => false, 'col' => '12', 'options' => ['0' => 'No', '1' => 'Yes']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calculator me-1"></i> Add Leave Balance',
                        'short_label' => 'Configure leave allocation for employees',
                        'button' => 'Save Leave Balance',
                        'script' => 'window.general.select();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ',[] , "single", true);'
                    ];
                    break;
                default:
                    return ResponseHelper::emptyPopup();
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}