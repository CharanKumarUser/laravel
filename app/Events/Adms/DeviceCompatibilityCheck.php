<?php
namespace App\Events\Adms;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting device compatibility check updates.
 */
class DeviceCompatibilityCheck implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $onboardingId;
    public $deviceCount;
    public $syncedDevices;
    public $latestDevice;

    /**
     * Create a new event instance.
     *
     * @param string $onboardingId
     * @param int $deviceCount
     * @param int $syncedDevices
     * @param array|null $latestDevice
     */
    public function __construct(string $onboardingId, int $deviceCount, int $syncedDevices, ?array $latestDevice = null)
    {
        $this->onboardingId = $onboardingId;
        $this->deviceCount = $deviceCount;
        $this->syncedDevices = $syncedDevices;
        $this->latestDevice = $latestDevice;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new Channel('device-compatibility-check.' . $this->onboardingId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'deviceCount' => $this->deviceCount,
            'syncedDevices' => $this->syncedDevices,
            'latestDevice' => $this->latestDevice,
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'deviceCompatibilityCheck';
    }
}