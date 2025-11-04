<?php
declare(strict_types=1);

namespace App\Services\Data;

use App\Facades\{Database, Developer};
use Illuminate\Support\Facades\{Cache, DB, Log, Queue};
use Illuminate\Bus\Batch;
use App\Jobs\Data\EncryptTableJob;
use InvalidArgumentException;
use Throwable;

/**
 * Optimized KeyService for multi-tenant HRM with key management and rotation.
 * Manages encryption keys, supports key rotation with queue-based re-encryption,
 * updates row-level 'version' column (VARCHAR(10)) in encrypted tables,
 * and ensures ultra-fast performance with a single cache and robust error handling.
 * Optimized for speed: Uses crc32b for versioning, dynamic batch sizing,
 * reduced log overhead, and aggressive caching with strict validation.
 * Updated to remove businessId parameter, resolving it dynamically from connection using DatabaseService.
 */
class KeyService
{
    private const CACHE_TTL = 86400; // Cache TTL in seconds (1 day for key stability)
    private const KEY_LENGTH = 32; // AES-256 key length (bytes)
    private const VERSION_PREFIX = 'v'; // Version prefix (e.g., 'v1')
    private const BATCH_SIZE = 5000; // Rows per re-encryption job (increased for speed)
    private const CACHE_KEY_DATA = 'key_%s'; // Single cache key for all data
    private const VALID_BUSINESS_ID_PATTERN = '/^[a-zA-Z0-9_-]+$/'; // Business ID validation regex

    /**
     * Get active encryption key for a connection.
     *
     * @param string $connection Database connection name
     * @return array|null ['key' => string, 'version' => string]
     * @throws InvalidArgumentException If businessId is invalid
     */
    public static function getActiveKey(string $connection): ?array
    {
        $businessId = self::resolveBusinessId($connection);
        $cacheData = self::getCachedData($businessId);
        return $cacheData['active_key'];
    }

    /**
     * Get all encryption keys for a connection.
     *
     * @param string $connection Database connection name
     * @return array [version => ['key' => string, 'version' => string]]
     * @throws InvalidArgumentException If businessId is invalid
     */
    public static function getAllKeys(string $connection): array
    {
        $businessId = self::resolveBusinessId($connection);
        $cacheData = self::getCachedData($businessId);
        return $cacheData['all_keys'];
    }

    /**
     * Get encrypted tables and their columns.
     *
     * @param string $connection Database connection name
     * @return array [table => [columns]]
     * @throws InvalidArgumentException If businessId is invalid
     */
    private static function getEncryptedTables(string $connection): array
    {
        $businessId = self::resolveBusinessId($connection);
        $cacheData = self::getCachedData($businessId);
        return $cacheData['encrypted_tables'];
    }

