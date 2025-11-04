<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId, $chatId, $channelType, $channelId;

    public function __construct($userId, $chatId, $channelType, $channelId)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
        $this->channelType = $channelType; // 'business', 'company', or 'scope'
        $this->channelId = $channelId; // business_id, company_id, or scope_id
    }

    public function broadcastOn()
    {
        $channelName = "presence-{$this->channelType}.{$this->channelId}";
        \Log::info("Broadcasting UserTyping to {$channelName} for user {$this->userId}");
        return new PresenceChannel($channelName);
    }

    public function broadcastAs()
    {
        return 'UserTyping';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
            'channel_type' => $this->channelType,
            'channel_id' => $this->channelId,
        ];
    }
}