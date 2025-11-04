<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\CentralDB;
use App\Facades\Data;
use App\Facades\Random;
use App\Facades\Select;
use App\Facades\Skeleton;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Helper;
use App\Http\Helpers\PopupHelper;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Controller for rendering the add form for BusinessManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new BusinessManagement entities.
     *
     * @param  Request  $request  HTTP request object
     * @param  array  $params  Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (! $token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['key'])) {
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
                case 'central_business_devices':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'business_id', 'label' => 'Business', 'required' => true, 'options' => Helper::business('array'), 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'serial_number', 'label' => 'Serial Number', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Device Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'ip', 'label' => 'IP Address', 'required' => true, 'col' => '6', 'attr' => ['pattern' => '^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$']],
                            ['type' => 'number', 'name' => 'port', 'label' => 'Port', 'required' => true, 'col' => '6', 'attr' => ['min' => '1', 'max' => '65535']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ], 
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-network-wired me-1"></i>Add Device',
                        'short_label' => 'Register a business device',
                        'button' => 'Add Device',
                        'script' => 'window.general.select();',
                    ];
                    break;
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
                        'script' => 'window.general.select();',
                    ];
                    break;
                case 'central_business_plans':
                    $modulePriceArrData = Data::get('central', 'business_module_pricing', ['where' => ['is_approved' => '1']]);
                    $modulePriceArr = $modulePriceArrData['data'];
                    $source = '';
                    $target = '';
                    foreach ($modulePriceArr as $set) {
                        $source .= '<div data-drag-item data-value="'.$set['module_price_id'].'" data-sum="'.$set['price'].'" class="d-flex flex-row justify-content-start align-items-center gap-2 bg-light p-1 px-1 border rounded-2 mb-1">
                    <div><span class="avatar avatar-sm avatar-rounded p-1 rounded-circle" style="background: '.Helper::colors('gradient-dark-2', 'background').'">'.Helper::textProfile($set['module_name'], 2).'</span></div>
                    <div class="d-flex flex-column justify-content-start w-100">
                    <div class="sf-14 fw-bold text-nowrap pe-3">'.$set['module_name'].'</div>
                    <div class="d-flex flex-row justify-content-between"><span class="sf-9 text-muted">'.$set['module_id'].'</span><span class="sf-9 text-danger">₹ '.$set['price'].'</span></div></div></div>';
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
                        setTimeout(() => {recalculateAll();addRecalculationEvents();observeDroppedModuleSum();}, 500);',
                    ];
                    break;
                case 'central_onboard_business':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            [
                                'type' => 'stepper',
                                'stepper' => 'linear',
                                'progress' => 'bar+icon',
                                'submit_text' => 'Submit Onboarding',
                                'btn_class' => '',
                                'steps' => [
                                    [
                                        'title' => 'Business Details',
                                        'icon' => 'fa-building',
                                        'fields' => [
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Business Name', 'required' => true, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'legal_name', 'label' => 'Legal Name', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'industry', 'label' => 'Industry', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'business_type', 'label' => 'Business Type', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'registration_no', 'label' => 'Registration Number', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'tax_id', 'label' => 'Tax ID', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'url', 'name' => 'website', 'label' => 'Website', 'required' => false, 'col' => 12, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'select', 'name' => 'business_size', 'label' => 'Business Size', 'options' => ['micro' => 'Micro', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'number', 'name' => 'no_of_employees', 'label' => 'Number of Employees', 'required' => false, 'col' => 6, 'attr' => ['min' => 0]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Contact Information',
                                        'icon' => 'fa-address-book',
                                        'fields' => [
                                            ['type' => 'email', 'name' => 'email', 'label' => 'Business Email', 'required' => true, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'tel', 'name' => 'phone', 'label' => 'Business Phone', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 15]],
                                            ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'state', 'label' => 'State', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'pincode', 'label' => 'Pincode', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 10]],
                                            ['type' => 'email', 'name' => 'hr_contact_email', 'label' => 'HR Contact Email', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'tel', 'name' => 'hr_contact_phone', 'label' => 'HR Contact Phone', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 15]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Admin Details',
                                        'icon' => 'fa-user',
                                        'fields' => [
                                            ['type' => 'text', 'name' => 'admin_first_name', 'label' => 'Admin First Name', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'admin_last_name', 'label' => 'Admin Last Name', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'email', 'name' => 'admin_email', 'label' => 'Admin Email', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 150]],
                                            ['type' => 'tel', 'name' => 'admin_phone', 'label' => 'Admin Phone', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 20]],
                                            ['type' => 'password', 'name' => 'admin_password', 'label' => 'Admin Password', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 255]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Billing & Status',
                                        'icon' => 'fa-credit-card',
                                        'fields' => [
                                            ['type' => 'select', 'name' => 'plan_id', 'label' => 'Plan ID', 'options' => Select::options('business_plans', 'array', ['plan_id' => 'name'], ['where' => ['is_approved' => '1']]), 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'select', 'name' => 'billing_status', 'label' => 'Billing Status', 'options' => ['onhold' => 'On Hold', 'active' => 'Active', 'cancelled' => 'Cancelled', 'expired' => 'Expired'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'select', 'name' => 'payment_method', 'label' => 'Payment Method', 'options' => ['card' => 'Card', 'upi' => 'UPI', 'netbanking' => 'Netbanking', 'invoice' => 'Invoice', 'wallet' => 'Wallet'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'select', 'name' => 'payment_status', 'label' => 'Payment Status', 'options' => ['initiated' => 'Initiated', 'success' => 'Success', 'failed' => 'Failed'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'datetime', 'name' => 'paid_on', 'label' => 'Paid On', 'required' => false, 'col' => 6],
                                            ['type' => 'select', 'name' => 'onboarding_stage', 'label' => 'Onboarding Stage', 'options' => ['plan-selection' => 'Plan Selection', 'account-creation' => 'Account Creation', 'business-info' => 'Business Info', 'device-info' => 'Device Info', 'device-passed' => 'Device Passed', 'billing-info' => 'Billing Info', 'payment-confirmation' => 'Payment Confirmation', 'payment-initiated' => 'Payment Initiated', 'payment-success' => 'Payment Success', 'payment-failed' => 'Payment Failed', 'active' => 'active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'], 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'active' => 'Active', 'inactive' => 'Inactive'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'number', 'name' => 'device_count', 'label' => 'Device Count', 'required' => false, 'col' => 6, 'attr' => ['min' => 0]],
                                            ['type' => 'text', 'name' => 'device_code', 'label' => 'Device Code', 'value' => Random::uniqueId('BIZ', 6), 'required' => true, 'col' => 6, 'attr' => ['maxlength' => 20, 'readonly' => 'readonly']],
                                            ['type' => 'select', 'name' => 'device_check', 'label' => 'Device Check', 'options' => ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'partial' => 'Partial'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                        ],
                                    ],
                                ],
                                'col' => 12,
                            ],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-briefcase me-1"></i>Add Business Onboarding',
                        'short_label' => 'Onboard a new business with detailed information',
                        'button' => 'Start Onboarding',
                        'footer' => 'hide',
                        'script' => 'window.general.select();window.general.stepper();',
                    ];
                    break;
                case 'central_skeleton_permissions':
                    $permissions = Skeleton::loadPermissions('all', 'user', 'user-id', $reqSet['id']);
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => '
                <input type="hidden" name="save_token" value="'.$reqSet['token'].'_a_'.$reqSet['id'].'">
                <div class="row justify-content-start mt-1 g-3">
                    <div class="col-md-3">
                        <div class="float-input-control">
                            <select class="form-float-input" placeholder="business_id" name="business_id" data-select="dropdown">
                                '.Select::options('businesses', 'html', ['business_id' => 'name']).'
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
                window.skeleton.permissions('.json_encode($permissions, JSON_UNESCAPED_SLASHES).');
            ', ];
                    break;
                case 'central_convert_to_business':
                    $onboarding = CentralDB::table('business_onboarding')->where('onboarding_id', $reqSet['id'])->first();
                    $rows = [];
                    if (! empty($onboarding)) {
                        foreach (array_keys((array) $onboarding) as $field) {
                            $label = str_replace('_', ' ', ucwords($field));
                            $value = isset($onboarding->$field) ? htmlspecialchars($onboarding->$field) : '-';
                            $rows[] = "<tr><td>$label</td><td>$value</td></tr>";
                        }
                    } else {
                        $rows[] = '<tr><td colspan="2">No onboarding data available</td></tr>';
                    }
                    $tableHtml = '
            <div class="accordion border border-1 border-info rounded" id="onboardingAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="onboardingHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#onboardingCollapse" aria-expanded="false" aria-controls="onboardingCollapse">
                            <h4>'.$onboarding->name.' Details</h4>
                        </button>
                    </h2>
                    <div id="onboardingCollapse" class="accordion-collapse collapse" aria-labelledby="onboardingHeading" data-bs-parent="#onboardingAccordion">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead><tr><th>Field</th><th>Value</th></tr></thead>
                                    <tbody>'.implode('', $rows).'</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => $tableHtml],
                            ['type' => 'hidden', 'name' => 'onboarding_id', 'label' => 'Plan Name', 'value' => $onboarding->onboarding_id, 'required' => true, 'col' => 12, 'attr' => ['maxlength' => 100]],
                            ['type' => 'select', 'name' => 'plan_id', 'label' => 'Plan', 'options' => Select::options('business_plans', 'array', ['plan_id' => 'name'], ['where' => ['is_approved' => '1']]), 'required' => true, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => true, 'col' => 6],
                            ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => true, 'col' => 6],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'cancelled' => 'cancelled', 'expired' => 'Expired'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'auto_renew', 'label' => 'Auto Renew', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => 'Onboard Admin',
                        'short_label' => 'Onboard a new admin to Got-It HR Solutions. Fill in the form to get started with administrative access.',
                        'button' => 'Onboard',
                        'script' => 'window.general.select();',
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
