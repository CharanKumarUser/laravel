<?php
namespace App\Http\Controllers\Lander\Actions;
use App\Facades\{Select, Developer, Skeleton, Helper};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for developer entities.
 */
class ShowAddController extends Controller
{
    /**
     * Renders a popup form for adding new developer entities.
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
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                // Handle token configuration form
                case 'lander_landing_requests':
                    // Define form fields for adding a new token
                    switch ($reqSet['id']) {
                        case 'quotation':
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'text', 'name' => 'name', 'label' => 'Your Name', 'required' => true, 'col' => '6'],
                                    ['type' => 'text', 'name' => 'phone', 'label' => 'Phone Number', 'required' => true, 'col' => '6', 'attr' => ['minlength' => '10', 'maxlength' => '10', 'pattern' => '[6-9][0-9]{9}'], 'data-validate' => 'indian-phone'],
                                    ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'email']],
                                    ['type' => 'text', 'name' => 'company', 'label' => 'Company Name', 'required' => true, 'col' => '6'],
                                    ['type' => 'number', 'name' => 'employee_count', 'label' => 'Number of Employees', 'required' => true, 'col' => '6'],
                                    [
                                        'type' => 'select',
                                        'name' => 'plan',
                                        'label' => 'Select Plan',
                                        'required' => true,
                                        'col' => '6',
                                        'options' => [
                                            'PPLOX2ZNXQJOE' => '🏷️ Seed | 💰 ₹5999 | ⏳ 1 Year',
                                            'PPLOXHPRGQXHF' => '🏷️ Plant | 💰 ₹14999 | ⏳ 1 Year',
                                            'PPLRQZRXIS4IG' => '🏷️ Tree | 💰 ₹24999 | ⏳ 1 Year',
                                            'PPLRQZRXICUSTOM' => '🏷️ Custom | 💰 ₹162558 | ⏳ 1 Year',
                                        ],
                                        'attr' => ['data-select' => 'dropdown']
                                    ],
                                    ['type' => 'textarea', 'name' => 'message', 'label' => 'Additional Requirements or Comments', 'required' => true, 'col' => '12', 'attr' => ['rows' => 4]],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => 'Request a Quote',
                                'short_label' => 'Fill out the form below to receive a customized quotation for Got-It HR Solutions.',
                                'button' => 'Submit',
                                'fullscreen_btn' => 'd-none',
                                'script' => 'window.general.select();'
                            ];
                            break;
                        case 'demo-request':
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'text', 'name' => 'name', 'label' => 'Your Name', 'required' => true, 'col' => '12'],
                                    ['type' => 'text', 'name' => 'phone', 'label' => 'Phone Number', 'required' => true, 'col' => '6', 'attr' => ['minlength' => '10', 'maxlength' => '10', 'pattern' => '[6-9][0-9]{9}', 'data-validate' => 'indian-phone']],
                                    ['type' => 'email', 'name' => 'email', 'label' => 'Email Address', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'email']],
                                    ['type' => 'text', 'name' => 'company', 'label' => 'Company Name', 'required' => true, 'col' => '6'],
                                    ['type' => 'number', 'name' => 'employee_count', 'label' => 'Team Size', 'required' => true, 'col' => '6'],
                                    ['type' => 'textarea', 'name' => 'message', 'label' => 'What would you like to explore in the demo?', 'required' => false, 'col' => '12', 'attr' => ['rows' => 4]],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-md',
                                'position' => 'end',
                                'label' => 'Book a Demo',
                                'short_label' => 'Fill out the form below to schedule your personalized demo of Got-It HR Solutions.',
                                'button' => 'Book Now',
                                'fullscreen_btn' => 'd-none',
                                'reload_btn' => 'd-none',
                                'script' => ''
                            ];
                            break;
                        case 'reseller':
                            $resellerForm = '<div class="p-0">
                                    <div data-stepper-container data-stepper-type="linear" data-progress-type="bar+icon" data-submit-btn-text="Submit Now" data-btn-class="lander-form-btn">
                                    <div data-step data-title="User Details" data-icon="fa-user">
                                    <div class="row g-3 pb-4">
                                        <div class="col-lg-12">
                                        <div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . asset('default/preview-square.svg') . '"></div>
                                        </div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="first_name" class="form-float-input" required="" data-validate="name" placeholder="First Name"><label for="first name" class="form-float-label">First Name<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="last_name" class="form-float-input"  placeholder="Last Name"><label for="last name" class="form-float-label">Last Name</label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="tel" name="phone" class="form-float-input" required=""  data-validate="indian-phone" placeholder="Phone"><label for="phone" class="form-float-label">Phone<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="email" name="email" class="form-float-input" required="" data-validate="email" placeholder="@email"><label for="email" class="form-float-label">Email<span class="text-danger">*</span></label></div></div>
                                    </div>
                                    </div>
                                    <div data-step data-title="Organization Details" data-icon="fa-building-user">
                                        <div class="row g-3">
                                            <div class="col-12"><div class="float-input-control"><input type="text" name="company_name" class="form-float-input" placeholder="Company Name"><label for="company name" class="form-float-label">Company Name</label></div></div>
                                            <div class="col-6"><div class="float-input-control"><input type="date" name="incorporation_date " class="form-float-input" placeholder="Incorporation Date"><label for="Incorporation Date" class="form-float-label">Incorporation Date</label></div></div>
                                           <div class="col-6">
                                                <div class="float-input-control">
                                                    <select name="entity_type" class="form-float-input" placeholder="Select Entity" data-select="dropdown" tabindex="-1" aria-hidden="true">
                                                        <option value="" disabled selected>Select an option</option>
                                                        <option value="private_limited">Private Limited Company</option>
                                                        <option value="llp">Limited Liability Partnership (LLP)</option>
                                                        <option value="partnership">Partnership Firm</option>
                                                        <option value="sole_proprietorship">Sole Proprietorship</option>
                                                        <option value="opc">One Person Company (OPC)</option>
                                                        <option value="public_limited">Public Limited Company</option>
                                                        <option value="ngo">Non-Profit / NGO / Section 8</option>
                                                        <option value="branch_office">Branch Office (Foreign Entity)</option>
                                                    </select>
                                                    <label for="Entity Type" class="form-float-label">Select Entity</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="float-input-control">
                                                    <select name="business_nature" class="form-float-input" placeholder="Select Nature of Business" data-select="dropdown" tabindex="-1" aria-hidden="true">
                                                        <option value="" disabled selected>Select an option</option>
                                                        <option value="manufacturing">Manufacturing</option>
                                                        <option value="trading">Trading</option>
                                                        <option value="wholesale">Wholesale</option>
                                                        <option value="retail">Retail</option>
                                                        <option value="import_export">Import / Export</option>
                                                        <option value="it_software">IT / Software Development</option>
                                                        <option value="ecommerce">E-commerce / Online Marketplace</option>
                                                        <option value="services">Professional Services</option>
                                                        <option value="consulting">Consulting</option>
                                                        <option value="education">Education / Training</option>
                                                        <option value="healthcare">Healthcare / Medical</option>
                                                        <option value="pharmaceuticals">Pharmaceuticals</option>
                                                        <option value="finance">Financial Services / NBFC</option>
                                                        <option value="insurance">Insurance</option>
                                                        <option value="banking">Banking</option>
                                                        <option value="real_estate">Real Estate / Construction</option>
                                                        <option value="transportation">Transportation</option>
                                                        <option value="logistics">Logistics / Supply Chain</option>
                                                        <option value="hospitality">Hospitality / Travel / Tourism</option>
                                                        <option value="food_beverage">Food & Beverage</option>
                                                        <option value="agriculture">Agriculture / Agro Products</option>
                                                        <option value="textile">Textile / Apparel / Garments</option>
                                                        <option value="automobile">Automobile / Auto Components</option>
                                                        <option value="media">Media / Entertainment</option>
                                                        <option value="telecom">Telecommunications</option>
                                                        <option value="fmcg">FMCG</option>
                                                        <option value="energy">Energy / Oil & Gas</option>
                                                        <option value="mining">Mining / Natural Resources</option>
                                                        <option value="aerospace">Aerospace / Defense</option>
                                                        <option value="electronics">Electronics / Hardware</option>
                                                        <option value="chemical">Chemical / Industrial Goods</option>
                                                        <option value="printing">Printing / Publishing</option>
                                                        <option value="event_management">Event Management</option>
                                                        <option value="beauty_wellness">Beauty / Wellness / Salon</option>
                                                        <option value="sports_fitness">Sports / Fitness</option>
                                                        <option value="arts_crafts">Arts / Crafts</option>
                                                        <option value="non_profit">Non-Profit / NGO</option>
                                                        <option value="government">Government / PSU</option>
                                                        <option value="freelancer">Freelancer / Independent Professional</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <label for="Nature of Business" class="form-float-label">Nature of Business</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="float-input-control">
                                                    <select name="vendor_type" class="form-float-input" placeholder="Select Vendor Type" data-select="dropdown" tabindex="-1" aria-hidden="true">
                                                        <option value="" disabled selected>Select an option</option>
                                                        <option value="manufacturer">Manufacturer</option>
                                                        <option value="wholesaler">Wholesaler</option>
                                                        <option value="distributor">Distributor</option>
                                                        <option value="retailer">Retailer</option>
                                                        <option value="trader">Trader</option>
                                                        <option value="importer">Importer</option>
                                                        <option value="exporter">Exporter</option>
                                                        <option value="service_provider">Service Provider</option>
                                                        <option value="contractor">Contractor</option>
                                                        <option value="consultant">Consultant</option>
                                                        <option value="freelancer">Freelancer</option>
                                                        <option value="agency">Agency</option>
                                                        <option value="supplier">Supplier</option>
                                                        <option value="logistics">Logistics Provider</option>
                                                        <option value="transport">Transport Provider</option>
                                                        <option value="maintenance">Maintenance Vendor</option>
                                                        <option value="hardware_vendor">Hardware Vendor</option>
                                                        <option value="software_vendor">Software Vendor</option>
                                                        <option value="utility_vendor">Utility Vendor</option>
                                                        <option value="government">Government / Public Sector</option>
                                                        <option value="non_profit">NGO / Non-Profit Vendor</option>
                                                        <option value="others">Other</option>
                                                    </select>
                                                    <label for="Vendor Type" class="form-float-label">Vendor Type</label>
                                                </div>
                                            </div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="cin" class="form-float-input"  data-validate="cin" placeholder="CIN"><label for="cin" class="form-float-label">CIN Number</label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="roc" class="form-float-input" data-validate="roc"  placeholder="ROC"><label for="roc" class="form-float-label">ROC NUmber</label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="msme" class="form-float-input" data-validate="msme_udyam"  placeholder="MSME"><label for="msme" class="form-float-label">MSME Number</label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="gst" class="form-float-input" data-validate="gst_in" placeholder="GST"><label for="gst" class="form-float-label">GST Number</label></div></div>

                                        </div>
                                    </div>
                                    <div data-step data-title="Business Address" data-icon="fa-location-dot">
                                    <div class="row g-3">
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="address_line1" class="form-float-input" required="" data-validate="address" placeholder="Address Line 1"><label for="Address Line 1" class="form-float-label">Address Line 1<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="address_line2" class="form-float-input"  data-validate="address" placeholder="Address Line 2"><label for="Address Line 2" class="form-float-label">Address Line 2</label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="city" class="form-float-input" required="" data-validate="city" placeholder="City"><label for="City" class="form-float-label">City<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="district" class="form-float-input" required="" placeholder="District"><label for="District" class="form-float-label">District<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="text" name="state" class="form-float-input" required="" data-validate="state" placeholder="state"><label for="state" class="form-float-label">State<span class="text-danger">*</span></label></div></div>
                                        <div class="col-6"><div class="float-input-control"><input type="number" name="pincode" class="form-float-input" required="" data-validate="pincode" placeholder="Pin Code"><label for="Pin Code" class="form-float-label">Pin Code<span class="text-danger">*</span></label></div></div>
                                    </div>
                                    </div>
                                    
                                    
                                    </div>
                                    </div>';
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'raw', 'html' => $resellerForm],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => 'Become a Reseller',
                                'short_label' => 'Become a Got-It HR Solutions reseller today. Fill in the form and we’ll help you get started.',
                                'button' => 'Join Now',
                                'fullscreen_btn' => 'd-none',
                                'reload_btn' => 'd-none',
                                'footer' => 'hide',
                                'script' => 'window.general.stepper();window.general.files();window.general.select();'
                            ];
                            break;

                             case 'onboarding':
                            $onboardingForm = '<div class="p-0">
    <div data-stepper-container data-stepper-type="linear" data-progress-type="bar+icon" data-submit-btn-text="Submit Now">
        <div data-step data-title="Business Information" data-icon="fa-building">
            <div class="row g-3">
                <div class="col-6"><div class="float-input-control"><input type="text" name="name" class="form-float-input" required data-validate="name" placeholder="Business Name"><label for="name" class="form-float-label">Business Name<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="legal_name" class="form-float-input" required data-validate="name" placeholder="Legal Name"><label for="legal_name" class="form-float-label">Legal Name<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="industry" class="form-float-input" required placeholder="Industry"><label for="industry" class="form-float-label">Industry<span class="text-danger">*</span></label></div></div>
                <div class="col-6">
                    <div class="float-input-control">
                        <select name="business_type" class="form-float-input" required data-select="dropdown" tabindex="-1" aria-hidden="true">
                            <option value="" disabled selected>Select Business Type</option>
                            <option value="private_limited">Private Limited Company</option>
                            <option value="llp">Limited Liability Partnership</option>
                            <option value="partnership">Partnership Firm</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="opc">One Person Company</option>
                            <option value="public_limited">Public Limited Company</option>
                            <option value="ngo">Non-Profit / NGO</option>
                        </select>
                        <label for="business_type" class="form-float-label">Business Type<span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="registration_no" class="form-float-input" data-validate="cin" placeholder="Registration Number"><label for="registration_no" class="form-float-label">Registration Number</label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="tax_id" class="form-float-input" data-validate="gst_in" placeholder="Tax ID"><label for="tax_id" class="form-float-label">Tax ID</label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="email" name="email" class="form-float-input" required data-validate="email" placeholder="Business Email"><label for="email" class="form-float-label">Business Email<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="tel" name="phone" class="form-float-input" required data-validate="indian-phone" placeholder="Business Phone"><label for="phone" class="form-float-label">Business Phone<span class="text-danger">*</span></label></div></div>
                <div class="col-12"><div class="float-input-control"><input type="url" name="website" class="form-float-input" data-validate="url" placeholder="Website"><label for="website" class="form-float-label">Website</label></div></div>
            </div>
        </div>
        <div data-step data-title="Address Details" data-icon="fa-location-dot">
            <div class="row g-3">
                <div class="col-6"><div class="float-input-control"><input type="text" name="address_line1" class="form-float-input" required data-validate="address" placeholder="Address Line 1"><label for="address_line1" class="form-float-label">Address Line 1<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="address_line2" class="form-float-input" data-validate="address" placeholder="Address Line 2"><label for="address_line2" class="form-float-label">Address Line 2</label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="city" class="form-float-input" required data-validate="city" placeholder="City"><label for="city" class="form-float-label">City<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="state" class="form-float-input" required data-validate="state" placeholder="State"><label for="state" class="form-float-label">State<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="country" class="form-float-input" required data-validate="country" placeholder="Country"><label for="country" class="form-float-label">Country<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="number" name="pincode" class="form-float-input" required data-validate="pincode" placeholder="Pincode"><label for="pincode" class="form-float-label">Pincode<span class="text-danger">*</span></label></div></div>
            </div>
        </div>
        <div data-step data-title="Admin & HR Details" data-icon="fa-user-tie">
            <div class="row g-3">
                <div class="col-6"><div class="float-input-control"><input type="text" name="admin_first_name" class="form-float-input" required data-validate="name" placeholder="Admin First Name"><label for="admin_first_name" class="form-float-label">Admin First Name<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="admin_last_name" class="form-float-input" required data-validate="name" placeholder="Admin Last Name"><label for="admin_last_name" class="form-float-label">Admin Last Name<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="email" name="admin_email" class="form-float-input" required data-validate="email" placeholder="Admin Email"><label for="admin_email" class="form-float-label">Admin Email<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="tel" name="admin_phone" class="form-float-input" required data-validate="indian-phone" placeholder="Admin Phone"><label for="admin_phone" class="form-float-label">Admin Phone<span class="text-danger">*</span></label></div></div>
                <div class="col-12"><div class="float-input-control"><input type="password" name="admin_password_hash" class="form-float-input" required data-validate="pswd-mix" placeholder="Admin Password"><label for="admin_password_hash" class="form-float-label">Admin Password<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="email" name="hr_contact_email" class="form-float-input" data-validate="email" placeholder="HR Contact Email"><label for="hr_contact_email" class="form-float-label">HR Contact Email</label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="tel" name="hr_contact_phone" class="form-float-input" data-validate="indian-phone" placeholder="HR Contact Phone"><label for="hr_contact_phone" class="form-float-label">HR Contact Phone</label></div></div>
            </div>
        </div>
        <div data-step data-title="Business & Billing" data-icon="fa-credit-card">
            <div class="row g-3">
                <div class="col-6">
                    <div class="float-input-control">
                        <select name="business_size" class="form-float-input" required data-select="dropdown" tabindex="-1" aria-hidden="true">
                            <option value="" disabled selected>Select Business Size</option>
                            <option value="micro">Micro</option>
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                        <label for="business_size" class="form-float-label">Business Size<span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-6"><div class="float-input-control"><input type="number" name="no_of_employees" class="form-float-input" required placeholder="Number of Employees"><label for="no_of_employees" class="form-float-label">Number of Employees<span class="text-danger">*</span></label></div></div>
                <div class="col-6"><div class="float-input-control"><input type="text" name="reseller_id" class="form-float-input" placeholder="Reseller ID"><label for="reseller_id" class="form-float-label">Reseller ID</label></div></div>
            </div>
        </div>
        <div data-step data-title="Device & Status" data-icon="fa-laptop">
            <div class="row g-3">
                <div class="col-12">
                    <div class="repeater-block w-100" data-repeater-container data-input="device_info" data-type="group">
                        <strong>Device Information</strong>
                        <div data-repeater class="d-flex flex-column gap-2 w-100 mt-2">
                            <div class="d-flex flex-row gap-2 align-items-end">
                                <div class="float-input-control flex-grow-1">
                                    <input type="text" name="device_name" class="form-float-input" required placeholder="Device Name">
                                    <label class="form-float-label">Device Name<span class="text-danger">*</span></label>
                                </div>
                                <div class="float-input-control flex-grow-1">
                                    <input type="text" name="ip" class="form-float-input" required data-validate="ip" placeholder="IP Address">
                                    <label class="form-float-label">IP Address<span class="text-danger">*</span></label>
                                </div>
                                <div class="float-input-control flex-grow-1">
                                    <input type="number" name="port" class="form-float-input" required placeholder="Port">
                                    <label class="form-float-label">Port<span class="text-danger">*</span></label>
                                </div>
                                <div class="float-input-control flex-grow-1">
                                    <input type="text" name="location" class="form-float-input" required data-validate="address" placeholder="Location">
                                    <label class="form-float-label">Location<span class="text-danger">*</span></label>
                                </div>
                                <button data-repeater-add type="button" class="btn btn-primary">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
               
                <div class="col-6">
                    <div class="float-input-control">
                        <select name="status" class="form-float-input" required data-select="dropdown" tabindex="-1" aria-hidden="true">
                            <option value="" disabled selected>Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                        <label for="status" class="form-float-label">Status<span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
                            $popup = [
                                'form' => 'builder',
                                'labelType' => 'floating',
                                'fields' => [
                                    ['type' => 'raw', 'html' => $onboardingForm],
                                ],
                                'type' => 'modal',
                                'size' => 'modal-lg',
                                'position' => 'end',
                                'label' => 'Client Onboarding',
                                'short_label' => 'Get started with Got-It HR Solutions. Fill out the form to onboard your company quickly and efficiently.',
                                'button' => 'Get Started',

                                'fullscreen_btn' => 'd-none',
                                'reload_btn' => 'd-block',
                                'footer' => 'hide',
                                'script' => 'window.general.stepper();window.general.files();window.general.select();window.general.repeater();'
                            ];
                            break;
                        default:
                            return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
                    }
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
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'fullscreen_btn' => $popup['fullscreen_btn'] ?? 'd-none', 'reload_btn' => $popup['reload_btn'] ?? 'd-none', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'status' => true, 'title' => 'Form Generated', 'message' => 'Add form for ' . $reqSet['key'] . ' generated successfully.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
