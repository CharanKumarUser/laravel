<?php
namespace App\Jobs\Adms;

use App\Facades\{Adms, Developer};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Cache, Config};
use InvalidArgumentException;
use Exception;
use Carbon\Carbon;

/**
 * Job to process ADMS iClock data (cdata) for OPERLOG (USER, FP, FACE) and ATTLOG.
 * Optimized for ultra-high throughput (100K pins, 10K devices, 100K requests/hour) with Redis queue/cache,
 * batch processing, and comprehensive error handling.
 * All parameters configurable via config/env.
 */
class AdmsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries;
    public $timeout;
    protected string $deviceId;
    protected string $businessId;
    protected string $type;
    protected string $data;
    protected array $meta;

    /**
     * Initialize job with device and business context.
     *
     * @param string $deviceId Device identifier
     * @param string $businessId Business identifier
     * @param string $type Data type (e.g., cdata, fdata)
     * @param string $data Raw tab-separated data
     * @param array $meta Metadata including table type
     */
    public function __construct(string $deviceId, string $businessId, string $type, string $data, array $meta)
    {
        $this->deviceId = $deviceId;
        $this->businessId = $businessId;
        $this->type = $type;
        $this->data = $data;
        $this->meta = $meta;
        $this->tries = Config::get('adms.queue.retries', 3);
        $this->timeout = Config::get('adms.queue.timeout', 300);
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job to parse and store records with rate limiting and error handling.
     *
     * @throws InvalidArgumentException If data is invalid
     */
    public function handle(): void
    {
        try {
            // Validate input data
            $supportedTypes = Config::get('adms.supported_data_types', ['cdata', 'fdata']);
            if (!in_array($this->type, $supportedTypes)) {
                throw new InvalidArgumentException("Invalid data type: {$this->type}");
            }
            if (empty(trim($this->data))) {
                throw new InvalidArgumentException('Empty data payload');
            }

            // Device rate limiting for job execution
            $rateLimitKey = "ratelimit:device:{$this->businessId}:{$this->deviceId}";
            $jobRate = Config::get('adms.rate_limit.per_device', 100);
            $rateLimitTtl = Config::get('adms.rate_limit.ttl', 60);
            if (!$this->checkTemporaryCacheRateLimit($rateLimitKey, $jobRate, $rateLimitTtl)) {
                throw new Exception("Job rate limit exceeded for {$this->businessId}:{$this->deviceId}");
            }

            // Parse data into structured records
            $records = $this->parseData();
            if (empty($records['USER']) && empty($records['FP']) && empty($records['FACE']) && empty($records['ATND'])) {
                Developer::warning('No valid records parsed', [
                    'type' => $this->type,
                    'deviceId' => $this->deviceId,
                    'businessId' => $this->businessId,
                ]);
                return;
            }

            // Process records
            $this->processRecords($records);
        } catch (InvalidArgumentException $e) {
            Developer::warning("Job execution failed: {$e->getMessage()}", [
                'deviceId' => $this->deviceId,
                'businessId' => $this->businessId,
                'type' => $this->type,
            ]);
            throw $e;
        } catch (Exception $e) {
            Developer::error("Job execution error: {$e->getMessage()}", [
                'deviceId' => $this->deviceId,
                'businessId' => $this->businessId,
                'type' => $this->type,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse tab-separated data into structured records matching table columns.
     * Uses caching to avoid redundant parsing for identical data.
     *
     * @return array Structured records for USER, FP, FACE, and ATND
     */
    protected function parseData(): array
    {
        $dataHash = md5($this->data);
        $cacheKey = "adms:parsed:{$this->businessId}:{$this->deviceId}:{$dataHash}";
        $cacheTtl = Config::get('adms.cache.commands_ttl', 30);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $records = ['USER' => [], 'FP' => [], 'FACE' => [], 'ATND' => []];
            $lines = array_filter(explode("\n", trim($this->data)), 'strlen');
            if (empty($lines)) {
                return $records;
            }

            $table = $this->meta['table'] ?? '';
            $batchSize = Config::get('adms.batch_size', 1000);

            // Helper closure to parse key-value pairs
            $parseKeyValueRecord = function (array $parts, string $pinKey): ?array {
                $record = [];
                $deviceUserId = '';
                foreach ($parts as $pair) {
                    if (!str_contains($pair, '=')) {
                        continue;
                    }
                    [$key, $value] = explode('=', $pair, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if ($key === $pinKey) {
                        $deviceUserId = $value;
                        $record['device_user_id'] = $value;
                    } else {
                        $record[$key] = $value;
                    }
                }
                return empty($deviceUserId) ? null : $record;
            };

            // Process lines in chunks for memory efficiency
            collect($lines)->chunk($batchSize)->each(function ($chunk) use ($table, $parseKeyValueRecord, &$records) {
                foreach ($chunk as $line) {
                    if (!str_contains($line, "\t")) {
                        continue;
                    }
                    $parts = explode("\t", trim($line));

                    if ($table === 'ATTLOG') {
                        if (count($parts) < 9) {
                            continue;
                        }
                        $records['ATND'][] = [
                            'device_id' => $this->deviceId,
                            'device_user_id' => $parts[0],
                            'timestamp' => Carbon::parse($parts[1] ?? now())->toDateTimeString(),
                            'method' => (int) ($parts[3] ?? 1),
                            'punch' => (int) ($parts[2] ?? 255),
                            'code_1' => $parts[4] ?? null,
                            'code_2' => $parts[7] ?? null,
                            'code_3' => $parts[8] ?? null,
                            'created_at' => now(),
                        ];
                    } elseif ($table === 'OPERLOG') {
                        if (str_starts_with($line, 'USER PIN')) {
                            if ($user = $parseKeyValueRecord($parts, 'USER PIN')) {
                                $records['USER'][] = [
                                    'device_user_id' => $user['device_user_id'],
                                    'device_id' => $this->deviceId,
                                    'name' => $user['Name'] ?? 'Unknown',
                                    'privilege' => (int) ($user['Pri'] ?? 0),
                                    'password' => $user['Passwd'] ?? null,
                                    'card_number' => $user['Card'] ?? null,
                                    'group_id' => (int) ($user['Grp'] ?? 1),
                                    'time_zone' => $user['time_zone'] ?? '0000000100000000',
                                    'expires' => (int) ($user['Expires'] ?? 0),
                                    'start_datetime' => $user['StartDatetime'] ?? null,
                                    'end_datetime' => $user['EndDatetime'] ?? null,
                                    'valid_count' => (int) ($user['valid_count'] ?? 0),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        } elseif (str_starts_with($line, 'FP PIN')) {
                            if ($fp = $parseKeyValueRecord($parts, 'FP PIN')) {
                                $records['FP'][] = [
                                    'device_user_id' => $fp['device_user_id'],
                                    'device_id' => $this->deviceId,
                                    'fid' => (int) ($fp['FID'] ?? 0),
                                    'size' => (int) ($fp['SIZE'] ?? 0),
                                    'valid' => (int) ($fp['Valid'] ?? 1),
                                    'template' => $fp['TMP'] ?? '',
                                    'created_at' => now(),
                                ];
                            }
                        } elseif (str_starts_with($line, 'FACE PIN')) {
                            if ($face = $parseKeyValueRecord($parts, 'FACE PIN')) {
                                $records['FACE'][] = [
                                    'device_user_id' => $face['device_user_id'],
                                    'device_id' => $this->deviceId,
                                    'fid' => (int) ($face['FID'] ?? 0),
                                    'size' => (int) ($face['SIZE'] ?? 0),
                                    'valid' => (int) ($face['Valid'] ?? 1),
                                    'template' => $face['TMP'] ?? '',
                                    'created_at' => now(),
                                ];
                            }
                        }
                    }
                }
                gc_collect_cycles(); // Free memory after each chunk
            });

            return $records;
        });
    }

    /**
     * Process records by calling AdmsService methods with batching for high volume.
     *
     * @param array $records Records to process (USER, FP, FACE, ATND)
     */
    protected function processRecords(array $records): void
    {
        $recordTypes = [
            'USER' => 'storeUser',
            'FP' => 'storeFingerprint',
            'FACE' => 'storeFace',
            'ATND' => 'storeAttendance',
        ];
        $batchSize = Config::get('adms.batch_size', 1000);

        foreach ($recordTypes as $type => $method) {
            if (!empty($records[$type])) {
                // Process in chunks to optimize for high volume
                collect($records[$type])->chunk($batchSize)->each(function ($chunk) use ($method) {
                    Adms::$method($this->businessId, $this->deviceId, $chunk->toArray());
                    gc_collect_cycles(); // Free memory after each chunk
                });
            }
        }
    }

    /**
     * Returns the queue name for job processing.
     *
     * @return string
     */
    protected function getQueueName(): string
    {
        return Config::get('adms.queue.name', 'adms');
    }

    /**
     * Checks rate limit using temporary cache for a given key and limit.
     *
     * @param string $key Cache key for rate limiting
     * @param int $maxRequests Maximum allowed requests
     * @param int $ttl Cache TTL
     * @return bool
     */
    protected function checkTemporaryCacheRateLimit(string $key, int $maxRequests, int $ttl): bool
    {
        $count = Cache::get($key, 0);
        if ($count >= $maxRequests) {
            return false;
        }

        Cache::put($key, $count + 1, $ttl);
        return true;
    }
}