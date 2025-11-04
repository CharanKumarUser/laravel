<?php

use App\Facades\Skeleton;
use App\Http\Controllers\Authorization\AuthorizationController;
use App\Http\Controllers\Device\AdmsRequestsController;
use App\Http\Controllers\Device\PdfTest;
use App\Http\Controllers\Lander\LanderRouteController;
use App\Http\Controllers\Lander\NavigationController;
use App\Http\Controllers\System\Business\SmartPresence\NavCtrl;
use App\Http\Controllers\System\Business\SmartPresence\TokenCtrl;
use App\Http\Controllers\System\SystemRouteController;
use App\Services\NotificationService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication and include the homepage,
| help page, unsubscribe page, and onboarding/landing flows.
|
*/
Route::get('/', [NavigationController::class, 'index'])->name('home');
Route::get('/help', [NavigationController::class, 'help'])->name('help.page');
Route::get('/unsubscribe', [NavigationController::class, 'unsubscribe'])->name('unsubscribe.page');

// Onboarding and Landing Routes
Route::get('/g/plans/{token?}/{name?}', [NavigationController::class, 'plan_view'])->name('plans.view');
Route::get('/g/onboarding/{type?}', [NavigationController::class, 'onboarding'])->name('onboarding.type');
Route::post('/g/onboarding/devices', [NavigationController::class, 'onboarded_devices'])->name('onboarding.devices');
Route::post('/g/landing/forms', [NavigationController::class, 'landing_forms'])->name('landing.forms');
Route::post('/g/onboarding/forms', [NavigationController::class, 'onboarding_forms'])->name('onboarding.forms');

