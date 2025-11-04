<?php
// app/Jobs/SmartPresence/GenerateQrJob.php
namespace App\Jobs\SmartPresence;

use App\Facades\Data;
use App\Events\SmartPresence\QrUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $businessId;
    protected $companyId;
    protected $interval;

    public function __construct($businessId, $companyId, $interval = 3)
    {
        $this->businessId = $businessId;
        $this->companyId = $companyId;
        $this->interval = $interval;
    }

    public function handle()
    {
        $cacheKeyActive = "qr_active_{$this->businessId}_{$this->companyId}";
        $cacheKeyRunning = "qr_job_running_{$this->companyId}_{$this->businessId}";
        $cacheKeyLastScan = "last_scan_{$this->businessId}_{$this->companyId}";

        // Check if active and no recent broadcast needed stop
        $lastScan = Cache::get($cacheKeyLastScan, now());
        $secondsSinceScan = now()->diffInSeconds($lastScan);

        if (!Cache::get($cacheKeyActive) || $secondsSinceScan >= 13) {
            Cache::forget($cacheKeyRunning);
            Cache::forget($cacheKeyActive);
            Cache::forget($cacheKeyLastScan);
            return; // Stop self and don't redispatch
        }

        // Update last scan to now for this broadcast
        Cache::put($cacheKeyLastScan, now(), now()->addMinutes(10));

        // Fetch company token
        $tokenData = Data::fetch($this->businessId, 'smart_qr_tokens', ['where' => ['company_id' => $this->companyId, 'is_active' => 1]]);

        if (!$tokenData || empty($tokenData['data'])) {
            // No active token, stop
            Cache::forget($cacheKeyRunning);
            return;
        }

        $companyToken = $tokenData['data'][0]['token'];
        $sessionToken = Str::random(16);

        $finalToken = "{$companyToken}-{$sessionToken}";

        // Broadcast to frontend
        broadcast(new QrUpdated($finalToken, $this->companyId, $this->businessId))->toOthers();

        // Re-dispatch after interval if still active
        if (Cache::get($cacheKeyActive)) {
            self::dispatch($this->businessId, $this->companyId, $this->interval)
                ->delay(now()->addSeconds($this->interval))
                ->onQueue('default');
        } else {
            Cache::forget($cacheKeyRunning);
        }
    }

    public function failed(\Throwable $exception)
    {
        Cache::forget("qr_job_running_{$this->businessId}_{$this->companyId}");
        // Log if needed
    }
}