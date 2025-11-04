<?php

namespace App\Events\DataSet;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataSetEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $businessId;
    public string $key;

    // force broadcast immediately (optional)
    public $connection = 'sync';

    public function __construct(string $businessId, string $key)
    {
        $this->businessId = $businessId;
        $this->key = $key;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("business.{$this->businessId}.dataset");
    }

    public function broadcastAs(): string
    {
        return 'dataset.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'token' => $this->key,
            'businessId' => $this->businessId,
        ];
    }
}
