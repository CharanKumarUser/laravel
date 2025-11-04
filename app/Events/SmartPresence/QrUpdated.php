<?php

namespace App\Events\SmartPresence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class QrUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public string $qr;
    public string $companyId;
    public string $businessId;
 
    /**
     * @param string $qr         The token to render in the QR
     * @param string $companyId  Company ID for channel
     * @param string $businessId Business ID for channel
     */
    public function __construct(string $qr, string $companyId, string $businessId)
    {
        $this->qr = $qr;
        $this->companyId = $companyId;
        $this->businessId = $businessId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        // Private channel per business + company
        return new Channel("business.{$this->businessId}.{$this->companyId}");
    }

    /**
     * Optional: set custom event name
     */
    public function broadcastAs(): string
    {
        return 'QrUpdated';
    }

    /**
     * Optional: data sent to the frontend
     */
    public function broadcastWith(): array
    {
        return [
            'qr' => $this->qr,
            'companyId' => $this->companyId,
            'businessId' => $this->businessId,
        ];
    }
}
 