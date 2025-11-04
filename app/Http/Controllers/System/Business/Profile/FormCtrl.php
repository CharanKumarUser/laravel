<?php
namespace App\Http\Controllers\System\Business\Profile;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{ResponseHelper, Helper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
/**
 * Controller for saving new Profile entities.
 */
class FormCtrl extends Controller
{
    protected $google2fa;
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    /**
     * Saves new Profile entity data based on validated input.
     *
     * @param Request $request HTTP request with form data and token
     * @return JsonResponse Success or error message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize variables
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'Profile data saved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            // Common data retrieval
            $user = Skeleton::authUser();
            $system = Skeleton::authUser('system');
            if (!$user) {
                return ResponseHelper::moduleError('Unauthorized', 'User not authenticated.', 401);
            }
            switch ($reqSet['key']) {
                case 'open_two_factor_enable':
                    $validator = Validator::make($request->all(), [
                        'code' => 'required|string|max:6',
                    ]);
                    $store = false;
                    if ($validator->fails()) {
                        $result = [
                            'status'  => false,
                            'message' => $validator->errors()->first(),
                        ];
                        $title   = 'Validation Failed';
                        $message = $result['message'];
                        break;
                    }
                    $validated = $validator->validated();
                    $where = ['user_id' => $user->user_id];
                    if ($user->two_factor_via === 'app') {
                        // Verify app code
                        $newRecord = [
                        'two_factor_enabled' => true,
                        ];

                        $valid = $this->google2fa->verifyKey(
                            $user->two_factor_secret,
                            $validated['code']
                        );
                    } elseif ($user->two_factor_via === 'email') {
                        // Verify email OTP
                        $newRecord = [
                            'email_verification_enabled' => true,
                        ];

                        $valid = hash_equals($user->verification_token, $validated['code']);
                    } else {
                        $valid = false;
                        $newRecord = [
                            'email_verification_enabled' => false,
                            'two_factor_enabled' => false
                        ];
                        
                    }
                    if (!$valid) {
                        $result = [
                            'status'  => false,
                            'message' => 'The two-factor authentication code you entered is incorrect.',
                        ];
                        $title   = 'Invalid Code';
                        $message = $result['message'];
                        break;
                    }

                    
                       $singleJson = $user->settings ?? '';
                        // Update JSON with new notification preferences
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'update',
                            null,
                            null
                        );


                    // If valid, enable 2FA
                    $recoveryCodes = collect(range(1, 8))
                        ->mapWithKeys(fn() => [Str::random(16) => false])
                        ->toArray();
                    $tfData = [
                        'two_factor'                  => 'enabled',
                        'two_factor_confirmed_at'    => now(),
                        'two_factor_recovery_codes'  => json_encode($recoveryCodes),
                        'verification_token'         => null,
                        'settings'                   => $result,
                        'updated_at'                 => now(),
                    ];
                    $updateResult = Data::update($system, 'users', $tfData, $where, $token);
                    if (!$updateResult) {
                        $result = [
                            'status'  => false,
                            'message' => 'Failed to enable two-factor authentication. Please try again later.',
                        ];
                        $title   = 'Update Failed';
                        $message = $result['message'];
                        break;
                    }
                    $result = [
                        'status'  => true,
                        'data'    => [
                            'id'             => $user->user_id,
                            'recovery_codes' => $recoveryCodes,
                        ],
                        'message' => 'Two-factor authentication has been enabled successfully.',
                    ];
                    $reloadPage = true;
                    $title      = '2FA Enabled';
                    $message    = $result['message'];
                    break;
                case 'open_profile_edit':
                    $accountnumber = $reqSet['id'] ?? '';
                    $singleJson = $request->input('existing_json') ?? '';
                    $validated = $request->validate([
                        'bank_name' => ['required', 'string', 'max:255'],
                        'account_number' => ['required', 'string', 'max:255'],
                        'ifsc_code' => ['nullable', 'string', 'max:255'],
                        'account_type' => ['nullable', 'string', 'max:255'],
                        'branch' => ['nullable', 'string', 'max:255'],
                        'city' => ['nullable', 'string', 'max:255'],
                    ]);
                    // New record to update
                    $newRecord = [
                        'bank_name' => $validated['bank_name'],
                        'account_number' => $validated['account_number'],
                        'ifsc_code' => $validated['ifsc_code'],
                        'account_type' => $validated['account_type'],
                        'branch' => $validated['branch'],
                        'city' => $validated['city'],
                    ];
                    // Update specific record using account_number as identifier
                    $result = Helper::modifyJson(
                        $singleJson,
                        $newRecord,
                        'update',
                        'account_number',
                        $accountnumber
                    );
                    $userinfo = [
                        'bank_info' => $result,
                    ];
                    $userInfoWhere = ['user_id' => $user->user_id, 'is_active' => 1];
                    $userInfoResult = Data::update($system, 'user_info', $userinfo, $userInfoWhere, $token);
                    if (!$userInfoResult['status']) {
                        return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                    }
                    $store = false;
                    $reloadPage = true;
                    $title = 'Profile Updated';
                    $message = 'Your profile has been updated successfully.';
                    $result = ['status' => true, 'data' => ['id' => $user->user_id]];
                    break;
                case 'open_profile_settings':

                    $type = $reqSet['id'] ?? ''; // Use request input for type
                    if ($type == 'notificationpreference') {
                        $validated = $request->validate([
                            'email_notifications' => ['nullable', 'string', 'max:255'],
                            'sms_notifications' => ['nullable', 'string', 'max:255'],
                            'push_notifications' => ['nullable', 'string', 'max:255'],
                        ]);
                        // Convert string values to boolean
                        $newRecord = [
                            'email_notifications' => filter_var($validated['email_notifications'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'sms_notifications' => filter_var($validated['sms_notifications'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'push_notifications' => filter_var($validated['push_notifications'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                        // Get existing JSON from user settings, with fallback to default JSON
                        $singleJson = $user->settings ?? json_encode([
                            'allow_fcm' => false,
                            'max_login_limit' => 5,
                            'auto_logout_on_password_change' => false,
                            'allow_logout_all_devices' => false,
                            'two_factor_enabled' => false,
                            'email_verification_enabled' => false,
                            'social_logins' => ['google' => false, 'facebook' => false, 'github' => false, 'x' => false],
                            'session_timeout_minutes' => 30,
                            'failed_login_attempts_limit' => 3,
                            'lockout_duration_minutes' => 5,
                            'password_rotation_days' => 90,
                            'ip_whitelist' => [],
                            'rate_limit_attempts' => 10,
                            'rate_limit_window_seconds' => 60,
                            'secure_session_token' => true,
                            'email_notifications' => false,
                            'sms_notifications' => false,
                            'push_notifications' => false,
                            'language' => 'en',
                            'allow_marketing' => false,
                            'profile_visibility' => 'public',
                            'timezone' => 'UTC',
                            'date_format' => 'd-m-Y',
                            'theme' => 'light'
                        ]);
                        // Update JSON with new notification preferences
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'update',
                            null,
                            null
                        );
                        // Validate JSON result
                        if (json_decode($result, true) === null) {
                            return ResponseHelper::moduleError('Invalid JSON generated for settings', 'Failed to encode settings JSON', 400);
                        }
                        // Update user settings in the database
                        $userinfo = [
                            'settings' => $result,
                        ];
                        $userInfoWhere = ['user_id' => $user->user_id];
                        $userInfoResult = Data::update($system, 'users', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('Notification Settings Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Notification Settings Updated';
                        $message = 'Your notification preferences have been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $user->user_id]];
                    } else if ($type === 'securitysettings') {
    $validated = $request->validate([
        'allow_marketing' => ['nullable', 'string', 'max:255'],
        'allow_fcm' => ['nullable', 'string', 'max:255'],
        'auto_logout_on_password_change' => ['nullable', 'string', 'max:255'],
        'allow_logout_all_devices' => ['nullable', 'string', 'max:255'],
        'profile_visibility' => ['nullable', 'string', 'max:255'],
        'max_login_limit' => ['nullable', 'string', 'max:255'],
        'session_timeout_minutes' => ['nullable', 'string', 'max:255'],
        'push_notifications' => ['nullable', 'string', 'max:255'],
        'failed_login_attempts_limit' => ['nullable', 'string', 'max:255'],
        'lockout_duration_minutes' => ['nullable', 'string', 'max:255'],
        'password_rotation_days' => ['nullable', 'string', 'max:255'],
        'rate_limit_attempts' => ['nullable', 'string', 'max:255'],
        'rate_limit_window_seconds' => ['nullable', 'string', 'max:255'],
    ]);

    $newRecord = [
        'allow_marketing' => filter_var($validated['allow_marketing'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'allow_fcm' => filter_var($validated['allow_fcm'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'auto_logout_on_password_change' => filter_var($validated['auto_logout_on_password_change'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'allow_logout_all_devices' => filter_var($validated['allow_logout_all_devices'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'push_notifications' => filter_var($validated['push_notifications'] ?? false, FILTER_VALIDATE_BOOLEAN),

        'profile_visibility' => $validated['profile_visibility'] ?? 'private',
        'max_login_limit' => (int) ($validated['max_login_limit'] ?? 0),
        'session_timeout_minutes' => (int) ($validated['session_timeout_minutes'] ?? 0),
        'failed_login_attempts_limit' => (int) ($validated['failed_login_attempts_limit'] ?? 0),
        'lockout_duration_minutes' => (int) ($validated['lockout_duration_minutes'] ?? 0),
        'password_rotation_days' => (int) ($validated['password_rotation_days'] ?? 0),
        'rate_limit_attempts' => (int) ($validated['rate_limit_attempts'] ?? 0),
        'rate_limit_window_seconds' => (int) ($validated['rate_limit_window_seconds'] ?? 0),
    ];

    $singleJson = $user->settings ?? '';
    $result = Helper::modifyJson(
        $singleJson,
        $newRecord,
        'update',
        null,
        null
    );

    $userinfo = ['settings' => $result];
    $userInfoWhere = ['user_id' => $user->user_id];
    $userInfoResult = Data::update($system, 'users', $userinfo, $userInfoWhere, $token);

    if (!$userInfoResult['status']) {
        return ResponseHelper::moduleError(
            'Security Settings Update Failed',
            $userInfoResult['message'],
            400
        );
    }

    $store = false;
    $reloadPage = true;
    $title = 'Security Settings Updated';
    $message = 'Your security preferences have been updated successfully.';
    $result = ['status' => true, 'data' => ['id' => $user->user_id]];
}else if ($type == 'sociallogins') {
                        $validated = $request->validate([
                            'social_logins' => ['nullable', 'array'],
                            'social_logins.google' => ['nullable', 'string', 'max:255'],
                            'social_logins.facebook' => ['nullable', 'string', 'max:255'],
                            'social_logins.github' => ['nullable', 'string', 'max:255'],
                            'social_logins.x' => ['nullable', 'string', 'max:255'],
                        ]);
                        // Convert string values to boolean for social_logins
                        $socialLogins = [
                            'google' => filter_var($validated['social_logins']['google'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'facebook' => filter_var($validated['social_logins']['facebook'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'github' => filter_var($validated['social_logins']['github'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'x' => filter_var($validated['social_logins']['x'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                        // Prepare new record for JSON update
                        $newRecord = [
                            'social_logins' => $socialLogins,
                        ];
                        // Get existing JSON from user settings
                        $singleJson = $user->settings ?? json_encode($user->settings);
                        // Update JSON with new social login preferences
                        $result = Helper::modifyJson(
                            $singleJson,
                            $newRecord,
                            'update',
                            null,
                            null
                        );
                        // Update user settings in the database
                        $userinfo = [
                            'settings' => $result,
                        ];
                        $userInfoWhere = ['user_id' => $user->user_id];
                        $userInfoResult = Data::update($system, 'users', $userinfo, $userInfoWhere, $token);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('Social Logins Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Social Logins Updated';
                        $message = 'Your social login preferences have been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $user->user_id]];
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
            if ($store) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
                // Insert data
                $result = Data::insert('central', $reqSet['table'], $validated);
            }
            // Generate response
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}
