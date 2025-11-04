<?php

namespace App\Events\Notifications;

use Illuminate\Broadcasting\{Channel, PrivateChannel, InteractsWithSockets};
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Collection;

/**
 * UserNotification event broadcasts notifications to specific users.
 *
 * This event is triggered when a notification is sent to individual users, broadcasting
 * details such as title, message, and custom HTML content to private channels for each user.
 * It aligns with the updated NotificationService, using an options array for HTML, image, and sender data.
 */
class UserNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    // Public properties with type declarations for better clarity and IDE support
    public string $notificationId;
    public array $userIds;
    public string $title;
    public string $message;
    public string $category;
    public string $type;
    public string $priority;
    public ?string $medium;
    public array $options;

    /**
     * Create a new UserNotification event instance.
     *
     * @param string $notificationId Unique identifier for the notification.
     * @param array $userIds Array of user IDs to receive the notification.
     * @param string $title Notification title.
     * @param string $message Notification message.
     * @param string $category Notification category (e.g., 'welcome', 'system').
     * @param string $type Notification type (e.g., 'success', 'info').
     * @param string $priority Notification priority (e.g., 'low', 'medium', 'high').
     * @param ?string $medium Delivery medium (e.g., 'app', 'email', 'sms').
     * @param array $options Additional options (html, image, target).
     */
    public function __construct(
        string $notificationId,
        array $userIds,
        string $title,
        string $message,
        string $category,
        string $type,
        string $priority,
        ?string $medium,
        array $options
    ) {
        $this->notificationId = $notificationId;
        $this->userIds = $userIds;
        $this->title = $title;
        $this->message = $message;
        $this->category = $category;
        $this->type = $type;
        $this->priority = $priority;
        $this->medium = $medium;
        $this->options = $options;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Maps each user ID to a private channel (user.{userId}) for targeted broadcasting.
     *
     * @return array<int, PrivateChannel> Array of private channels.
     */
    public function broadcastOn(): array
    {
        return collect($this->userIds)
            ->map(fn(string $userId): PrivateChannel => new PrivateChannel("user.{$userId}"))
            ->toArray();
    }

    /**
     * Get the event name for broadcasting.
     *
     * @return string The event name.
     */
    public function broadcastAs(): string
    {
        return 'UserNotification';
    }

    /**
     * Get the data to broadcast.
     *
     * Includes notification details and options (html, image, sender_id).
     * The html field contains resolved placeholder content from NotificationService.
     *
     * @return array<string, mixed> The broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'type' => $this->type,
            'priority' => $this->priority,
            'medium' => $this->medium,
            'html' => $this->options['html'] ?? null,
            'image' => $this->options['image'] ?? null,
            'sender_id' => $this->options['target'] ?? null,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}