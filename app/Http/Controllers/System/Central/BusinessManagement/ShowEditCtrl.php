<?php

namespace App\Http\Controllers\System\Central\BusinessManagement;

use App\Facades\Data;
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
 * Controller for rendering the edit form for BusinessManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing BusinessManagement entities.
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
            if (! isset($reqSet['key']) || ! isset($reqSet['act']) || ! isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = Data::fetch($reqSet['system'], $reqSet['table'], [$reqSet['act'] => $reqSet['id']]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            if (! $data) {
                return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
            }
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
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
                            ['type' => 'select', 'name' => 'business_id', 'label' => 'Business', 'required' => true, 'options' => Select::options('businesses', 'array', ['business_id' => 'name'], ['where' => ['is_active' => 1]]), 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->business_id]],
                            ['type' => 'text', 'name' => 'serial_number', 'label' => 'Serial Number', 'required' => true, 'value' => $data->serial_number, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Device Name', 'required' => true, 'value' => $data->name, 'col' => '6', 'attr' => ['maxlength' => '150']],
                            ['type' => 'text', 'name' => 'ip', 'label' => 'IP Address', 'required' => true, 'value' => $data->ip, 'col' => '6', 'attr' => ['pattern' => '^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$']],
                            ['type' => 'number', 'name' => 'port', 'label' => 'Port', 'required' => true, 'value' => $data->port, 'col' => '6', 'attr' => ['min' => '1', 'max' => '65535']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_approved]],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-network-wired me-1"></i> Edit Device',
                        'short_label' => '',
                        'button' => 'Update Device',
                        'script' => 'window.general.select();',
                    ];
                    break;
                case 'central_business_module_pricings':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'module_id', 'label' => 'Module', 'required' => true, 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name'], ['where' => ['system' => ['in' => ['business', 'open']], 'is_approved' => '1']]), 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'readonly' => 'readonly', 'data-value' => $data->module_id]],
                            ['type' => 'select', 'name' => 'dependent_module_ids', 'label' => 'Depends on Modules', 'options' => Select::options('skeleton_modules', 'array', ['module_id' => 'name'], ['where' => ['system' => ['in' => ['business', 'open']], 'is_approved' => '1']]), 'col' => '12', 'class' => ['h-auto'], 'attr' => ['data-select' => 'dropdown', 'multiple' => 'multiple', 'data-value' => explode(',', $data->dependent_module_ids) ?? []]],
                            ['type' => 'number', 'name' => 'price', 'label' => 'Price (₹)', 'required' => true, 'value' => $data->price, 'col' => '6', 'attr' => ['step' => '0.01']],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Approval', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => true, 'value' => $data->description, 'col' => '12', 'attr' => ['maxlength' => '500']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-box me-1"></i>Update Module Pricing',
                        'short_label' => 'Update pricing to this modules',
                        'button' => 'Update Module Price',
                        'script' => 'window.general.select();',
                    ];
                    break;
                case 'central_business_plans':
                    $modulePriceArrData = Data::get('central', 'business_module_pricing', ['where' => ['is_approved' => '1']]);
                    $modulePriceArr = $modulePriceArrData['data'];
                    $modulePriceArrSelected = explode(',', $data->module_pricing_ids);
                    $source = '';
                    $target = '';
                    foreach ($modulePriceArr as $set) {
                        if (in_array($set['module_price_id'], $modulePriceArrSelected)) {
                            $target .= '<div data-drag-item data-value="'.$set['module_price_id'].'" data-sum="'.$set['price'].'" class="d-flex flex-row justify-content-start align-items-center gap-2 bg-light p-1 px-1 border rounded-2 mb-1">
                            <div><span class="avatar avatar-sm avatar-rounded p-1 rounded-circle" style="background: '.Helper::colors('gradient-dark-2', 'background').'">'.Helper::textProfile($set['module_name'], 2).'</span></div>
                            <div class="d-flex flex-column justify-content-start w-100">
                            <div class="sf-14 fw-bold text-nowrap pe-3">'.$set['module_name'].'</div>
                            <div class="d-flex flex-row justify-content-between"><span class="sf-9 text-muted">'.$set['module_id'].'</span><span class="sf-9 text-danger">₹ '.$set['price'].'</span></div></div></div>';
                        } else {
                            $source .= '<div data-drag-item data-value="'.$set['module_price_id'].'" data-sum="'.$set['price'].'" class="d-flex flex-row justify-content-start align-items-center gap-2 bg-light p-1 px-1 border rounded-2 mb-1">
                            <div><span class="avatar avatar-sm avatar-rounded p-1 rounded-circle" style="background: '.Helper::colors('gradient-dark-2', 'background').'">'.Helper::textProfile($set['module_name'], 2).'</span></div>
                            <div class="d-flex flex-column justify-content-start w-100">
                            <div class="sf-14 fw-bold text-nowrap pe-3">'.$set['module_name'].'</div>
                            <div class="d-flex flex-row justify-content-between"><span class="sf-9 text-muted">'.$set['module_id'].'</span><span class="sf-9 text-danger">₹ '.$set['price'].'</span></div></div></div>';
                        }
                    }
                    $decoded = json_decode($data->duration, true);
                    $duration = [];
                    if (is_array($decoded)) {
                        $key = array_key_first($decoded);
                        $value = $decoded[$key];
                        $duration = [
                            'key' => $key,
                            'value' => $value,
                        ];
                    }
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'name', 'label' => 'Plan Name', 'required' => true, 'value' => $data->name, 'col' => '4', 'attr' => ['maxlength' => '150']],
                            ['type' => 'select', 'name' => 'duration_type', 'label' => 'Duration Type', 'options' => ['month' => 'Month', 'year' => 'Year'], 'required' => true, 'col' => '2', 'attr' => ['data-select' => 'dropdown', 'data-value' => $duration['key'] ?? '']],
                            ['type' => 'number', 'name' => 'duration_value', 'label' => 'Duration Value', 'value' => $duration['value'] ?? '', 'required' => true, 'col' => '3'],
                            ['type' => 'text', 'name' => 'icon', 'label' => 'Plan Icon', 'required' => false, 'value' => $data->icon, 'col' => '3'],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Plan Type', 'options' => ['fixed' => 'Fixed', 'custom' => 'Custom'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->type]],
                            ['type' => 'number', 'name' => 'display_order', 'label' => 'Display Order', 'required' => false, 'value' => $data->display_order, 'col' => '3'],
                            ['type' => 'select', 'name' => 'landing_visibility', 'label' => 'Visible on Landing?', 'options' => ['1' => 'Yes', '0' => 'No'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->landing_visibility]],
                            ['type' => 'select', 'name' => 'is_approved', 'label' => 'Is Approved?', 'options' => ['1' => 'Approved', '0' => 'Rejected'], 'required' => true, 'col' => '3', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_approved]],
                            ['type' => 'dragger', 'source' => ['html' => $source, 'input_string' => '.area_1_values', 'input_sum' => '.area_1_sum', 'separator' => ',', 'class' => ['drag-area']], 'target' => ['html' => $target, 'input_string' => '.dropped-module-ids', 'input_sum' => '.dropped-module-sum', 'separator' => ',', 'class' => ['drag-area']], 'col' => '12'],
                            ['type' => 'number', 'name' => 'discount', 'label' => 'Discount (%)', 'required' => false, 'value' => $data->discount, 'col' => '2', 'attr' => ['step' => '0.1', 'max' => '99']],
                            ['type' => 'repeater', 'name' => 'tax', 'set' => 'pair', 'value' => $data->tax, 'fields' => [['type' => 'select', 'name' => 'label', 'label' => 'Tax Type', 'options' => ['' => '-- Select --', 'gst' => 'GST', 'cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'], 'required' => true], ['type' => 'number', 'name' => 'value', 'label' => 'Value', 'placeholder' => 'Tax', 'required' => true]], 'col' => '5'],
                            ['type' => 'number', 'name' => 'amount', 'label' => 'Amount (₹)', 'class' => ['dropped-module-sum'], 'required' => true, 'value' => $data->amount, 'col' => '2', 'attr' => ['step' => '0.01', 'id' => 'amount', 'readonly' => 'readonly']],
                            ['type' => 'number', 'name' => 'total_amount', 'label' => 'Total Amount (₹)', 'required' => true, 'data-value' => $data->total_amount, 'col' => '3', 'attr' => ['step' => '0.01', 'id' => 'amount', 'readonly' => 'readonly']],
                            ['type' => 'raw', 'html' => '<div class="mt-2"><label>Description</label></div><div data-editor-id="plan-description" data-editor-name="description" data-tools="minimum"></div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="mt-2"><label>Features</label></div><div data-editor-id="plan-features" data-editor-name="features" data-tools="minimum"></div>', 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-layer-group me-1"></i>Edit Plan',
                        'short_label' => 'Update the details of the selected plan',
                        'button' => 'Edit Plan',
                        'script' => 'window.general.select();window.skeleton.drag();window.general.repeater();window.skeleton.editor("plan-description", "", "'.addslashes($data->description).'");window.skeleton.editor("plan-features", "", "'.addslashes($data->features).'");
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
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Business Name', 'required' => true, 'col' => 6, 'value' => $data->name, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'legal_name', 'label' => 'Legal Name', 'required' => false, 'col' => 6, 'value' => $data->legal_name, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'industry', 'label' => 'Industry', 'required' => false, 'col' => 6, 'value' => $data->industry, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'business_type', 'label' => 'Business Type', 'required' => false, 'col' => 6, 'value' => $data->business_type, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'registration_no', 'label' => 'Registration Number', 'required' => false, 'col' => 6, 'value' => $data->registration_no, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'text', 'name' => 'tax_id', 'label' => 'Tax ID', 'required' => false, 'col' => 6, 'value' => $data->tax_id, 'attr' => ['maxlength' => 50]],
                                            ['type' => 'url', 'name' => 'website', 'label' => 'Website', 'required' => false, 'col' => 12, 'value' => $data->website, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'select', 'name' => 'business_size', 'label' => 'Business Size', 'options' => ['micro' => 'Micro', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->business_size]],
                                            ['type' => 'number', 'name' => 'no_of_employees', 'label' => 'Number of Employees', 'required' => false, 'col' => 6, 'value' => $data->no_of_employees, 'attr' => ['min' => 0]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Contact Information',
                                        'icon' => 'fa-address-book',
                                        'fields' => [
                                            ['type' => 'email', 'name' => 'email', 'label' => 'Business Email', 'required' => false, 'col' => 6, 'value' => $data->email, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'tel', 'name' => 'phone', 'label' => 'Business Phone', 'required' => false, 'col' => 6, 'value' => $data->phone, 'attr' => ['maxlength' => 15]],
                                            ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'required' => false, 'col' => 6, 'value' => $data->address_line1, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'required' => false, 'col' => 6, 'value' => $data->address_line2, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'col' => 6, 'value' => $data->city, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'state', 'label' => 'State', 'required' => false, 'col' => 6, 'value' => $data->state, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'required' => false, 'col' => 6, 'value' => $data->country, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'pincode', 'label' => 'Pincode', 'required' => false, 'col' => 6, 'value' => $data->pincode, 'attr' => ['maxlength' => 10]],
                                            ['type' => 'email', 'name' => 'hr_contact_email', 'label' => 'HR Contact Email', 'required' => false, 'col' => 6, 'value' => $data->hr_contact_email, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'tel', 'name' => 'hr_contact_phone', 'label' => 'HR Contact Phone', 'required' => false, 'col' => 6, 'value' => $data->hr_contact_phone, 'attr' => ['maxlength' => 15]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Admin Details',
                                        'icon' => 'fa-user',
                                        'fields' => [
                                            ['type' => 'text', 'name' => 'admin_first_name', 'label' => 'Admin First Name', 'required' => false, 'col' => 6, 'value' => $data->admin_first_name, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'text', 'name' => 'admin_last_name', 'label' => 'Admin Last Name', 'required' => false, 'col' => 6, 'value' => $data->admin_last_name, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'email', 'name' => 'admin_email', 'label' => 'Admin Email', 'required' => false, 'col' => 6, 'value' => $data->admin_email, 'attr' => ['maxlength' => 150]],
                                            ['type' => 'tel', 'name' => 'admin_phone', 'label' => 'Admin Phone', 'required' => false, 'col' => 6, 'value' => $data->admin_phone, 'attr' => ['maxlength' => 20]],
                                            ['type' => 'password', 'name' => 'admin_password', 'label' => 'Admin Password', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 255]], // Password typically not pre-filled
                                        ],
                                    ],
                                    [
                                        'title' => 'Billing & Status',
                                        'icon' => 'fa-credit-card',
                                        'fields' => [
                                            ['type' => 'select', 'name' => 'plan_id', 'label' => 'Plan ID', 'options' => Select::options('business_plans', 'array', ['plan_id' => 'name'], ['where' => ['is_approved' => '1']]), 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->plan_id]],
                                            ['type' => 'select', 'name' => 'billing_status', 'label' => 'Billing Status', 'options' => ['onhold' => 'On Hold', 'active' => 'Active', 'cancelled' => 'Cancelled', 'expired' => 'Expired'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->billing_status]],
                                            ['type' => 'select', 'name' => 'payment_method', 'label' => 'Payment Method', 'options' => ['card' => 'Card', 'upi' => 'UPI', 'netbanking' => 'Netbanking', 'invoice' => 'Invoice', 'wallet' => 'Wallet'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->payment_method]],
                                            ['type' => 'select', 'name' => 'payment_status', 'label' => 'Payment Status', 'options' => ['initiated' => 'Initiated', 'success' => 'Success', 'failed' => 'Failed'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->payment_status]],
                                            ['type' => 'datetime', 'name' => 'paid_on', 'label' => 'Paid On', 'required' => false, 'col' => 6, 'value' => $data->paid_on],
                                            ['type' => 'select', 'name' => 'onboarding_stage', 'label' => 'Onboarding Stage', 'options' => ['plan-selection' => 'Plan Selection', 'account-creation' => 'Account Creation', 'business-info' => 'Business Info', 'device-info' => 'Device Info', 'device-passed' => 'Device Passed', 'billing-info' => 'Billing Info', 'payment-confirmation' => 'Payment Confirmation', 'payment-initiated' => 'Payment Initiated', 'payment-success' => 'Payment Success', 'payment-failed' => 'Payment Failed', 'active' => 'active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'], 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->onboarding_stage]],
                                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'active' => 'Active', 'inactive' => 'Inactive'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->status]],
                                            ['type' => 'number', 'name' => 'device_count', 'label' => 'Device Count', 'required' => false, 'col' => 6, 'value' => $data->device_count, 'attr' => ['min' => 0]],
                                            ['type' => 'text', 'name' => 'device_code', 'label' => 'Device Code', 'required' => false, 'col' => 6, 'value' => $data->device_code, 'attr' => ['maxlength' => 20, 'readonly' => 'readonly']],
                                            ['type' => 'select', 'name' => 'device_check', 'label' => 'Device Check', 'options' => ['pending' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed', 'partial' => 'Partial'], 'required' => false, 'col' => 6, 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->device_check]],
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

    /**
     * Renders a popup to confirm bulk update of records.
     *
     * @param  Request  $request  HTTP request object containing input data.
     * @param  array  $params  Route parameters including token.
     * @return JsonResponse Custom UI configuration for the popup or an error message.
     */
    public function bulk(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['system']) || ! isset($reqSet['table']) || ! isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or missing required data.', 400);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id', '')));
            if (empty($ids)) {
                return ResponseHelper::moduleError('Invalid Data', 'No records specified for update.', 400);
            }
            // Fetch records details
            $result = Data::get($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]], 'all');
            if (! $result['status'] || empty($result['data'])) {
                return ResponseHelper::moduleError('Records Not Found', $result['message'] ?: 'The requested records were not found.', 404);
            }
            $records = $result['data'];
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            $recordCount = count($records);
            $maxDisplayRecords = 5;
            // Generate accordion for records
            $detailsHtml = sprintf('<div class="alert alert-warning" role="alert"><div class="accordion" id="updateAccordion-%s"><div class="accordion-item border-0"><h2 class="accordion-header p-0 my-0"><button class="accordion-button collapsed p-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-%s" aria-expanded="false" aria-controls="collapse-%s">Confirm Bulk Update of %d Record(s)</button></h2><div id="collapse-%s" class="accordion-collapse collapse" data-bs-parent="#updateAccordion-%s"><div class="accordion-body p-2 bg-light"><div class="accordion" id="updateRecords-%s">', $token, $token, $token, $recordCount, $token, $token, $token);
            if ($recordCount > $maxDisplayRecords) {
                $detailsHtml .= sprintf('<div class="d-flex justify-content-between align-items-center"><div class="text-muted">Updating <b>%d</b> records.</div><button class="btn btn-link btn-sm text-decoration-none text-primary sf-12" type="button" data-bs-toggle="collapse" data-bs-target="#details-%s" aria-expanded="false" aria-controls="details-%s">Details</button></div><div class="collapse mt-2" id="details-%s"><div class="table-responsive" style="max-height: 200px;">', $recordCount, $token, $token, $token);
            }
            $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            $displayRecords = $recordCount > $maxDisplayRecords ? array_slice($records, 0, 5) : $records;
            foreach ($displayRecords as $index => $record) {
                $recordArray = (array) $record;
                $recordId = htmlspecialchars($recordArray[$reqSet['act']] ?? 'N/A');
                $detailsHtml .= sprintf('<tr><td colspan="2"><b>Record %d (ID: %s)</b></td></tr>', $index + 1, $recordId);
                if (empty($recordArray)) {
                    $detailsHtml .= '<tr><td colspan="2" class="text-muted">No displayable details available</td></tr>';
                } else {
                    foreach ($recordArray as $key => $value) {
                        $detailsHtml .= sprintf('<tr><td>%s</td><td><b>%s</b></td></tr>', htmlspecialchars(ucwords(str_replace('_', ' ', $key))), htmlspecialchars($value ?? ''));
                    }
                }
            }
            $detailsHtml .= $recordCount > $maxDisplayRecords ? sprintf('<tr><td colspan="2" class="text-muted">... and %d more records</td></tr></table></div></div>', $recordCount - count($displayRecords)) : '</table>';
            $detailsHtml .= sprintf('</div><div class="mt-2"><i class="sf-10"><span class="text-danger">Note: </span>Only non-unique fields can be updated in bulk. Changes will apply to all %d selected records. Ensure values are valid to avoid data conflicts.</i></div></div></div></div></div></div>', $recordCount);
            // Initialize popup configuration
            $popup = [];
            $detailsHtmlPlacement = 'top';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'central_businesses':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            // Logo Upload
                            ['type' => 'raw', 'html' => '<div class="file-upload-container mt-3" data-file="image" data-file-crop="profile" data-label="Logo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="'.asset('default/preview-square.svg').'"></div>', 'col' => '12'],
                            // Business Identification
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '4', 'value' => $data->name ?? ''],
                            ['type' => 'text', 'name' => 'legal_name', 'label' => 'Legal Name', 'required' => true, 'col' => '4', 'value' => $data->legal_name ?? ''],
                            ['type' => 'text', 'name' => 'registration_no', 'label' => 'Registration No', 'required' => true, 'col' => '4', 'value' => $data->registration_no ?? ''],
                            ['type' => 'text', 'name' => 'tax_id', 'label' => 'Tax ID', 'required' => true, 'col' => '4', 'value' => $data->tax_id ?? ''],
                            ['type' => 'text', 'name' => 'license_key', 'label' => 'License Key', 'required' => true, 'col' => '4', 'value' => $data->license_key ?? ''],
                            ['type' => 'text', 'name' => 'industry', 'label' => 'Industry', 'required' => true, 'col' => '4', 'value' => $data->industry ?? ''],
                            ['type' => 'text', 'name' => 'type', 'label' => 'Type', 'required' => false, 'col' => '4', 'value' => $data->type ?? ''],
                            ['type' => 'date', 'name' => 'founded_date', 'label' => 'Founded Date', 'required' => true, 'col' => '4', 'value' => $data->founded_date ?? ''],
                            ['type' => 'select', 'name' => 'business_size', 'label' => 'Business Size', 'options' => ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], 'required' => false, 'col' => '4', 'attr' => ['data-select' => 'dropdown'], 'value' => $data->business_size ?? ''],
                            // Logo Text (Optional fallback)
                            ['type' => 'text', 'name' => 'logo', 'label' => 'Logo URL', 'required' => false, 'col' => '4', 'value' => $data->logo ?? ''],
                            // Contact Information
                            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'col' => '4', 'value' => $data->email ?? ''],
                            ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'required' => true, 'col' => '4', 'value' => $data->phone ?? ''],
                            ['type' => 'email', 'name' => 'hr_contact_email', 'label' => 'HR Email', 'required' => true, 'col' => '4', 'value' => $data->hr_contact_email ?? ''],
                            ['type' => 'text', 'name' => 'hr_contact_phone', 'label' => 'HR Phone', 'required' => true, 'col' => '4', 'value' => $data->hr_contact_phone ?? ''],
                            ['type' => 'text', 'name' => 'website', 'label' => 'Website', 'required' => false, 'col' => '4', 'value' => $data->website ?? ''],
                            // Address
                            ['type' => 'textarea', 'name' => 'address', 'label' => 'Address', 'required' => true, 'col' => '12', 'value' => $data->address ?? ''],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i>'.($data->business_id ?? false ? 'Update Business' : 'Add Business'),
                        'short_label' => ($data->business_id ?? false ? 'Update existing business' : 'Create new business here'),
                        'button' => ($data->business_id ?? false ? 'Update Business' : 'Add Business'),
                        'script' => 'window.general.select();window.general.unique();window.general.files();',
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
            $content = '<input type="hidden" name="update_ids" value="'.$request->input('id', '').'">';
            $content .= $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            $content = $detailsHtmlPlacement === 'top' ? $detailsHtml.$content : $content.$detailsHtml;

            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
