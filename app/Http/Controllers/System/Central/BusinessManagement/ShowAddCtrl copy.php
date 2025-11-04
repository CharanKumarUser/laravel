<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\{CentralDB, Data, Developer, Random, Select, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{Helper, PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for rendering the add form for BusinessManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new BusinessManagement entities.
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
                case 'central_business_module_pricings':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'module_id', 'label' => 'Module', 'required' => true, 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name'], ['where' => ['system' => ['in' => ['business', 'open']], 'is_approved' => '1']]), 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'dependent_module_ids', 'label' => 'Depends on Modules', 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name'], ['where' => ['system' => ['in' => ['business', 'open']], 'is_approved' => '1']]), 'col' => '12', 'class' => ['h-auto'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple']],
                            ['type' => 'number', 'name' => 'price', 'label' => 'Price (₹)', 'required' => true, 'col' => '6', 'attr' => ['step' => '0.01', 'id' => 'amount']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '500']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-box me-1"></i>Add Module Pricing',
                        'short_label' => 'Create pricing for individual modules',
                        'button' => 'Add Module Price',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'central_business_plans':
                    $modulePriceArrData = Data::get('central', 'business_module_pricing', ['where' => ['is_approved' => '1']]);
                    $modulePriceArr = $modulePriceArrData['data'];
                    $source = '';
                    $target = '';
                    foreach ($modulePriceArr as $set) {
                        $source .= '<div data-drag-item data-value="' . $set['module_price_id'] . '" data-sum="' . $set['price'] . '" class="d-flex flex-row justify-content-start align-items-center gap-2 bg-light p-1 px-1 border rounded-2 mb-1">
                    <div><span class="avatar avatar-sm avatar-rounded p-1 rounded-circle" style="background: ' . Helper::colors('gradient-dark-2', 'background') . '">' . Helper::textProfile($set['module_name'], 2) . '</span></div>
                    <div class="d-flex flex-column justify-content-start w-100">
                    <div class="sf-14 fw-bold text-nowrap pe-3">' . $set['module_name'] . '</div>
                    <div class="d-flex flex-row justify-content-between"><span class="sf-9 text-muted">' . $set['module_id'] . '</span><span class="sf-9 text-danger">₹ ' . $set['price'] . '</span></div></div></div>';
                    }
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Plan Name', 'required' => true, 'col' => '4', 'attr' => ['maxlength' => '150']],
                            ['type' => 'select', 'name' => 'duration_type', 'label' => 'Duration Type', 'options' => ['month' => 'Month', 'year' => 'Year'], 'required' => true, 'col' => '2', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'duration_value', 'label' => 'Duration Value', 'required' => true, 'col' => '3'],
                            ['type' => 'text', 'name' => 'icon', 'label' => 'Plan Icon', 'required' => false, 'col' => '3'],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Plan Type', 'options' => ['fixed' => 'Fixed', 'custom' => 'Custom'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'display_order', 'label' => 'Display Order', 'required' => false, 'col' => '3'],
                            ['type' => 'select', 'name' => 'landing_visibility', 'label' => 'Visible on Landing?', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Is Approved?', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'dragger', 'source' => ['html' => $source, 'input_string' => '.area_1_values', 'input_sum' => '.area_1_sum', 'separator' => ',', 'class' => ['drag-area']], 'target' => ['html' => $target, 'input_string' => '.dropped-module-ids', 'input_sum' => '.dropped-module-sum', 'separator' => ',', 'class' => ['drag-area']], 'col' => '12'],
                            ['type' => 'number', 'name' => 'discount', 'label' => 'Discount (%)', 'required' => false, 'col' => '2', 'attr' => ['step' => '0.1', 'max' => '99']],
                            ['type' => 'repeater', 'name' => 'tax', 'set' => 'pair', 'fields' => [['type' => 'select', 'name' => 'label', 'label' => 'Tax Type', 'options' => ['' => '-- Select --', 'gst' => 'GST', 'cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'], 'required' => true], ['type' => 'number', 'name' => 'value', 'label' => 'Value', 'placeholder' => 'Tax', 'required' => true]], 'col' => '5'],
                            ['type' => 'number', 'name' => 'amount', 'label' => 'Amount (₹)', 'class' => ['dropped-module-sum'], 'required' => true, 'col' => '2', 'attr' => ['step' => '0.01', 'id' => 'amount', 'readonly' => 'readonly']],
                            ['type' => 'number', 'name' => 'total_amount', 'label' => 'Total Amount (₹)', 'required' => true, 'col' => '3', 'attr' => ['step' => '0.01', 'id' => 'amount', 'readonly' => 'readonly']],
                            ['type' => 'raw', 'html' => '<div class="mt-2"><label>Description</label></div><div data-editor-id="plan-description" data-editor-name="description" data-tools="minimum"></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="mt-2"><label>Features</label></div><div data-editor-id="plan-features" data-editor-name="features" data-tools="minimum"></div>', 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-layer-group me-1"></i>Add New Plan',
                        'short_label' => 'Create and configure pricing plans for bundled modules.',
                        'button' => 'Add Plan',
                        'script' => 'window.general.select();window.skeleton.drag();window.general.repeater();window.skeleton.editor("plan-description", "", "");window.skeleton.editor("plan-features", "", "");
                        function recalculateAll() {
                            const baseInput = document.querySelector(".dropped-module-sum");
                            const discountInput = document.querySelector("input[name=discount]");
                            const amountInput = document.querySelector("input[name=amount]");
                            const totalAmountInput = document.querySelector("input[name=total_amount]");
                            if (!baseInput || !amountInput || !totalAmountInput) return;
                            const baseValue = parseFloat(baseInput.value || "0") || 0;
                            const discountValue = parseFloat(discountInput?.value || "0") || 0;
                            const discountedAmount = baseValue - (baseValue * (discountValue / 100));
                            let totalTax = 0;
                            document.querySelectorAll("[data-repeater]").forEach(function(repeaterRow) {
                                const taxValueInput = repeaterRow.querySelector("input[placeholder=Tax]");
                                if (!taxValueInput) return;
                                const taxPercent = parseFloat(taxValueInput.value || "0") || 0;
                                if (taxPercent > 0) {totalTax += (discountedAmount * taxPercent / 100);}
                            });const finalAmount = discountedAmount + totalTax;totalAmountInput.value = finalAmount.toFixed(2);
                        }
                        function addRecalculationEvents() {
                            const dynamicSelectors = [".dropped-module-sum","input[name=discount]","[data-repeater-container]"];
                            dynamicSelectors.forEach(selector => {
                                document.querySelectorAll(selector).forEach(el => {
                                    el.addEventListener("input", recalculateAll);
                                    el.addEventListener("change", recalculateAll);
                                });
                            });
                            document.querySelectorAll("[data-repeater-container]").forEach(container => {
                                container.addEventListener("click", function(e) {
                                    if (e.target && e.target.closest("[data-repeater-add]")) {
                                        setTimeout(() => {addRecalculationEvents();recalculateAll();}, 100);
                                    }
                                });
                            });
                        }
                        function observeDroppedModuleSum() {
                            const input = document.querySelector(".dropped-module-sum");
                            if (!input) return;
                            const observer = new MutationObserver(recalculateAll);
                            observer.observe(input, { attributes: true });
                            setInterval(() => {const prev = input.getAttribute("data-prev-val") || "";if (prev !== input.value) {input.setAttribute("data-prev-val", input.value);recalculateAll();}}, 300);
                        }
                        setTimeout(() => {recalculateAll();addRecalculationEvents();observeDroppedModuleSum();}, 500);'
                    ];
                    break;
                case 'central_onboard_business':
                    $onboardForm = '<div class="p-0">
                        <div data-stepper-container data-stepper-type="linear" data-progress-type="bar+icon" data-submit-btn-text="Submit Now" data-btn-class="lander-form-btn">
                            
                            <div data-step data-title="Admin Details" data-icon="fa-user">
                                <div class="row g-3 pb-4">
                                    <div class="col-6"><div class="float-input-control"><input type="text" name="admin_first_name" class="form-float-input" data-validate="name" placeholder="First Name" required><label class="form-float-label">First Name<span class="text-danger">*</span></label></div></div>
                                    <div class="col-6"><div class="float-input-control"><input type="text" name="admin_last_name" class="form-float-input" placeholder="Last Name" required><label class="form-float-label">Last Name</label></div></div>
                                    <div class="col-6"><div class="float-input-control"><input type="tel" name="admin_phone" class="form-float-input" data-validate="indian-phone" placeholder="Phone" required><label class="form-float-label">Phone<span class="text-danger">*</span></label></div></div>
                                    <div class="col-6"><div class="float-input-control"><input type="email" name="admin_email" class="form-float-input" data-validate="email" placeholder="@email" required><label class="form-float-label">Email<span class="text-danger">*</span></label></div></div>
                                    <div class="col-12"><div class="float-input-control"><span class="float-group-end toggle-password"><i class="ti ti-eye-off"></i></span><input type="password" name="admin_password_hash" class="form-float-input" placeholder="Password" required><label class="form-float-label">Password<span class="text-danger">*</span></label></div></div>
                                </div>
                            </div>

                            <!-- Step 2: Organization Details -->
                            <div data-step data-title="Organization Details" data-icon="fa-building-user">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="name" class="form-float-input" placeholder="Company Name" required>
                                            <label class="form-float-label">Company Name<span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-6"><div class="float-input-control"><input type="text" name="legal_name" class="form-float-input" placeholder="Legal Name"><label class="form-float-label">Legal Name</label></div></div>
                                    <div class="col-6"><div class="float-input-control"><input type="email" name="email" class="form-float-input" placeholder="Email" data-validate="email" required><label class="form-float-label">Email<span class="text-danger">*</span></label></div></div>
                                    <div class="col-6"><div class="float-input-control"><input type="tel" name="phone" class="form-float-input" data-validate="indian-phone" placeholder="Phone" required><label class="form-float-label">Phone<span class="text-danger">*</span></label></div></div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <select name="industry" class="form-float-input" data-select="dropdown">
                                                <option value="" disabled selected>Select Industry</option>
                                                ' . Helper::dropdown('industry', 'html') . '
                                            </select>
                                            <label class="form-float-label">Industry</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <select name="business_type" class="form-float-input" data-select="dropdown">
                                                <option value="" disabled selected>Select Business Type</option>
                                                ' . Helper::dropdown('business_type', 'html') . '
                                            </select>
                                            <label class="form-float-label">Business Type</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="registration_no" class="form-float-input" placeholder="Registration No" data-validate="roc">
                                            <label class="form-float-label">Registration No</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="tax_id" class="form-float-input" placeholder="Tax ID" data-validate="gstin">
                                            <label class="form-float-label">Tax ID</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <select name="business_size" class="form-float-input" data-select="dropdown">
                                                <option value="" disabled selected>Select Business Size</option>
                                                ' . Helper::dropdown('business_size', 'html') . '
                                            </select>
                                            <label class="form-float-label">Business Size</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="number" name="no_of_employees" class="form-float-input" placeholder="No. of Employees">
                                            <label class="form-float-label">No. of Employees</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <select name="plan_id" class="form-float-input" data-select="dropdown">
                                                <option value="" disabled selected>Select Plan</option>
                                                ' . Select::options('business_plans', 'html', ['plan_id' => 'name']) . '
                                            </select>
                                            <label class="form-float-label">Plan</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="website" data-validate="url" class="form-float-input" placeholder="Website">
                                            <label class="form-float-label">Website</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Admin Address -->
                            <div data-step data-title="Admin Address" data-icon="fa-location-dot">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="country" class="form-float-input" data-validate="country" placeholder="Country">
                                            <label class="form-float-label"> Country </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="state" class="form-float-input" data-validate="state" placeholder="State">
                                            <label class="form-float-label"> State </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="text" name="city" class="form-float-input" data-validate="city" placeholder="City">
                                            <label class="form-float-label"> City </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <input type="number" name="pincode" class="form-float-input" data-validate="pincode" placeholder="Pin Code">
                                            <label class="form-float-label">Pin Code</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <textarea name="address_line1" class="form-float-input" data-validate="address" placeholder="Address Line 1"></textarea>
                                            <label class="form-float-label">Address Line 1</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="float-input-control">
                                            <textarea name="address_line2" class="form-float-input" data-validate="address" placeholder="Address Line 2"></textarea>
                                            <label class="form-float-label">Address Line 2</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Device Info -->
                            <div data-step data-title="Device Info" data-icon="fa-tablet">
                               <div data-repeater-container data-input="device_info" data-type="array">
                                    <div data-repeater class="d-flex flex-row gap-3 w-100 align-items-end mt-3">
                                        <div class="float-input-control flex-grow-1">
                                            <input type="text" name="sno" class="form-float-input" required placeholder="Serial Number">
                                            <label class="form-float-label"> SNO </label>
                                        </div>
                                        <div class="float-input-control flex-grow-1">
                                            <input type="text" name="device_name" class="form-float-input" required placeholder="Device Name">
                                            <label class="form-float-label"> Device Name </label>
                                        </div>
                                        <div class="float-input-control flex-grow-1">
                                            <input type="text" name="location" class="form-float-input" required placeholder="Location">
                                            <label class="form-float-label"> Location </label>
                                        </div>
                                        <button data-repeater-add type="button">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';

                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [['type' => 'raw', 'html' => $onboardForm]],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => 'Onboard Admin',
                        'short_label' => 'Onboard a new admin to Got-It HR Solutions. Fill in the form to get started with administrative access.',
                        'button' => 'Join Now',
                        'fullscreen_btn' => 'd-none',
                        'reload_btn' => 'd-none',
                        'footer' => 'hide',
                        'script' => 'window.general.stepper();window.general.select();window.general.repeater();'
                    ];
                    break;

                case 'central_skeleton_permissions':
                    $permissions = Skeleton::loadPermissions('all', 'user', 'user-id', $reqSet['id']);
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '
                <input type="hidden" name="save_token" value="' . $reqSet['token'] . '_a_' . $reqSet['id'] . '">
                <div class="row justify-content-start mt-1 g-3">
                    <div class="col-md-3">
                        <div class="float-input-control">
                            <select class="form-float-input" placeholder="business_id" name="business_id" data-select="dropdown">
                                ' . Select::options('businesses', 'html', ['business_id' => 'name']) . '
                            </select>
                            <label class="form-float-label">
                                Business <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div data-permissions-container>
                    <div id="accordion-permissions" class="accordion"></div>
                    <input type="hidden" id="permission_ids" name="permission_ids" value="[]">
                    <div id="errorMessage" class="alert alert-danger d-none"></div>
                </div>',
                        'type' => 'modal',
                        'size' => 'modal-xl',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Edit User Permissions',
                        'short_label' => '',
                        'button' => 'Update Permissions',
                        'script' => '
                window.skeleton.select();
                window.skeleton.permissions(' . json_encode($permissions, JSON_UNESCAPED_SLASHES) . ');
            ',
                    ];
                    break;
            case 'central_add_to_business':
                $onboardingId = explode('_', $token);
                $onboardingId = end($onboardingId);

                $onboard = CentralDB::table('business_onboarding')
                    ->where('onboarding_id', $onboardingId)
                    ->first();

                $business = '
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                        <div>
                            <strong>Warning:</strong> Are you sure you want to add <strong>' . e($onboard->name) . '</strong> to the Business?
                        </div>
                    </div>
                ';

                $popup = [
                    'form' => 'builder',
                    'labelType' => 'floating',
                    'fields' => [
                        ['type' => 'raw', 'html' => $business],
                        ['type' => 'hidden', 'name' => 'onboarding_id', 'label' => 'Onboarding Id', 'required' => true, 'value' => $onboardingId, 'col' => '4'],
                    ],
                    'type' => 'modal',
                    'size' => 'modal-md',
                    'position' => 'end',
                    'label' => '<i class="fa-regular fa-briefcase-blank me-1"></i>Add To Business',
                    'short_label' => 'Add Admin to Business',
                    'button' => 'Yes',
                    'script' => 'window.general.select();'
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
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
