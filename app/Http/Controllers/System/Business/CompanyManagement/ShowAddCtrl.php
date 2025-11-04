<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, Select};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request,};
use Illuminate\Support\Facades\{Config,Validator};
/**
 * Controller for rendering the add form for CompanyManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new CompanyManagement entities.
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
            Developer::info($reqSet);
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
                 case 'business_companies':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                       'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Company Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'legal_name', 'label' => 'Legal Name', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'industry', 'label' => 'Industry', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Company Type', 'options' => ['Private Limited' => 'Private Limited', 'Public Limited' => 'Public Limited', 'Partnership' => 'Partnership', 'Sole Proprietorship' => 'Sole Proprietorship', 'LLP' => 'LLP'], 'required' => false, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150', 'data-validate' => 'email']],
                            ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '10', 'data-validate' => 'indian-phone', 'pattern'=> '^[6-9]\d{9}$']],
                            ['type' => 'url', 'name' => 'website', 'label' => 'Website', 'required' => false, 'col' => '12', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'state', 'label' => 'State', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'pincode', 'label' => 'Pincode', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '20', 'data-validate' => 'pincode']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-building me-1"></i> Add Company',
                        'short_label' => 'Fill out this form to add a company',
                        'button' => 'Save Company',
                        'script' => 'window.general.select();window.general.validateForm();window.general.files();'
                    ];
                    break;
                case 'business_company_holidays':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'hidden', 'col_class' => 'my-0', 'name' => 'company_id', 'value' => $reqSet['id'] ?? '', 'class' => ['mb-0']],
                            ['type' => 'raw','html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Holiday image (Optional)" data-name="image" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src=""></div>','col' => '12'],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Holiday Name', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '150']],
                            ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'select', 'name' => 'recurring_type', 'label' => 'Recurring Type', 'options' => ['none' => 'None', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'onchange' => 'handleRecurringTypeChange(this)']],
                            ['type' => 'select', 'name' => 'recurring_day', 'col_class' => 'd-none', 'label' => 'Recurring Day', 'options' => ['' => 'Select Day', 'sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'recurring_week', 'col_class' => 'd-none', 'label' => 'Recurring Week', 'options' => ['' => 'Select Week', 'all' => 'All', 'first' => 'First', 'second' => 'Second', 'third' => 'Third', 'fourth' => 'Fourth', 'last' => 'Last'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'color', 'name' => 'color', 'label' => 'Background Color (Optional)', 'value' => "#E8C6A6", 'required' => false, 'col' => '4', 'attr' => ['placeholder' => '#FFFFFF']],
                            ['type' => 'color', 'name' => 'text_color', 'label' => 'Text Color (Optional)', 'value' => "#FFFFFF", 'required' => false, 'col' => '4', 'attr' => ['placeholder' => '#000000']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'col' => '12', 'attr' => ['rows' => '3']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-calendar me-1"></i> Add Holiday',
                        'short_label' => 'Add new company holiday with scheduling options',
                        'button' => 'Save Holiday',
                        'script' => '
                            window.general.select();
                            window.general.validateForm();
                            window.general.files();
                            window.skeleton.datePicker();

                        window.handleRecurringTypeChange = function(selectEl) {
                    const value = selectEl.value;

                    const recurringDay = document.querySelector("[name=\'recurring_day\']");
                    const recurringWeek = document.querySelector("[name=\'recurring_week\']");

                    const recurringDayCol = recurringDay?.closest("[class*=\'col\']");
                    const recurringWeekCol = recurringWeek?.closest("[class*=\'col\']");

                    if (!recurringDay || !recurringWeek) return;

                    if (value !== "none") {
                        // Show both column and select
                        recurringDayCol?.classList.remove("d-none");
                        recurringWeekCol?.classList.remove("d-none");
                        recurringDay.classList.remove("d-none");
                        recurringWeek.classList.remove("d-none");

                        // If using Select2, force re-render
                        if ($(recurringDay).data("select2")) $(recurringDay).select2();
                        if ($(recurringWeek).data("select2")) $(recurringWeek).select2();
                    } else {
                        // Hide both column and select
                        recurringDayCol?.classList.add("d-none");
                        recurringWeekCol?.classList.add("d-none");
                        recurringDay.classList.add("d-none");
                        recurringWeek.classList.add("d-none");
                    }
                };


                        '
                    ];
                    break;

                    break;
                case 'business_company_policies':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                       'fields' => [

                            ['type' => 'hidden', 'col_class'=>'my-0', 'name' => 'company_id', 'value' => $request->id ?? '', 'class'=>['mb-0']],
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Policy Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '500', 'rows' => '4']],
                            ['type' => 'text', 'name' => 'category', 'label' => 'Category', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '100']],
                            ['type' => 'date', 'name' => 'effective_date', 'label' => 'Effective Date', 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'date', 'name' => 'expiry_date', 'label' => 'Expiry Date', 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date','data-date-picker-allow' => 'future']],
                            ['type' => 'text', 'name' => 'version', 'label' => 'Version', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '20']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-building me-1"></i> Add Policy',
                        'short_label' => 'Fill out this form to add a company',
                        'button' => 'Save Policy',
                        'script' => 'window.general.select();window.skeleton.datePicker();'
                            
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