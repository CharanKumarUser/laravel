<?php

namespace App\Services;

use App\Facades\{Data, Skeleton};
use App\Events\Presence\{UserStatus, UserTyping};
use Illuminate\Support\Facades\{Log, Event};
use Exception;
use InvalidArgumentException;

/**
 * PresenceService handles user presence and typing events for business, company, and scope channels with robust validation and error handling.
 */
class PresenceService
{
    private const VALID_STATUSES = ['online', 'offline', 'away', 'dnd', 'invisible'];
    private const VALID_CHANNEL_TYPES = ['business', 'company', 'scope'];

    /**
     * Update user status and broadcast.
     */
    public function updateUserStatus(string $userId, string $status, string $channelType, string $channelId, ?string $lastSeenAt = null): bool
    {
        try {
            if (!in_array($status, self::VALID_STATUSES)) {
                throw new InvalidArgumentException('Invalid status. Use: ' . implode(', ', self::VALID_STATUSES));
            }
            if (!in_array($channelType, self::VALID_CHANNEL_TYPES)) {
                throw new InvalidArgumentException('Invalid channel type. Use: ' . implode(', ', self::VALID_CHANNEL_TYPES));
            }
            $this->validateUserIds([$userId]);
            $this->validateChannelId($channelType, $channelId);
            $system = Skeleton::getUserSystem() ?? 'central';
            $data = ['status' => $status];
            if ($lastSeenAt) {
                $data['last_seen_at'] = $lastSeenAt;
            } else {
                $data['last_seen_at'] = now()->toDateTimeString();
            }
            $response = Data::update(
                $system,
                'users',
                $data,
                ['user_id' => $userId, 'deleted_at' => null],
                "user_status_update_{$userId}_{$channelType}_{$channelId}"
            );
            if (!$response['status']) {
                throw new Exception('Failed to update user status in database.');
            }
            if ($status !== 'invisible') {
                Event::dispatch(new UserStatus($userId, $status, $channelType, $channelId, $data['last_seen_at']));
            }
            return true;
        } catch (Exception $e) {
            Log::error("Update user status failed for user {$userId} on {$channelType}.{$channelId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Broadcast typing event.
     */
    public function userTyping(string $userId, string $chatId, string $channelType, string $channelId): bool
    {
        try {
            $this->validateUserIds([$userId]);
            if (empty($chatId)) {
                throw new InvalidArgumentException('Chat ID is required.');
            }
            if (!in_array($channelType, self::VALID_CHANNEL_TYPES)) {
                throw new InvalidArgumentException('Invalid channel type. Use: ' . implode(', ', self::VALID_CHANNEL_TYPES));
            }
            $this->validateChannelId($channelType, $channelId);
            Event::dispatch(new UserTyping($userId, $chatId, $channelType, $channelId));
            return true;
        } catch (Exception $e) {
            Log::error("User typing event failed for user {$userId} on {$channelType}.{$channelId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch user status.
     */
    public function getUserStatus(string $userId, string $channelType, string $channelId): array
    {
        try {
            $this->validateUserIds([$userId]);
            $this->validateChannelId($channelType, $channelId);
            $system = Skeleton::getUserSystem() ?? 'central';
            $response = Data::fetch($system, 'users', [
                'columns' => ['user_id', 'name', 'status', 'last_seen_at'],
                'where' => [
                    'user_id' => $userId,
                    $channelType . '_id' => $channelId,
                    'deleted_at' => null
                ]
            ]);
            if (!$response['status'] || empty($response['data'])) {
                throw new Exception("User not found: {$userId} in {$channelType}.{$channelId}");
            }
            return [
                'success' => true,
                'user' => $response['data'][0]
            ];
        } catch (Exception $e) {
            Log::error("Fetch user status failed for user {$userId} in {$channelType}.{$channelId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate user IDs.
     */
    private function validateUserIds(array $userIds): void
    {
        if (empty($userIds)) {
            throw new InvalidArgumentException('At least one user ID is required.');
        }
        $system = Skeleton::getUserSystem() ?? 'central';
        $response = Data::fetch($system, 'users', [
            'columns' => ['user_id'],
            'where' => ['user_id' => $userIds, 'deleted_at' => null]
        ]);
        if (!$response['status'] || count($response['data']) !== count($userIds)) {
            throw new InvalidArgumentException('One or more user IDs are invalid or deleted.');
        }
    }

    /**
     * Validate channel ID (business_id, company_id, or scope_id).
     */
    private function validateChannelId(string $channelType, string $channelId): void
    {
        if (empty($channelId)) {
            throw new InvalidArgumentException(ucfirst($channelType) . ' ID is required.');
        }
        $system = Skeleton::getUserSystem() ?? 'central';
        $table = $channelType === 'business' ? 'businesses' : ($channelType === 'company' ? 'companies' : 'scopes');
        $column = $channelType . '_id';
        $response = Data::fetch($system, $table, [
            'columns' => [$column],
            'where' => [$column => $channelId, 'deleted_at' => null, 'is_active' => 1]
        ]);
        if (!$response['status'] || empty($response['data'])) {
            throw new InvalidArgumentException("Invalid or inactive {$channelType} ID: {$channelId}");
        }
    }
}