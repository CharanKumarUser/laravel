<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{Data, Developer, Random, Skeleton, Scope, Select, BusinessDB, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the add form for AssetManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new AssetManagement entities.
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
                 case 'business_assets':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            [
                                'type' => 'stepper',
                                'stepper' => 'linear',
                                'progress' => 'bar+icon',
                                'submit_text' => 'Save Asset',
                                'btn_class' => '',
                                'steps' => [
                                    // Step 1 - Asset Information
                                    [
                                        'title' => 'Asset Information',
                                        'icon' => 'fa-box',
                                        'fields' => [
                                            ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Asset Image" data-name="image_url" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2"></div>', 'col' => 12],
                                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => 4, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Company', 'options'=> Select::options('companies', 'array', ['company_id'=>'name']), 'required' => true, 'col' => 4, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'select', 'name' => 'type', 'label' => 'Asset Type', 'options'=> ['group_asset'=>'Group Asset', 'individual_asset'=>'Individual Asset'], 'required' => true, 'col' => 4, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Asset Name', 'required' => true, 'col' => 4, 'attr' => ['data-validate' => 'name']],
                                            ['type' => 'select', 'name' => 'category_id', 'label' => 'Category', 'options' => Select::options('asset_categories', 'array', ['category_id' => 'name']), 'required' => true, 'col' => 4, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'switch', 'name' => 'allow_repair_request', 'label' => 'Can Request Repair', 'required' => true, 'col' => '4'],

                                        ],
                                    ],

                                    // Step 2 - Purchase, Vendor & Status (Combined)
                                    [
                                        'title' => 'Purchase, Vendor & Status',
                                        'icon' => 'fa-credit-card',
                                        'fields' => [
                                            // Purchase & Warranty
                                            ['type' => 'date', 'name' => 'purchase_date', 'label' => 'Purchase Date', 'required' => false, 'col' => 4, 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                                            ['type' => 'number', 'name' => 'purchase_cost', 'label' => 'Purchase Cost', 'required' => false, 'col' => 4, 'attr' => ['min' => 0, 'step' => '0.01']],
                                            ['type' => 'number', 'name' => 'quantity', 'label' => 'Quantity', 'required' => true, 'col' => 4, 'attr' => ['maxlength'=>10]],
                                            ['type' => 'date', 'name' => 'warranty_expiry', 'label' => 'Warranty Expiry', 'required' => false, 'col' => 4, 'attr' => ['data-date-picker' => 'date']],
                                            // Vendor & Status
                                            ['type' => 'text', 'name' => 'vendor_name', 'label' => 'Vendor Name', 'required' => false, 'col' => 4, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'number', 'name' => 'vendor_contact', 'label' => 'Vendor Contact', 'required' => false, 'col' => 4, 'attr' => ['maxlength' => 10, 'data-validate' => 'indian-phone']],
                                            ['type' => 'text', 'name' => 'location', 'label' => 'Location', 'required' => false, 'col' => 6, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => 6, 'attr' => ['data-select' => 'dropdown']],
                                            ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'required' => false, 'col' => 12, 'attr' => ['rows' => 4, 'maxlength' => 1000]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Asset Configuration',
                                        'icon' => 'fa-toolbox',
                                        'fields' => [
                                             [
                                                'type' => 'raw',
                                                'html' => '
                                                    <div style="
                                                        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
                                                        border: 1px solid #d6e4ff;
                                                        border-radius: 10px;
                                                        padding: 15px 18px;
                                                        font-size: 14px;
                                                        color: #1e293b;
                                                        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                                                    ">
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <span style="font-size: 18px;">💡</span>
                                                            <strong style="font-size: 15px;">Example Configuration</strong>
                                                        </div>
                                                        <div style="margin-left: 26px; line-height: 1.6;">
                                                            Add the key specs for this asset. <br>
                                                            For instance:
                                                            <ul style="margin: 6px 0 0 15px; padding: 0; list-style: disc;">
                                                                <li><b>Processor:</b> Intel i5 (12th Gen)</li>
                                                                <li><b>RAM:</b> 8 GB</li>
                                                                <li><b>Storage:</b> 512 GB SSD</li>
                                                                <li><b>OS:</b> Windows 11</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                ',
                                                'col' => 12
                                            ],


                                            ['type' => 'repeater', 'name' => 'configuration_json', 'set' => 'pair', 'fields' => [['type' => 'text', 'name' => 'label', 'label' => 'Title', 'placeholder' => 'Value', 'required' => false],['type' => 'text', 'name' => 'value', 'label' => 'Value', 'placeholder' => 'Display', 'required' => false]], 'col' => '12'],
                                        ],

                                    ]
                                    
                                ],
                                'col' => 12,
                            ],
                        ],

                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-box me-1"></i> Add Asset',
                        'short_label' => 'Register a new business asset',
                        'button' => 'Save Asset',
                        'footer' => 'hide',
                        'script' => 'window.general.select();window.general.stepper();window.general.files();window.skeleton.datePicker();window.general.repeater();',
                    ];
                break;

                case 'business_asset_categories':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => '12', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'name', 'maxlength' => '255', 'data-unique' => Skeleton::skeletonToken('business_asset_categories') . '_u', 'data-unique-msg' => 'This name is already registered']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Add Asset Category',
                        'short_label' => 'Create new asset category for company',
                        'button' => 'Save Category',
                        'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();'
                    ];
                    break;
                case 'business_asset_assignment':
                    $scopes = Scope::getScopePaths('all', $request->id ?? null, true);
                    $token = Skeleton::skeletonToken('business_asset_assignment') . '_s';
                    $field = [];
                    $allAssets = BusinessDB::table('assets')
                    ->select('company_id', 'asset_id', 'name', 'type', 'quantity', 'available_quantity', 'image_url')
                    ->where('status','available')
                    ->whereNull('deleted_at')
                    ->get()
                    ->map(function($asset){
                        $asset->image_path = $asset->image_url 
                            ? FileManager::getFile($asset->image_url) 
                            : asset('default/preview-square.svg');
                        return $asset;
                    })
                    ->toArray();
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'company', 'label' => 'Company', 'id' => 'company-select' ,'options' => Select::options('companies', 'array', ['company_id' => 'name']), 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Asset Type','id' => 'asset-type' , 'options' => ['group_asset' => 'Group Asset', 'individual_asset' => 'Individual Asset'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'asset_id', 'label' => 'Asset', 'options' => [], 'required' => true, 'col' => '4', 'id' => 'asset-select', 'col_class'=>'asset-div', 'attr' => ['data-select' => 'dropdown',]], 
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100" data-path-id="scope-paths" data-path-name="scope_id"><input type="hidden" data-scope data-source="' . $token . '" data-select-trigger=".update-users-select" data-set="scope" name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area></div></div>', 'col' => '6'],
                            ['type' => 'select', 'name' => 'user_id', 'label' => 'User', 'class' => ['update-users-select'], 'options' => [''], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dynamic']],
                            ['type' => 'text', 'name' => 'quantity', 'label' => 'Quantity', 'required' => true, 'col' => '6', 'attr' => []],
                            ['type' => 'date', 'name' => 'assigned_date', 'label' => 'Assigned Date', 'required' => true, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'required' => false, 'col' => '12', 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-handshake me-1"></i>Assign Asset',
                        'short_label' => 'Assign an asset to a user',
                        'button' => 'Save Assignment',
                       'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ', [], "single", true);

                       (function(){
                            function initAssetFilter(assets) {
                                const assetSelect = document.querySelector("#asset-select");
                                const companySelect = document.querySelector("#company-select");
                                const typeSelect = document.querySelector("#asset-type");
                                const assetDiv = document.querySelector(".asset-div"); 
                                if(!assetSelect || !companySelect || !typeSelect || !assetDiv) return;

                                let previewContainer = document.querySelector(".asset-preview");
                                if(!previewContainer){
                                    previewContainer = document.createElement("div");
                                    previewContainer.classList.add("col-12","mt-2","asset-preview");
                                    assetSelect.closest(".asset-div").after(previewContainer); // <- this moves it next to asset-div
                                }

                                function updateOptions() {
                                    const companyId = companySelect.value;
                                    const assetType = typeSelect.value;
                                    const filtered = assets.filter(a => a.company_id == companyId && a.type == assetType);

                                    assetSelect.innerHTML = filtered.length
                                        ? filtered.map(a=>`<option value="${a.asset_id}">${a.name}</option>`).join("")
                                        : "<option value=\"\">No assets found</option>";

                                    assetSelect.disabled = false;
                                    if(filtered.length) assetSelect.value = filtered[0].asset_id;

                                    if($(assetSelect).hasClass("select2-hidden-accessible")){
                                        $(assetSelect).trigger("change.select2");
                                    } else if(window.general?.select){
                                        window.general.select();
                                    }

                                    assetSelect.style.border = filtered.length ? "" : "1px solid red";

                                    updateAssetDetails();

                                    console.warn("Asset dropdown updated. Company:", companyId, "Type:", assetType, "Options:", filtered.map(a=>a.name));
                                }
                                function formatText(type) {
                                    if (!type) return "";
                                    return type
                                        .split("_")                      
                                        .map(word => word.charAt(0).toUpperCase() + word.slice(1)) 
                                        .join(" ");
                                }


                                function updateAssetDetails() {
                            const selectedId = assetSelect.value;
                            const asset = assets.find(a => a.asset_id == selectedId);
                            if(!asset){
                                previewContainer.innerHTML = "";
                                return;
                            }

                            const imagePath = asset.image_path;

                            previewContainer.innerHTML = `
                                <div class="p-2">
                                    <div class="table-responsive">
                                        <table class="table table-borderless table-striped table-hover w-100 dataTable mb-0">
                                            <thead class="bg-primary text-white">
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Total Quantity</th>
                                                    <th>Available Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <img src="${imagePath}" alt="${asset.name}" 
                                                            class="rounded-circle" 
                                                            style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                                    </td>
                                                    <td><span class="badge bg-success rounded-pill">${asset.name}</span></td>
                                                    <td><span class="badge bg-primary rounded-pill">${formatText(asset.type)}</span></td>
                                                    <td><span class="text-dark">${asset.quantity}</span></td>
                                                    <td><span class="text-dark">${asset.available_quantity}</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }
                                setTimeout(updateOptions, 100);

                                $(document).off("change.assetFilter").on("change.assetFilter","#company-select,#asset-type", updateOptions);
                                $(document).off("change.assetSelected").on("change.assetSelected","#asset-select", ()=>{
                                    if(quantityInput) quantityInput.value = "";
                                    if(notesInput) notesInput.value = "";
                                    updateAssetDetails();
                                });
                            }


                            function onModalOpen() {
                                const assets = ' . json_encode($allAssets) . ';
                                setTimeout(()=>initAssetFilter(assets), 100);
                            }

                            if(window.Skeleton?.onModalShow){
                                window.Skeleton.onModalShow(onModalOpen);
                            } else {
                                onModalOpen();
                            }
                        })();
                        '
                    ];
                break;  
                
                case 'business_asset_maintenance':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Company', 'options' => Select::options('companies', 'array', ['company_id' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-target' => Skeleton::skeletonToken('business_company_assets_select') . '_s']],
                            ['type' => 'select', 'name' => 'asset_id', 'label' => 'Asset', 'options' => [], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-source' => Skeleton::skeletonToken('business_company_assets_select') . '_s']],
                            ['type' => 'select', 'name' => 'maintenance_type', 'label' => 'Maintenance Type', 'options' => ['repair' => 'Repair', 'service' => 'Service', 'inspection' => 'Inspection'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'date', 'name' => 'maintenance_date', 'label' => 'Maintenance Date', 'required' => true, 'col' => '4', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'date', 'name' => 'next_due_date', 'label' => 'Next Due Date', 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'text', 'name' => 'vendor_name', 'label' => 'Vendor Name', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '255']],
                            ['type' => 'text', 'name' => 'vendor_contact', 'label' => 'Vendor Contact', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '255']],
                            ['type' => 'number', 'name' => 'cost', 'label' => 'Cost', 'required' => false, 'col' => '6'],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['scheduled' => 'Scheduled', 'pending' => 'Pending', 'completed' => 'Completed'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-tools me-1"></i> Add Maintenance Record',
                        'short_label' => 'Record maintenance activity for an asset',
                        'button' => 'Save Maintenance',
                        'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();'
                    ];
                    break;
                case 'business_asset_maintenance_request':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Reason for the Maintenance', 'required' => true, 'col' => '12', 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-tools me-1"></i> Request Maintenance',
                        'short_label' => 'Request maintenance for the asset',
                        'button' => 'Request Maintenance',
                        'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();'
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
