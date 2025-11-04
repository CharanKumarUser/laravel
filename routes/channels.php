<?php

use App\Facades\Developer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| This file registers all event broadcasting channels supported by the
| application. Each channel includes authorization logic to ensure only
| permitted users can subscribe to private or presence channels.
|
*/

/*
|--------------------------------------------------------------------------
| Private Channels
|--------------------------------------------------------------------------
|
| Private channels restrict access to specific users based on authentication
| and authorization logic.
|
*/

/**
 * Private channel for individual user models.
 * Authorizes only if the authenticated user's ID matches the requested ID.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return Auth::check() && (int) $user->id === (int) $id;
});

/**
 * Private channel for user-specific notifications.
 * Authorizes only if the authenticated user's ID matches the requested userId.
 * Logs unauthorized access attempts for security monitoring.
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    if (!Auth::check() || (string) $user->user_id !== (string) $userId) {
        Log::warning('Unauthorized access attempt to user notification channel', [
            'user_id' => $userId,
        ]);
        return false;
    }

    return [
        'user_id' => $user->user_id,
        'name' => $user->first_name . ' ' . $user->last_name,
    ];
});

/**
 * Private channel for business dataset access.
 * Authorizes only if the authenticated user belongs to the requested business.
 */
Broadcast::channel('business.{businessId}.dataset', function ($user, $businessId) {
    if (!Auth::check() || (string) $user->business_id !== (string) $businessId) {
        return false;
    }

    return [
        'user_id' => $user->user_id,
        'business_id' => $user->business_id,
    ];
});

/*
|--------------------------------------------------------------------------
| Presence Channels
|--------------------------------------------------------------------------
|
| Presence channels allow tracking of users currently subscribed to the channel.
| These channels return user details for authorized users.
|
*/

/**
 * Presence channel for a specific business.
 * Authorizes only if the user belongs to the requested business.
 * Returns user details including status and last seen time.
 */
Broadcast::channel('presence-business.{businessId}', function ($user, $businessId) {
    if ((string) $user->business_id !== (string) $businessId) {
        return false;
    }

    return [
        'id' => $user->user_id,
        'name' => $user->name,
        'status' => $user->status,
        'last_seen_at' => $user->last_seen_at ? $user->last_seen_at->toDateTimeString() : null,
        'business_id' => $user->business_id,
    ];
});

/**
 * Presence channel for a specific company.
 * Authorizes only if the user belongs to the requested company.
 * Returns user details including status and last seen time.
 */
Broadcast::channel('presence-company.{companyId}', function ($user, $companyId) {
    if ((string) $user->company_id !== (string) $companyId) {
        return false;
    }

    return [
        'id' => $user->user_id,
        'name' => $user->name,
        'status' => $user->status,
        'last_seen_at' => $user->last_seen_at ? $user->last_seen_at->toDateTimeString() : null,
        'company_id' => $user->company_id,
    ];
});

/**
 * Presence channel for a specific scope.
 * Authorizes only if the user belongs to the requested scope.
 * Returns user details including status and last seen time.
 */
Broadcast::channel('presence-scope.{scopeId}', function ($user, $scopeId) {
    if ((string) $user->scope_id !== (string) $scopeId) {
        return false;
    }

    return [
        'id' => $user->user_id,
        'name' => $user->name,
        'status' => $user->status,
        'last_seen_at' => $user->last_seen_at ? $user->last_seen_at->toDateTimeString() : null,
        'scope_id' => $user->scope_id,
    ];
});

/*
|--------------------------------------------------------------------------
| Public Channels
|--------------------------------------------------------------------------
|
| Public channels allow access without strict authentication, often used for
| broadcasting events to a wider audience or logging interactions.
|
*/

/**
 * Public channel for business QR updates.
 * Allows public access for QR token updates without authentication.
 */
Broadcast::channel('business.{businessId}.{companyId}', function ($user, $businessId, $companyId) {
    return true; // Public access for QR updates
});

/**
 * Public channel for device compatibility checks during onboarding.
 * Allows public access and logs the event for tracking purposes.
 */
Broadcast::channel('device-compatibility-check.{onboardingId}', function ($user, $onboardingId) {
    Log::info('Allowing public access to device-compatibility-check channel', [
        'onboarding_id' => $onboardingId,
    ]);
    return true;
});

/**
 * Public table channel for authorized users.
 * Authorizes only authenticated users and logs developer info for monitoring.
 */
Broadcast::channel('table-channel', function ($user) {
    if (!Auth::check()) {
        return false;
    }

    Developer::info('Authorizing chat channel');
    return true;
});