<?php
namespace App\Http\Controllers\System\Central\Profile;
use App\Http\Controllers\Controller;
use App\Facades\{Data, Skeleton, Developer};
use App\Http\Helpers\ResponseHelper;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
/**
 * Handles navigation views for the Profile module.
 */
class NavCtrl extends Controller
{
    protected $google2fa;
    /**
     * Initialize Google2FA instance.
     */
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    /**
     * Renders profile views based on route parameters.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters (module, section, item, token)
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request, array $params)
    {
        try {
            // Set default view and extract parameters
            // Extract route parameters
            $baseView = 'system.central.' . strtolower('Profile');
            $module = $params['module'] ?? 'Profile';
            $section = $params['section'] ?? null;
            $item = $params['item'] ?? null;
            $token = $params['token'] ?? null;
            // Build view path
            $viewPath = $baseView;
            if ($section) {
                $viewPath .=  . $section;
                if ($item) {
                    $viewPath .= "\\" . $item;
                }
            } else {
                $viewPath .= '.index';
            }
            // Extract view name and normalize path
            $viewName = strtolower(str_replace(' ', '-', str_replace("{$baseView}.", '', $viewPath)));
            $viewPath = strtolower(str_replace(' ', '-', $viewPath));
            Developer::info($viewPath);
            Developer::info($viewName);
            // Initialize base data
            $data = [
                'status' => true,
                'module' => $module,
                'section' => $section,
                'item' => $item,
                'token' => $token,
                'title' => 'Page Loaded',
                'message' => 'Profile module page loaded successfully.'
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different view names
            switch ($viewName) {
                case 'index':
                    // Fetch authenticated user
                    $user = Skeleton::authUser();
                    $system = Skeleton::authUser('system');
                    if (!$user) {
                        return ResponseHelper::moduleError('Unauthorized', 'User not authenticated.', 401);
                    }
                    // Fetch user data from users table
                    $userParams = [
                        'select' => [
                            'users.user_id',
                            'users.business_id',
                            'users.username',
                            'users.email',
                            'users.first_name',
                            'users.last_name',
                            'users.settings',
                            'users.email_verified_at',
                            'users.two_factor_confirmed_at',
                            'users.two_factor_via',
                            'users.two_factor',
                            'users.two_factor_secret',
                            'users.two_factor_recovery_codes',
                            'users.last_password_changed_at',
                            'users.last_login_at',
                            'users.account_status',
                            'users.profile',
                            'users.cover'
                        ],
                        'where' => [
                            ['column' => 'users.user_id', 'operator' => '=', 'value' => $user->user_id]
                        ]
                    ];
                    $userResult = Data::query($system, 'users', $userParams);
                    if (!$userResult['status']) {
                        return ResponseHelper::moduleError('User Fetch Failed', $userResult['message'], 400);
                    }
                    // Fetch user profile data from user_info table, including JSON bank_info and social_urls
                    $userInfoParams = [
                        'select' => [
                            'user_info.id',
                            'user_info.user_id',
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
                    if (!$userInfoResult['status']) {
                        return ResponseHelper::moduleError('User Info Fetch Failed', $userInfoResult['message'], 400);
                    }
                    // Fetch social providers from user_providers table
                    $providerParams = [
                        'select' => [
                            'user_providers.provider',
                            'user_providers.provider_id',
                            'user_providers.provider_token',
                            'user_providers.provider_expires_at',
                            'user_providers.created_at'
                        ],
                        'where' => [
                            ['column' => 'user_providers.user_id', 'operator' => '=', 'value' => $user->user_id]
                        ]
                    ];
                    $providerResult = Data::query($system, 'user_providers', $providerParams);
                    if (!$providerResult['status']) {
                        return ResponseHelper::moduleError('Providers Fetch Failed', $providerResult['message'], 400);
                    }
                    // Fetch authentication logs from auth_logs table
                    $authLogParams = [
                        'select' => [
                            'auth_logs.id',
                            'auth_logs.device_info',
                            'auth_logs.ip_address',
                            'auth_logs.session_token',
                            'auth_logs.login_at',
                            'auth_logs.login_via',
                            'auth_logs.logout_at',
                            'auth_logs.is_online',
                            'auth_logs.last_activity_at'
                        ],
                        'where' => [
                            ['column' => 'auth_logs.user_id', 'operator' => '=', 'value' => $user->user_id]
                        ],
                        'orderBy' => ['auth_logs.id' => 'desc'],
                        'limit' => 50
                    ];
                    $authLogResult = Data::query($system, 'auth_logs', $authLogParams);
                    if (!$authLogResult['status']) {
                        return ResponseHelper::moduleError('Auth Logs Fetch Failed', $authLogResult['message'], 400);
                    }
                    // Prepare 2FA settings
                    $tf = $user->two_factor;
                    $tf_via = $user->two_factor_via;
                    $tf_set = [];
                    if ($user->two_factor === 'enabled') {
                        if ($user->two_factor_via === 'app') {
                            $tf_set['codes'] = json_decode($user->two_factor_recovery_codes ?? '[]', true);
                        }
                    } elseif ($user->two_factor === 'pending') {
                        if ($user->two_factor_via === 'app') {
                            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                                config('app.name'),
                                $userResult['data'][0]['email'],
                                $userResult['data'][0]['two_factor_secret']
                            );
                            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
                            $writer = new Writer($renderer);
                            $tf_set['qr'] = base64_encode($writer->writeString($qrCodeUrl));
                            $tf_set['code'] = $userResult['data'][0]['two_factor_secret'];
                        }
                    }
                    // Prepare social providers status
                    $socialProviders = [
                        'google' => 'disconnected',
                        'facebook' => 'disconnected',
                        'github' => 'disconnected',
                        'x' => 'disconnected',
                    ];
                    foreach ($providerResult['data'] as $provider) {
                        $socialProviders[$provider['provider']] = 'connected';
                    }
                    // Parse JSON data for bank_info and social_urls
                    $bankInfo = json_decode($userInfoResult['data'][0]['bank_info'] ?? '[]', true);
                    $socialUrls = json_decode($userInfoResult['data'][0]['social_links'] ?? '[]', true);
                    $educationDetails = json_decode($userInfoResult['data'][0]['education'] ?? '[]', true);
                    $experience = json_decode($userInfoResult['data'][0]['experience'] ?? '[]', true);
                    $emergencyInfo = json_decode($userInfoResult['data'][0]['emergency_info'] ?? '[]', true);
                    // Prepare view data
                    $settings = json_decode($userResult['data'][0]['settings'] ?? '{}', true);
                    unset($userResult['data'][0]['two_factor_secret']);
                    $data = [
                        'user' => $userResult['data'][0] ?? [],
                        'info' => $userInfoResult['data'][0] ?? [],
                        'settings' => $settings,
                        'providers' => $socialProviders,
                        'bank_info' => $bankInfo,
                        'social_urls' => $socialUrls,
                        'education_details' => $educationDetails,
                        'experience' => $experience,
                        'emergency_info' => $emergencyInfo,
                        'tf' => $tf,
                        'tf_via' => $tf_via,
                        'tf_set' => $tf_set,
                        'documents' => [],
                        'auth_logs' => $authLogResult['data'] ?? [],
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
            // Render view if it exists
            if (View::exists($viewPath)) {
                return view($viewPath, compact('data'));
            }
            // Return 404 view if view does not exist
            return response()->view('errors.404', ['status' => false, 'title' => 'Page Not Found', 'message' => 'The requested page does not exist.'], 404);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while loading the page.', 500);
        }
    }
}
