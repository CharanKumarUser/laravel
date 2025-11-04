<?php
namespace App\Http\Controllers\System\Business\SupportAndHelp;
use App\Facades\{Data, Developer, Random, Skeleton, Select, Helper};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for SupportAndHelp entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new SupportAndHelp entities.
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
                case 'business_support_tickets':
                case 'business_support_my_tickets':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw','html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Issue Photo" data-name="attachment_path" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src=""></div>','col' => '12'],
                            ['type' => 'select', 'name' => 'issue_category', 'label' => 'Category', 'options' => Helper::dropdown('ticket_category'), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'subject', 'label' => 'Subject', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '255']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => true, 'col' => '12', 'attr' => ['rows' => 4, 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-ticket me-1"></i> Add Support Ticket',
                        'short_label' => '',
                        'button' => 'Save Ticket',
                        'script' => 'window.general.select();window.general.files();'
                    ];
                    break;
                case 'business_support_faqs':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Company', 'options' => Select::options('companies', 'array', ['company_id' => 'name', 'name' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'faq_id', 'label' => 'FAQ ID', 'required' => false, 'col' => '6', 'attr' => ['placeholder' => 'Auto-generated if empty']],
                            ['type' => 'text', 'name' => 'question', 'label' => 'Question', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '255']],
                            ['type' => 'textarea', 'name' => 'answer', 'label' => 'Answer', 'required' => true, 'col' => '12', 'attr' => ['rows' => 4, 'maxlength' => '1000']],
                            ['type' => 'text', 'name' => 'category', 'label' => 'Category', 'required' => true, 'col' => '6'],
                            ['type' => 'text', 'name' => 'tags', 'label' => 'Tags', 'required' => false, 'col' => '6', 'attr' => ['placeholder' => 'Comma-separated tags']],
                            ['type' => 'select', 'name' => 'is_public', 'label' => 'Public?', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Active?', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-circle-question me-1"></i> Add FAQ',
                        'short_label' => '',
                        'button' => 'Save FAQ',
                        'script' => 'window.general.select();'
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