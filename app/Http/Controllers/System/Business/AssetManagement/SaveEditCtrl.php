<?php
namespace App\Http\Controllers\System\Business\AssetManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, BusinessDB};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving updated AssetManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated AssetManagement entity data based on validated input.
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
            Developer::debug('SaveEditCtrl', ['request' => $request->all(), 'resolved' => $reqSet]);
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'AssetManagement record updated successfully.';
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
                    $reloadTable = true;
                    $reloadCard = true;
                    $title = 'Asset Category Updated';
                    $message = 'Asset category Updated successfully.';
                    break;
              case 'business_asset_assignment':

                // Validate request
                $validator = Validator::make($request->all(), [
                    'asset_id' => 'required|string|max:50',
                    'user_id' => 'required|string',
                    'assigned_date' => 'required|string',
                    'quantity' => 'required|int|min:1',
                    'additional_quantity' => 'nullable|int|min:0',
                    'notes' => 'nullable|string|max:1000',
                ]);

                if ($validator->fails()) {
                    return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                }

                $validated = $validator->validated();
                if (!$validated['user_id']) {
                    return ResponseHelper::moduleError('Validation Error', 'User is required.');
                }

                // Fetch asset
                $asset = BusinessDB::table('assets')
                    ->where('asset_id', $validated['asset_id'])
                    ->whereNull('deleted_at')
                    ->first();

                if (!$asset) {
                    return ResponseHelper::moduleError('Asset Not Found', 'Selected asset does not exist or is deleted.');
                }

                // Only allow assignment if asset is assignable
                $assignableStatuses = ['available', 'partially_assigned', 'partially_returned'];
                if (!in_array($asset->status, $assignableStatuses)) {
                    return ResponseHelper::moduleError(
                        'Asset Not Assignable',
                        "Cannot assign asset with status '{$asset->status}'."
                    );
                }

                // Calculate total assigned quantity
                $additionalQty = (int)($validated['additional_quantity'] ?? 0);
                $totalAssignedQty = (int)$validated['quantity'] + $additionalQty;
                unset($validated['additional_quantity']); // temporary field

                // Determine update for asset table
                if ($asset->type === 'individual_asset') {
                    if ($asset->status === 'assigned') {
                        return ResponseHelper::moduleError('Asset Already Assigned', 'This individual asset is already assigned.');
                    }
                    if ($totalAssignedQty > 1) {
                        return ResponseHelper::moduleError('Invalid Quantity', 'Individual assets must be assigned with quantity = 1.');
                    }

                    $update = [
                        'available_quantity' => 0,
                        'status' => 'assigned',
                    ];
                } else { // group asset
                    $currentAvailable = isset($asset->available_quantity) ? (int)$asset->available_quantity : (int)$asset->quantity;

                    if ($additionalQty > $currentAvailable) {
                        return ResponseHelper::moduleError('Insufficient Quantity', "Only {$currentAvailable} units available for this group asset.");
                    }

                    $newAvailable = $currentAvailable - $totalAssignedQty;

                    if ($newAvailable === 0) {
                        $status = 'assigned';
                    } elseif ($newAvailable < $asset->quantity) {
                        $status = 'partially_assigned';
                    } else {
                        $status = 'available';
                    }

                    $update = [
                        'available_quantity' => $newAvailable,
                        'status' => $status,
                    ];
                }

                // Update asset table
                $assetData = Data::update($reqSet['system'], 'assets', $update, ['asset_id' => $validated['asset_id']], $reqSet['key']);
                if (!$assetData['status']) {
                    return ResponseHelper::moduleError('Asset Status Update Failed', $assetData['message'], 400);
                }

                // Log movement for additional quantity if > 0
                if ($additionalQty > 0) {
                    $movement = [
                        'asset_id' => $validated['asset_id'],
                        'assignment_id' => $request->assignment_id ?? null,
                        'user_id' => $validated['user_id'],
                        'type' => 'assigned',
                        'quantity' => $additionalQty,
                        'available_quantity' => $update['available_quantity'],
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => auth()->id() ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $assetMovement = Data::insert($reqSet['system'], 'asset_movements', $movement);
                    if (!$assetMovement['status']) {
                        return ResponseHelper::moduleError('Asset Movement Logging Failed', $assetMovement['message'], 400);
                    }
                }

                $reloadTable = true;
                $reloadCard = true;
                $title = 'Asset Updated Successfully';
                $message = 'Asset Assignment Updated Successfully.';

            break;

                case 'business_return_assets':
                    $validator = Validator::make($request->all(), [
                        'return_date' => 'required|date',
                        'return_notes' => 'nullable|string|max:1000',
                        'return_quantity' => 'nullable|integer|min:1',
                    ]);

                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }

                    $validated = $validator->validated();

                    $assignment = BusinessDB::table('asset_assignments')
                        ->where('assignment_id', $request->assignment_id)
                        ->whereNull('deleted_at')
                        ->first();

                    if (!$assignment) {
                        return ResponseHelper::moduleError('Assignment Not Found', 'The asset assignment record does not exist.');
                    }

                    $asset = BusinessDB::table('assets')
                        ->where('asset_id', $assignment->asset_id)
                        ->whereNull('deleted_at')
                        ->first();

                    if (!$asset) {
                        return ResponseHelper::moduleError('Asset Not Found', 'The asset associated with this assignment was not found.');
                    }

                    $isGroup = $asset->type === 'group_asset';
                    $returnQty = (int)($validated['return_quantity'] ?? 1);
                    $alreadyReturned = (int)($assignment->returned_quantity ?? 0);
                    $assignedQty = (int)$assignment->quantity;

                    if ($isGroup) {
                        $remainingQty = $assignedQty - $alreadyReturned;
                        if ($returnQty > $remainingQty) {
                            return ResponseHelper::moduleError('Invalid Quantity', "You can return up to {$remainingQty} units only.");
                        }

                        $newReturned = $alreadyReturned + $returnQty;
                        $assetAvailable = (int)($asset->available_quantity ?? 0) + $returnQty;

                        $assignmentUpdate = [
                            'returned_quantity' => $newReturned,
                            'status' => $newReturned >= $assignedQty ? 'returned' : 'partially_returned',
                            'return_date' => $validated['return_date'],
                            'return_notes' => $validated['return_notes'] ?? null,
                        ];

                        $assetUpdate = [
                            'available_quantity' => $assetAvailable,
                            'status' => $assetAvailable >= $asset->quantity ? 'available' : 'partially_assigned',
                        ];
                    } else {
                        if ($assignment->status === 'returned') {
                            return ResponseHelper::moduleError('Already Returned', 'This asset is already marked as returned.');
                        }

                        $assignmentUpdate = [
                            'returned_quantity' => 1,
                            'status' => 'returned',
                            'return_date' => $validated['return_date'],
                            'return_notes' => $validated['return_notes'] ?? null,
                        ];

                        $assetUpdate = [
                            'status' => 'available',
                            'available_quantity' => 1,
                        ];
                    }

                    $updateAssign = Data::update($reqSet['system'], 'asset_assignments', $assignmentUpdate, ['assignment_id' => $request->assignment_id], $reqSet['key']);
                    if (!$updateAssign['status']) {
                        return ResponseHelper::moduleError('Assignment Update Failed', $updateAssign['message'], 400);
                    }

                    $updateAsset = Data::update($reqSet['system'], 'assets', $assetUpdate, ['asset_id' => $assignment->asset_id], $reqSet['key']);
                    if (!$updateAsset['status']) {
                        return ResponseHelper::moduleError('Asset Update Failed', $updateAsset['message'], 400);
                    }

                    // Log the return in asset_movements
                    $movement = [
                        'asset_id' => $assignment->asset_id,
                        'assignment_id' => $assignment->assignment_id,
                        'user_id' => $assignment->user_id,
                        'type' => 'returned',
                        'quantity' => $returnQty,
                        'available_quantity' => $assetUpdate['available_quantity'],
                        'notes' => $validated['return_notes'] ?? null,
                        'created_by' => Skeleton::getAuthenticatedUser()['id'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $assetMovement = Data::insert($reqSet['system'], 'asset_movements', $movement);
                    if (!$assetMovement['status']) {
                        return ResponseHelper::moduleError('Asset Movement Logging Failed', $assetMovement['message'], 400);
                    }

                    $reloadTable = true;
                    $title = 'Asset Returned Successfully';
                    $message = 'The asset return has been recorded successfully.';
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
                    if($validated['status'] == 'completed'){
                        $update = ['status' => 'available'];
                    }else{
                        $update = ['status' => 'under_maintenance'];
                    }
                    $assetData = Data::update($reqSet['system'], 'assets', $update, ['asset_id'=> $validated['asset_id']], $reqSet['key']);
                    if (!$assetData['status']) {
                        return ResponseHelper::moduleError('Asset Status Update Failed', $assetData['message'], 400);
                    }  
                    $reloadTable = true;
                    $reloadCard = true;
                    $title = 'Asset Maintenance Updated';
                    $message = 'AssetManagement maintenance Updated successfully.';
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
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['updated_at'] = now();
                }
            // Update data in the database
            $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [$reqSet['act'] => $reqSet['id']], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated AssetManagement entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function bulk(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key']) || !isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Split update_ids into individual IDs
            $ids = array_filter(explode('@', $request->input('update_ids', '')));
            if (empty($ids)) {
                return response()->json(['status' => false, 'title' => 'Invalid Data', 'message' => 'No valid IDs provided for update.']);
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'AssetManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'AssetManagement_entities':
                    $validator = Validator::make($request->all(), [
                        'type' => 'required|in:data,unique,select,other',
                        'status' => 'required|in:active,inactive',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadTable = true;
                    $title = 'Entities Updated';
                    $message = 'AssetManagement entities configuration updated successfully.';
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
                    $validated['updated_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['updated_at'] = now();
                }
            // Update data in the database
            $result = Data::update($reqSet['system'], $reqSet['table'], $validated, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}