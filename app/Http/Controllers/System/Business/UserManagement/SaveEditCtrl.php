<?php
namespace App\Http\Controllers\System\Business\UserManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{ResponseHelper, Helper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator, Hash, Cache};
/**
 * Controller for saving updated UserManagement entities.
 */
class SaveEditCtrl extends Controller
{
    /**
     * Saves updated UserManagement entity data based on validated input.
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
            if (!isset($reqSet['key']) || !isset($reqSet['act']) || !isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'UserManagement record updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_um_roles':
                    $validator = Validator::make($request->all(), [
                        'sno'                => ['required', 'string', 'max:10'],
                        'name'               => ['required', 'string', 'max:255'],
                        'parent_role_id'     => ['nullable', 'string', 'max:255'],
                        'is_system_role'     => ['required', 'string', 'max:255'],
                        'is_active'          => ['required', 'string', 'max:255'],
                        'description'        => ['nullable', 'string', 'max:255'],
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $reloadCard = true;
                    $reloadTable = true;
                    $title = '';
                    $message = 'Role Updated successfully.';
                    break;
                case 'open_scope_mapping':
                case 'open_um_users':
                case 'open_um_admins':
                    try {
                        // Validate request fields
                        $validated = $request->validate([
                            'first_name'     => ['required', 'string', 'max:255'],
                            'last_name'      => ['required', 'string', 'max:255'],
                            'email'          => ['required', 'email', 'max:255'],
                            'phone'          => ['nullable', 'string', 'max:20'],
                            'alt_phone'      => ['nullable', 'string', 'max:20'],
                            'bio'            => ['nullable', 'string', 'max:1000'],
                            'date_of_birth'  => ['nullable', 'date', 'before:today'],
                            'hire_date'      => ['nullable', 'date', 'before_or_equal:today'],
                            'gender'         => ['nullable', 'in:male,female,non_binary,other,prefer_not_to_say'],
                            'username'       => ['nullable', 'string', 'max:255'],
                            'role_id'        => ['required', 'string', 'max:20'],
                            'unique_code'    => ['nullable', 'string', 'max:50'],
                            'sno'            => ['required', 'string', 'max:20'],
                            'scope_id'       => ['nullable', 'string', 'max:255'],
                            'scope_data'     => ['nullable'],
                        ]);
                        $reloadTable = true;
                        $reloadCard = true;
                        // Get system and user context
                        $system = Skeleton::getUserSystem();
                        $businessId = Skeleton::authUser()->business_id;
                        $authUserId = Skeleton::authUser()->user_id;
                        // Upload profile photo (if provided)
                        $fileId = null;
                        if ($request->hasFile('profile_photo')) {
                            $folderKey = $system . '_profiles';
                            $fileResult = FileManager::saveFile($request, $folderKey, 'profile_photo', 'Profile', $businessId, false);
                            if (!$fileResult['status']) {
                                throw new Exception('Failed to upload profile photo: ' . ($fileResult['message'] ?? 'Unknown error'), 400);
                            }
                            $fileId = $fileResult['data']['file_id'];
                            $reloadPage = true;
                        }
                        $validated['company_id']= Scope::getMostParentScope($validated['scope_id'])['scope_id'];
                        \Log::info($validated['company_id']);
                        // Create user entry
                        $usersData = [
                            'sno'        => $validated['sno'],
                            'business_id' => $businessId,
                            'company_id' => $validated['company_id'],
                            'first_name' => $validated['first_name'],
                            'last_name'  => $validated['last_name'],
                            'email'      => $validated['email'],
                            'username'   => $validated['username'] ?? null,
                            'profile'    => $fileId,
                            'scope_id' => $validated['scope_id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $usersResult = Data::update($system, 'users', $usersData, [['column' => 'user_id', 'value' => $reqSet['id']]], $token);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, [['column' => 'user_id', 'value' => $reqSet['id']]], $token);
                        }
                        if (!$usersResult['status']) {
                            throw new Exception('Failed to create user: ' . ($usersResult['message'] ?? 'Unknown error'), 400);
                        }
                        // Create user_role entry
                        $roleData = [
                            'role_id' => $validated['role_id'] ?? null,
                            'valid_from' => now(),
                            'is_active'    => 1,
                            'created_by'   => $authUserId,
                            'created_at'   => now(),
                            'updated_at' => now(),
                        ];
                        $roleResult = Data::update($system, 'user_roles', $roleData, [['column' => 'user_id', 'value' => $reqSet['id']]], $token);
                        if (!$roleResult['status']) {
                            throw new Exception('Failed to create user: ' . ($roleResult['message'] ?? 'Unknown error'), 400);
                        }
                        // Prepare user info data
                        $userInfoData = [
                            'unique_code'   => $validated['unique_code'] ?? null,
                            'phone'         => $validated['phone'] ?? null,
                            'alt_phone'     => $validated['alt_phone'] ?? null,
                            'gender'        => $validated['gender'] ?? null,
                            'date_of_birth' => $validated['date_of_birth'] ?? null,
                            'hire_date'     => $validated['hire_date'] ?? null,
                            'is_active'     => 1,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                        // Check if user_info exists
                        $fetchResult = Data::fetch($system, 'user_info', ['user_id' => $reqSet['id']]);
                        if (!empty($fetchResult['data'])) {
                            // Exists → update
                            $userInfoResult = Data::update(
                                $system,
                                'user_info',
                                $userInfoData,
                                [['column' => 'user_id', 'value' => $reqSet['id']]],
                                $token
                            );
                            if (!$userInfoResult['status']) {
                                throw new Exception('Failed to update user info: ' . ($userInfoResult['message'] ?? 'Unknown error'), 400);
                            }
                        } else {
                            // Not exists → insert
                            $userInfoData['user_id'] = $reqSet['id']; // make sure to include user_id
                            $userInfoResult = Data::insert($system, 'user_info', $userInfoData, $token);
                            if (!$userInfoResult['status']) {
                                throw new Exception('Failed to create user info: ' . ($userInfoResult['message'] ?? 'Unknown error'), 400);
                            }
                        }
                        // Handle scope_mapping and scope_data
                        if (!empty($validated['scope_id'])) {
                            $scopeId = $validated['scope_id'];
                            // ======== scope_mapping ========
                            $scopeUsers = [
                                'scope_id'   => $scopeId,
                                'created_by' => $authUserId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            // Check if scope_mapping exists
                            $fetchScopeMapping = Data::fetch($system, 'scope_mapping', ['user_id' => $reqSet['id']]);
                            if (!empty($fetchScopeMapping['data'])) {
                                // Exists → update
                                $scopeUsersResult = Data::update(
                                    $system,
                                    'scope_mapping',
                                    $scopeUsers,
                                    [['column' => 'user_id', 'value' => $reqSet['id']]],
                                    $token
                                );
                                if (!$scopeUsersResult['status']) {
                                    throw new Exception('Failed to update scope mapping: ' . ($scopeUsersResult['message'] ?? 'Unknown error'), 400);
                                }
                            } else {
                                // Not exists → insert
                                $scopeUsers['user_id'] = $reqSet['id'];
                                $scopeUsersResult = Data::insert($system, 'scope_mapping', $scopeUsers, $token);
                                if (!$scopeUsersResult['status']) {
                                    throw new Exception('Failed to create scope mapping: ' . ($scopeUsersResult['message'] ?? 'Unknown error'), 400);
                                }
                            }
                            // ======== scope_data ========
                            if (!empty($validated['scope_data'])) {
                                $scopeStringData = json_decode($validated['scope_data'], true);
                                $scopeData = [
                                    'scope_id'   => $scopeId,
                                    'data'       => json_encode($scopeStringData),
                                    'schema'     => json_encode($scopeStringData),
                                    'snap'       => json_encode($scopeStringData),
                                    'version'    => '1',
                                    'is_active'  => 1,
                                    'created_by' => $authUserId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                                // Check if scope_data exists
                                $fetchScopeData = Data::fetch($system, 'scope_data', ['user_id' => $reqSet['id']]);
                                if (!empty($fetchScopeData['data'])) {
                                    // Exists → update
                                    $scopeDataResult = Data::update(
                                        $system,
                                        'scope_data',
                                        $scopeData,
                                        [['column' => 'user_id', 'value' => $reqSet['id']]],
                                        $token
                                    );
                                    if (!$scopeDataResult['status']) {
                                        throw new Exception('Failed to update scope data for scope ID ' . $scopeId . ': ' . ($scopeDataResult['message'] ?? 'Unknown error'), 400);
                                    }
                                } else {
                                    // Not exists → insert
                                    $scopeData['user_id'] = $reqSet['id'];
                                    $scopeDataResult = Data::insert($system, 'scope_data', $scopeData, $token);
                                    if (!$scopeDataResult['status']) {
                                        throw new Exception('Failed to create scope data for scope ID ' . $scopeId . ': ' . ($scopeDataResult['message'] ?? 'Unknown error'), 400);
                                    }
                                }
                            }
                        }
                        $cacheKey = "users_{$businessId}_set";
                        Cache::forget($cacheKey);
                        // Final response
                        $store = false;
                        $reloadCard = true;
                        $reloadTable = true;
                        $title = 'User Updated';
                        $message = 'The user has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $reqSet['id']]];
                    } catch (Exception $e) {
                        return ResponseHelper::moduleError('Ooops!', $e->getMessage(), $e->getCode() ?: 400);
                    }
                    break;
                case 'open_um_role_permissions':
                    // Validate role permission-related fields
                    $validator = Validator::make($request->all(), [
                        'permission_ids' => 'required|json',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    // Decode permission_ids JSON
                    $permissionIds = json_decode($validated['permission_ids'], true);
                    if (!is_array($permissionIds)) {
                        return ResponseHelper::moduleError('Invalid Data', 'Permission IDs must be a valid JSON array.');
                    }
                    // Fallback to route param if role_id not passed in form
                    $roleId = trim($reqSet['id']);
                    // Determine business context
                    $businessId = $validated['business_id'] ?? null;
                    $isCentral = strtoupper($businessId) === 'CENTRAL' || empty($businessId);
                    if ($isCentral) {
                        Skeleton::managePermissions('role', $roleId, $permissionIds, null);
                    } else {
                        Skeleton::managePermissions('role', $roleId, $permissionIds, $businessId);
                    }
                    return response()->json([
                        'status'       => true,
                        'title'        => 'Role Permissions Updated',
                        'message'      => 'Role permissions updated successfully.',
                        'reload_table' => true,
                        'reload_card'  => false,
                        'token'        => $reqSet['token'],
                    ]);
                    break;
                // Handle user permission updates
                case 'open_um_user_permissions':
                    // Validate user permission-related fields
                    $validator = Validator::make($request->all(), [
                        'permission_ids' => 'nullable|json'
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                    }
                    $validated = $validator->validated();
                    $permissionIds = json_decode($validated['permission_ids'], true);
                    if (!is_array($permissionIds)) {
                        return ResponseHelper::moduleError('Invalid Data', 'Permission IDs must be a valid JSON array.');
                    }
                    $userId = trim($reqSet['id']);
                    Skeleton::managePermissions('user', $userId, $permissionIds, null);
                    return response()->json([
                        'status' => true,
                        'title' => 'User Permissions Updated',
                        'message' => 'User permissions updated successfully.',
                        'reload_table' => true,
                        'reload_card' => false,
                        'token' => $reqSet['token'],
                    ]);
                    break;
                case 'open_um':
                    $userId = $request->input('user_id') ?? '';
                    $system = Skeleton::authUser('system');
                    $userParams = [
                        'select' => ['user_id', 'business_id', 'username', 'email', 'password', 'first_name', 'last_name', 'settings', 'email_verified_at', 'two_factor_confirmed_at', 'last_password_changed_at', 'last_login_at', 'account_status'],
                        'where' => [['column' => 'user_id', 'value' => $userId]]
                    ];
                    $userResult = Data::query($system, 'users', $userParams, '1');
                    if (!$userResult['status'] || !$userResult['data']) {
                        return ResponseHelper::moduleError('User Fetch Failed', $userResult['message'] ?? 'Authenticated user data not found.', 404);
                    }
                    $userInfoParams = [
                        'select' => ['phone', 'alt_phone', 'job_title', 'department', 'portfolio_url', 'bio'],
                        'where' => [['column' => 'user_id', 'value' => $userId]]
                    ];
                    $userInfoResult = Data::query($system, 'user_info', $userInfoParams, '1');
                    $userData = (object) $userResult['data'][0];
                    $type = $request->input('type') ?? '';
                    if ($type == 'bio') {
                        $validated = $request->validate([
                            'bio' => ['required', 'string']
                        ]);
                        $userinfo = [
                            'bio' =>  $validated['bio'],
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'skills') {
                        $validated = $request->validate([
                            'skills' => ['required', 'string']
                        ]);
                        $userinfo = [
                            'skills' => $validated['skills'],
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id]
                        ];
                    } else if ($type == 'basicinfo') {
                        $validated = $request->validate([
                            'phone'     => ['required', 'string', 'max:255'],
                            'email'      => ['required', 'email', 'max:255'],
                            'alt_phone'          => ['nullable', 'string', 'max:255'],
                            'alt_email'          => ['nullable', 'email', 'max:255'],
                            'gender'      => ['required', 'string', 'max:255'],
                            'date_of_birth'      => ['nullable', 'string', 'max:255'],
                            'nationality'     => ['nullable', 'string', 'max:255'],
                        ]);
                        // Update `users` table
                        $usersData = [
                            'email' => $validated['email'],
                            'updated_at' => now()
                        ];
                        $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $usersResult = Data::update($system, 'users', $usersData, $usersWhere, $token);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, $usersWhere, $token);
                        }
                        if (!$usersResult['status']) {
                            return ResponseHelper::moduleError('User Update Failed', $usersResult['message'], 400);
                        }
                        // Update `user_info` table
                        $userInfoData = [
                            'phone'         => $validated['phone'] ?? null,
                            'alt_phone'     => $validated['alt_phone'] ?? null,
                            'alt_email'     => $validated['alt_email'] ?? null,
                            'gender'    => $validated['gender'] ?? null,
                            'date_of_birth' => $validated['date_of_birth'] ?? null,
                            'nationality'     => $validated['nationality'] ?? null,
                            'updated_at'    => now()
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userInfoData, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'address') {
                        $validated = $request->validate([
                            'address_line1'     => ['required', 'string', 'max:255'],
                            'address_line2'      => ['nullable', 'string', 'max:255'],
                            'city'          => ['nullable', 'string', 'max:255'],
                            'state'          => ['nullable', 'string', 'max:255'],
                            'postal_code'      => ['nullable', 'string', 'max:255'],
                            'country'      => ['nullable', 'string', 'max:255'],
                        ]);
                        // Update `users` table
                        $usersData = [
                            'address_line1' => $validated['address_line1'],
                            'address_line2' => $validated['address_line2'],
                            'city' => $validated['city'],
                            'state' => $validated['state'],
                            'postal_code' => $validated['postal_code'],
                            'country' => $validated['country'],
                            'updated_at' => now()
                        ];
                        $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $usersResult = Data::update($system, 'user_info', $usersData, $usersWhere, $token);
                        if (!$usersResult['status']) {
                            return ResponseHelper::moduleError('User Update Failed', $usersResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'educationadd') {
                        $singleJson = $request->input('existing_json') ?? '';
                        $validated = $request->validate([
                            'university' => ['required', 'string', 'max:255'],
                            'degree'     => ['required', 'string', 'max:255'],
                            'start_year' => ['nullable', 'string', 'max:255'],
                            'end_year'   => ['nullable', 'string', 'max:20'],
                        ]);
                        // This is the new record to append
                        $newRecord = [
                            'university' => $validated['university'],
                            'degree'     => $validated['degree'],
                            'start_year' => $validated['start_year'],
                            'end_year'   => $validated['end_year'],
                        ];
                        // Append the new university
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'add'
                        );
                        $userinfo = [
                            'education' =>  $result,
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'educationedit') {
                        $education = $request->input('education');
                        // Optional: Validate the input structure
                        foreach ($education as $index => $entry) {
                            $validator = Validator::make($entry, [
                                'university' => ['required', 'string', 'max:255'],
                                'degree'     => ['required', 'string', 'max:255'],
                                'start_year' => ['nullable', 'date'],
                                'end_year'   => ['nullable', 'date'],
                            ]);
                            if ($validator->fails()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Validation failed for education entry #$index",
                                    'errors' => $validator->errors(),
                                ], 422);
                            }
                        }
                        $userinfo = [];
                        $userinfo['education'] = json_encode(array_values($education)); // force re-indexing
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'experienceadd') {
                        $singleJson = $request->input('existing_json') ?? '';
                        $validated = $request->validate([
                            'company' => ['required', 'string', 'max:255'],
                            'position'     => ['required', 'string', 'max:255'],
                            'start_date' => ['nullable', 'string', 'max:255'],
                            'end_date'   => ['nullable', 'string', 'max:20'],
                        ]);
                        // This is the new record to append
                        $newRecord = [
                            'company' => $validated['company'],
                            'position'     => $validated['position'],
                            'start_date' => $validated['start_date'],
                            'end_date'   => $validated['end_date'],
                        ];
                        // Append the new university
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'add'
                        );
                        $userinfo = [
                            'experience' =>  $result,
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'experienceedit') {
                        $experience = $request->input('experience', []);
                        if (!is_array($experience)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Invalid experience format.',
                            ], 400);
                        }
                        foreach ($experience as $index => $entry) {
                            $validator = Validator::make($entry, [
                                'company'    => ['required', 'string', 'max:255'],
                                'position'   => ['required', 'string', 'max:255'],
                                'start_date' => ['nullable', 'date'],
                                'end_date'   => ['nullable', 'date'],
                            ]);
                            if ($validator->fails()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Validation failed for Experience entry #" . ($index + 1),
                                    'errors' => $validator->errors(),
                                ], 422);
                            }
                        }
                        $userinfo = [
                            'experience' => json_encode(array_values($experience)), // clean reindex
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id],
                        ];
                    } else if ($type == 'sociallinks') {
                        $allowedPlatforms = ['linkedin', 'github', 'youtube', 'facebook', 'instagram', 'x'];
                        $socialLinks = [];
                        foreach ($allowedPlatforms as $platform) {
                            $inputKey = $platform . '_url';
                            $url = trim($request->input($inputKey));
                            if (!empty($url)) {
                                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                                    return response()->json([
                                        'status' => 'error',
                                        'message' => "Invalid URL for {$platform}.",
                                    ], 422);
                                }
                                $socialLinks[$platform] = $url;
                            }
                        }
                        $userInfoUpdate = [
                            'social_links' => json_encode($socialLinks),
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userInfoUpdate, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('Social Links Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Social Links Updated';
                        $message = 'Your social links have been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id],
                        ];
                    } else if ($type == 'emergency') {
                        $emergency = $request->input('emergency', []);
                        if (!is_array($emergency)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Invalid experience format.',
                            ], 400);
                        }
                        foreach ($emergency as $index => $entry) {
                            $validator = Validator::make($entry, [
                                'name'    => ['required', 'string', 'max:255'],
                                'relation'   => ['required', 'string', 'max:255'],
                                'phone' => ['nullable', 'string'],
                            ]);
                            if ($validator->fails()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Validation failed for Experience entry #" . ($index + 1),
                                    'errors' => $validator->errors(),
                                ], 422);
                            }
                        }
                        $userinfo = [
                            'emergency_info' => json_encode(array_values($emergency)), // clean reindex
                        ];
                        $userInfoWhere = [
                            'user_id' => $userData->user_id,
                            'is_active' => 1,
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id],
                        ];
                    } else if ($type == 'main') {
                        $validated = $request->validate([
                            'first_name'     => ['required', 'string', 'max:255'],
                            'last_name'      => ['required', 'string', 'max:255'],
                            'email'          => ['required', 'email', 'max:255'],
                            'phone'          => ['nullable', 'string', 'max:20'],
                            'alt_phone'      => ['nullable', 'string', 'max:20'],
                            'job_title'      => ['nullable', 'string', 'max:255'],
                            'department'     => ['nullable', 'string', 'max:255'],
                            'bio'            => ['nullable', 'string', 'max:1000'],
                            'profile_photo'  => ['nullable', 'file', 'image']
                        ]);
                        // Attempt to save uploaded profile photo
                        $fileId = null;
                        if ($request->hasFile('profile_photo')) {
                            $folderKey = $system . '_profiles';
                            $fileResult = FileManager::saveFile($request, $folderKey, 'profile_photo', 'Profile', $userData->business_id, false);
                            if ($fileResult['status']) {
                                $fileId = $fileResult['data']['file_id'];
                            }
                        }
                        // Update `users` table
                        $usersData = [
                            'first_name' => $validated['first_name'],
                            'last_name'  => $validated['last_name'],
                            'email'      => $validated['email'],
                            'updated_at' => now()
                        ];
                        if ($fileId) {
                            $usersData['profile'] = $fileId;
                        }
                        $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $usersResult = Data::update($system, 'users', $usersData, $usersWhere, $token);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, $usersWhere, $token);
                        }
                        if (!$usersResult['status']) {
                            return ResponseHelper::moduleError('User Update Failed', $usersResult['message'], 400);
                        }
                        // Update `user_info` table
                        $userInfoData = [
                            'user_id'       => $userData->user_id,
                            'phone'         => $validated['phone'] ?? null,
                            'alt_phone'     => $validated['alt_phone'] ?? null,
                            'job_title'     => $validated['job_title'] ?? null,
                            'department'    => $validated['department'] ?? null,
                            'bio'           => $validated['bio'] ?? null,
                            'is_active'     => 1,
                            'updated_at'    => now()
                        ];
                        $userInfoWhere = ['user_id' => $userData->user_id, 'is_active' => 1];
                        $userInfoResult = Data::update($system, 'user_info', $userInfoData, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'banner') {
                        $validated = $request->validate([
                            'cover_photo' => ['nullable', 'file', 'image'],
                        ]);
                        // Attempt to save uploaded cover photo
                        $fileId = null;
                        if ($request->hasFile('cover_photo')) {
                            $folderKey = $system . '_profile_covers';
                            $fileResult = FileManager::saveFile($request, $folderKey, 'cover_photo', 'Cover', $userData->business_id, false);
                            if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                                $fileId = $fileResult['data']['file_id'] ?? null;
                            }
                        }
                        if ($fileId) {
                            $usersData = [
                                'cover' => $fileId,
                            ];
                            $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                            $usersResult = Data::update($system, 'users', $usersData, $usersWhere, $token);
                            if ($system != "central") {
                                $usersResult = Data::update('central', 'users', $usersData, $usersWhere, $token);
                            }
                            if (empty($usersResult['status']) || $usersResult['status'] !== true) {
                                return ResponseHelper::moduleError(
                                    'User Update Failed',
                                    $usersResult['message'] ?? 'Unknown error',
                                    400
                                );
                            }
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Cover Image Updated';
                        $message = 'Your cover image has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => [
                                'id' => $userData->user_id,
                            ],
                        ];
                    } else if ($type == 'profilechange') {
                        $validated = $request->validate([
                            'profile_photo' => ['nullable', 'file', 'image'],
                        ]);
                        // Attempt to save uploaded profile photo
                        $fileId = null;
                        if ($request->hasFile('profile_photo')) {
                            $folderKey = $system . '_profiles';
                            $fileResult = FileManager::saveFile(
                                $request,
                                $folderKey,
                                'profile_photo',
                                'Profile',
                                $userData->business_id,
                                false
                            );
                            if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                                $fileId = $fileResult['data']['file_id'] ?? null;
                            }
                        }
                        if ($fileId) {
                            $usersData = [
                                'profile' => $fileId,
                            ];
                            $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                            $usersResult = Data::update($system, 'users', $usersData, $usersWhere, $token);
                            if ($system != "central") {
                                $usersResult = Data::update('central', 'users', $usersData, $usersWhere, $token);
                            }
                            if (empty($usersResult['status']) || $usersResult['status'] !== true) {
                                return ResponseHelper::moduleError(
                                    'User Update Failed',
                                    $usersResult['message'] ?? 'Unknown error',
                                    400
                                );
                            }
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Image Updated';
                        $message = 'Your profile image has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => [
                                'id' => $userData->user_id,
                            ],
                        ];
                    } else if ($type == 'bankadd') {
                        $singleJson = $request->input('existing_json') ?? '';
                        $validated = $request->validate([
                            'bank_name' => ['required', 'string', 'max:255'],
                            'account_number' => ['required', 'string', 'max:255'],
                            'ifsc_code' => ['nullable', 'string', 'max:255'],
                            'account_type' => ['nullable', 'string', 'max:255'],
                            'branch' => ['nullable', 'string', 'max:255'],
                            'city' => ['nullable', 'string', 'max:255'],
                        ]);
                        // Check if account_number already exists
                        if (!empty(trim($singleJson))) {
                            $data = json_decode($singleJson, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $data = array_is_list($data) ? $data : [$data]; // Ensure array
                                foreach ($data as $item) {
                                    if (isset($item['account_number']) && $item['account_number'] === $validated['account_number']) {
                                        return ResponseHelper::moduleError('Account Number Already Exists', 'The provided account number is already registered.', 400);
                                    }
                                }
                            }
                        }
                        // New record to append
                        $newRecord = [
                            'bank_name' => $validated['bank_name'],
                            'account_number' => $validated['account_number'],
                            'ifsc_code' => $validated['ifsc_code'],
                            'account_type' => $validated['account_type'],
                            'branch' => $validated['branch'],
                            'city' => $validated['city'],
                        ];
                        // Append the new record
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'add'
                        );
                        $userinfo = [
                            'user_id' => $userData->user_id,
                            'bank_info' => $result,
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere);
                        if ($system != "central") {
                            $userInfoResult = Data::update('central', 'user_info', $userinfo, $userInfoWhere);
                        }
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'User profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'bankedit') {
                        $banks = $request->input('bank', []);
                        if (!is_array($banks)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Invalid bank details format.',
                            ], 400);
                        }
                        foreach ($banks as $index => $entry) {
                            $validator = Validator::make($entry, [
                                'bank_name'      => ['nullable', 'string', 'max:255'],
                                'account_number' => ['nullable', 'string', 'max:50'],
                                'ifsc_code'      => ['nullable', 'string', 'max:20'],
                                'account_type'   => ['nullable'],
                                'branch'         => ['nullable', 'string', 'max:255'],
                                'city'           => ['nullable', 'string', 'max:255'],
                            ]);
                            if ($validator->fails()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Validation failed for Bank Detail entry #" . ($index + 1),
                                    'errors' => $validator->errors(),
                                ], 422);
                            }
                        }
                        $userinfo = [
                            'bank_info' => json_encode(array_values($banks)), // reindex for clean JSON
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Bank Details Updated';
                        $message = 'Your bank details have been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id],
                        ];
                    } else if ($type == 'changePassword') {
                        $validated = $request->validate([
                            'current_password' => ['required', 'string', 'min:6'],
                            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
                        ]);
                        // Check if current password is correct
                        if (!Hash::check($validated['current_password'], $userData->password)) {
                            return ResponseHelper::moduleError('Invalid Password', 'The current password you entered is incorrect.', 400);
                        }
                        // Update password
                        $usersData = [
                            'password'   => Hash::make($validated['new_password']),
                            'last_password_changed_at' => now(),
                            'updated_at' => now()
                        ];
                        $usersWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $usersResult = Data::update($system, 'users', $usersData, $usersWhere, $token);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, $usersWhere, $token);
                        }
                        if (!$usersResult['status']) {
                            return ResponseHelper::moduleError('Password Update Failed', $usersResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Password Changed';
                        $message = 'Your password has been changed successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'deleteaccount') {
                        $userinfo = [
                            'account_status' => 'inactive',
                            'deleted_at' => now(),
                        ];
                        $userInfoWhere = [['column' => 'user_id', 'value' => $userData->user_id]];
                        $userInfoResult = Data::update($system, 'users', $userinfo, $userInfoWhere, $token);
                        if ($system != "central") {
                            $userInfoResult = Data::update('central', 'users', $userinfo, $userInfoWhere, $token);
                        }
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('Account Deletion Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Account Deleted';
                        $message = 'Your account has been successfully deactivated and marked for deletion.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    }
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
                $result = Data::update('central', $reqSet['table'], $validated,  [['column' => $reqSet['act'], 'value' => $reqSet['id']]], $reqSet['key']);
            }
            // Return response based on update success
            return response()->json(['status' => $result > 0, 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result, 'title' => $result > 0 ? $title : 'Failed', 'message' => $result > 0 ? $message : 'No changes were made.']);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
    /**
     * Saves bulk updated UserManagement entity data based on validated input.
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
            $message = 'UserManagement records updated successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
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
