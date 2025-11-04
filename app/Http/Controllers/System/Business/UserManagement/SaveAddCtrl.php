<?php
namespace App\Http\Controllers\System\Business\UserManagement;
use App\Facades\{Data, Developer, FileManager, Notification, Random, Skeleton, Scope};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator, Cache};
/**
 * Controller for saving new UserManagement entities.
 */
class SaveAddCtrl extends Controller
{
    /**
     * Saves new UserManagement entity data based on validated input.
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
            $message = 'UserManagement record added successfully.';
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
                    $validated['role_id'] = Random::unique(3, 'USRL');
                    $reloadCard = true;
                    $reloadTable = true;
                    $store = false;
       
                    $usersRole = Data::insert($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
                    if (!$usersRole['status']) {
                            throw new Exception('Failed to create Role: ' . ($usersRole['message'] ?? 'Unknown error'), 400);
                    }
                    $title = 'Success';
                    $message = 'New Role created successfully.';
                    $result = ['status' => true, 'data' => ['id' => $validated['role_id']]];

                    break;
                case 'open_scope_mapping':
                case 'open_um_users':
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
                            'designation_id' => ['required', 'string', 'max:20'],
                            'username'       => ['required', 'string', 'max:255'],
                            'role_id'        => ['required', 'string', 'max:20'],
                            'unique_code'    => ['nullable', 'string', 'max:50'],
                            'sno'            => ['required', 'string', 'max:20'],
                            'scope_id'       => ['required', 'string', 'max:255'],
                            'scope_data'     => ['nullable'],
                        ]);
                        $reloadTable = true;
                        $reloadCard = true;
                        $checks = [
                            ['table' => 'users', 'field' => 'email', 'value' => $validated['email'], 'message' => 'The email already exists'],
                            ['table' => 'users', 'field' => 'sno', 'value' => $validated['sno'], 'message' => 'The SNO already exists'],
                            ['table' => 'users', 'field' => 'username', 'value' => $validated['username'], 'message' => 'The Username already exists'],
                            ['table' => 'user_info', 'field' => 'unique_code', 'value' => $validated['unique_code'] ?? null, 'message' => 'The Unique Code already exists'],
                            ['table' => 'user_info', 'field' => 'phone', 'value' => $validated['phone'] ?? null, 'message' => 'The phone Number already exists'],
                        ];

                        foreach ($checks as $c) {
                            if (!empty($c['value'])) {
                                foreach (['central', 'business'] as $db) {
                                    $check = Data::fetch($db, $c['table'], [$c['field'] => $c['value']]);
                                    if ($check['status'] && count($check['data']) > 0) {
                                        throw new Exception("Failed to create user: {$c['message']}");
                                    }
                                }
                            }
                        }
                        // Get system and user context
                        $system = Skeleton::getUserSystem();
                        $businessId = Skeleton::authUser()->business_id;
                        $authUserId = Skeleton::authUser()->user_id;
                        $userID = Random::uniqueId('U', '14', true);
                        $randomPassword = Random::uniqueId('P', '5');
                        $bycriptPassword =bcrypt($randomPassword);
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
                        // Create user entry
                        $usersData = [
                            'sno'        => $validated['sno'],
                            'user_id'    => $userID,
                            'business_id' => $businessId,
                            'designation_id' => $validated['designation_id'],
                            'company_id' => $validated['company_id'],
                            'first_name' => $validated['first_name'],
                            'last_name'  => $validated['last_name'],
                            'email'      => $validated['email'],
                            'username'   => $validated['username'] ?? null,
                            'password'   => $bycriptPassword,
                            'profile'    => $fileId,
                            'scope_id' => $validated['scope_id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                       
                        $usersResult = Data::insert($system, 'users', $usersData, $token);    
                        if (!$usersResult['status']) {
                            throw new Exception('Failed to create user: ' . ($usersResult['message'] ?? 'Unknown error'), 400);
                        }
                        $centralUser = Data::insert('central', 'users', $usersData, $token);    
                        if (!$centralUser['status']) {
                            throw new Exception('Failed to create user: ' . ($centralUser['message'] ?? 'Unknown error'), 400);
                        }
                        Notification::mail(
                            'send_password_mail',
                            $validated['email'],
                            ['password' => $randomPassword, 'username' => $validated['username']],
                            [],
                            'high'
                        );
                        
                        // Create user_role entry
                        $roleData = [
                            'user_id'    => $userID,
                            'role_id' => $validated['role_id'] ?? null,
                            'valid_from' => now(),
                            'is_active'    => 1,
                            'created_by'   => $authUserId,
                            'created_at'   => now(),
                            'updated_at' => now(),
                        ];
                        $roleResult = Data::insert($system, 'user_roles', $roleData, $token);
                        if (!$roleResult['status']) {
                            throw new Exception('Failed to create user: ' . ($roleResult['message'] ?? 'Unknown error'), 400);
                        }
                        // Create user_info entry
                        $userInfoData = [
                            'user_id'      => $userID,
                            'unique_code'  => $validated['unique_code'] ?? null,
                            'phone'        => $validated['phone'] ?? null,
                            'alt_phone'    => $validated['alt_phone'] ?? null,
                            'gender'       => $validated['gender'] ?? null,
                            'date_of_birth' => $validated['date_of_birth'] ?? null,
                            'hire_date'    => $validated['hire_date'] ?? null,
                            'is_active'    => 1,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                        $userInfoResult = Data::insert($system, 'user_info', $userInfoData, $token);

                        if (!$userInfoResult['status']) {
                            throw new Exception('Failed to create user info: ' . ($userInfoResult['message'] ?? 'Unknown error'), 400);
                        }
                        // Handle scope_mapping and scope_data
                        if (!empty($validated['scope_id'])) {
                            $reloadTable = $validated['scope_id'];
                            $reloadCard = $validated['scope_id'];
                            // Create scope_mapping entry
                            $scopeUsers = [
                                'user_id'      => $userID,
                                'scope_id'     => $validated['scope_id'],
                                'created_by'   => $authUserId,
                                'created_at'   => now(),
                            ];
                            $scopeUsersResult = Data::insert($system, 'scope_mapping', $scopeUsers, $token);
                            if (!$scopeUsersResult['status']) {
                                throw new Exception('Failed to create scope user: ' . ($scopeUsersResult['message'] ?? 'Unknown error'), 400);
                            }
                            // Process scope_data for each scope_id
                            if (!empty($validated['scope_data'])) {
                                // Create scope_data entry
                                $scopeStringData = json_decode($validated['scope_data'], true);
                                $scopeData = [
                                    'user_id'      => $userID,
                                    'scope_id'     => $validated['scope_id'],
                                    'data'         => json_encode($scopeStringData),
                                    'schema'       => json_encode($scopeStringData),
                                    'snap'         => json_encode($scopeStringData),
                                    'version'      => '1',
                                    'is_active'    => 1,
                                    'created_by'   => $authUserId,
                                    'created_at'   => now(),
                                ];
                                $scopeDataResult = Data::insert($system, 'scope_data', $scopeData, $token);
                                if (!$scopeDataResult['status']) {
                                    throw new Exception('Failed to create scope data for scope ID ' . $validated['scope_id'] . ': ' . ($scopeDataResult['message'] ?? 'Unknown error'), 400);
                                }
                            }
                        }
                        $cacheKey = "users_{$businessId}_set";
                        Cache::forget($cacheKey);
                        // Final response
                        $store = false;
                        $reloadCard=true;
                        $reloadTable=true;
                        $title = 'User Added';
                        $message = 'The new central user has been created successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userID]];
                    } catch (Exception $e) {
                        return ResponseHelper::moduleError('Ooops!', $e->getMessage(), $e->getCode() ?: 400);
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
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
                // Insert data into the database
                $result = Data::insert($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
            }
            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
