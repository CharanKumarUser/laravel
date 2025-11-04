<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{BusinessDB, Data, Developer, Random, Skeleton, Select, FileManager, Helper, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config};
/**
 * Controller for rendering the edit form for AssetManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing AssetManagement entities.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['column'=>[$reqSet['act'], 'value'=> $reqSet['id']]]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            if (!$data) {
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
                                            ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Asset Image" data-name="image_url" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . ($data && isset($data->image_url) && $data->image_url ? FileManager::getFile($data->image_url) : asset('default/preview-square.svg')) . '"></div>', 'col' => 12],
                                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => 4, 'value' => $data->sno, 'attr' => ['maxlength' => 100]],
                                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Company','options'=> Select::options('companies', 'array', ['company_id'=>'name']),'required' => true, 'col' => 4, 'attr' => ['data-select' => 'dropdown','data-value'=>$data->company_id]],
                                            ['type' => 'select', 'name' => 'type', 'label' => 'Asset Type', 'options'=> ['group_asset'=>'Group Asset', 'individual_asset'=>'Individual Asset'], 'required' => true, 'col' => 4, 'attr' => ['data-select' => 'dropdown','data-value'=>$data->type]],
                                            ['type' => 'text', 'name' => 'name', 'label' => 'Asset Name', 'required' => true, 'col' => 6, 'value' => $data->name],
                                            ['type' => 'select', 'name' => 'category_id', 'label' => 'Category', 'options' => Select::options('asset_categories', 'array', ['category_id' => 'name']), 'required' => true, 'col' => 6, 'attr' => ['data-select' => 'dropdown','data-value'=>$data->category_id]],
                                        ],
                                    ],

                                    // Step 3 - Vendor & Status
                                    [
                                        'title' => 'Purchase , Vendor & Status',
                                        'icon' => 'fa-industry',
                                        'fields' => [
                                            ['type' => 'date', 'name' => 'purchase_date', 'label' => 'Purchase Date', 'required' => false, 'col' => 4, 'value' => $data->purchase_date, 'attr' => ['data-date-picker' => 'date']],
                                            ['type' => 'number', 'name' => 'purchase_cost', 'label' => 'Purchase Cost', 'required' => false, 'col' => 4, 'value' => $data->purchase_cost, 'attr' => ['min' => 0, 'step' => '0.01']],
                                            ['type' => 'number', 'name' => 'quantity', 'label' => 'Quantity', 'required' => false, 'col' => 4, 'value' => $data->quantity, 'attr' => ['maxlength'=>10]],
                                            ['type' => 'date', 'name' => 'warranty_expiry', 'label' => 'Warranty Expiry', 'required' => false, 'col' => 4, 'value' => $data->warranty_expiry, 'attr' => ['data-date-picker' => 'date']],
                                            ['type' => 'text', 'name' => 'vendor_name', 'label' => 'Vendor Name', 'required' => false, 'col' => 4, 'value' => $data->vendor_name, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'vendor_contact', 'label' => 'Vendor Contact', 'required' => false, 'col' => 4, 'value' => $data->vendor_contact, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'text', 'name' => 'location', 'label' => 'Location', 'required' => false, 'col' => 6, 'value' => $data->location, 'attr' => ['maxlength' => 255]],
                                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => 6, 'attr' => ['data-select' => 'dropdown','data-value'=>$data->is_active]],
                                            ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'required' => false, 'col' => 12, 'value' => $data->notes, 'attr' => ['rows' => 4, 'maxlength' => 1000]],
                                        ],
                                    ],
                                    [
                                        'title' => 'Asset Configuration',
                                        'icon' => 'fa-toolbox',
                                        'fields' => [
                                            ['type' => 'repeater', 'name' => 'configuration_json', 'set' => 'pair', 'value' => $data->configuration_json, 'fields' => [['type' => 'text', 'name' => 'label', 'label' => 'Title', 'placeholder' => 'Value', 'required' => false],['type' => 'text', 'name' => 'value', 'label' => 'Value', 'placeholder' => 'Display', 'required' => false]], 'col' => '12'],
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
                            ['type' => 'text', 'name' => 'sno', 'label' => 'SNO', 'required' => true, 'col' => '12', 'value' => $data->sno],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '12', 'value' => $data->name],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Is Active', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'value' => $data->description, 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Edit Asset Category',
                        'short_label' => 'Modify asset category details and activation status',
                        'button' => 'Update Category',
                        'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();'
                    ];
                    break;
                case 'business_asset_assignment':
                    $scopes = Scope::getScopePaths('all', null, true);
                    $selectToken = Skeleton::skeletonToken('business_asset_assignment') . '_s';
                    $asset= BusinessDB::table('assets')->where('asset_id', $data->asset_id)->whereNull('deleted_at')->first();
                    $allAssets = BusinessDB::table('assets')
                        ->select('company_id', 'asset_id', 'name', 'type', 'quantity', 'available_quantity', 'image_url')
                        ->whereNull('deleted_at')
                        ->get()
                        ->map(function($asset){
                            $asset->image_path = $asset->image_url 
                                ? FileManager::getFile($asset->image_url) 
                                : asset('default/preview-square.svg');
                            return $asset;
                        })
                        ->toArray();
                    $user=BusinessDB::table('users')->where('user_id', $data->user_id)->first();
                    $popup = 
                    [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'hidden', 'name' => 'assignment_id', 'label' => 'Company', 'required' => true, 'col_class'=>'m-0', 'col' => '12','value'=>$data->assignment_id, 'attr' => []],
                            ['type' => 'select', 'name' => 'company', 'label' => 'Company', 'id' => 'company-select' ,'options' => Select::options('companies', 'array', ['company_id' => 'name']), 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value'=> $asset->company_id, 'disabled' => 'disabled']],
                            ['type' => 'select', 'name' => 'type', 'label' => 'Asset Type','id' => 'asset-type' , 'options' => [ 'individual_asset' => 'Individual Asset','group_asset' => 'Group Asset'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value'=> $asset->type, 'disabled' => 'disabled']],
                            ['type' => 'select', 'name' => 'asset_id', 'label' => 'Asset', 'options' => [], 'required' => true, 'col' => '4', 'id' => 'asset-select', 'col_class'=>'asset-div', 'attr' => ['data-select' => 'dropdown','data-value' => $data->asset_id, 'disabled' => 'disabled']], 
                            ['type' => 'raw', 'html' => '<div class="path-dropdown w-100" data-path-id="scope-paths" data-path-name="scope_id" ><input type="hidden" data-scope data-source="' . $selectToken . '" data-select-trigger=".update-users-select" data-set="scope" name="scope_id"><div class="path-trigger" data-placeholder="Select Scope">Select an option</div><div class="path-dropdown-menu" data-scope-area ></div></div>', 'col' => '6'],
                            ['type' => 'select', 'name' => 'user_id', 'label' => 'User', 'class' => ['update-users-select'], 'options' => [], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dynamic','data-value' => $data->user_id, 'disabled' => 'disabled']],
                            ['type' => 'text', 'name' => 'quantity', 'label' => 'Assigned Quantity', 'required' => true, 'value'=> $data->quantity ,'col' => '4', 'attr' => ['readonly' => 'readonly']],
                            ['type'=>'number','name'=>'additional_quantity','label'=>'Additional Quantity','required'=>false,'value'=>0,'col'=>'4','attr'=>['min'=>0,'max'=>$asset->available_quantity,'placeholder'=>'Enter additional units']],
                            ['type' => 'date', 'name' => 'assigned_date', 'label' => 'Assigned Date', 'required' => true, 'col' => '4','value'=> $data->assigned_date, 'attr' => ['data-date-picker' => 'date']],
                            ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'required' => false, 'col' => '12', 'attr' => ['rows' => '4', 'maxlength' => '1000'], 'value'=> $data->notes],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-handshake me-1"></i> Edit Assigned Asset',
                        'short_label' => 'Modify assigned asset information and return status',
                        'button' => 'Update Assignment',
                        'script' => 'window.general.select();window.general.unique();window.skeleton.datePicker();window.skeleton.path("scope-paths", ' . json_encode($scopes) . ', ["'.$user->scope_id .'"], "single", true);
                        (function(){
                            function initAssetFilter(assets) {
                                const assetSelect = document.querySelector("#asset-select");
                                const companySelect = document.querySelector("#company-select");
                                const typeSelect = document.querySelector("#asset-type");
                                const notesInput = document.querySelector("[name=notes]");
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
                                // Enable disabled fields on submit
                                const parentForm = assetSelect.closest("form");
                                if(parentForm){
                                    parentForm.addEventListener("submit", () => {
                                        parentForm.querySelectorAll("select, input, textarea").forEach(field => {
                                            field.removeAttribute("disabled");
                                        });
                                    });
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
                        })();'
                    ];
                break;
                case 'business_return_assets':
                    
                    $asset = BusinessDB::table('assets')->where('asset_id', $data->asset_id)->whereNull('deleted_at')->first();
                    if (!$asset) return ResponseHelper::moduleError('Asset Not Found', 'The assigned asset record could not be found.');

                    $assetImage = $asset->image_url ? FileManager::getFile($asset->image_url) : asset('default/preview-square.svg');
                    $isGroup = $asset->type === 'group_asset';
                    $fields = [];

                    $fields[] = ['type' => 'raw','html' => 
                    '<div class="p-2">
                        <div class="table-responsive">
                            <table class="table table-borderless table-striped table-hover w-100 dataTable mb-0">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>Image</th>
                                        <th>Asset</th>
                                        <th>Type</th>
                                        <th>Assigned Quantity</th>
                                        <th>Returned Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <img src="'.$assetImage.' " alt="${asset.name}" 
                                                class="rounded-circle" 
                                                style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                        </td>
                                        <td><span class="badge bg-success rounded-pill">'.$asset->name.'</span></td>
                                        <td><span class="badge bg-primary rounded-pill">'.ucwords(str_replace('_', ' ', $asset->type)).'</span></td>
                                        <td><span class="text-dark">'.$data->quantity.'</span></td>
                                        <td><span class="text-dark">'.$data->returned_quantity.'</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>'];
                    $fields[] = ['type' => 'hidden', 'name' => 'assignment_id', 'label' => 'Company', 'required' => true, 'col_class'=>'m-0', 'col' => '12','value'=>$data->assignment_id, 'attr' => []];

                    if ($isGroup) {
                        $fields[] = ['type' => 'number','name' => 'return_quantity','label' => 'Return Quantity','required' => true,'col' => '6','attr' => ['min' => 1]];
                        $fields[] = ['type' => 'date','name' => 'return_date','label' => 'Return Date','required' => true,'col' => '6','attr' => ['data-date-picker' => 'date']];
                    } else {
                        $fields[] = ['type' => 'text','name' => 'quantity','label' => 'Quantity','col' => '6','attr' => ['readonly' => 'readonly','value' => 1]];
                        $fields[] = ['type' => 'date','name' => 'return_date','label' => 'Return Date','required' => true,'col' => '6','attr' => ['data-date-picker' => 'date']];
                    }
                    $fields[] = ['type' => 'textarea','name' => 'return_notes','label' => 'Return Notes','required' => false,'col' => '12','attr' => ['rows' => '3','maxlength' => '1000']];
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => $fields,
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-rotate-left me-1"></i>Return Asset',
                        'short_label' => 'Return assigned asset',
                        'button' => 'Save Return',
                        'script' => 'window.general.select(); window.skeleton.datePicker();',
                    ];
                break;


                case 'business_asset_maintenance':
                    $asset=BusinessDB::table('assets')->where('asset_id', $data->asset_id)->whereNull('deleted_at')->first();
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Company', 'options' => Select::options('companies', 'array', ['company_id' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-target' => Skeleton::skeletonToken('business_company_assets_select') . '_s', 'data-value'=> $asset->company_id]],
                            ['type' => 'select', 'name' => 'asset_id', 'label' => 'Asset', 'options' => [], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-source' => Skeleton::skeletonToken('business_company_assets_select') . '_s', 'data-value'=> $data->asset_id]],
                            ['type' => 'select', 'name' => 'maintenance_type', 'label' => 'Maintenance Type', 'options' => ['routine' => 'Routine', 'repair' => 'Repair', 'inspection' => 'Inspection'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->maintenance_type]],
                            ['type' => 'date', 'name' => 'maintenance_date', 'label' => 'Maintenance Date', 'required' => true, 'col' => '4', 'value' => $data->maintenance_date, 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                            ['type' => 'date', 'name' => 'next_due_date', 'label' => 'Next Due Date', 'required' => false, 'col' => '4', 'value' => $data->next_due_date, 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                            ['type' => 'text', 'name' => 'vendor_name', 'label' => 'Vendor Name', 'required' => false, 'col' => '6', 'value' => $data->vendor_name, 'attr' => ['maxlength' => '255']],
                            ['type' => 'text', 'name' => 'vendor_contact', 'label' => 'Vendor Contact', 'required' => false, 'col' => '6', 'value' => $data->vendor_contact, 'attr' => ['maxlength' => '255']],
                            ['type' => 'number', 'name' => 'cost', 'label' => 'Cost', 'required' => false, 'col' => '6', 'value' => $data->cost, 'attr' => ['min' => '0', 'step' => '0.01']],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->status]],
                            ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'required' => false, 'col' => '12', 'value' => $data->description, 'attr' => ['rows' => '4', 'maxlength' => '1000']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-tools me-1"></i> Edit Maintenance Record',
                        'short_label' => 'Modify maintenance record for the asset',
                        'button' => 'Update Maintenance',
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
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0','hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
    /**
     * Renders a popup to confirm bulk update of records.
     *
     * @param Request $request HTTP request object containing input data.
     * @param array $params Route parameters including token.
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
            if (!isset($reqSet['system']) || !isset($reqSet['table']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or missing required data.', 400);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id', '')));
            if (empty($ids)) {
                return ResponseHelper::moduleError('Invalid Data', 'No records specified for update.', 400);
            }
            // Fetch records details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]], 'all');
            if (!$result['status'] || empty($result['data'])) {
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
                $recordArray = (array)$record;
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
                case 'AssetManagement_entities':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['data' => 'Data', 'unique' => 'Unique', 'select' => 'Select', 'other' => 'Other'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit AssetManagement Entities',
                        'short_label' => '',
                        'button' => 'Update Entities',
                        'script' => 'window.skeleton.select();window.skeleton.unique();'
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
            $content = '<input type="hidden" name="update_ids" value="' . $request->input('id', '') . '">';
            $content .= $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            $content = $detailsHtmlPlacement === 'top' ? $detailsHtml . $content : $content . $detailsHtml;
            // Generate response
            return response()->json(['token' => $token,'type' => $popup['type'],'size' => $popup['size'],'position' => $popup['position'],'label' => $popup['label'],'short_label' => $popup['short_label'],'content' => $content,'script' => $popup['script'],'button_class' => $popup['button_class'] ?? '','button' => $popup['button'] ?? '','footer' => $popup['footer'] ?? '','header' => $popup['header'] ?? '','validate' => $reqSet['validate'] ?? '0','hold_popup' => $holdPopup,'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}