<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{Business, BusinessDB, Data, Developer, Random, Skeleton, FileManager, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new AssetManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new AssetManagement entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'AssetManagement record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
            case 'business_assets':
                $validator = Validator::make($request->all(), [
                    'sno' => 'required|string|max:100',
                    'company_id' => 'required|string',
                    'name' => 'required|string|regex:/^[a-zA-Z0-9\s_-]{3,255}$/|max:255',
                    'category_id' => 'required|string|max:100',
                    'image_url' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:2048', 
                    'purchase_date' => 'nullable|date',
                    'purchase_cost' => 'nullable|numeric|min:0',
                    'quantity' => 'required|numeric',
                    'allow_repair_request' => 'required',
                    'warranty_expiry' => 'nullable|date',
                    'location' => 'nullable|string|max:255',
                    'vendor_name' => 'nullable|string|max:255',
                    'vendor_contact' => 'nullable|string|max:255',
                    'notes' => 'nullable|string|max:1000',
                    'configuration_json' => 'nullable|string',
                    'is_active' => 'required|in:1,0',
                ]);
                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }
                $validated = $validator->validated();
                $user = Skeleton::authUser();
                $asset_img = null;
                if ($request->hasFile('image_url')) {
                    $folderKey = $reqSet['system'] . '_assets';
                    $fileResult = FileManager::saveFile($request, $folderKey, 'image_url', 'Assets', $user->business_id, false);
                    if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                        $asset_img = $fileResult['data']['file_id'] ?? null;
                    }
                    $reloadPage=true;
                }
                if ($asset_img) {
                    $validated['image_url'] = $asset_img;
                }
                $validated['allow_repair_request'] = $request->has('allow_repair_request') ? 1 : 0;
                $validated['asset_id'] = Random::unique(6, 'AST');
                $reloadPage = true;
                $title = 'Asset Added';
                $message = 'Asset added successfully.';
                break;
                case 'business_asset_categories':
                    $validator = Validator::make($request->all(), [
                        'sno' => 'required|string|max:100',
                        'name' => 'required|string|regex:/^[a-zA-Z0-9\s_-]{3,255}$/|max:255',
                        'description' => 'nullable|string|max:1000',
                        'is_active' => 'required|in:1,0',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['category_id'] = Random::unique(6, 'CAT', true);
                    $reloadTable = true;
                    $reloadCard = true;
                    $title = 'Asset Category Saved';
                    $message = 'Asset category saved successfully.';
                    break;

               case 'business_asset_assignment':
                    $validator = Validator::make($request->all(), [
                        'asset_id' => 'required|string|max:50',
                        'user_id' => 'required|string',
                        'assigned_date' => 'required|date',
                        'quantity' => 'required|int|min:1',
                        'notes' => 'nullable|string|max:1000',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();
                    if(!$validated['user_id']){
                        return ResponseHelper::moduleError('Validation Error', 'User is required.');
                    }

                    $exist = BusinessDB::table('asset_assignments')
                        ->where('asset_id', $validated['asset_id'])
                        ->where('user_id', $validated['user_id'])
                        ->whereNull('deleted_at')
                        ->exists();
                    if($exist){
                        return ResponseHelper::moduleError('Duplicate Found', 'This asset already has been assigned to this user you can edit that record.');
                    }

                    $asset = BusinessDB::table('assets')
                        ->where('asset_id', $validated['asset_id'])
                        ->whereNull('deleted_at')
                        ->first();

                    if (!$asset) {
                        return ResponseHelper::moduleError('Asset Not Found', 'Selected asset does not exist or is deleted.');
                    }

                    if ($asset->type === 'individual_asset') {
                        if ($asset->status === 'assigned') {
                            return ResponseHelper::moduleError('Asset Already Assigned', 'This individual asset is already assigned.');
                        }
                        if ($validated['quantity'] > 1) {
                            return ResponseHelper::moduleError('Invalid Quantity', 'Individual assets must be assigned with quantity = 1.');
                        }
                        $update = [
                            'status' => 'assigned',
                            'available_quantity' => 0,
                        ];
                    } else {
                        $currentAvailable = isset($asset->available_quantity) ? (int)$asset->available_quantity : (int)$asset->quantity;
                        if ($validated['quantity'] > $currentAvailable) {
                            return ResponseHelper::moduleError('Insufficient Quantity', "Only {$currentAvailable} units available for this group asset.");
                        }
                        $newAvailable = max($currentAvailable - (int)$validated['quantity'], 0);
                        $update = ['available_quantity' => $newAvailable];
                        if ($newAvailable === 0) $update['status'] = 'assigned';
                    }

                    $validated['assignment_id'] = Random::unique(6, 'ASN', true);
                    $validated['status'] = 'assigned';

                    $assetData = Data::update($reqSet['system'], 'assets', $update, ['asset_id'=> $validated['asset_id']], $reqSet['key']);
                    if (!$assetData['status']) {
                        return ResponseHelper::moduleError('Asset Status Update Failed', $assetData['message'], 400);
                    }

                    // Log assignment in asset_movements using Data facade
                    $movement = [
                        'asset_id' => $validated['asset_id'],
                        'assignment_id' => $validated['assignment_id'],
                        'user_id' => $validated['user_id'],
                        'type' => 'assigned',
                        'quantity' => $validated['quantity'],
                        'available_quantity' => $update['available_quantity'] ?? ($asset->quantity - $validated['quantity']),
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => Skeleton::getAuthenticatedUser()['id'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $assetMovement =Data::insert($reqSet['system'], 'asset_movements', $movement);
                    if (!$assetMovement['status']) {
                        return ResponseHelper::moduleError('Asset Status Update Failed', $assetMovement['message'], 400);
                    }
                    $reloadTable = true;
                    $title = 'Asset Assignment Saved';
                    $message = 'AssetManagement assignment configuration saved successfully.';
                break;
                case 'business_asset_maintenance':
                    $validator = Validator::make($request->all(), [
                        'asset_id' => 'required|string|max:50',
                        'maintenance_type' => 'required|in:routine,repair,inspection',
                        'description' => 'nullable|string|max:1000',
                        'maintenance_date' => 'required|date',
                        'cost' => 'nullable|numeric|min:0',
                        'vendor_name' => 'nullable|string|max:255',
                        'vendor_contact' => 'nullable|string|max:255',
                        'next_due_date' => 'nullable|date',
                        'status' => 'required|in:scheduled,completed,pending',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $validated['maintenance_id'] = Random::unique(6, 'MNT', true);
                    if($validated['status'] != 'completed'){
                        $update = ['status' => 'under_maintenance'];
                    }
                    $assetData = Data::update($reqSet['system'], 'assets', $update, ['asset_id'=> $validated['asset_id']], $reqSet['key']);
                    if (!$assetData['status']) {
                        return ResponseHelper::moduleError('Asset Status Update Failed', $assetData['message'], 400);
                    }   
                    $reloadTable = true;
                    $reloadCard = true;
                    $title = 'Asset Maintenance Saved';
                    $message = 'AssetManagement maintenance configuration saved successfully.';
                break;

                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            if ($store) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
            // Insert data into the database
            $result = Data::insert($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
            }
            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] ?? '' : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}