/*
|--------------------------------------------------------------------------
| Form Routes
|--------------------------------------------------------------------------
|
| Routes handling lander-specific form actions.
|
*/
Route::post('/lander-action/{token}', [LanderRouteController::class, 'dispatch'])->name('lander.action');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for user authentication, including login, registration, social login,
| password reset, email verification, two-factor authentication, and logout.
|
*/
Route::middleware('guest')->group(function () {
    // Login Routes
    Route::get('/login', [AuthorizationController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthorizationController::class, 'login'])->name('login.post');

    // Registration Routes
    Route::get('/register', [AuthorizationController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthorizationController::class, 'register'])->name('register.post');

    // Social Login Routes
    Route::get('/auth/{provider}', [AuthorizationController::class, 'redirectToProvider'])->name('social.login');
    Route::get('/auth/{provider}/callback', [AuthorizationController::class, 'handleProviderCallback'])->name('social.callback');

    // Password Reset Routes
    Route::get('/forgot-password', [AuthorizationController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthorizationController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password', [AuthorizationController::class, 'showResetPassword'])->name('password.reset')->where(['email' => '.*']);
    Route::post('/reset-password', [AuthorizationController::class, 'resetPassword'])->name('password.update');

    // Email Verification Routes
    Route::get('/verify-email', [AuthorizationController::class, 'showVerifyEmail'])->name('verification.show');
    Route::post('/verify-email', [AuthorizationController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/verify-email/resend', [AuthorizationController::class, 'sendResetLinkEmail'])->name('verification.resend');
});

// Two-Factor Authentication Routes
Route::get('/two-factor/verify', [AuthorizationController::class, 'showTwoFactorForm'])->name('two-factor.verify');
Route::post('/two-factor/verify', [AuthorizationController::class, 'verifyTwoFactor'])->name('two-factor.verify.post');

// Logout Route (Available for both guests and authenticated users)
Route::match(['get', 'post'], '/logout', [AuthorizationController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
|
| Routes requiring authentication and skeleton middleware. Includes dynamic
| system routes and skeleton actions.
|
*/
Route::middleware(['auth', 'skeleton'])->group(function () {
    // Dynamically Register Skeleton Routes
    foreach (Skeleton::getRoutes() as $route) {
        Route::get('/' . $route, [SystemRouteController::class, 'dispatch']);
    }

    // Skeleton Action Routes
    Route::post('/skeleton-action/{token}', [SystemRouteController::class, 'dispatch'])->name('system.action');
    Route::get('/reload-skeleton', [SystemRouteController::class, 'reload_skeleton'])->name('reload.skeleton');

    // Retrieve Tokens Associated with Skeleton Key
    Route::post('/get-token/skeleton-key', function (Request $request) {
        try {
            $request->validate(['key' => 'required|string']);
            $userId = Skeleton::authUser()->user_id;
            $tokenMap = Session::get('skeleton_tokens_auth_' . $userId, []);

            if (empty($tokenMap) || !is_array($tokenMap)) {
                return ['status' => 'error', 'message' => 'Session token is missing or invalid.'];
            }

            $key = $request->input('key');
            $table = collect($tokenMap)->first(fn($data, $id) => $id === $key || ($data['key'] ?? null) === $key)['table'] ?? null;

            if (!$table) {
                return ['status' => 'error', 'message' => 'Token not found.'];
            }

            $relatedTokens = collect($tokenMap)->filter(fn($data) => ($data['table'] ?? null) === $table)->keys()->values()->toArray();

            return [
                'status' => 'success',
                'table' => $table,
                'tokens' => $relatedTokens,
                'count' => count($relatedTokens),
            ];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    })->name('skeleton.token');

    // Smart Presence QR Routes
    Route::post('/s/smart/presence/qr/start', [NavCtrl::class, 'start'])->name('smart.presence.qr.start');
    Route::post('/s/smart/presence/qr/stop', [NavCtrl::class, 'stop'])->name('smart.presence.qr.stop');
    Route::get('/s/smart/presence/qr/{token}', [TokenCtrl::class, 'qr_code'])->name('smart.presence.qr.code');
});

/*
|--------------------------------------------------------------------------
| ADMS Device Routes
|--------------------------------------------------------------------------
|
| Routes for biometric device communication via ADMS protocol. Supports
| endpoints like ping, cdata, devicecmd, getrequest, and fdata.
|
*/
Route::match(['get', 'post'], '/dc/{code}/iclock/{endpoint}', [AdmsRequestsController::class, 'check'])
    ->where('code', '[A-Za-z0-9]+')
    ->where('endpoint', 'ping(\.aspx|\.php)?|cdata(\.aspx|\.php)?|devicecmd(\.aspx|\.php)?|getrequest(\.aspx|\.php)?|fdata(\.aspx|\.php)?')
    ->name('dc.iclock');

Route::match(['get', 'post'], '/d/{code}/iclock/{endpoint}', [AdmsRequestsController::class, 'handle'])
    ->where('code', '[A-Za-z0-9]+')
    ->where('endpoint', 'ping(\.aspx|\.php)?|cdata(\.aspx|\.php)?|devicecmd(\.aspx|\.php)?|getrequest(\.aspx|\.php)?|fdata(\.aspx|\.php)?')
    ->name('d.iclock');

// Web-based Queue Trigger for ADMS Jobs
Route::get('/queue/work', function () {
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'adms:*']);
    return response('Queue processed');
})->middleware('throttle:60,1')->name('queue.work');

Route::get('/skeleton-action/token', [PdfTest::class, 'check'])->name('skeleton.action.token');

/*
|--------------------------------------------------------------------------
| Notification Service Routes
|--------------------------------------------------------------------------
|
| Routes for handling notifications, requiring authentication and skeleton
| middleware. Includes fetching, marking as read, and setting reminders.
|
*/
Route::middleware(['auth', 'skeleton'])->group(function () {
    // Fetch All Notifications for Authenticated User
    Route::get('/realtime/notifications/fetch', function () {
        return (new NotificationService)->fetchForUser(Auth::user()->business_id, Auth::user()->user_id);
    })->name('notifications.fetch');

    // Mark a Specific Notification as Read
    Route::post('/realtime/notifications/mark-read', function (Request $request) {
        $request->validate(['notification_id' => 'required|string']);
        return (new NotificationService)->markAsRead(
            Auth::user()->business_id,
            Auth::user()->user_id,
            $request->notification_id
        );
    })->name('notifications.mark-read');

    // Mark All Notifications as Read
    Route::post('/realtime/notifications/mark-all-read', function () {
        return (new NotificationService)->markAllAsRead(Auth::user()->business_id, Auth::user()->user_id);
    })->name('notifications.mark-all-read');

    // Set a Reminder for a Notification
    Route::post('/realtime/notifications/remind-later', function (Request $request) {
        $request->validate([
            'notification_id' => 'required|string',
            'remind_at' => 'required|date',
        ]);
        return (new NotificationService)->remindLater(
            Auth::user()->business_id,
            Auth::user()->user_id,
            $request->notification_id,
            $request->remind_at
        );
    })->name('notifications.remind-later');
});

/*
|--------------------------------------------------------------------------
| Search Route
|--------------------------------------------------------------------------
|
| Route for global search functionality using the SearchService.
|
*/
Route::get('/global/search/by/skeleton', function (Request $request) {
    $query = $request->query('query');
    $searchService = app(SearchService::class);
    return response()->json($searchService->search($query));
})->name('global.search');