<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId, $status, $lastSeenAt, $channelType, $channelId;

    public function __construct($userId, $status, $channelType, $channelId, $lastSeenAt = null)
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->lastSeenAt = $lastSeenAt;
        $this->channelType = $channelType; // 'business', 'company', or 'scope'
        $this->channelId = $channelId; // business_id, company_id, or scope_id
    }

    public function broadcastOn()
    {
        $channelName = "presence-{$this->channelType}.{$this->channelId}";
        \Log::info("Broadcasting UserStatusUpdated to {$channelName} for user {$this->userId}");
        return new PresenceChannel($channelName);
    }

    public function broadcastAs()
    {
        return 'UserStatusUpdated';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'last_seen_at' => $this->lastSeenAt,
            'channel_type' => $this->channelType,
            'channel_id' => $this->channelId,
        ];
    }
}