<?php
namespace App\Services;
use App\Facades\{Database, Data, Developer, Skeleton};
use App\Events\Notifications\{UserNotification, RoleNotification, ScopeNotification, CompanyNotification};
use Illuminate\Support\Facades\{Config, Event};
use App\Jobs\Notifications\SendMailJob;
use Illuminate\Support\{Str};
use Exception;
use InvalidArgumentException;
/**
 * NotificationService handles email and in-app notifications with efficient data handling and broadcasting.
 */
class NotificationService
{
    private const TEMPLATE_TABLE = 'skeleton_templates';
    private const MAIL_QUEUE_HIGH = 'mail_high';
    private const MAIL_QUEUE_LOW = 'mail_low';
    private const VALID_ATTACHMENT_TYPES = ['base64', 'file', 'url'];
    private const VALID_PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const VALID_NOTIFICATION_TYPES = [
        'info',
        'success',
        'warning',
        'error',
        'alert',
        'reminder',
        'update',
        'announcement',
        'event',
        'deadline',
        'system',
        'critical',
        'custom',
        'approval',
        'rejection',
        'request_pending',
        'leave_request',
        'leave_approved',
        'leave_rejected',
        'overtime_request',
        'overtime_approved',
        'overtime_rejected',
        'expense_request',
        'expense_approved',
        'expense_rejected',
        'travel_request',
        'travel_approved',
        'travel_rejected',
        'promotion_request',
        'transfer_request',
        'job_posting',
        'application_received',
        'interview_scheduled',
        'interview_feedback',
        'offer_made',
        'offer_accepted',
        'offer_rejected',
        'onboarding',
        'probation',
        'confirmation',
        'contract',
        'contract_renewal',
        'contract_expiry',
        'termination',
        'resignation',
        'retirement',
        'offboarding',
        'transfer',
        'role_change',
        'promotion',
        'attendance',
        'absent',
        'late',
        'early_leave',
        'shift_change',
        'shift_swap',
        'schedule_update',
        'payroll',
        'payslip',
        'salary_credit',
        'deduction',
        'bonus',
        'incentive',
        'reimbursement',
        'tax_update',
        'benefits',
        'insurance',
        'performance_review',
        'goal_setting',
        'goal_update',
        'goal_completed',
        'feedback',
        'training_assigned',
        'training_completed',
        'certification',
        'achievement',
        'policy_update',
        'compliance',
        'audit',
        'document_required',
        'document_verified',
        'document_expiry',
        'message',
        'survey',
        'announcement_hr',
        'wellbeing',
        'birthday',
        'work_anniversary',
        'celebration'
    ];
    private const VALID_MEDIUMS = ['app', 'email', 'sms', 'whatsapp'];
    private const SUPPORTED_TABLES = ['users', 'companies', 'scopes'];
    private const SUPPORTED_COLUMNS = [
        'users' => ['user_id', 'first_name', 'last_name', 'email', 'name'],
        'companies' => ['company_id', 'name'],
        'scopes' => ['scope_id', 'name']
    ];
    /**
     * Send an email using a template key.
     *
     * @param string $key Template key for email content.
     * @param string|array<string> $to Recipient email address(es).
     * @param array<string, string|null> $pairs Placeholder key-value pairs for email content.
     * @param array<array{type: string, content: string, name: string, extension: string}> $attachments Email attachments.
     * @param string $priority Queue priority ('low', 'medium', 'high', 'critical').
     * @return bool Success status of the email dispatch.
     * @throws InvalidArgumentException|Exception
     */
    public function mail(string $key, $to, array $pairs = [], array $attachments = [], string $priority = 'low'): bool
    {
        try {
            if (empty($key) || empty($to)) {
                throw new InvalidArgumentException('Template key and recipient address are required.');
            }
            $toAddresses = is_array($to) ? $to : array_map('trim', explode(',', $to));
            foreach ($toAddresses as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Invalid email address: {$email}");
                }
            }
            $priority = strtolower($priority);
            if (!in_array($priority, self::VALID_PRIORITIES)) {
                Developer::error("Invalid email priority: {$priority}");
                throw new InvalidArgumentException('Invalid priority. Use: ' . implode(', ', self::VALID_PRIORITIES));
            }
            $response = Data::fetch('central', self::TEMPLATE_TABLE, ['select' => ['key', 'name', 'subject', 'content', 'placeholders', 'mailer', 'from_name', 'from_address'], 'key' => $key]);
            Developer::info($response);
            if (!$response['status'] || empty($response['data'])) {
                throw new Exception('Template not found for key: ' . $key);
            }
            $template = $response['data'][0];
            if (empty($template['from_address']) || !filter_var($template['from_address'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid or missing from_address in template.');
            }
            if (!empty($template['mailer']) && !in_array($template['mailer'], array_keys(Config::get('mail.mailers', [])))) {
                throw new InvalidArgumentException("Invalid mailer: {$template['mailer']}");
            }
            foreach ($attachments as $index => $attachment) {
                if (!isset($attachment['type'], $attachment['content'], $attachment['name'], $attachment['extension'])) {
                    throw new InvalidArgumentException("Invalid attachment format at index {$index}.");
                }
                if (!in_array($attachment['type'], self::VALID_ATTACHMENT_TYPES)) {
                    throw new InvalidArgumentException("Invalid attachment type: {$attachment['type']}");
                }
                if ($attachment['type'] === 'file' && !file_exists($attachment['content'])) {
                    throw new InvalidArgumentException("Attachment file not found: {$attachment['content']}");
                }
                if ($attachment['type'] === 'url' && !filter_var($attachment['content'], FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException("Invalid attachment URL: {$attachment['content']}");
                }
            }
            foreach ($pairs as $placeholder => $value) {
                if (!is_string($value) && !is_null($value)) {
                    throw new InvalidArgumentException("Placeholder value for {$placeholder} must be a string or null.");
                }
            }
            $queue = $priority === 'high' ? self::MAIL_QUEUE_HIGH : self::MAIL_QUEUE_LOW;
            SendMailJob::dispatch($template, $toAddresses, $pairs, $attachments)->onQueue($queue);
            return true;
        } catch (Exception $e) {
            Developer::error('Email dispatch failed: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Send a notification to user(s).
     *
     * @param string $businessId.
     * @param string $businessId Comma-separated user IDs.
     * @param string $ids Comma-separated user IDs.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority (defaults to 'low').
     * @param ?string $medium Delivery medium (optional).
     * @param ?string $html Custom HTML content with placeholders (optional).
     * @param ?string $image Image ID for notification (optional).
     * @param ?string $target Sender ID (e.g., 'User_12331', 'Company_ABC123').
     * @return bool Success status.
     */
    public function user(string $businessId, string $ids, string $title, string $message, string $category, string $type, string $priority = 'low', ?string $medium = null, ?string $html = null, ?string $image = null, ?string $target = null): bool
    {
        return $this->sendNotification($businessId, 'user', $ids, $title, $message, $category, $type, $priority, $medium, [
            'html' => $html,
            'image' => $image,
            'target' => $target
        ], UserNotification::class);
    }
    /**
     * Send a notification to role(s).
     *
     * @param string $businessId.
     * @param string $ids Comma-separated role IDs.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority (defaults to 'low').
     * @param ?string $medium Delivery medium (optional).
     * @param ?string $html Custom HTML content with placeholders (optional).
     * @param ?string $image Image ID for notification (optional).
     * @param ?string $target Sender ID (e.g., 'User_12331', 'Company_ABC123').
     * @return bool Success status.
     */
    public function role(string $businessId, string $ids, string $title, string $message, string $category, string $type, string $priority = 'low', ?string $medium = null, ?string $html = null, ?string $image = null, ?string $target = null): bool
    {
        return $this->sendNotification($businessId, 'role', $ids, $title, $message, $category, $type, $priority, $medium, [
            'html' => $html,
            'image' => $image,
            'target' => $target
        ], RoleNotification::class);
    }
    /**
     * Send a notification to scope(s).
     *
     * @param string $businessId.
     * @param string $ids Comma-separated scope IDs.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority (defaults to 'low').
     * @param ?string $medium Delivery medium (optional).
     * @param ?string $html Custom HTML content with placeholders (optional).
     * @param ?string $image Image ID for notification (optional).
     * @param ?string $target Sender ID (e.g., 'User_12331', 'Company_ABC123').
     * @return bool Success status.
     */
    public function scope(string $businessId, string $ids, string $title, string $message, string $category, string $type, string $priority = 'low', ?string $medium = null, ?string $html = null, ?string $image = null, ?string $target = null): bool
    {
        return $this->sendNotification($businessId, 'scope', $ids, $title, $message, $category, $type, $priority, $medium, [
            'html' => $html,
            'image' => $image,
            'target' => $target
        ], ScopeNotification::class);
    }
    /**
     * Send a notification to company(ies).
     *
     * @param string $businessId.
     * @param string $ids Comma-separated company IDs.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority (defaults to 'low').
     * @param ?string $medium Delivery medium (optional).
     * @param ?string $html Custom HTML content with placeholders (optional).
     * @param ?string $image Image ID for notification (optional).
     * @param ?string $target Sender ID (e.g., 'User_12331', 'Company_ABC123').
     * @return bool Success status.
     */
    public function company(string $businessId, string $ids, string $title, string $message, string $category, string $type, string $priority = 'low', ?string $medium = null, ?string $html = null, ?string $image = null, ?string $target = null): bool
    {
        return $this->sendNotification($businessId, 'company', $ids, $title, $message, $category, $type, $priority, $medium, [
            'html' => $html,
            'image' => $image,
            'target' => $target
        ], CompanyNotification::class);
    }
    /**
     * Fetch notifications for a user.
     *
     * @param string $businessId Business ID context.
     * @param string $userId     User ID to fetch notifications for.
     * @param array<string, mixed> $filters Optional extra filters.
     * @return array{success: bool, notifications: array}
     */
    public function fetchForUser(string $businessId, string $userId, array $filters = []): array
    {
        try {
            $connection = Database::getConnection($businessId);
            // Validate and normalize user IDs
            $validUserIds = $this->validateUserIds($connection, [$userId]);
            if (empty($validUserIds)) {
                return ['success' => false, 'notifications' => []];
            }
            $defaultFilters = [
                'notification_recipients.user_id'    => $validUserIds[0],
                'notification_recipients.status'     => 'unread',
                'notification_recipients.deleted_at' => null,
                'notifications.deleted_at'           => null,
            ];
            $where = array_merge($defaultFilters, $filters);
            $notifications = $connection->table('notification_recipients')
                ->select([
                    'notification_recipients.notification_id',
                    'notifications.title',
                    'notifications.message',
                    'notifications.category',
                    'notifications.type',
                    'notifications.priority',
                    'notifications.medium',
                    'notification_recipients.html',
                    'notifications.sender_id',
                    'notification_recipients.created_at',
                    'notification_recipients.notified_at',
                    'notification_recipients.read_at',
                    'notification_recipients.remind_at',
                ])
                ->leftJoin(
                    'notifications',
                    'notification_recipients.notification_id',
                    '=',
                    'notifications.notification_id'
                )
                ->where($where)
                ->orderBy('notification_recipients.created_at', 'desc')
                ->limit(50)
                ->get()
                ->toArray();
            if (!empty($notifications)) {
                $this->markNotified($connection, $validUserIds[0], array_column($notifications, 'notification_id'));
            }
            return ['success' => true, 'notifications' => $notifications];
        } catch (\Exception $e) {
            Developer::error('Fetch notifications failed: ' . $e->getMessage());
            return ['success' => false, 'notifications' => []];
        }
    }
    /**
     * Mark a single notification as read.
     *
     * @return array{success: bool, error?: string}
     */
    public function markAsRead(string $businessId, string $userId, string $notificationId): array
    {
        try {
            $connection = Database::getConnection($businessId);
            $validUserIds = $this->validateUserIds($connection, [$userId]);
            if (empty($validUserIds)) {
                throw new \InvalidArgumentException('Invalid user ID.');
            }
            if (empty($notificationId)) {
                throw new \InvalidArgumentException('Notification ID is required.');
            }
            $affected = $connection->table('notification_recipients')
                ->where([
                    'user_id'        => $validUserIds[0],
                    'notification_id' => $notificationId,
                    'deleted_at'     => null,
                ])
                ->update([
                    'status'  => 'read',
                    'read_at' => now(),
                ]);
            return ['success' => $affected > 0];
        } catch (\Exception $e) {
            Developer::error('Mark as read failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Mark all notifications as read for a user.
     *
     * @return array{success: bool, error?: string}
     */
    public function markAllAsRead(string $businessId, string $userId): array
    {
        try {
            $connection = Database::getConnection($businessId);
            $validUserIds = $this->validateUserIds($connection, [$userId]);
            if (empty($validUserIds)) {
                throw new \InvalidArgumentException('Invalid user ID.');
            }
            $affected = $connection->table('notification_recipients')
                ->where([
                    'user_id'    => $validUserIds[0],
                    'status'     => 'unread',
                    'deleted_at' => null,
                ])
                ->update([
                    'status'  => 'read',
                    'read_at' => now(),
                ]);
            return ['success' => $affected > 0];
        } catch (\Exception $e) {
            Developer::error('Mark all as read failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Set a notification to remind later.
     *
     * @return array{success: bool, error?: string}
     */
    public function remindLater(string $businessId, string $userId, string $notificationId, ?string $remindAt = null): array
    {
        try {
            $connection = Database::getConnection($businessId);
            $validUserIds = $this->validateUserIds($connection, [$userId]);
            if (empty($validUserIds)) {
                throw new \InvalidArgumentException('Invalid user ID.');
            }
            if (empty($notificationId)) {
                throw new \InvalidArgumentException('Notification ID is required.');
            }
            $remindAt = $remindAt ?? now()->addMinutes(30)->toDateTimeString();
            if (!strtotime($remindAt)) {
                throw new \InvalidArgumentException('Invalid remind_at datetime.');
            }
            $affected = $connection->table('notification_recipients')
                ->where([
                    'user_id'        => $validUserIds[0],
                    'notification_id' => $notificationId,
                    'deleted_at'     => null,
                ])
                ->update([
                    'status'    => 'remind_later',
                    'remind_at' => $remindAt,
                ]);
            return ['success' => $affected > 0];
        } catch (\Exception $e) {
            Developer::error('Remind later failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Mark notifications as notified (internal).
     */
    private function markNotified($connection, string $userId, array $notificationIds): void
    {
        if (empty($notificationIds)) {
            return;
        }
        try {
            $connection->table('notification_recipients')
                ->where('user_id', $userId)
                ->whereIn('notification_id', $notificationIds)
                ->update([
                    'notified_at' => now(),
                ]);
        } catch (\Exception $e) {
            Developer::error('Mark notified failed: ' . $e->getMessage());
        }
    }
    /**
     * Generic method to send notifications and broadcast, rendering placeholders for message and html.
     *
     * @param string $businessId.
     * @param string $set Notification set type ('user', 'role', 'scope', 'company').
     * @param string $ids Comma-separated IDs for the set.
     * @param string $title Notification title.
     * @param string $message Notification message (may contain placeholders).
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority.
     * @param ?string $medium Delivery medium.
     * @param array<string, mixed> $options Additional options (html, image, target).
     * @param string $eventClass Event class to dispatch.
     * @return bool Success status.
     */
    private function sendNotification(string $businessId, string $set, string $ids, string $title, string $message, string $category, string $type, string $priority, ?string $medium, array $options, string $eventClass): bool
    {
        try {
            $this->validateNotificationParameters($title, $message, $category, $type, $priority, $medium, $options);
            $connection = Database::getConnection($businessId);
            $idArray = array_filter(array_map('trim', explode(',', $ids)));
            if (empty($idArray)) {
                throw new InvalidArgumentException('At least one ID is required.');
            }
            $userIds = $this->getUserIdsForSet($connection, $set, $idArray);
            if (empty($userIds)) {
                throw new InvalidArgumentException("No users found for {$set} IDs.");
            }
            $notificationId = 'NOT' . Str::upper(Str::random(10));
            $htmlTemplate = $options['html'] ?? null;
            $image = $options['image'] ?? null;
            $target = $options['target'] ?? null;
            // Fetch target entity details once
            $targetData = null;
            if ($target) {
                if (str_starts_with($target, 'User_')) {
                    $targetId = substr($target, 5);
                    $targetData = $connection->table('users')->where('user_id', $targetId)->first();
                } elseif (str_starts_with($target, 'Company_')) {
                    $targetId = substr($target, 8);
                    $targetData = $connection->table('companies')->where('company_id', $targetId)->first();
                } elseif (str_starts_with($target, 'Scope_')) {
                    $targetId = substr($target, 6);
                    $targetData = $connection->table('scopes')->where('scope_id', $targetId)->first();
                }
                if (!$targetData) {
                    Developer::warning("Target not found for ID: {$target}");
                }
            }
            // Insert notification (without message/html, as they are user-specific)
            $this->insertNotification($connection, $set, $idArray, $notificationId, $title, $message, $category, $type, $priority, $medium, $options);
            // Process placeholders for each user and insert recipient records
            $recipientOptions = [];
            foreach ($userIds as $userId) {
                $baseUser = $connection->table('users')->where('user_id', $userId)->first();
                if (!$baseUser) {
                    Developer::warning("User not found for ID: {$userId}");
                    continue;
                }
                // Process placeholders for html (or fallback to message)
                $customHtml = '';
                if ($htmlTemplate) {
                    $customHtml = $this->replacePlaceholders($htmlTemplate, $baseUser, $targetData, $target);
                    $options['html'] = $customHtml;
                }
                // Add image if provided
                if ($image) {
                    $imageUrl = '/files/' . e($image);
                    $customHtml = '<img src="' . $imageUrl . '" alt="Notification Image" style="max-width: 100%;">' . $customHtml;
                }
                $recipientOptions[$userId] = [
                    'message' => $message,
                    'html' => $customHtml,
                    'image' => $image,
                    'target' => $target,
                ];
            }
            // Insert recipients with resolved content
            $this->insertNotificationRecipients($connection, $notificationId, $recipientOptions);
            // Dispatch event with resolved content for broadcasting
            $event = new $eventClass($notificationId, $userIds, $title, $message, $category, $type, $priority, $medium, $options);
            Event::dispatch($event);
            return true;
        } catch (Exception $e) {
            Developer::error("Send notification failed for set {$set}: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Replace placeholders in a template string with user and target data.
     *
     * Supported placeholders:
     *  - ::base_users_first_name::
     *  - ::target_users_last_name::
     *  - ::target_companies_name::
     *
     * @param string      $template    The template string with placeholders.
     * @param object|null $baseUser    The recipient user data (base).
     * @param object|null $targetData  The target entity data (user, company, or scope).
     * @param string|null $targetType  The original target type string (e.g., 'User_', 'Company_', 'Scope_').
     *
     * @return string The template with placeholders replaced.
     */
    private function replacePlaceholders(
        string $template,
        ?object $baseUser,
        ?object $targetData,
        ?string $targetType = null
    ): string {
        $customContent = $template;
        preg_match_all('/::(base|target)_([a-zA-Z]+)_([a-zA-Z_]+)::/', $customContent, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $placeholder = $match[0];
            $type       = $match[1];
            $table      = strtolower($match[2]);
            $column     = $match[3];
            if (!in_array($table, self::SUPPORTED_TABLES)) {
                $customContent = str_replace($placeholder, '', $customContent);
                continue;
            }
            if (!in_array($column, self::SUPPORTED_COLUMNS[$table] ?? [])) {
                $customContent = str_replace($placeholder, '', $customContent);
                continue;
            }
            $value = '';
            if ($type === 'base' && $baseUser) {
                $value = $baseUser->$column ?? '';
            } elseif ($type === 'target' && $targetData) {
                $isValidTarget = false;
                if ($targetType) {
                    $isValidTarget =
                        ($table === 'users' && str_starts_with($targetType, 'User_')) ||
                        ($table === 'companies' && str_starts_with($targetType, 'Company_')) ||
                        ($table === 'scopes' && str_starts_with($targetType, 'Scope_'));
                }
                if ($isValidTarget) {
                    $value = $targetData->$column ?? '';
                } else {
                    Developer::warning("Invalid target type '{$targetType}' for table '{$table}' in placeholder: {$placeholder}");
                }
            } else {
                Developer::warning("Data not available for placeholder: {$placeholder}");
            }
            $customContent = str_replace($placeholder, e($value), $customContent);
        }
        return $customContent;
    }
    /**
     * Validate notification parameters.
     *
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority.
     * @param ?string $medium Delivery medium.
     * @param array<string, mixed> $options Additional options.
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateNotificationParameters(string $title, string $message, string $category, string $type, string $priority, ?string $medium, array $options): void
    {
        if (empty($title) || empty($message) || empty($category)) {
            throw new InvalidArgumentException('Title, message, and category are required.');
        }
        if (!in_array($type, self::VALID_NOTIFICATION_TYPES)) {
            Developer::error("Invalid notification type: {$type}");
            throw new InvalidArgumentException('Invalid notification type. Use: ' . implode(', ', self::VALID_NOTIFICATION_TYPES));
        }
        $priority = strtolower($priority);
        if (!in_array($priority, self::VALID_PRIORITIES)) {
            Developer::error("Invalid notification priority: {$priority}");
            throw new InvalidArgumentException('Invalid priority. Use: ' . implode(', ', self::VALID_PRIORITIES));
        }
        if ($medium) {
            $mediums = array_map('trim', explode(',', $medium));
            if (array_diff($mediums, self::VALID_MEDIUMS)) {
                Developer::error("Invalid medium: {$medium}");
                throw new InvalidArgumentException('Invalid medium. Use: ' . implode(', ', self::VALID_MEDIUMS));
            }
        }
        if (isset($options['html']) && !is_string($options['html'])) {
            throw new InvalidArgumentException('HTML must be a string.');
        }
        if (isset($options['image']) && !is_string($options['image'])) {
            throw new InvalidArgumentException('Image must be a string.');
        }
        if (isset($options['target']) && !is_string($options['target'])) {
            throw new InvalidArgumentException('Target must be a string.');
        }
    }
    /**
     * Validate user IDs with current DB connection.
     *
     * Instead of returning true/false, this returns the list of valid IDs.
     * This avoids duplicate DB calls and gives the caller direct usable IDs.
     *
     * @param \Illuminate\Database\Connection $connection Active DB connection.
     * @param array<string> $userIds Input user IDs.
     * @return array<string> Valid user IDs (active + not deleted).
     */
    private function validateUserIds($connection, array $userIds): array
    {
        if (empty($userIds)) {
            throw new \InvalidArgumentException('At least one user ID is required.');
        }
        $validUserIds = $connection->table('users')
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->where('account_status', 'active')
            ->pluck('user_id')
            ->toArray();
        return $validUserIds;
    }
    /**
     * Validate role IDs and return valid ones.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $roleIds
     * @return array<string> Valid role IDs.
     */
    private function validateRoleIds($connection, array $roleIds): array
    {
        if (empty($roleIds)) {
            throw new InvalidArgumentException('At least one role ID is required.');
        }
        return $connection->table('roles')
            ->whereIn('role_id', $roleIds)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->pluck('role_id')
            ->toArray();
    }
    /**
     * Validate scope IDs and return valid ones.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $scopeIds
     * @return array<string> Valid scope IDs.
     */
    private function validateScopeIds($connection, array $scopeIds): array
    {
        if (empty($scopeIds)) {
            throw new InvalidArgumentException('At least one scope ID is required.');
        }
        return $connection->table('scopes')
            ->whereIn('scope_id', $scopeIds)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->pluck('scope_id')
            ->toArray();
    }
    /**
     * Validate company IDs and return valid ones.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $companyIds
     * @return array<string> Valid company IDs.
     */
    private function validateCompanyIds($connection, array $companyIds): array
    {
        if (empty($companyIds)) {
            throw new InvalidArgumentException('At least one company ID is required.');
        }
        return $connection->table('companies')
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->pluck('company_id')
            ->toArray();
    }
    /**
     * Get user IDs for a given set type.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param string $set Set type ('user', 'role', 'scope', 'company').
     * @param array<string> $idArray Array of IDs.
     * @return array<string> Array of user IDs.
     */
    private function getUserIdsForSet($connection, string $set, array $idArray): array
    {
        $method = "getUserIdsBy" . ucfirst($set) . "Ids";
        if (method_exists($this, $method)) {
            return $this->$method($connection, $idArray);
        }
        throw new InvalidArgumentException("Invalid set type: $set");
    }
    /**
     * Get user IDs by role IDs.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $roleIds
     * @return array<string>
     */
    private function getUserIdsByRoleIds($connection, array $roleIds): array
    {
        $validRoleIds = $this->validateRoleIds($connection, $roleIds);
        if (empty($validRoleIds)) {
            return [];
        }
        return $connection->table('user_roles')
            ->whereIn('role_id', $validRoleIds)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->pluck('user_id')
            ->toArray();
    }
    /**
     * Get user IDs by scope IDs.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $scopeIds
     * @return array<string>
     */
    private function getUserIdsByScopeIds($connection, array $scopeIds): array
    {
        $validScopeIds = $this->validateScopeIds($connection, $scopeIds);
        if (empty($validScopeIds)) {
            return [];
        }
        return $connection->table('scope_mapping')
            ->whereIn('scope_id', $validScopeIds)
            ->whereNull('deleted_at')
            ->pluck('user_id')
            ->toArray();
    }
    /**
     * Get user IDs by company IDs.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $companyIds
     * @return array<string>
     */
    private function getUserIdsByCompanyIds($connection, array $companyIds): array
    {
        $validCompanyIds = $this->validateCompanyIds($connection, $companyIds);
        if (empty($validCompanyIds)) {
            return [];
        }
        return $connection->table('users')
            ->whereIn('company_id', $validCompanyIds)
            ->whereNull('deleted_at')
            ->where('account_status', 'active')
            ->pluck('user_id')
            ->toArray();
    }
    /**
     * Get user IDs by direct user IDs.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param array<string> $userIds
     * @return array<string>
     */
    private function getUserIdsByUserIds($connection, array $userIds): array
    {
        if (empty($userIds)) {
            throw new InvalidArgumentException('At least one user ID is required.');
        }
        return $connection->table('users')
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->where('account_status', 'active')
            ->pluck('user_id')
            ->toArray();
    }
    /**
     * Insert notification into notifications table.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param string $set Notification set type.
     * @param array<string> $setIds Set IDs.
     * @param string $notificationId Notification ID.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category.
     * @param string $type Notification type.
     * @param string $priority Notification priority.
     * @param ?string $medium Delivery medium.
     * @param array<string, mixed> $options Additional options.
     * @return void
     * @throws \Exception
     */
    private function insertNotification(
        $connection,
        string $set,
        array $setIds,
        string $notificationId,
        string $title,
        string $message,
        string $category,
        string $type,
        string $priority,
        ?string $medium,
        array $options
    ): void {
        $inserted = $connection->table('notifications')->insert([
            'notification_id' => $notificationId,
            'set'             => $set,
            'set_ids'         => implode(',', $setIds),
            'title'           => $title,
            'message'         => $message,
            'category'        => $category,
            'type'            => $type,
            'priority'        => $priority,
            'medium'          => $medium,
            'sender_id'       => $options['target'] ?? null,
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        if (!$inserted) {
            throw new \Exception("Failed to insert notification: {$notificationId}");
        }
    }
    /**
     * Insert notification recipients with pre-rendered content.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param string $notificationId Notification ID.
     * @param array<string, array{message: string, html: string, image: ?string}> $recipientOptions User-specific notification data.
     * @return void
     * @throws \Exception
     */
    private function insertNotificationRecipients($connection, string $notificationId, array $recipientOptions): void
    {
        if (empty($recipientOptions)) {
            return;
        }
        $rows = [];
        $now = now();
        foreach ($recipientOptions as $userId => $options) {
            $rows[] = [
                'notification_id' => $notificationId,
                'user_id'         => $userId,
                'html'            => $options['html'],
                'image'           => $options['image'] ?? null,
                'status'          => 'unread',
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }
        $inserted = $connection->table('notification_recipients')->insert($rows);
        if (!$inserted) {
            throw new \Exception("Failed to insert recipients for notification {$notificationId}");
        }
    }
}
