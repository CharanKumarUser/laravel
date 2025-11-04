<?php
namespace App\Http\Controllers\System\Central\Profile;
use App\Facades\{Data, Developer, FileManager, Random, Skeleton, Notification};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{AgentHelper, PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
/**
 * Controller for rendering the add form for Profile entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new Profile entities.
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
            // Common data retrieval for all cases
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
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'open_profile_edit':
                    $type = $reqSet['id'];
                    if ($type == 'firstform') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                                 ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer Not to Say'], 'required' => true, 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'date', 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'value' => $info->date_of_birth ?? '', 'required' => false, 'col' => '4', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                                ['type' => 'text', 'name' => 'nationality', 'label' => 'Nationality', 'value' => $info->nationality ?? '', 'col' => '4'],
                                ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'value' => $info->phone ?? '', 'col' => '6', 'required' => true, 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'text', 'name' => 'alt_phone', 'label' => 'Alternate Phone', 'value' => $info->alt_phone ?? '', 'col' => '6' , 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'value' => $info->address_line1 ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'value' => $info->address_line2 ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $info->city ?? '', 'required' => false, 'col' => '4', 'attr' => ['data-validate' => 'city']],
                                ['type' => 'text', 'name' => 'state', 'label' => 'State', 'value' => $info->state ?? '', 'required' => false, 'col' => '4', 'attr' => ['data-validate' => 'state']],
                                ['type' => 'text', 'name' => 'postal_code', 'label' => 'Postal Code', 'value' => $info->postal_code ?? '', 'required' => true, 'col' => '4', 'attr' => ['data-validate' => 'pincode']],
                                ['type' => 'textarea', 'name' => 'bio', 'label' => 'Bio', 'value' => $info->bio ?? '', 'col' => '12', 'attr' => ['placeholder' => 'Enter your bio']]
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-user me-1"></i> Complete Your Profile',
                            'short_label' => 'Finish profile to continue',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.general.files();'
                        ];
                    } else if ($type == 'main') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                                ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . FileManager::getFile($userData->profile) . '"></div>', 'col' => '12'],
                                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'value' => $userData->first_name ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'value' => $userData->last_name ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'username', 'label' => 'User Name', 'value' => $userData->username ?? '', 'required' => true, 'col' => '6', 'attr' => ['readonly' => 'readonly']],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $userData->email ?? '', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'phone', 'label' => 'Phone', 'value' => $info->phone ?? '', 'col' => '6'],
                                ['type' => 'text', 'name' => 'alt_phone', 'label' => 'Alternate Phone', 'value' => $info->alt_phone ?? '', 'col' => '6'],
                                ['type' => 'text', 'name' => 'job_title', 'label' => 'Job Title/Role', 'value' => $info->job_title ?? '', 'col' => '6'],
                                ['type' => 'text', 'name' => 'department', 'label' => 'Department/Faculty', 'value' => $info->department ?? '', 'col' => '6'],
                                ['type' => 'textarea', 'name' => 'bio', 'label' => 'Bio', 'value' => $info->bio ?? '', 'col' => '12', 'attr' => ['placeholder' => 'Enter your bio']]
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-user me-1"></i> Edit Profile',
                            'short_label' => 'Update your profile details',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.general.files();'
                        ];
                    } else if ($type == 'bio') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'textarea', 'name' => 'bio', 'label' => 'About', 'value' => $info->bio ?? '', 'required' => true, 'col' => '12', 'attr' => ['placeholder' => 'Enter your bio']],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'About You',
                            'label' => '<i class="fa-solid fa-address-card"></i> Introduce Yourself',
                            'button' => 'Update',
                            'script' => ''
                        ];
                    } else if ($type == 'skills') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'skills', 'label' => 'Skills', 'value' => $info->skills ?? '', 'required' => false, 'col' => '12', 'class' => ['h-auto'], 'attr' => ['placeholder' => 'Enter your Skills', 'data-pills' => 'normal']],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Skills',
                            'label' => '<i class="fa-solid fa-wand-magic-sparkles"></i> Show Off Your Skills',
                            'button' => 'Update',
                            'script' => 'window.general.pills();'
                        ];
                    } else if ($type == 'sociallinks') {
                        $socialLinks = [];
                        if (!empty($info->social_links)) {
                            $decoded = json_decode($info->social_links, true);
                            if (is_array($decoded)) {
                                $socialLinks = array_intersect_key($decoded, array_flip([
                                    'linkedin',
                                    'github',
                                    'youtube',
                                    'facebook',
                                    'instagram',
                                    'x'
                                ]));
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <div class="row p-2 g-3">';
                        foreach (
                            [
                                'facebook' => ['label' => 'Facebook', 'icon' => 'facebook.svg', 'db_key' => 'facebook'],
                                'instagram' => ['label' => 'Instagram', 'icon' => 'instagram.svg', 'db_key' => 'instagram'],
                                'youtube' => ['label' => 'YouTube', 'icon' => 'youtube.svg', 'db_key' => 'youtube'],
                                'x' => ['label' => 'X', 'icon' => 'x.svg', 'db_key' => 'x'],
                                'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin.svg', 'db_key' => 'linkedin'],
                                'github' => ['label' => 'GitHub', 'icon' => 'github.svg', 'db_key' => 'github'],
                            ] as $platform => $data
                        ) {
                            $content .= '
                                <div class="row align-items-center gy-3">
                                    <div class="col-12 col-md-5 d-flex align-items-center gap-3">
                                        <img src="' . asset('social/' . $data['icon']) . '" alt="' . $data['label'] . '"
                                            class="img-fluid rounded-circle" style="width: 30px; height: 30px;">
                                        <div>
                                            <p class="fw-bold mb-1">' . $data['label'] . '</p>
                                            <p class="text-muted small m-0">Integrate your ' . $data['label'] . ' account</p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="float-input-control">
                                            <input type="text" id="' . $platform . '_url" name="' . $platform . '_url"
                                                value="' . htmlspecialchars($socialLinks[$data['db_key']] ?? '') . '" 
                                                class="form-float-input" placeholder="https://">
                                            <label for="' . $platform . '_url" class="form-float-label">' . $data['label'] . '</label>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Online Presence',
                            'label' => '<i class="ti ti-steam"></i> Showcase Your Online Profiles',
                            'button' => 'Update',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'basicinfo') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'tel', 'name' => 'phone', 'label' => 'Phone', 'value' => $info->phone ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $userData->email ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'email']],
                                ['type' => 'tel', 'name' => 'alt_phone', 'label' => 'Alt Phone', 'value' => $info->alt_phone ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'indian-phone']],
                                ['type' => 'email', 'name' => 'alt_email', 'label' => 'Alt Email', 'value' => $info->alt_email ?? '', 'required' => false, 'col' => '6'],
                                ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer Not to Say'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'date', 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'value' => $info->date_of_birth ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date', 'data-date-picker-allow' => 'past']],
                                ['type' => 'text', 'name' => 'nationality', 'label' => 'Nationality', 'value' => $info->nationality ?? '', 'col' => '6'],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-regular fa-user me-1"></i> Edit Profile',
                            'short_label' => 'Update your profile details',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'address') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'address_line1', 'label' => 'Address Line 1', 'value' => $info->address_line1 ?? '', 'required' => true, 'col' => '12', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'address_line2', 'label' => 'Address Line 2', 'value' => $info->address_line2 ?? '', 'required' => false, 'col' => '12', 'attr' => ['data-validate' => 'address']],
                                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $info->city ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'city']],
                                ['type' => 'text', 'name' => 'state', 'label' => 'State', 'value' => $info->state ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'state']],
                                ['type' => 'text', 'name' => 'postal_code', 'label' => 'Postal Code', 'value' => $info->postal_code ?? '', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'pincode']],
                                ['type' => 'text', 'name' => 'country', 'label' => 'Country', 'value' => $info->country ?? '', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'country']],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'label' => '<i class="fa-solid fa-location-dot me-1"></i> Update Address',
                            'short_label' => 'Manage your address info',
                            'button' => 'Save Profile',
                            'script' => 'window.general.select();window.general.validateForm();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'educationadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'university', 'label' => 'University / College', 'required' => true, 'col' => '12'],
                                ['type' => 'text', 'name' => 'degree', 'label' => 'Degree', 'required' => true, 'col' => '12'],
                                ['type' => 'date', 'name' => 'start_year', 'label' => 'Start Year', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'date', 'name' => 'end_year', 'label' => 'End Year', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->education ?? ''],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Education',
                            'label' => '<i class="fa-solid fa-graduation-cap"></i> Add Your Education Background',
                            'button' => 'Add',
                            'script' => 'window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'educationedit') {
                        $educationList = [];
                        if (!empty($info->education)) {
                            $decoded = json_decode($info->education, true);
                            if (is_array($decoded)) {
                                $educationList = $decoded;
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <div class="row p-2 g-3">';
                        foreach ($educationList as $index => $edu) {
                            $content .= '
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-bold mb-3">Education Entry #' . ($index + 1) . '</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="university_' . $index . '" name="education[' . $index . '][university]" 
                                                    value="' . htmlspecialchars($edu['university'] ?? '') . '" 
                                                    class="form-float-input" placeholder="University Name">
                                                <label for="university_' . $index . '" class="form-float-label">University</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="degree_' . $index . '" name="education[' . $index . '][degree]" 
                                                    value="' . htmlspecialchars($edu['degree'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Degree">
                                                <label for="degree_' . $index . '" class="form-float-label">Degree</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="start_year_' . $index . '" name="education[' . $index . '][start_year]" 
                                                    value="' . htmlspecialchars($edu['start_year'] ?? '') . '"  data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="start_year_' . $index . '" class="form-float-label">Start Year</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="end_year_' . $index . '" name="education[' . $index . '][end_year]" 
                                                    value="' . htmlspecialchars($edu['end_year'] ?? '') . '" data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="end_year_' . $index . '" class="form-float-label">End Year</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Education History',
                            'label' => '<i class="ti ti-school"></i> Edit Your Education Background',
                            'button' => 'Save Education',
                            'script' => 'window.general.select();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'experienceadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'company', 'label' => 'Company', 'required' => true, 'col' => '12'],
                                ['type' => 'text', 'name' => 'position', 'label' => 'Position', 'required' => true, 'col' => '12'],
                                ['type' => 'date', 'name' => 'start_date', 'label' => 'Start Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'date', 'name' => 'end_date', 'label' => 'End Date', 'required' => false, 'col' => '6', 'attr' => ['data-date-picker' => 'date']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->experience ?? ''],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Your Experience',
                            'label' => '<i class="fa-solid fa-graduation-cap"></i> Add Your Work Experience',
                            'button' => 'Add',
                            'script' => 'window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'experienceedit') {
                        $experienceList = [];
                        if (!empty($info->experience)) {
                            $decoded = json_decode($info->experience, true);
                            if (is_array($decoded)) {
                                $experienceList = $decoded;
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <div class="row p-2 g-3">';
                        foreach ($experienceList as $index => $exp) {
                            $content .= '
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-bold mb-3">Experience Entry #' . ($index + 1) . '</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="company_' . $index . '" name="experience[' . $index . '][company]" 
                                                    value="' . htmlspecialchars($exp['company'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Company Name">
                                                <label for="company_' . $index . '" class="form-float-label">Company</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="position_' . $index . '" name="experience[' . $index . '][position]" 
                                                    value="' . htmlspecialchars($exp['position'] ?? '') . '" 
                                                    class="form-float-input" placeholder="Job Title">
                                                <label for="position_' . $index . '" class="form-float-label">Position</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="start_date_' . $index . '" name="experience[' . $index . '][start_date]" 
                                                    value="' . htmlspecialchars($edu['start_year'] ?? '') . '"  data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="start_date_' . $index . '" class="form-float-label">Start Date</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="date" id="end_date_' . $index . '" name="experience[' . $index . '][end_date]" 
                                                    value="' . htmlspecialchars($edu['end_year'] ?? '') . '" data-date-picker = "date"
                                                    class="form-float-input">
                                                <label for="end_date_' . $index . '" class="form-float-label">End Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-lg',
                            'position' => 'end',
                            'short_label' => 'Work Experience',
                            'label' => '<i class="ti ti-briefcase"></i> Edit Your Work Experience',
                            'button' => 'Save Experience',
                            'script' => 'window.general.select();window.skeleton.datePicker();'
                        ];
                    } else if ($type == 'emergency') {
                        $emergencyList = [
                            [
                                'type' => 'Primary',
                                'name' => '',
                                'relation' => '',
                                'phone' => ''
                            ],
                            [
                                'type' => 'Secondary',
                                'name' => '',
                                'relation' => '',
                                'phone' => ''
                            ]
                        ];
                        if (!empty($info->emergency_info)) {
                            $decoded = json_decode($info->emergency_info, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $i => $entry) {
                                    if (isset($emergencyList[$i])) {
                                        $emergencyList[$i] = array_merge($emergencyList[$i], $entry);
                                    }
                                }
                            }
                        }
                        $content = '
                            <input type="hidden" name="save_token" value="' . $token . '">
                            <input type="hidden" name="type" value="' . $type . '">
                            <div class="row p-2 g-3">';
                        foreach ($emergencyList as $index => $contact) {
                            $label = htmlspecialchars($contact['type']);
                            $content .= '
                                <div class="p-1 mb-3">
                                    <h6 class="fw-bold mb-3">' . $label . ' Emergency Contact</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="float-input-control">
                                                <input type="text" id="name_' . $index . '" name="emergency[' . $index . '][name]" 
                                                    value="' . htmlspecialchars($contact['name']) . '" 
                                                    class="form-float-input" placeholder="Full Name">
                                                <label for="name_' . $index . '" class="form-float-label">Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="relation_' . $index . '" name="emergency[' . $index . '][relation]" 
                                                    value="' . htmlspecialchars($contact['relation']) . '" 
                                                    class="form-float-input" placeholder="Relation">
                                                <label for="relation_' . $index . '" class="form-float-label">Relation</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="float-input-control">
                                                <input type="text" id="phone_' . $index . '" name="emergency[' . $index . '][phone]" 
                                                    value="' . htmlspecialchars($contact['phone']) . '"  data-validate = "indian-phone"
                                                    class="form-float-input" placeholder="Phone Number">
                                                <label for="phone_' . $index . '" class="form-float-label">Phone</label>
                                            </div>
                                        </div>
                                        <input type="hidden" name="emergency[' . $index . '][type]" value="' . $label . '">
                                    </div>
                                </div>';
                        }
                        $content .= '</div>';
                        $popup = [
                            'form' => 'content',
                            'labelType' => 'floating',
                            'content' => $content,
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Emergency Contacts',
                            'label' => '<i class="ti ti-alert-triangle"></i> Emergency Contact Info',
                            'button' => 'Save Contacts',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'bankadd') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'text', 'name' => 'bank_name', 'label' => 'Bank Name', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'account_number', 'label' => 'Account Number', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'bank-account']],
                                ['type' => 'text', 'name' => 'ifsc_code', 'label' => 'Ifsc Code', 'required' => true, 'col' => '6', 'attr' => ['data-validate' => 'ifsc']],
                                ['type' => 'select', 'name' => 'account_type', 'label' => 'Account Type', 'required' => true, 'col' => '6',  'options' => ['savings' => 'Savings', 'checking' => 'Checking'], 'attr' => ['data-select' => 'dropdown']],
                                ['type' => 'text', 'name' => 'branch', 'label' => 'Branch', 'required' => true, 'col' => '6'],
                                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'col' => '6', 'attr' => ['data-validate' => 'city']],
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->bank_info ?? ''],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Bank Details',
                            'label' => '<i class="fa-solid fa-building-columns"></i> Enter Your Bank Details',
                            'button' => 'Add Bank',
                            'script' => 'window.general.select();'
                        ];
                    } else if ($type == 'bankdelete') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'hidden', 'name' => 'existing_json', 'value' => $info->bank_info ?? ''],
                                [
                                    'type' => 'raw',
                                    'html' => '
                                        <div class="w-100 rounded p-2 text-center">
                                            <div class="mb-3">
                                                <i class="fa-solid fa-circle-exclamation fa-2x text-danger"></i>
                                            </div>
                                            <h5 class="text-danger mb-2">Confirm Deletion</h5>
                                            <p class="mb-0 text-muted">
                                                Are you absolutely sure you want to delete this bank record?<br>
                                                <strong>This action is permanent and cannot be undone.</strong>
                                            </p>
                                        </div>',
                                    'col' => '12'
                                ],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                                ['type' => 'hidden', 'name' => 'account_number', 'value' => $reqSet['param'] ?? ''],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Delete Bank Record',
                            'label' => '<i class="fa-solid fa-trash"></i> Confirm Delete Bank Info',
                            'button' => 'Delete',
                            'script' => '',
                        ];
                    } else if ($type == 'deleteaccount') {
                        $popup = [
                            'form' => 'builder',
                            'labelType' => 'floating',
                            'fields' => [
                                ['type' => 'raw', 'html' => '<div class="w-100 rounded p-2 text-center"><div class="mb-3"><i class="fa-solid fa-circle-exclamation fa-2x text-danger"></i></div><h5 class="text-danger mb-2">Confirm Account Deletion</h5><p class="mb-0 text-muted">Are you absolutely sure you want to delete your account?<br><strong>This action is permanent and will erase all your data. It cannot be undone.</strong></p></div>', 'col' => '12'],
                                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                            ],
                            'type' => 'modal',
                            'size' => 'modal-md',
                            'position' => 'end',
                            'short_label' => 'Delete Bank Record',
                            'label' => '<i class="fa-solid fa-trash"></i> Confirm Delete Bank Info',
                            'button' => 'Delete',
                            'script' => '',
                        ];
                    }
                    break;
                case 'open_profile_photo_change':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="profile" data-label="Profile Photo" data-name="profile_photo" data-crop-size="300:300" data-target="#profile-photo-input" data-recommended-size="300px x 300px" data-file-size="2" data-src="' . FileManager::getFile($userData->profile) . '"></div>', 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-camera me-1"></i> Change Profile Photo',
                        'short_label' => 'Upload a new profile photo',
                        'button' => 'Upload Profile',
                        'script' => 'window.general.select();window.general.files();'
                    ];
                    break;
                case 'open_cover_photo_change':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => '<div class="file-upload-container" data-file="image" data-file-crop="cover" data-label="Cover Photo" data-name="cover_photo" data-crop-size="400:150" data-target="#profile-photo-input" data-recommended-size="600px x 200px" data-file-size="2" data-src="' . FileManager::getFile($userData->cover) . '"></div>', 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-camera me-1"></i> Change Cover Photo',
                        'short_label' => 'Upload a new cover photo',
                        'button' => 'Upload Cover',
                        'script' => 'window.general.select();window.general.files();'
                    ];
                    break;
                case 'open_profile_change_password':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'password', 'name' => 'current_password', 'label' => 'Current Password', 'required' => true, 'col' => '12'],
                            ['type' => 'password', 'name' => 'new_password', 'label' => 'New Password', 'required' => true, 'col' => '12'],
                            ['type' => 'password', 'name' => 'new_password_confirmation', 'label' => 'Confirm New Password', 'required' => true, 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-lock me-1"></i> Change Password',
                        'short_label' => 'Update your account password',
                        'button' => 'Change Password',
                        'script' => 'window.general.select();window.general.validate();'
                    ];
                    break;
                case 'open_logout_all_devices':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'raw', 'html' => '<div class="sf-13 px-3 text-center"><div class="mb-3"><i class="fa-solid fa-triangle-exclamation fa-2x text-danger"></i></div><h5 class="text-danger mb-2">Confirm Logout from All Devices</h5><p class="text-muted mb-0">This will log you out from all devices except the one you are currently using.<br><strong>Are you sure you want to continue?</strong></p></div>', 'col' => '12'],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-right-from-bracket me-1"></i> Logout from All Devices',
                        'short_label' => 'Terminate all other sessions',
                        'button' => 'Logout from All',
                        'script' => ''
                    ];
                    break;
                case 'open_manage_two_factor':
                    $content = '';
                    $lbl = 'Manage Two-Factor Authentication';
                    $shrtLbl = 'manage two-factor';
                    $confirmBtn = 'Enable';
                    $via = (!empty($userData->two_factor_via) && $userData->two_factor_via === "app") ? "Authenticator App" : "Email";
                    if ($userData->two_factor === "enabled") {
                        // Already enabled → show disable form
                        $fields = [
                            ['type' => 'hidden', 'name' => 'type', 'value' => 'disable', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="alert border border-warning text-warning sf-15 mb-3 rounded-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Are you sure you want to <b>disable</b> two-factor authentication via <b>' . $via . '</b>?</div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="sf-13 px-3"><i class="fa-regular fa-circle-info me-1"></i> <b>Note:</b> To enable another method, you must first disable the current method, then set it up again.</div>', 'col' => '12'],
                        ];
                        $lbl = "Disable Two-Factor Authentication";
                        $shrtLbl = "disable two-factor";
                        $confirmBtn = "Confirm Disable";
                    } elseif ($userData->two_factor === "pending") {
                        // Pending → allow cancel setup
                        $fields = [
                            ['type' => 'hidden', 'name' => 'type', 'value' => 'cancel', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="alert border border-warning text-warning sf-15 mb-3 rounded-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Your two-factor authentication setup via <b>' . $via . '</b> is currently <b>pending</b>.<br>Do you want to cancel and disable it?</div>', 'col' => '12'],
                            ['type' => 'raw', 'html' => '<div class="sf-13 px-3"><i class="fa-regular fa-circle-info me-1"></i> <b>Note:</b> To complete setup, visit your profile page and follow the instructions:<ul class="mt-1 mb-0"><li><b>Authenticator App:</b> Scan the QR code shown and enter the code from your app to activate.</li><li><b>Email:</b> Enter the OTP sent to your email to activate.</li></ul></div>', 'col' => '12'],
                        ];
                        $lbl = "Cancel Pending Two-Factor Setup";
                        $shrtLbl = "cancel two-factor";
                        $confirmBtn = "Cancel Setup";
                    } else {
                        // Not enabled → offer to enable
                        $fields = [
                            ['type' => 'hidden', 'name' => 'type', 'value' => 'enable', 'col' => '12'],
                            ['type' => 'select', 'name' => 'system', 'label' => 'Authentication Method', 'options' => ['app' => 'Authenticator App', 'email' => 'Email'], 'required' => true, 'col' => '12', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'raw', 'html' => '<div class="alert border border-primary text-primary sf-13 mt-3 rounded-3"><i class="fa-regular fa-circle-info me-1"></i> After enabling two-factor authentication, complete the setup on your profile page:<ul class="mt-1 mb-0"><li><b>Authenticator App:</b> Scan the QR code and enter the app-generated code to activate.</li><li><b>Email:</b> Enter the OTP sent to your email to activate.</li></ul></div>', 'col' => '12'],
                        ];
                        $lbl = "Enable Two-Factor Authentication";
                        $shrtLbl = "enable two-factor";
                        $confirmBtn = "Enable";
                    }
                    // Generate form content
                    $content .= PopupHelper::generateBuildForm($token, $fields, 'floating');
                    $popup = [
                        'form' => 'custom',
                        'labelType' => 'floating',
                        'content' => $content,
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => $lbl,
                        'short_label' => $shrtLbl,
                        'button' => $confirmBtn,
                        'script' => 'window.general.select();'
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
