<?php
namespace App\Http\Controllers\System\Central\Profile;
use App\Facades\{Data, Developer, FileManager, Notification, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{ResponseHelper, Helper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Config, DB, Hash, Validator};
use PragmaRX\Google2FA\Google2FA;
/**
 * Controller for saving new Profile entities.
 */
class SaveAddCtrl extends Controller
{
    protected $google2fa;
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    /**
     * Saves new Profile entity data based on validated input.
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
            $message = 'Profile record added successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Common data retrieval
            $user = Skeleton::authUser();
            $system = Skeleton::authUser('system');
            if (!$user) {
                return ResponseHelper::moduleError('Unauthorized', 'User not authenticated.', 401);
            }
            $userParams = [
                'select' => [
                    'users.user_id',
                    'users.business_id',
                    'users.username',
                    'users.email',
                    'users.first_name',
                    'users.last_name',
                    'users.profile',
                    'users.cover',
                    'users.settings',
                    'users.email_verified_at',
                    'users.two_factor',
                    'users.two_factor_via',
                    'users.two_factor_confirmed_at',
                    'users.last_password_changed_at',
                    'users.last_login_at',
                    'users.account_status'
                ],
                'where' => [
                    ['column' => 'users.user_id', 'operator' => '=', 'value' => $user->user_id]
                ]
            ];
            $userResult = Data::query($system, 'users', $userParams);
            if (!$userResult['status']) {
                return ResponseHelper::moduleError('User Fetch Failed', $userResult['message'], 400);
            }
            $userInfoParams = [
                'select' => [
                    'user_info.unique_code',
                    'user_info.bio',
                    'user_info.gender',
                    'user_info.date_of_birth',
                    'user_info.nationality',
                    'user_info.marital_status',
                    'user_info.alt_email',
                    'user_info.phone',
                    'user_info.alt_phone',
                    'user_info.address_line1',
                    'user_info.address_line2',
                    'user_info.city',
                    'user_info.state',
                    'user_info.postal_code',
                    'user_info.country',
                    'user_info.latitude',
                    'user_info.longitude',
                    'user_info.job_title',
                    'user_info.department',
                    'user_info.hire_date',
                    'user_info.user_type',
                    'user_info.portfolio_url',
                    'user_info.social_links',
                    'user_info.skills',
                    'user_info.education',
                    'user_info.certifications',
                    'user_info.experience',
                    'user_info.emergency_info',
                    'user_info.bank_info',
                    'user_info.onboarding_status',
                    'user_info.onboarding_tasks',
                    'user_info.offboarding_date',
                    'user_info.is_active'
                ],
                'where' => [
                    ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $user->user_id],
                    ['column' => 'user_info.is_active', 'operator' => '=', 'value' => 1]
                ]
            ];
            $userInfoResult = Data::query($system, 'user_info', $userInfoParams);
            $userData = (object) ($userResult['data'][0] ?? []);
            $info = (object) ($userInfoResult['data'][0] ?? []);
            $type = $request->input('type') ?? '';
            switch ($reqSet['key']) {
                case 'open_profile_edit':
                    if ($type == 'firstform') {
                        $validated = $request->validate([
                            'phone'     => ['required', 'string', 'max:255'],
                            'alt_phone'          => ['nullable', 'string', 'max:255'],
                            'alt_email'          => ['nullable', 'email', 'max:255'],
                            'gender'      => ['nullable', 'string', 'max:255'],
                            'date_of_birth'      => ['nullable', 'string', 'max:255'],
                            'nationality'     => ['nullable', 'string', 'max:255'],
                            'address_line1'     => ['required', 'string', 'max:255'],
                            'address_line2'      => ['nullable', 'string', 'max:255'],
                            'city'          => ['nullable', 'string', 'max:255'],
                            'state'          => ['nullable', 'string', 'max:255'],
                            'postal_code'      => ['nullable', 'string', 'max:255'],
                            'bio' => ['required', 'string']
                        ]);
                        // Update `user_info` table
                        $userInfoData = [
                            'user_id'       => $userData->user_id,
                            'phone'         => $validated['phone'] ?? null,
                            'alt_phone'     => $validated['alt_phone'] ?? null,
                            'alt_email'     => $validated['alt_email'] ?? null,
                            'gender'    => $validated['gender'] ?? null,
                            'date_of_birth' => $validated['date_of_birth'] ?? null,
                            'nationality'     => $validated['nationality'] ?? null,
                            'address_line1' => $validated['address_line1'],
                            'address_line2' => $validated['address_line2'],
                            'city' => $validated['city'],
                            'state' => $validated['state'],
                            'bio' =>  $validated['bio'],
                            'postal_code' => $validated['postal_code'],
                            'updated_at'    => now()
                        ];
                        $userInfoResult = Data::insert($system, 'user_info', $userInfoData);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'bio') {
                        $validated = $request->validate([
                            'bio' => ['required', 'string']
                        ]);
                        $userinfo = [
                            'bio' =>  $validated['bio'],
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'skills') {
                        $validated = $request->validate([
                            'skills' => ['required', 'string']
                        ]);
                        $userinfo = [
                            'skills' => $validated['skills'],
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = [
                            'status' => true,
                            'data' => ['id' => $userData->user_id]
                        ];
                    } else if ($type == 'basicinfo') {
                        $validated = $request->validate([
                            'phone'     => ['required', 'string', 'max:255'],
                            'email'      => ['required', 'email', 'max:255'],
                            'alt_phone'          => ['required', 'string', 'max:255'],
                            'alt_email'          => ['nullable', 'email', 'max:255'],
                            'gender'      => ['nullable', 'string', 'max:255'],
                            'date_of_birth'      => ['nullable', 'string', 'max:255'],
                            'nationality'     => ['nullable', 'string', 'max:255'],
                        ]);
                        // Update `users` table
                        $usersData = [
                            'email' => $validated['email'],
                            'updated_at' => now()
                        ];
                        $usersResult = Data::update($system, 'user', $usersData, [
                            ['column' => 'user.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'user', $usersData, [
                                ['column' => 'user.user_id', 'operator' => '=', 'value' => $userData->user_id]
                            ]);
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
                        $userInfoResult = Data::update($system, 'user_info', $userInfoData, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
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
                        $userInfoResult = Data::update($system, 'user_info', $usersData, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'educationadd') {
                        $singleJson = $request->input('existing_json') ?? '';
                        Developer::info($singleJson);
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
                        Developer::info($result);
                        $userinfo = [
                            'education' =>  $result,
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
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
                        $userinfo['education'] = json_encode(array_values($education));
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'experienceadd') {
                        $singleJson = $request->input('existing_json') ?? '';
                        Developer::info($singleJson);
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
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    }else if ($type == 'sociallinks') {
    $validated = $request->validate([
        'facebook_url' => ['nullable', 'url', 'max:255'],
        'instagram_url' => ['nullable', 'url', 'max:255'],
        'youtube_url' => ['nullable', 'url', 'max:255'],
        'x_url' => ['nullable', 'url', 'max:255'],
        'linkedin_url' => ['nullable', 'url', 'max:255'],
        'github_url' => ['nullable', 'url', 'max:255'],
    ]);

    $socialLinks = array_filter([
        'facebook' => $validated['facebook_url'] ?? null,
        'instagram' => $validated['instagram_url'] ?? null,
        'youtube' => $validated['youtube_url'] ?? null,
        'x' => $validated['x_url'] ?? null,
        'linkedin' => $validated['linkedin_url'] ?? null,
        'github' => $validated['github_url'] ?? null,
    ], fn($value) => !is_null($value) && $value !== '');

    $userinfo = [
        'social_links' => json_encode($socialLinks),
        'created_at' => now()
    ];

    $userInfoResult = Data::update($system, 'user_info', $userinfo, [
        ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
    ]);

    if (!$userInfoResult['status']) {
        return ResponseHelper::moduleError('Social Links Save Failed', $userInfoResult['message'], 400);
    }

    $store = false;
    $reloadPage = true;
    $title = 'Profile Updated';
    $message = 'Your social links have been saved successfully.';
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
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
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
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
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
                        $usersResult = Data::update($system, 'users', $usersData, [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
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
                        $userInfoResult = Data::update($system, 'user_info', $userInfoData, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
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
                            'bank_info' => $result,
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('User Info Update Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Profile Updated';
                        $message = 'Your profile has been updated successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'bankdelete') {
                        $singleJson = $request->input('existing_json') ?? '';
                        $accountNumber = $request->input('account_number') ?? '';
                        // Validate input
                        $validated = $request->validate([
                            'account_number' => ['required', 'string', 'max:255'],
                        ]);
                        // Check if account_number exists
                        $data = json_decode($singleJson, true);
                        if (json_last_error() === JSON_ERROR_NONE && array_is_list($data)) {
                            $found = false;
                            foreach ($data as $item) {
                                if (isset($item['account_number']) && $item['account_number'] === $validated['account_number']) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                return ResponseHelper::moduleError('Bank Record Not Found', 'No bank record found with the provided account number.', 404);
                            }
                        }
                        // Delete the record with matching account_number
                        $result = Helper::modifyJson(
                            $singleJson,
                            [],
                            'delete',
                            'account_number',
                            $validated['account_number']
                        );
                        $userinfo = [
                            'bank_info' => $result,
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if (!$userInfoResult['status']) {
                            return ResponseHelper::moduleError('Bank Info Deletion Failed', $userInfoResult['message'], 400);
                        }
                        $store = false;
                        $reloadPage = true;
                        $title = 'Bank Record Deleted';
                        $message = 'The bank record has been deleted successfully.';
                        $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    } else if ($type == 'deleteaccount') {
                        $userinfo = [
                            'account_status' => 'inactive',
                            'deleted_at' => now(),
                        ];
                        $userInfoResult = Data::update($system, 'user_info', $userinfo, [
                            ['column' => 'user_info.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if ($system != "central") {
                            $userInfoResult = Data::update('central', 'users', $userinfo, [
                                ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                            ]);
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
                case 'open_profile_photo_change':
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
                        $usersResult = Data::update($system, 'users', $usersData, [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, [
                                ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                            ]);
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
                    break;
                case 'open_cover_photo_change':
                    $validated = $request->validate([
                        'cover_photo' => ['nullable', 'file', 'image'],
                    ]);
                    // Attempt to save uploaded cover photo
                    $fileId = null;
                    if ($request->hasFile('cover_photo')) {
                        $folderKey = $system . '_profile_covers';
                        $fileResult = FileManager::saveFile(
                            $request,
                            $folderKey,
                            'cover_photo',
                            'Cover',
                            $userData->business_id,
                            false
                        );
                        if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                            $fileId = $fileResult['data']['file_id'] ?? null;
                        }
                    }
                    if ($fileId) {
                        $usersData = [
                            'cover' => $fileId,
                        ];
                        $usersResult = Data::update($system, 'users', $usersData, [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                        if ($system != "central") {
                            $usersResult = Data::update('central', 'users', $usersData, [
                                ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                            ]);
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
                    break;
                case 'open_profile_change_password':
                    // Validate input
                    $validated = $request->validate([
                        'current_password' => ['required', 'string', 'min:6'],
                        'new_password'     => ['required', 'string', 'min:8', 'confirmed'], // assumes `confirm_password` field with same value
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
                    $usersResult = Data::update($system, 'users', $usersData, [
                        ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                    ]);
                    if ($system != "central") {
                        $usersResult = Data::update('central', 'users', $usersData, [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                    }
                    if (!$usersResult['status']) {
                        return ResponseHelper::moduleError('Password Update Failed', $usersResult['message'], 400);
                    }
                    $store = false;
                    $reloadPage = true;
                    $title = 'Password Changed';
                    $message = 'Your password has been changed successfully.';
                    $result = ['status' => true, 'data' => ['id' => $userData->user_id]];
                    break;
                case 'open_logout_all_devices':
                    // Get the current user's ID & session
                    $userId = $userData->user_id;
                    $currentSessionId = session()->getId();
                    // Remove all other sessions for this user
                    $sessionTable = DB::connection('central')->table('sessions');
                    $sessionTable->where('user_id', $userId)->where('id', '!=', $currentSessionId)->delete();
                    $store = false;
                    $reloadPage = true;
                    $title = 'Logged out from All Devices';
                    $message = 'You have been logged out from all other devices successfully.';
                    $result = ['status' => true, 'data' => ['id' => $userId]];
                    break;
                case 'open_manage_two_factor':
                    // Validate input
                    $validated = $request->validate([
                        'type'   => ['required', 'in:enable,disable,cancel'],
                        'system' => ['nullable'],
                    ]);
                    // Initialize
                    $updateData  = [];
                    $where       = ['user_id' => $userData->user_id];
                    $title       = '';
                    $message     = '';
                    $store       = false;
                    $reloadPage  = true;
                    $type = $validated['type'] ?? '';
                    // Set settings based on type and system
                    $newRecord = [];
                    if ($validated['type'] === 'enable') {
                        if ($validated['system'] === 'app') {
                            $newRecord['two_factor_enabled'] = true;
                        } else {
                            $newRecord['two_factor_enabled'] = false;
                        }
                    } else {
                        $newRecord['two_factor_enabled'] = false;
                    }
                    $singleJson = $userData->settings ?? '';
                    // Update JSON settings
                    $result = Helper::modifyJson(
                        $singleJson,
                        $newRecord,
                        'update',
                        null,
                        null
                    );
                    if ($validated['type'] === 'disable') {
                        // Disable current 2FA
                        $updateData = [
                            'two_factor'              => 'disabled',
                            'two_factor_via'          => null,
                            'two_factor_secret'       => null,
                            'verification_token'      => null,
                            'two_factor_confirmed_at' => null,
                            'settings'                => $result,
                            'updated_at'              => now(),
                        ];
                        $title   = 'Two-Factor Disabled';
                        $message = 'Two-Factor Authentication has been disabled successfully.';
                    } elseif ($validated['type'] === 'cancel') {
                        // Cancel pending setup
                        $updateData = [
                            'two_factor'              => 'disabled',
                            'two_factor_via'          => null,
                            'two_factor_secret'       => null,
                            'verification_token'      => null,
                            'two_factor_confirmed_at' => null,
                            'settings'                => $result,
                            'updated_at'              => now(),
                        ];
                        $title   = 'Two-Factor Setup Canceled';
                        $message = 'Pending Two-Factor setup has been canceled.';
                    } elseif ($validated['type'] === 'enable') {
                        // Start setup (pending)
                        if ($validated['system'] === 'app') {
                            // For app: generate secret key
                            $secret = $this->google2fa->generateSecretKey();
                            $updateData = [
                                'two_factor_secret'  => $secret,
                                'verification_token' => null,
                            ];
                        } else {
                            // For email: generate 6-digit OTP
                            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            Notification::mail(
                                'two_factor_confirm_email_otp',
                                $userData->email,
                                ['otp' => $otp],
                                [],
                                'high'
                            );
                            $updateData = [
                                'two_factor_secret'  => null,
                                'verification_token' => $otp,
                            ];
                        }
                        // Merge common fields
                        $updateData = array_merge($updateData, [
                            'two_factor'              => 'pending',
                            'two_factor_via'          => $validated['system'],
                            'two_factor_confirmed_at' => null,
                            'settings'                => $result,
                            'updated_at'              => now(),
                        ]);
                        $title   = 'Two-Factor Setup Started';
                        $message = 'Two-Factor Authentication setup has started. Please complete the setup on your profile page.';
                    }
                    // Perform database update
                    $result = Data::update($system, 'users', $updateData, [
                        ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                    ]);
                    if ($system != "central") {
                        $result = Data::update('central', 'users', $updateData, [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $userData->user_id]
                        ]);
                    }
                    if (!$result['status']) {
                        return ResponseHelper::moduleError(
                            'Two-Factor Update Failed',
                            $result['message'],
                            400
                        );
                    }
                    $result = [
                        'status' => true,
                        'data'   => ['id' => $userData->user_id],
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
            if ($store) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
                // Insert data into the database
                $result = Data::create($reqSet['system'], $reqSet['table'], $validated, $reqSet['key']);
                if ($system != "central") {
                    $result = Data::insert('central', $reqSet['table'], $validated, $reqSet['key']);
                }
            }
            // Return response based on creation success
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['id'] : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