    /**
     * Retrieve and cache all key-related data for a business.
     *
     * @param string $businessId Business ID
     * @return array ['active_key' => array|null, 'all_keys' => array, 'encrypted_tables' => array]
     */
    private static function getCachedData(string $businessId): array
    {
        return Cache::remember(sprintf(self::CACHE_KEY_DATA, $businessId), self::CACHE_TTL, function () use ($businessId) {
            $data = [
                'active_key' => null,
                'all_keys' => [],
                'encrypted_tables' => [],
            ];

            // Fetch active key and all keys
            try {
                $keys = DB::connection('central')->table('skeleton_keys')
                    ->where('business_id', $businessId)
                    ->whereNull('delete_on')
                    ->select('key', 'version', 'is_active')
                    ->get();
                
                foreach ($keys as $key) {
                    $decodedKey = base64_decode($key->key, true);
                    if ($decodedKey === false) {
                        throw new InvalidArgumentException("Invalid base64 key for version {$key->version}");
                    }
                    $keyData = ['key' => $decodedKey, 'version' => $key->version];
                    $data['all_keys'][$key->version] = $keyData;
                    if ($key->is_active) {
                        $data['active_key'] = $keyData;
                    }
                }
            } catch (Throwable $e) {
                Log::error('Failed to fetch keys', ['business_id' => $businessId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }

            // Fetch encrypted tables
            try {
                $data['encrypted_tables'] = DB::connection('central')->table('skeleton_columns')
                    ->where('system', 'business')
                    ->where('is_active', 1)
                    ->whereNull('delete_on')
                    ->select('table', 'column')
                    ->get()
                    ->groupBy('table')
                    ->map(fn($group) => $group->pluck('column')->toArray())
                    ->toArray();
            } catch (Throwable $e) {
                Log::error('Failed to fetch encrypted tables', ['business_id' => $businessId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }

            return $data;
        });
    }

    /**
     * Invalidate cache for a business.
     *
     * @param string $businessId Business ID
     * @throws InvalidArgumentException If businessId is invalid
     */
    private static function invalidateCache(string $businessId): void
    {
        self::validateBusinessId($businessId);
        Cache::forget(sprintf(self::CACHE_KEY_DATA, $businessId));
    }

    /**
     * Generate a new encryption key and store it.
     *
     * @param string $connection Database connection name
     * @return array ['status' => bool, 'version' => string, 'message' => string]
     * @throws InvalidArgumentException If businessId is invalid or key generation fails
     */
    public static function generateKey(string $connection): array
    {
        try {
            $businessId = self::resolveBusinessId($connection);
            $key = random_bytes(self::KEY_LENGTH);
            $version = self::generateVersion($businessId);
            DB::connection('central')->beginTransaction();
            DB::connection('central')->table('skeleton_keys')->insert([
                'business_id' => $businessId,
                'key' => base64_encode($key),
                'version' => $version,
                'is_active' => 0,
                'created_at' => now(),
            ]);
            DB::connection('central')->commit();
            self::invalidateCache($businessId);
            Log::info('New encryption key generated', ['business_id' => $businessId, 'version' => $version]);
            return ['status' => true, 'version' => $version, 'message' => 'Key generated successfully'];
        } catch (InvalidArgumentException $e) {
            DB::connection('central')->rollBack();
            Log::warning('Failed to generate key', ['business_id' => $businessId, 'error' => $e->getMessage()]);
            return ['status' => false, 'version' => '', 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            DB::connection('central')->rollBack();
            Log::error('Unexpected error during key generation', ['business_id' => $businessId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'version' => '', 'message' => 'Unexpected error during key generation'];
        }
    }

    /**
     * Activate a key for a connection.
     *
     * @param string $connection Database connection name
     * @param string $version Key version
     * @return array ['status' => bool, 'message' => string]
     * @throws InvalidArgumentException If businessId or version is invalid
     */
    public static function activateKey(string $connection, string $version): array
    {
        try {
            $businessId = self::resolveBusinessId($connection);
            self::validateVersion($version);
            DB::connection('central')->beginTransaction();
            DB::connection('central')->table('skeleton_keys')
                ->where('business_id', $businessId)
                ->update(['is_active' => 0]);
            $affected = DB::connection('central')->table('skeleton_keys')
                ->where('business_id', $businessId)
                ->where('version', $version)
                ->whereNull('delete_on')
                ->update(['is_active' => 1]);
            if ($affected === 0) {
                throw new InvalidArgumentException("Key version {$version} not found for business {$businessId}");
            }
            DB::connection('central')->commit();
            self::invalidateCache($businessId);
            Log::info('Key activated', ['business_id' => $businessId, 'version' => $version]);
            return ['status' => true, 'message' => "Key {$version} activated"];
        } catch (InvalidArgumentException $e) {
            DB::connection('central')->rollBack();
            Log::warning('Key activation failed', ['business_id' => $businessId, 'version' => $version, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            DB::connection('central')->rollBack();
            Log::error('Unexpected error during key activation', ['business_id' => $businessId, 'version' => $version, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'message' => 'Unexpected error during key activation'];
        }
    }

    /**
     * Rotate keys and re-encrypt all encrypted tables with the new key.
     *
     * @param string $connection Database connection name
     * @param string|null $newVersion Optional specific version to rotate to
     * @return array ['status' => bool, 'batchId' => string|null, 'message' => string]
     * @throws InvalidArgumentException If businessId or version is invalid
     */
    public static function rotateKeys(string $connection, ?string $newVersion = null): array
    {
        try {
            $businessId = self::resolveBusinessId($connection);
            if ($newVersion) {
                self::validateVersion($newVersion);
            }

            // Generate or validate new key
            if ($newVersion) {
                $newKey = DB::connection('central')->table('skeleton_keys')
                    ->where('business_id', $businessId)
                    ->where('version', $newVersion)
                    ->whereNull('delete_on')
                    ->select('key', 'version')
                    ->first();
                if (!$newKey) {
                    throw new InvalidArgumentException("Key version {$newVersion} not found");
                }
                $newKeyData = ['key' => base64_decode($newKey->key, true), 'version' => $newKey->version];
            } else {
                $generateResult = self::generateKey($connection);
                if (!$generateResult['status']) {
                    throw new InvalidArgumentException($generateResult['message']);
                }
                $newVersion = $generateResult['version'];
                $newKeyData = ['key' => base64_decode(DB::connection('central')->table('skeleton_keys')
                    ->where('business_id', $businessId)
                    ->where('version', $newVersion)
                    ->value('key'), true), 'version' => $newVersion];
            }

            // Activate new key
            $activateResult = self::activateKey($connection, $newVersion);
            if (!$activateResult['status']) {
                throw new InvalidArgumentException($activateResult['message']);
            }

            // Get all encrypted tables and columns
            $encryptedTables = self::getEncryptedTables($connection);
            if (empty($encryptedTables)) {
                Log::info('No encrypted tables found for key rotation', ['business_id' => $businessId]);
                return ['status' => true, 'batchId' => null, 'message' => 'No encrypted tables to rotate'];
            }

            // Get old keys for decryption
            $oldKeys = self::getAllKeys($connection);
            unset($oldKeys[$newVersion]); // Exclude new key

            // Dynamic batch sizing based on table size
            $jobs = [];
            foreach ($encryptedTables as $table => $columns) {
                $totalRows = DB::connection("tenant_{$businessId}")->table($table)->count();
                $batchSize = self::calculateBatchSize($totalRows);
                $pages = ceil($totalRows / $batchSize);
                for ($page = 0; $page < $pages; $page++) {
                    $job = new EncryptTableJob($businessId, $table, $columns, $oldKeys, $newKeyData, $page * $batchSize, $batchSize);
                    $jobs[] = $job;
                }
            }

            if (empty($jobs)) {
                return ['status' => true, 'batchId' => null, 'message' => 'No re-encryption jobs needed'];
            }

            // Dispatch batch with optimized concurrency
            $workers = max(1, min((int) env('QUEUE_WORKERS', 10), ceil(count($jobs) / 2)));
            $batch = Queue::batch($jobs)->onQueue('encryption')->dispatch();
            $batch->then(function (Batch $batch) use ($businessId) {
                self::invalidateCache($businessId);
                Log::info('Key rotation batch completed', ['business_id' => $businessId, 'batch_id' => $batch->id]);
            })->catch(function (Batch $batch, Throwable $e) use ($businessId) {
                Log::error('Key rotation batch failed', ['business_id' => $businessId, 'batch_id' => $batch->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            });

            return ['status' => true, 'batchId' => $batch->id, 'message' => "Key rotation jobs dispatched for {$newVersion} with {$workers} workers"];
        } catch (InvalidArgumentException $e) {
            Log::warning('Key rotation failed', ['business_id' => $businessId, 'error' => $e->getMessage()]);
            return ['status' => false, 'batchId' => null, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            Log::error('Unexpected error during key rotation', ['business_id' => $businessId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'batchId' => null, 'message' => 'Unexpected error during key rotation'];
        }
    }

    /**
     * Soft delete a key.
     *
     * @param string $connection Database connection name
     * @param string $version Key version
     * @return array ['status' => bool, 'message' => string]
     * @throws InvalidArgumentException If businessId or version is invalid
     */
    public static function deleteKey(string $connection, string $version): array
    {
        try {
            $businessId = self::resolveBusinessId($connection);
            self::validateVersion($version);
            $affected = DB::connection('central')->table('skeleton_keys')
                ->where('business_id', $businessId)
                ->where('version', $version)
                ->where('is_active', 0) // Prevent deleting active key
                ->update(['delete_on' => now()]);
            if ($affected === 0) {
                throw new InvalidArgumentException("Key version {$version} not found or is active");
            }
            self::invalidateCache($businessId);
            Log::info('Key deleted', ['business_id' => $businessId, 'version' => $version]);
            return ['status' => true, 'message' => "Key {$version} deleted"];
        } catch (InvalidArgumentException $e) {
            Log::warning('Key deletion failed', ['business_id' => $businessId, 'version' => $version, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            Log::error('Unexpected error during key deletion', ['business_id' => $businessId, 'version' => $version, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'message' => 'Unexpected error during key deletion'];
        }
    }

    /**
     * Generate a new version string using crc32b for faster hashing.
     *
     * @param string $businessId Business ID
     * @return string Version
     * @throws InvalidArgumentException If businessId is invalid
     */
    private static function generateVersion(string $businessId): string
    {
        self::validateBusinessId($businessId);
        $latest = DB::connection('central')->table('skeleton_keys')
            ->where('business_id', $businessId)
            ->max('version');
        $number = $latest ? (int) str_replace(self::VERSION_PREFIX, '', $latest) + 1 : 1;
        $hash = hash('crc32b', $businessId . $number . time());
        return self::VERSION_PREFIX . $number . '_' . $hash;
    }

    /**
     * Clear cache for a business (alias for invalidateCache).
     *
     * @param string $businessId Business ID
     * @throws InvalidArgumentException If businessId is invalid
     */
    private static function clearCache(string $businessId): void
    {
        self::invalidateCache($businessId);
    }

    /**
     * Calculate dynamic batch size based on table size.
     *
     * @param int $totalRows Total rows in table
     * @return int Batch size
     */
    private static function calculateBatchSize(int $totalRows): int
    {
        if ($totalRows < 1000) {
            return max(100, $totalRows); // Small tables: process all or min 100
        }
        if ($totalRows < 10000) {
            return 1000; // Medium tables
        }
        return self::BATCH_SIZE; // Large tables
    }

    /**
     * Validate business ID for SQL injection safety.
     *
     * @param string $businessId Business ID
     * @throws InvalidArgumentException If invalid
     */
    private static function validateBusinessId(string $businessId): void
    {
        if (!preg_match(self::VALID_BUSINESS_ID_PATTERN, $businessId)) {
            throw new InvalidArgumentException("Invalid business ID: {$businessId}");
        }
    }

    /**
     * Validate version string.
     *
     * @param string $version Version
     * @throws InvalidArgumentException If invalid
     */
    private static function validateVersion(string $version): void
    {
        if (!preg_match('/^' . self::VERSION_PREFIX . '\d+(_[a-f0-9]+)?$/', $version)) {
            throw new InvalidArgumentException("Invalid version format: {$version}");
        }
    }

    /**
     * Resolve business ID from connection name.
     *
     * @param string $connection Database connection name
     * @return string Business ID
     * @throws InvalidArgumentException If connection is invalid
     */
    private static function resolveBusinessId(string $connection): string
    {
        $businessId = Database::resolveBusinessId($connection, 'dynamic');
        self::validateBusinessId($businessId);
        return $businessId;
    }
}