<?php
declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\{Auth, Cache, Config, DB, Log};
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Ultra-fast DatabaseService for multi-business setup.
 *
 * Features:
 * - Only two connection names: "central" and "business".
 * - Resolves CENTRAL, business_id (BIZxxx), or user context (business/open/dynamic).
 * - Per-request connection pooling (no repeated switching).
 * - Caches business_id → business DB mapping (memory + cache).
 * - Strict fail-fast error handling.
 * - Purge + health-check utilities.
 */
class DatabaseService
{
    private const CENTRAL = 'central';
    private const BUSINESS = 'business';
    private const CACHE_PREFIX = 'business_db_map_';
    private const CACHE_TTL = 300; // 5 minutes

    /** @var array<string, Connection> */
    private static array $pool = [];

    /** @var array<string, string> In-memory tenant cache */
    private static array $tenantCache = [];

    private static ?string $lastBusinessDb = null;

    /**
     * Get optimized connection instance.
     */
    public function getConnection(string $connection): Connection
    {
        $bizId = $this->resolveBusinessId($connection);
        $name  = $bizId === 'central' ? self::CENTRAL : self::BUSINESS;

        // Ensure central config always exists
        $this->setCentralConfig();

        if ($name === self::CENTRAL) {
            return $this->getOrMake(self::CENTRAL, $this->getCentralConfig());
        }

        // Business connection
        $businessDb = $this->getBusinessDatabase($bizId);

        if (self::$lastBusinessDb === $businessDb && isset(self::$pool[self::BUSINESS])) {
            $conn = self::$pool[self::BUSINESS];
            if ($this->alive($conn)) {
                return $conn;
            }
            unset(self::$pool[self::BUSINESS]);
        }

        $cfg = $this->getBusinessConfig($businessDb);
        $conn = $this->getOrMake(self::BUSINESS, $cfg);
        self::$lastBusinessDb = $businessDb;
        return $conn;
    }

    /**
     * Resolve business_id or central.
     */
    public function resolveBusinessId(string $connection): string
    {
        $c = trim($connection);

        if (strcasecmp($c, self::CENTRAL) === 0) {
            return 'central';
        }

        if (Str::startsWith($c, 'BIZ', true)) {
            return Str::upper($c);
        }

        if (in_array(strtolower($c), ['business', 'open', 'dynamic'], true)) {
            $user = Auth::user();
            if (!$user || empty($user->business_id)) {
                throw new InvalidArgumentException("No business_id found for {$connection}");
            }
            $biz = $user->business_id;
            return (strcasecmp($biz, self::CENTRAL) === 0 || !Str::startsWith($biz, 'BIZ', true))
                ? 'central' : Str::upper($biz);
        }

        throw new InvalidArgumentException("Invalid connection: {$connection}");
    }

    /**
     * Central config.
     */
    private function getCentralConfig(): array
    {
        $cfg = [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'got_it_v1'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ];

        $ssl = env('MYSQL_SSL_CA_PATH');
        if ($ssl && file_exists($ssl)) {
            $cfg['options'][\PDO::MYSQL_ATTR_SSL_CA] = $ssl;
            $cfg['options'][\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) env('MYSQL_SSL_VERIFY_SERVER_CERT', true);
        }

        return $cfg;
    }

    private function getBusinessConfig(string $db): array
    {
        $cfg = $this->getCentralConfig();
        $cfg['database'] = $db;
        return $cfg;
    }

    /**
     * Get business DB name (cached).
     */
    private function getBusinessDatabase(string $bizId): string
    {
        $upperBizId = Str::upper($bizId);

        // In-memory first
        if (isset(self::$tenantCache[$upperBizId])) {
            return self::$tenantCache[$upperBizId];
        }

        // Then Laravel cache
        $key = self::CACHE_PREFIX . $upperBizId;
        if ($db = Cache::get($key)) {
            self::$tenantCache[$upperBizId] = $db;
            return $db;
        }

        // Query central only if needed
        $central = $this->getOrMake(self::CENTRAL, $this->getCentralConfig());
        $db = $central->table('business_systems')
            ->where('business_id', $upperBizId)
            ->where('is_active', true)
            ->value('database');

        if (!$db) {
            throw new RuntimeException("Active system not found for {$upperBizId}");
        }

        // Store in both caches
        self::$tenantCache[$upperBizId] = $db;
        Cache::put($key, $db, self::CACHE_TTL);

        return $db;
    }

    /**
     * Create or reuse connection.
     */
    private function getOrMake(string $name, array $cfg): Connection
    {
        if (isset(self::$pool[$name]) && $this->alive(self::$pool[$name])) {
            return self::$pool[$name];
        }

        Config::set("database.connections.{$name}", $cfg);

        try {
            $c = DB::connection($name);
            $c->getPdo()->query('SELECT 1');
            return self::$pool[$name] = $c;
        } catch (Throwable $e) {
            Log::error('DB connection failed', [
                'connection' => $name,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException("Failed connection: {$e->getMessage()}");
        }
    }

    private function setCentralConfig(): void
    {
        Config::set("database.connections." . self::CENTRAL, $this->getCentralConfig());
    }

    private function alive(Connection $c): bool
    {
        try {
            $c->getPdo()->query('SELECT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** Purge one connection. */
    public function purge(string $connection): void
    {
        try {
            $biz = $this->resolveBusinessId($connection);
            $name = $biz === 'central' ? self::CENTRAL : self::BUSINESS;
            unset(self::$pool[$name]);
            DB::purge($name);
            if ($name === self::BUSINESS) {
                self::$lastBusinessDb = null;
            }
        } catch (Throwable $e) {
            Log::warning('Failed purge', [
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
        }
    }

    /** Purge all connections. */
    public function purgeAll(): void
    {
        foreach ([self::CENTRAL, self::BUSINESS] as $n) {
            unset(self::$pool[$n]);
            DB::purge($n);
        }
        self::$tenantCache = [];
        self::$lastBusinessDb = null;
    }

    /** Forget cached business mapping. */
    public function purgeBusinessCache(string $bizId): void
    {
        $upperBizId = Str::upper($bizId);
        Cache::forget(self::CACHE_PREFIX . $upperBizId);
        unset(self::$tenantCache[$upperBizId]);
        self::$lastBusinessDb = null;
        unset(self::$pool[self::BUSINESS]);
    }

    /** Connection health check. */
    public function exists(string $connection): bool
    {
        try {
            return $this->alive($this->getConnection($connection));
        } catch (Throwable) {
            return false;
        }
    }
}
