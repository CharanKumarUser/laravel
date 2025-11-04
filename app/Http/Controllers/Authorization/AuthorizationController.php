<?php

namespace App\Http\Controllers\Authorization;

use App\Http\Controllers\Controller;
use App\Facades\{Developer, Notification, Skeleton};
use App\Services\Data\DataService;
use App\Services\Database\DatabaseService;
use App\Http\Helpers\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Session, View, Log};
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use PragmaRX\Google2FA\Google2FA;
use Carbon\Carbon;

/**
 * Handles user authentication, registration, and session management with enhanced security features.
 * Optimized for performance with caching, rate limiting, and efficient database queries.
 */
class AuthorizationController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook', 'github', 'x'];
    private const DEFAULT_SETTINGS = [
        'allow_fcm' => true,
        'max_login_limit' => 3,
        'auto_logout_on_password_change' => true,
        'allow_logout_all_devices' => true,
        'two_factor_enabled' => false,
        'social_logins' => [
            'google' => false,
            'facebook' => false,
            'github' => false,
            'x' => false,
        ],
        'session_timeout_minutes' => 30,
        'failed_login_attempts_limit' => 3,
        'lockout_duration_minutes' => 5,
        'password_rotation_days' => 90,
        'ip_whitelist' => [],
        'rate_limit_attempts' => 5,
        'rate_limit_window_seconds' => 60,
        'secure_session_token' => true,
    ];
    private const OTP_EXPIRY_MINUTES = 10;
    private const CACHE_TTL = 18000; // 5 hours in seconds
    private bool $allowSocialRegistration = false;

    /**
     * Store hashed OTP in users table with expiry and return the plain OTP.
     *
     * @param object $user
     * @return string
     * @throws Exception
     */
    private function storeOtp($user): string
    {
        try {
            $otpSet = Helper::generateOtp(6);
            $otp = $otpSet->otp;
            $updateResult = DataService::update('central', 'users', [
                'verification_token' => $otpSet->token,
                'verification_token_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            if (!$updateResult['status']) {
                throw new Exception('Failed to store OTP: ' . ($updateResult['message'] ?? 'Unknown error'));
            }
            return $otp;
        } catch (Exception $e) {
            Developer::error('Failed to store OTP', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify OTP against hashed verification_token and check expiry.
     *
     * @param object $user
     * @param string $otp
     * @return bool
     */
    private function verifyOtp($user, string $otp): bool
    {
        try {
            if (is_null($user->verification_token) || is_null($user->verification_token_expires_at)) {
                return false;
            }
            $expiresAt = $user->verification_token_expires_at instanceof Carbon
                ? $user->verification_token_expires_at
                : Carbon::parse($user->verification_token_expires_at);
            if (now()->greaterThan($expiresAt)) {
                return false;
            }
            return Helper::verifyOtp($otp, $user->verification_token);
        } catch (Exception $e) {
            Developer::error('Failed to verify OTP', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Clear OTP and expiry from users table.
     *
     * @param string $userId
     * @return bool
     */
    private function clearOtp(string $userId): bool
    {
        try {
            $updateResult = DataService::update('central', 'users', [
                'verification_token' => null,
                'verification_token_expires_at' => null,
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
            return $updateResult['status'];
        } catch (Exception $e) {
            Developer::error('Failed to clear OTP', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Display the login form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLoginForm(Request $request)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            Session::forget(['heading', 'tagline', 'type', 'resend', 'email']);
            return view('auth.login', ['providers' => self::ALLOWED_PROVIDERS]);
        } catch (Exception $e) {
            Developer::error('Failed to load login form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading login page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user login with validation, email verification, 2FA, and rate limiting checks.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $heading = "Two-Step Verification";
            $tagline = "A verification code has been sent to your email as part of two-step verification. Enter it below to continue.";
            $type = "email";
            $rateLimitKey = 'login_attempts:' . $request->ip();
            $credentials = $request->validate([
                'username' => 'required|string|max:100',
                'password' => 'required|string|max:255',
                'remember' => 'nullable|in:on,1,true,false,0',
                'fcm_device_token' => 'nullable|string|max:255',
            ]);

            // Rate limiting
            $attempts = cache()->get($rateLimitKey, 0);
            if ($attempts >= self::DEFAULT_SETTINGS['rate_limit_attempts']) {
                cache()->put($rateLimitKey, $attempts, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
                return $this->handleError($request, 'Too many login attempts. Please try again later.', Response::HTTP_TOO_MANY_REQUESTS, 'errors.429');
            }
            cache()->increment($rateLimitKey);

            $userResult = DataService::fetch('central', 'users', [
                ['column' => 'username', 'operator' => '=', 'value' => $credentials['username']],
            ], 'CENTRAL')['data'][0] ?? null;

            if (!$userResult) {
                DataService::update('central', 'users', ['failed_login_attempts' => 1], [['column' => 'username', 'operator' => '=', 'value' => $credentials['username']]], 'CENTRAL');
                cache()->put($rateLimitKey, $attempts + 1, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
                return $this->handleError($request, 'Invalid credentials or inactive account.', Response::HTTP_UNAUTHORIZED, 'errors.message');
            }

            $user = (object) $userResult;
            $settings = array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []);

            // Verify credentials and account status
            if (!Hash::check($credentials['password'], $user->password) || ($user->account_status ?? 'active') !== 'active') {
                $failedAttempts = ($user->failed_login_attempts ?? 0) + 1;
                $updateData = ['failed_login_attempts' => $failedAttempts];
                if ($failedAttempts >= $settings['failed_login_attempts_limit']) {
                    $updateData['locked_at'] = now();
                }
                DataService::update('central', 'users', $updateData, [['column' => 'username', 'operator' => '=', 'value' => $credentials['username']]], 'CENTRAL');
                cache()->put($rateLimitKey, $attempts + 1, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
                return $this->handleError($request, 'Invalid credentials or inactive account.', Response::HTTP_UNAUTHORIZED, 'errors.message');
            }

            // Check account lock
            if (!is_null($user->locked_at ?? null)) {
                $lockedAt = $user->locked_at instanceof Carbon ? $user->locked_at : Carbon::parse($user->locked_at);
                if (now()->lessThan($lockedAt->addMinutes($settings['lockout_duration_minutes']))) {
                    cache()->put($rateLimitKey, $attempts + 1, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
                    return $this->handleError($request, 'Account is temporarily locked. Try again later.', Response::HTTP_LOCKED, 'errors.429');
                }
                DataService::update('central', 'users', ['locked_at' => null], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            }

            // Check password rotation
            if (!is_null($user->last_password_changed_at ?? null)) {
                $lastPasswordChanged = $user->last_password_changed_at instanceof Carbon
                    ? $user->last_password_changed_at
                    : Carbon::parse($user->last_password_changed_at);
                if (now()->diffInDays($lastPasswordChanged) > $settings['password_rotation_days']) {
                    return $this->handleError($request, 'Password expired. Please reset your password.', Response::HTTP_OK, 'errors.message', ['redirect' => route('password.request')]);
                }
            }

            // Reset failed login attempts
            DataService::update('central', 'users', ['failed_login_attempts' => 0], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            cache()->forget($rateLimitKey);

            // Email verification
            if (is_null($user->email_verified_at ?? null)) {
                $otp = $this->storeOtp($user);
                Notification::mail(
                    'email_verification_otp',
                    $user->email,
                    ['otp' => $otp, 'username' => $user->username],
                    [],
                    'high'
                );
                Session::put([
                    'login.user_id' => $user->user_id,
                    'heading' => $heading,
                    'tagline' => $tagline,
                    'type' => $type,
                    'email' => $user->email,
                ]);
                return response()->redirectToRoute('verification.show');
            }

            // Two-factor authentication
            if ($user->two_factor === "enabled" && $settings['two_factor_enabled'] && !is_null($user->two_factor_secret ?? null)) {
                Session::put('login.user_id', $user->user_id);
                return response()->redirectToRoute('two-factor.verify');
            }

            // Perform login
            if (Auth::guard('web')->attempt([
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'account_status' => 'active',
            ], filter_var($credentials['remember'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                $request->session()->regenerate(true);
                return $this->completeLogin($request, Auth::guard('web')->user(), $settings);
            }

            cache()->put($rateLimitKey, $attempts + 1, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
            return $this->handleError($request, 'Invalid credentials or inactive account.', Response::HTTP_UNAUTHORIZED, 'errors.message');
        } catch (Exception $e) {
            $rateLimitKey = $rateLimitKey ?? 'login_attempts:' . $request->ip();
            $attempts = cache()->get($rateLimitKey, 0);
            cache()->put($rateLimitKey, $attempts + 1, self::DEFAULT_SETTINGS['rate_limit_window_seconds']);
            Developer::error('Login attempt failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('password'),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Login failed. Please try again.', Response::HTTP_UNAUTHORIZED, 'errors.message');
        }
    }

    /**
     * Handle user logout with session and cache cleanup.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user) {
                $settings = $this->getUserSettings($user->user_id);
                if ($request->boolean('logout_all_devices') && ($settings['allow_logout_all_devices'] ?? false)) {
                    $this->logoutAllDevices($user->user_id);
                } else {
                    $this->logoutCurrentDevice($user->user_id, $user->session_token);
                }
                Skeleton::clearUserCache();
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->redirectTo('/login')->with('message', 'Logged out successfully.');
        } catch (Exception $e) {
            Developer::error('Logout failed', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, 'Failed to log out. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Redirect to social provider's authentication page.
     *
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function redirectToProvider(string $provider)
    {
        try {
            if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
                return $this->handleError(request(), 'Invalid social login provider.', Response::HTTP_BAD_REQUEST, 'errors.400');
            }
            Session::put('social_login_provider', $provider);
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            Developer::error('Failed to redirect to social provider', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError(request(), 'Unable to connect to ' . ucfirst($provider) . '.', Response::HTTP_BAD_REQUEST, 'errors.400');
        }
    }

    /**
     * Handle callback from social provider and authenticate or register user.
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handleProviderCallback(string $provider, Request $request)
    {
        try {
            $heading = "Two-Step Verification";
            $tagline = "A verification code has been sent to your email as part of two-step verification. Enter it below to continue.";
            $type = "email";

            if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
                return $this->handleError($request, 'Invalid social login provider.', Response::HTTP_BAD_REQUEST, 'errors.400');
            }
            $socialUser = Socialite::driver($provider)->stateless()->user();
            $user = $this->findOrCreateSocialUser($socialUser, $provider);
            if (!$user) {
                return $this->handleError($request, 'Social registration is disabled. Please use an existing account.', Response::HTTP_UNAUTHORIZED, 'errors.message');
            }
            $settings = array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []);
            if ($user->account_status !== 'active') {
                return $this->handleError($request, 'Account is not active.', Response::HTTP_FORBIDDEN, 'errors.403');
            }
            if (!($settings['social_logins'][$provider] ?? false)) {
                return $this->handleError($request, ucfirst($provider) . ' login is disabled for this account.', Response::HTTP_FORBIDDEN, 'errors.403');
            }
            if (($settings['two_factor_enabled'] ?? false) && !is_null($user->two_factor_secret)) {
                Session::put('login.user_id', $user->user_id);
                return response()->redirectToRoute('two-factor.verify');
            }
            $request->session()->regenerate(true);
            Auth::loginUsingId($user->user_id, true);
            Session::forget('social_login_provider');
            return $this->completeLogin($request, $user, $settings, $provider, $socialUser->id);
        } catch (Exception $e) {
            Developer::error('Social login failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, 'Social login failed: ' . (config('developer.mode') ? $e->getMessage() : 'Please try again.'), Response::HTTP_BAD_REQUEST, 'errors.400');
        }
    }

    /**
     * Display the two-factor authentication form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showTwoFactorForm(Request $request)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            if (!Session::has('login.user_id')) {
                return $this->handleError($request, 'Please log in to continue.', Response::HTTP_UNAUTHORIZED, 'errors.401');
            }
            return view('auth.two-factor-verify');
        } catch (Exception $e) {
            Developer::error('Failed to load two-factor verification form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading two-factor verification page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify two-factor authentication code.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function verifyTwoFactor(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $userId = Session::get('login.user_id');
            if (!$userId) {
                return $this->handleError($request, 'Invalid session. Please log in again.', Response::HTTP_UNAUTHORIZED, 'errors.401');
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                return $this->handleError($request, 'User not found.', Response::HTTP_NOT_FOUND, 'errors.404');
            }
            $user = (object) $userResult;
            $twoFactor = new Google2FA();
            if (!$this->verifyTwoFactorCode($twoFactor, $user, $request->code)) {
                return $this->handleError($request, 'Invalid two-factor code or recovery code.', Response::HTTP_UNAUTHORIZED, 'errors.message');
            }
            $request->session()->regenerate(true);
            Auth::loginUsingId($user->user_id, true);
            Session::forget('login.user_id');
            return $this->completeLogin($request, $user, array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []));
        } catch (Exception $e) {
            Developer::error('Two-factor verification failed', [
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, 'Two-factor verification failed.', Response::HTTP_UNAUTHORIZED, 'errors.message');
        }
    }

    /**
     * Display the registration form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegisterForm(Request $request)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            return view('auth.register', ['providers' => self::ALLOWED_PROVIDERS]);
        } catch (Exception $e) {
            Developer::error('Failed to load registration form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading registration page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $heading = "Verify Your Email";
            $tagline = "A verification code has been sent to your email. Please enter it below to continue.";
            $type = "email";
            $data = $request->validate([
                'username' => 'required|string|max:100|unique:central.users,username',
                'email' => 'required|email|max:255|unique:central.users,email',
                'password' => 'required|string|min:8|confirmed',
                'fcm_device_token' => 'nullable|string|max:255',
            ]);
            $userId = 'USR' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $settings = self::DEFAULT_SETTINGS;
            $userIdResult = DataService::insert('central', 'users', [
                'user_id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'account_status' => 'active',
                'business_id' => 'CENTRAL',
                'settings' => json_encode($settings),
                'last_password_changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'CENTRAL');
            if (!$userIdResult['status']) {
                return $this->handleError($request, 'Failed to create user: ' . ($userIdResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $profileResult = DataService::insert('central', 'user_info', [
                'user_id' => $userId,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'CENTRAL');
            if (!$profileResult['status']) {
                DataService::delete('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                return $this->handleError($request, 'Failed to create user profile: ' . ($profileResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                DataService::delete('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                DataService::delete('central', 'user_info', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                return $this->handleError($request, 'Failed to retrieve new user data.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $user = (object) $userResult;
            if (is_null($user->email_verified_at)) {
                $otp = $this->storeOtp($user);
                Notification::mail(
                    'email_verification_otp',
                    $user->email,
                    ['otp' => $otp, 'username' => $user->username],
                    [],
                    'high'
                );
                Session::put([
                    'login.user_id' => $user->user_id,
                    'heading' => $heading,
                    'tagline' => $tagline,
                    'type' => $type,
                    'email' => $user->email,
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'Registration successful. Please verify your email with the OTP sent.',
                    'data' => [],
                    'redirect' => route('verification.show'),
                ], Response::HTTP_OK);
            }
            $request->session()->regenerate(true);
            Auth::loginUsingId($user->user_id, true);
            return $this->completeLogin($request, $user, $settings);
        } catch (Exception $e) {
            Developer::error('Registration failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('password', 'password_confirmation'),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Registration failed. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the forgot password form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showForgotPassword(Request $request)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            return view('auth.forgot-password');
        } catch (Exception $e) {
            Developer::error('Failed to load forgot password form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading forgot password page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send a password reset OTP to the user's email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $heading = "Two-Step Verification";
            $tagline = "A verification code has been sent to your email as part of two-step verification. Enter it below to continue.";
            $type = "email";

            if ($request->input('type') === 'forgot-password' || session('type') === 'forgot-password') {
                $heading = "Reset Your Password";
                $tagline = "We've sent a verification code to your email. Enter it below to verify your identity and reset your password.";
                $type = "forgot-password";
            }

            $data = $request->validate([
                'email' => 'required|email|max:255',
            ]);
            $userResult = DataService::fetch('central', 'users', [['column' => 'email', 'operator' => '=', 'value' => $data['email']]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                return $this->handleError($request, 'No account found with this email.', Response::HTTP_NOT_FOUND, 'errors.404');
            }
            $user = (object) $userResult;
            if ($user->account_status !== 'active') {
                return $this->handleError($request, 'Account is not active.', Response::HTTP_FORBIDDEN, 'errors.403');
            }
            Session::put('login.user_id', $user->user_id);
            $otp = $this->storeOtp($user);
            if ($request->input('type') === 'forgot-password' || session('type') === 'forgot-password') {
                Notification::mail(
                    'password_reset_otp',
                    $user->email,
                    ['otp' => $otp, 'username' => $user->username],
                    [],
                    'high'
                );
            } else {
                Notification::mail(
                    'email_verification_otp',
                    $user->email,
                    ['otp' => $otp, 'username' => $user->username],
                    [],
                    'high'
                );
            }
            Session::put([
                'login.user_id' => $user->user_id,
                'heading' => $heading,
                'tagline' => $tagline,
                'type' => $type,
                'email' => $user->email,
            ]);
            if ($request->input('type') === 'resend') {
                Session::put('resend', true);
                return response()->redirectToRoute('verification.show');
            }
            return response()->redirectToRoute('verification.show');
        } catch (Exception $e) {
            Developer::error('Failed to send password reset OTP', [
                'error' => $e->getMessage(),
                'input' => $request->only('email'),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Failed to send reset OTP. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the password reset form.
     *
     * @param Request $request
     * @param string|null $email
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetPassword(Request $request, ?string $email = null)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            return view('auth.reset-password', ['request' => $request, 'email' => $email]);
        } catch (Exception $e) {
            Developer::error('Failed to load password reset form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading password reset page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle password reset.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);
            $userResult = DataService::fetch('central', 'users', [['column' => 'email', 'operator' => '=', 'value' => $data['email']]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                return $this->handleError($request, 'User not found.', Response::HTTP_NOT_FOUND, 'errors.404');
            }
            $user = (object) $userResult;
            $settings = array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []);
            $updateResult = DataService::update('central', 'users', [
                'password' => Hash::make($data['password']),
                'last_password_changed_at' => now(),
                'verification_token' => null,
                'verification_token_expires_at' => null,
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            if (!$updateResult['status']) {
                return $this->handleError($request, 'Failed to update password: ' . ($updateResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if ($settings['auto_logout_on_password_change']) {
                $this->logoutAllDevices($user->user_id);
            }
            return response()->redirectToRoute('login');
        } catch (Exception $e) {
            Developer::error('Password reset failed', [
                'error' => $e->getMessage(),
                'input' => $request->only('email', 'code'),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Failed to reset password. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the email verification form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVerifyEmail(Request $request)
    {
        try {
            if (Auth::check()) {
                return response()->redirectTo('/dashboard');
            }
            return view('auth.verify-email');
        } catch (Exception $e) {
            Developer::error('Failed to load email verification form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Error loading email verification page.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify email using OTP.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string|size:6',
                'verification_type' => 'nullable|string|in:email,forgot-password',
            ]);
            $userId = Session::get('login.user_id');
            if (!$userId) {
                return $this->handleError($request, 'Invalid session. Please log in again.', Response::HTTP_UNAUTHORIZED, 'errors.401');
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                return $this->handleError($request, 'User not found.', Response::HTTP_NOT_FOUND, 'errors.404');
            }
            $user = (object) $userResult;
            if (!$this->verifyOtp($user, $data['code'])) {
                return $this->handleError($request, 'Invalid or expired OTP.', Response::HTTP_BAD_REQUEST, 'errors.400');
            }
            switch ($data['verification_type'] ?? 'email') {
                case 'forgot-password':
                    $this->clearOtp($user->user_id);
                    return response()->json([
                        'status' => true,
                        'message' => 'OTP verified. Proceed to reset your password.',
                        'data' => [],
                        'redirect' => route('password.reset', ['email' => $user->email]),
                    ], Response::HTTP_OK);
                case 'email':
                default:
                    if (is_null($user->email_verified_at)) {
                        $updateResult = DataService::update('central', 'users', [
                            'email_verified_at' => now(),
                            'verification_token' => null,
                            'verification_token_expires_at' => null,
                            'updated_at' => now(),
                        ], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
                        if (!$updateResult['status']) {
                            return $this->handleError($request, 'Failed to verify email: ' . ($updateResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }
                    $request->session()->regenerate(true);
                    Auth::loginUsingId($user->user_id, true);
                    Session::forget('login.user_id');
                    $settings = array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []);
                    return $this->completeLogin($request, $user, $settings);
            }
        } catch (Exception $e) {
            Developer::error('Email verification failed', [
                'error' => $e->getMessage(),
                'input' => $request->only('code', 'verification_type'),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($request, config('developer.mode') ? $e->getMessage() : 'Failed to verify email. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Find or create a user based on social login data using user_providers table.
     *
     * @param object $socialUser
     * @param string $provider
     * @return object|null
     */
    private function findOrCreateSocialUser($socialUser, string $provider)
    {
        try {
            if (!$socialUser->email) {
                throw new Exception('Social provider did not return an email address.');
            }
            $providerResult = DataService::fetch('central', 'user_providers', [
                ['column' => 'provider', 'operator' => '=', 'value' => $provider],
                ['column' => 'provider_id', 'operator' => '=', 'value' => $socialUser->id],
            ], 'CENTRAL')['data'][0] ?? null;
            if ($providerResult) {
                $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $providerResult['user_id']]], 'CENTRAL')['data'][0] ?? null;
                if ($userResult) {
                    return $this->updateSocialUser($userResult, $socialUser, $provider);
                }
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'email', 'operator' => '=', 'value' => $socialUser->email]], 'CENTRAL')['data'][0] ?? null;
            if ($userResult) {
                return $this->updateSocialUser($userResult, $socialUser, $provider);
            }
            if (!$this->allowSocialRegistration) {
                return null;
            }
            $baseUsername = Str::before($socialUser->email, '@') . '_' . Str::lower($provider);
            $username = $this->generateUniqueUsername($baseUsername);
            return $this->createSocialUser($socialUser, $provider, $username);
        } catch (Exception $e) {
            Developer::error('Failed to find or create social user', [
                'provider' => $provider,
                'social_user_id' => $socialUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Generate a unique username.
     *
     * @param string $baseUsername
     * @return string
     */
    private function generateUniqueUsername(string $baseUsername): string
    {
        $username = $baseUsername;
        $counter = 1;
        while (true) {
            $userResult = DataService::fetch('central', 'users', [['column' => 'username', 'operator' => '=', 'value' => $username]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                return $username;
            }
            $username = $baseUsername . '_' . $counter++;
        }
    }

    /**
     * Update social user data in users and user_providers tables.
     *
     * @param array $userData
     * @param object $socialUser
     * @param string $provider
     * @return object
     */
    private function updateSocialUser($userData, $socialUser, string $provider)
    {
        try {
            $user = (object) $userData;
            $settings = array_merge(self::DEFAULT_SETTINGS, json_decode($user->settings ?? '{}', true) ?: []);
            $updateData = [];
            if (!isset($settings['social_logins'][$provider]) || !$settings['social_logins'][$provider]) {
                $settings['social_logins'][$provider] = true;
                $updateData['settings'] = json_encode($settings);
            }
            if ($socialUser->email && $socialUser->email !== $user->email) {
                $emailCheck = DataService::fetch('central', 'users', [['column' => 'email', 'operator' => '=', 'value' => $socialUser->email]], 'CENTRAL')['data'][0] ?? null;
                if (!$emailCheck) {
                    $updateData['email'] = $socialUser->email;
                    $updateData['email_verified_at'] = null;
                }
            }
            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                $updateResult = DataService::update('central', 'users', $updateData, [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
                if (!$updateResult['status']) {
                    throw new Exception('Failed to update user data: ' . ($updateResult['message'] ?? 'Unknown error'));
                }
            }
            $providerResult = DataService::fetch('central', 'user_providers', [
                ['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id],
                ['column' => 'provider', 'operator' => '=', 'value' => $provider],
            ], 'CENTRAL')['data'][0] ?? null;
            $providerData = [
                'user_id' => $user->user_id,
                'provider' => $provider,
                'provider_id' => $socialUser->id,
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'provider_expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                'updated_at' => now(),
            ];
            if ($providerResult) {
                $updateProviderResult = DataService::update('central', 'user_providers', $providerData, [
                    ['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id],
                    ['column' => 'provider', 'operator' => '=', 'value' => $provider],
                ], 'CENTRAL');
                if (!$updateProviderResult['status']) {
                    throw new Exception('Failed to update user_providers: ' . ($updateProviderResult['message'] ?? 'Unknown error'));
                }
            } else {
                $providerData['created_at'] = now();
                $createProviderResult = DataService::insert('central', 'user_providers', $providerData, 'CENTRAL');
                if (!$createProviderResult['status']) {
                    throw new Exception('Failed to create user_providers entry: ' . ($createProviderResult['message'] ?? 'Unknown error'));
                }
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                throw new Exception('Failed to retrieve updated user data.');
            }
            return (object) $userResult;
        } catch (Exception $e) {
            Developer::error('Failed to update social user', [
                'user_id' => $user->user_id ?? 'unknown',
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new user from social login data.
     *
     * @param object $socialUser
     * @param string $provider
     * @param string $username
     * @return object|null
     */
    private function createSocialUser($socialUser, string $provider, string $username)
    {
        try {
            $userId = 'USR' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $settings = array_merge(self::DEFAULT_SETTINGS, [
                'social_logins' => array_fill_keys(self::ALLOWED_PROVIDERS, false),
            ]);
            $settings['social_logins'][$provider] = true;
            $userIdResult = DataService::insert('central', 'users', [
                'user_id' => $userId,
                'username' => $username,
                'email' => $socialUser->email,
                'password' => Hash::make(Str::random(16)),
                'first_name' => $socialUser->name ?? null,
                'account_status' => 'active',
                'business_id' => 'CENTRAL',
                'settings' => json_encode($settings),
                'last_password_changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'CENTRAL');
            if (!$userIdResult['status']) {
                throw new Exception('Failed to create new user: ' . ($userIdResult['message'] ?? 'Unknown error'));
            }
            $profileResult = DataService::insert('central', 'user_info', [
                'user_id' => $userId,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'CENTRAL');
            if (!$profileResult['status']) {
                DataService::delete('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                throw new Exception('Failed to create user profile: ' . ($profileResult['message'] ?? 'Unknown error'));
            }
            $providerData = [
                'user_id' => $userId,
                'provider' => $provider,
                'provider_id' => $socialUser->id,
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'provider_expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $createProviderResult = DataService::insert('central', 'user_providers', $providerData, 'CENTRAL');
            if (!$createProviderResult['status']) {
                DataService::delete('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                DataService::delete('central', 'user_info', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                throw new Exception('Failed to create user_providers entry: ' . ($createProviderResult['message'] ?? 'Unknown error'));
            }
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL')['data'][0] ?? null;
            if (!$userResult) {
                DataService::delete('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                DataService::delete('central', 'user_info', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                DataService::delete('central', 'user_providers', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
                throw new Exception('Failed to retrieve new user data.');
            }
            Developer::info('Social user created', [
                'user_id' => $userId,
                'provider' => $provider,
                'username' => $username,
                'email' => $socialUser->email,
            ]);
            return (object) $userResult;
        } catch (Exception $e) {
            Developer::error('Failed to create social user', [
                'provider' => $provider,
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get user settings from database.
     *
     * @param string $userId
     * @return array
     */
    private function getUserSettings(string $userId): array
    {
        try {
            $userResult = DataService::fetch('central', 'users', [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL')['data'][0] ?? null;
            return $userResult
                ? array_merge(self::DEFAULT_SETTINGS, json_decode($userResult['settings'] ?? '{}', true) ?: [])
                : self::DEFAULT_SETTINGS;
        } catch (Exception $e) {
            Developer::error('Failed to get user settings', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::DEFAULT_SETTINGS;
        }
    }

    /**
     * Log out user from all devices.
     *
     * @param string $userId
     * @return void
     */
    private function logoutAllDevices(string $userId)
    {
        try {
            DataService::update('central', 'auth_logs', [
                'is_online' => false,
                'logout_at' => now(),
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
            DataService::update('central', 'users', [
                'session_token' => null,
                'remember_token' => null,
                'fcm_device_token' => null,
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
        } catch (Exception $e) {
            Developer::error('Failed to logout all devices', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Log out user from current device.
     *
     * @param string $userId
     * @param string|null $sessionToken
     * @return void
     */
    private function logoutCurrentDevice(string $userId, ?string $sessionToken)
    {
        try {
            if ($sessionToken) {
                DataService::update('central', 'auth_logs', [
                    'is_online' => false,
                    'logout_at' => now(),
                    'updated_at' => now(),
                ], [
                    ['column' => 'user_id', 'operator' => '=', 'value' => $userId],
                    ['column' => 'session_token', 'operator' => '=', 'value' => $sessionToken],
                ], 'CENTRAL');
            }
            DataService::update('central', 'users', [
                'session_token' => null,
                'remember_token' => null,
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $userId]], 'CENTRAL');
        } catch (Exception $e) {
            Developer::error('Failed to logout current device', [
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Verify two-factor authentication code.
     *
     * @param Google2FA $twoFactor
     * @param object $user
     * @param string $code
     * @return bool
     */
    private function verifyTwoFactorCode(Google2FA $twoFactor, $user, string $code): bool
    {
        try {
            if (strlen($code) == 6 && $twoFactor->verifyKey($user->two_factor_secret, $code)) {
                return true;
            }
            $recoveryCodes = json_decode($user->two_factor_recovery_codes ?? '[]', true) ?: [];
            $usedCode = null;
            foreach ($recoveryCodes as $recoveryCode => $used) {
                if (!$used && $recoveryCode === $code) {
                    $usedCode = $recoveryCode;
                    break;
                }
            }
            if ($usedCode === null) {
                return false;
            }
            $recoveryCodes[$usedCode] = true;
            $updateResult = DataService::update('central', 'users', [
                'two_factor_recovery_codes' => json_encode($recoveryCodes),
                'updated_at' => now(),
            ], [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            if (!$updateResult['status']) {
                throw new Exception('Failed to update recovery codes: ' . ($updateResult['message'] ?? 'Unknown error'));
            }
            return true;
        } catch (Exception $e) {
            Developer::error('Two-factor code verification failed', [
                'user_id' => $user->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Handle errors with consistent response format.
     *
     * @param Request $request
     * @param string $message
     * @param int $statusCode
     * @param string|null $view
     * @param array $data
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function handleError(Request $request, string $message, int $statusCode, string $view = 'errors.message', array $data = [])
    {
        try {
            Log::info('Handling error', [
                'message' => $message,
                'status_code' => $statusCode,
                'view' => $view,
                'data' => $data,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'data' => $data,
                ], $statusCode);
            }

            $errorView = $view ?? "errors.{$statusCode}";
            if (View::exists($errorView)) {
                return response()->view($errorView, array_merge(['error' => $message], $data), $statusCode);
            }

            Log::warning("Error view '$errorView' does not exist, falling back to redirect");
            return response()->redirectTo('/login')->with('error', $message);
        } catch (Exception $e) {
            Developer::error('Error handling failed', [
                'original_message' => $message,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->redirectTo('/login')->with('error', 'An unexpected error occurred.');
        }
    }

    /**
     * Complete login process with session and log updates.
     *
     * @param Request $request
     * @param object $user
     * @param array $settings
     * @param string $provider
     * @param string $providerId
     * @return \Illuminate\Http\RedirectResponse
     */
    private function completeLogin(Request $request, $user, array $settings, string $provider = 'normal', string $providerId = '')
    {
        try {
            $sessionToken = session()->getId();
            Session::forget(['heading', 'tagline', 'type', 'resend', 'email']);
            $updateData = [
                'session_token' => $sessionToken,
                'last_login_at' => now(),
                'updated_at' => now(),
            ];
            if ($request->has('fcm_device_token') && ($settings['allow_fcm'] ?? true)) {
                $updateData['fcm_device_token'] = $request->input('fcm_device_token');
            }
            $updateResult = DataService::update('central', 'users', $updateData, [['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id]], 'CENTRAL');
            if (!$updateResult['status']) {
                Auth::logout();
                $request->session()->invalidate();
                return $this->handleError($request, 'Failed to update user session: ' . ($updateResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $logData = [
                'user_id' => $user->user_id,
                'device_info' => $request->header('User-Agent') ?? 'Unknown',
                'ip_address' => $request->ip(),
                'session_token' => $sessionToken,
                'login_at' => now(),
                'login_via' => $provider,
                'is_online' => true,
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $logResult = DataService::insert('central', 'auth_logs', $logData, 'CENTRAL');
            if (!$logResult['status']) {
                Auth::logout();
                $this->logoutCurrentDevice($user->user_id, $sessionToken);
                $request->session()->invalidate();
                return $this->handleError($request, 'Failed to log login activity: ' . ($logResult['message'] ?? 'Unknown error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if (isset($settings['max_login_limit'])) {
                $activeSessionsResult = DataService::fetch('central', 'auth_logs', [
                    ['column' => 'user_id', 'operator' => '=', 'value' => $user->user_id],
                    ['column' => 'is_online', 'operator' => '=', 'value' => true],
                ], 'CENTRAL')['data'];
                if ($activeSessionsResult && count($activeSessionsResult) > $settings['max_login_limit']) {
                    Auth::logout();
                    $this->logoutCurrentDevice($user->user_id, $sessionToken);
                    $request->session()->invalidate();
                    return $this->handleError($request, 'Maximum login limit exceeded.', Response::HTTP_TOO_MANY_REQUESTS, 'errors.429');
                }
            }
            if (isset($settings['session_timeout_minutes'])) {
                Session::put('session_expires_at', now()->addMinutes($settings['session_timeout_minutes']));
            }
            Session::put('auth_user_id', $user->user_id);
            Session::put('auth_business_id', $user->business_id);
            Skeleton::clearUserCache();
            if ($user->business_id !== 'CENTRAL') {
                app(DatabaseService::class)->getConnection($user->business_id);
            }
            Developer::info('Login successful', [
                'user_id' => $user->user_id,
                'provider' => $provider,
                'provider_id' => $providerId,
                'session_id' => $request->session()->getId(),
            ]);
            return response()->redirectTo('/dashboard');
        } catch (Exception $e) {
            Developer::error('Failed to complete login', [
                'user_id' => $user->user_id ?? 'unknown',
                'provider' => $provider,
                'provider_id' => $providerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $request->session()->getId(),
            ]);
            Auth::logout();
            $request->session()->invalidate();
            return $this->handleError($request, 'Failed to complete login.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}