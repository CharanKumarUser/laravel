<?php

namespace App\Services;

use App\Facades\{Database, Developer, Skeleton};
use App\Jobs\ReencryptTableJob;
use Illuminate\Support\Facades\{Cache, Config};
use Illuminate\Database\{QueryException, Connection};
use Illuminate\Support\Str;
use Exception;
use RuntimeException;

/**
 * Service for handling encryption, decryption, and re-encryption tasks.
 */
class EncryptorService
{
    /**
     * Encrypt a value using the business's active key.
     *
     * @param mixed $value Data to encrypt
     * @param string $bizId Business ID
     * @return string Encrypted value (base64 encoded)
     * @throws RuntimeException
     */
    public function encrypt($value, string $bizId): string
    {
        if (is_null($value)) {
            return '';
        }

        try {
            $key = $this->getKey($bizId);
            $cipher = Config::get('skeleton.encryption_cipher', 'AES-256-CBC');
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new RuntimeException('Invalid encryption cipher: ' . $cipher);
            }
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encrypted = openssl_encrypt($value, $cipher, $key['key'], 0, $iv);

            if ($encrypted === false) {
                throw new RuntimeException('Encryption process failed');
            }

            Developer::debug('Data encrypted', ['biz_id' => $bizId, 'version' => $key['version']]);
            return base64_encode($iv . $encrypted);
        } catch (Exception $e) {
            Developer::error('Encryption failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Unable to encrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt a value using the business's active or specified key.
     *
     * @param string $value Encrypted value (base64 encoded)
     * @param string $bizId Business ID
     * @param string|null $key Optional key for decryption
     * @return string Decrypted value
     * @throws RuntimeException
     */
    public function decrypt(string $value, string $bizId, ?string $key = null): string
    {
        if (empty($value)) {
            return $value;
        }

        try {
            $key = $key ?? $this->getKey($bizId)['key'];
            $cipher = Config::get('skeleton.encryption_cipher', 'AES-256-CBC');
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new RuntimeException('Invalid encryption cipher: ' . $cipher);
            }

            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 encoded data');
            }

            if (strlen($decoded) < $ivLength) {
                throw new RuntimeException('Invalid encrypted data length');
            }

            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);

            if ($decrypted === false) {
                throw new RuntimeException('Decryption process failed');
            }

            Developer::debug('Data decrypted', ['biz_id' => $bizId]);
            return $decrypted;
        } catch (Exception $e) {
            Developer::error('Decryption failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Unable to decrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt a value or key-value pair using the provided key.
     *
     * @param mixed $value Data to encrypt (string or array of key-value pairs)
     * @param string $key Encryption key
     * @return mixed Encrypted value (string or array of encrypted values, base64 encoded)
     * @throws RuntimeException
     */
    public function enc($value, string $key)
    {
        if (is_null($value)) {
            return '';
        }

        try {
            $cipher = Config::get('skeleton.encryption_cipher', 'AES-256-CBC');
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new RuntimeException('Invalid encryption cipher: ' . $cipher);
            }
            $iv = openssl_random_pseudo_bytes($ivLength);

            if (is_array($value)) {
                $encryptedPair = [];
                foreach ($value as $k => $v) {
                    if (is_null($v)) {
                        $encryptedPair[$k] = '';
                        continue;
                    }
                    $encrypted = openssl_encrypt($v, $cipher, $key, 0, $iv);
                    if ($encrypted === false) {
                        throw new RuntimeException('Encryption process failed for key: ' . $k);
                    }
                    $encryptedPair[$k] = base64_encode($iv . $encrypted);
                }
                Developer::debug('Key-value pair encrypted', ['keys' => array_keys($value)]);
                return $encryptedPair;
            }

            $encrypted = openssl_encrypt($value, $cipher, $key, 0, $iv);
            if ($encrypted === false) {
                throw new RuntimeException('Encryption process failed');
            }

            Developer::debug('Data encrypted', []);
            return base64_encode($iv . $encrypted);
        } catch (Exception $e) {
            Developer::error('Encryption failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Unable to encrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt a value or key-value pair using the provided key.
     *
     * @param mixed $value Encrypted data (string or array of encrypted values, base64 encoded)
     * @param string $key Decryption key
     * @return mixed Decrypted value (string or array of decrypted values)
     * @throws RuntimeException
     */
    public function dec($value, string $key)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            $cipher = Config::get('skeleton.encryption_cipher', 'AES-256-CBC');
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new RuntimeException('Invalid encryption cipher: ' . $cipher);
            }

            if (is_array($value)) {
                $decryptedPair = [];
                foreach ($value as $k => $v) {
                    if (empty($v)) {
                        $decryptedPair[$k] = '';
                        continue;
                    }
                    $decoded = base64_decode($v, true);
                    if ($decoded === false) {
                        throw new RuntimeException('Invalid base64 encoded data for key: ' . $k);
                    }
                    if (strlen($decoded) < $ivLength) {
                        throw new RuntimeException('Invalid encrypted data length for key: ' . $k);
                    }
                    $iv = substr($decoded, 0, $ivLength);
                    $encrypted = substr($decoded, $ivLength);
                    $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
                    if ($decrypted === false) {
                        throw new RuntimeException('Decryption process failed for key: ' . $k);
                    }
                    $decryptedPair[$k] = $decrypted;
                }
                Developer::debug('Key-value pair decrypted', ['keys' => array_keys($value)]);
                return $decryptedPair;
            }

            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 encoded data');
            }

            if (strlen($decoded) < $ivLength) {
                throw new RuntimeException('Invalid encrypted data length');
            }

            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);

            if ($decrypted === false) {
                throw new RuntimeException('Decryption process failed');
            }

            Developer::debug('Data decrypted', []);
            return $decrypted;
        } catch (Exception $e) {
            Developer::error('Decryption failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Unable to decrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Get the active encryption key for a business.
     *
     * @param string $bizId Business ID
     * @return array{id: int, key: string, version: string} Key details
     * @throws RuntimeException
     */
    public function getKey(string $bizId): array
    {
        try {
            $cacheTtl = Config::get('skeleton.cache_ttl', 7200);
            return Cache::remember("secure_key_{$bizId}", $cacheTtl, function () use ($bizId): array {
                $key = Database::getConnection('central')
                    ->table('secure_keys')
                    ->where(['business_id' => $bizId, 'is_active' => true])
                    ->select(['id', 'key', 'version'])
                    ->first();

                if (!$key) {
                    throw new RuntimeException("No active key for business ID: {$bizId}");
                }

                return (array) $key;
            });
        } catch (QueryException $e) {
            Developer::error('Key retrieval failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Key retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Change the encryption key for a business and trigger re-encryption.
     *
     * @param string $bizId Business ID
     * @param string $newKey New encryption key
     * @return bool True on success
     * @throws RuntimeException
     */
    public function setKey(string $bizId, string $newKey): bool
    {
        try {
            if (strlen($newKey) < 32 || !preg_match('/^[a-zA-Z0-9+\/=]+$/', $newKey)) {
                throw new RuntimeException('Invalid new key: Must be at least 32 characters and contain only base64 characters');
            }

            $connection = Database::getConnection('central');
            $connection->beginTransaction();

            $oldKey = $connection
                ->table('secure_keys')
                ->where(['business_id' => $bizId, 'is_active' => true])
                ->select(['id', 'key', 'version'])
                ->first();

            if ($oldKey) {
                $connection
                    ->table('secure_keys')
                    ->where(['business_id' => $bizId, 'is_active' => true])
                    ->update(['is_active' => false]);
            }

            $version = Str::random(10);
            $connection->table('secure_keys')->insert([
                'business_id' => $bizId,
                'key' => $newKey,
                'version' => $version,
                'is_active' => true,
                'created_by' => $this->getUserId(),
                'created_at' => now(),
            ]);

            Cache::forget("secure_key_{$bizId}");
            $connection->commit();

            if ($oldKey) {
                $this->startReencryption($bizId, $oldKey->version, $version);
            }

            Developer::info('Encryption key changed', ['biz_id' => $bizId, 'new_version' => $version]);
            return true;
        } catch (QueryException $e) {
            $connection->rollBack();
            Developer::error('Key change failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Key change failed: ' . $e->getMessage());
        } catch (Exception $e) {
            $connection->rollBack();
            Developer::error('Key change failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Key change failed: ' . $e->getMessage());
        }
    }

    /**
     * Start re-encryption process for all encrypted tables in the business database.
     *
     * @param string $bizId Business ID
     * @param string $oldVersion Previous key version
     * @param string $newVersion New key version
     * @return void
     */
    protected function startReencryption(string $bizId, string $oldVersion, string $newVersion): void
    {
        try {
            $tables = Database::getConnection('central')
                ->table('encrypted_tables')
                ->where(['type' => 'business', 'is_active' => true])
                ->pluck('table')
                ->toArray();

            if (empty($tables)) {
                Developer::info('No encrypted tables to re-encrypt', ['biz_id' => $bizId]);
                return;
            }

            $connection = Database::getConnection('central');
            $connection->table('encryption_progress')->insert([
                'business_id' => $bizId,
                'database_name' => $this->getBusinessDatabaseName($bizId),
                'total_tables' => count($tables),
                'tables_encrypted' => 0,
                'status' => 'pending',
                'created_by' => $this->getUserId(),
                'created_at' => now(),
            ]);

            $queue = Config::get('skeleton.encryption_queue', 'encryption');
            foreach ($tables as $table) {
                ReencryptTableJob::dispatch($bizId, $table, $oldVersion, $newVersion)
                    ->onQueue($queue)
                    ->delay(now()->addSeconds(5));
            }

            Developer::info('Re-encryption initiated', [
                'biz_id' => $bizId,
                'tables' => count($tables),
                'new_version' => $newVersion,
            ]);
        } catch (Exception $e) {
            Developer::error('Re-encryption initiation failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $connection->table('encryption_progress')
                ->where(['business_id' => $bizId, 'status' => 'pending'])
                ->update(['status' => 'failed', 'updated_at' => now()]);
        }
    }

    /**
     * Re-encrypt a table's data with a new key.
     *
     * @param string $bizId Business ID
     * @param string $table Table name
     * @param string $oldVersion Previous key version
     * @param string $newVersion New key version
     * @return bool True on success
     * @throws RuntimeException
     */
    public function reencrypt(string $bizId, string $table, string $oldVersion, string $newVersion): bool
    {
        try {
            $columns = $this->getEncColumns($table);
            if (empty($columns)) {
                Developer::info('No encrypted columns', ['biz_id' => $bizId, 'table' => $table]);
                return true;
            }

            $oldKeyRecord = Database::getConnection('central')
                ->table('secure_keys')
                ->where(['business_id' => $bizId, 'version' => $oldVersion])
                ->select(['key'])
                ->first();

            if (!$oldKeyRecord) {
                throw new RuntimeException("Old key version {$oldVersion} not found for business {$bizId}");
            }

            $oldKey = $oldKeyRecord->key;
            $newKey = $this->getKey($bizId);

            $connection = Database::getConnection('business');
            $connection->beginTransaction();

            $records = $connection->table($table)->get();

            foreach ($records as $record) {
                $updates = [];
                foreach ($columns as $column) {
                    if (isset($record->$column) && !empty($record->$column)) {
                        try {
                            $decrypted = $this->decrypt($record->$column, $bizId, $oldKey);
                            $updates[$column] = $this->encrypt($decrypted, $bizId);
                            $updates['encryption_version'] = $newVersion;
                        } catch (Exception $e) {
                            Developer::error('Record re-encryption failed', [
                                'biz_id' => $bizId,
                                'table' => $table,
                                'record_id' => $record->id ?? 'unknown',
                                'column' => $column,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            continue;
                        }
                    }
                }

                if (!empty($updates)) {
                    $connection->table($table)->where(['id' => $record->id])->update($updates);
                }
            }

            $connection->commit();

            $connection = Database::getConnection('central');
            $connection->table('encryption_logs')->insert([
                'business_id' => $bizId,
                'user_id' => $this->getUserId(),
                'table' => $table,
                'old_version' => $oldVersion,
                'new_version' => $newVersion,
                're_encrypted_at' => now(),
                'created_by' => $this->getUserId(),
                'created_at' => now(),
            ]);

            $this->updateProgress($bizId);

            Developer::info('Table re-encrypted', [
                'biz_id' => $bizId,
                'table' => $table,
                'new_version' => $newVersion,
            ]);
            return true;
        } catch (QueryException $e) {
            $connection->rollBack();
            Developer::error('Table re-encryption failed', [
                'biz_id' => $bizId,
                'table' => $table,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Table re-encryption failed: ' . $e->getMessage());
        } catch (Exception $e) {
            $connection->rollBack();
            Developer::error('Table re-encryption failed', [
                'biz_id' => $bizId,
                'table' => $table,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Table re-encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Get encrypted columns for a table.
     *
     * @param string $table Table name
     * @return array<int, string> List of encrypted columns
     * @throws RuntimeException
     */
    protected function getEncColumns(string $table): array
    {
        try {
            $cacheTtl = Config::get('skeleton.cache_ttl', 7200);
            return Cache::remember("enc_cols_{$table}", $cacheTtl, function () use ($table): array {
                $record = Database::getConnection('central')
                    ->table('encrypted_tables')
                    ->where(['table' => $table, 'is_active' => true])
                    ->select(['columns'])
                    ->first();

                if (!$record) {
                    return [];
                }

                $columns = json_decode($record->columns, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Invalid JSON in encrypted_tables.columns for table: ' . $table);
                }

                return is_array($columns) ? $columns : [];
            });
        } catch (QueryException $e) {
            Developer::error('Encrypted columns retrieval failed', [
                'table' => $table,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Encrypted columns retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Update re-encryption progress.
     *
     * @param string $bizId Business ID
     * @return void
     */
    protected function updateProgress(string $bizId): void
    {
        try {
            $connection = Database::getConnection('central');
            $progress = $connection
                ->table('encryption_progress')
                ->where(['business_id' => $bizId, 'status' => 'pending'])
                ->first();

            if ($progress) {
                $tables_encrypted = $progress->tables_encrypted + 1;
                $status = $tables_encrypted >= $progress->total_tables ? 'completed' : 'pending';

                $connection
                    ->table('encryption_progress')
                    ->where(['id' => $progress->id])
                    ->update([
                        'tables_encrypted' => $tables_encrypted,
                        'status' => $status,
                        'updated_at' => now(),
                    ]);

                if ($status === 'completed') {
                    Developer::info('Re-encryption completed', ['biz_id' => $bizId]);
                }
            }
        } catch (Exception $e) {
            Developer::error('Progress update failed', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return string|null User ID or null if not authenticated
     * @throws RuntimeException
     */
    protected function getUserId(): ?string
    {
        try {
            $user = Skeleton::authUser();
            return $user->user_id;
        } catch (Exception $e) {
            Developer::warning('Failed to get user ID', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get the current business database name.
     *
     * @param string $bizId Business ID
     * @return string Database name
     * @throws RuntimeException
     */
    protected function getBusinessDatabaseName(string $bizId): string
    {
        try {
            $system = Database::getConnection('central')
                ->table('business_systems')
                ->where(['business_id' => $bizId, 'is_active' => true])
                ->select(['database'])
                ->first();

            if (!$system) {
                throw new RuntimeException("No active system found for business ID: {$bizId}");
            }

            return $system->database;
        } catch (QueryException $e) {
            Developer::error('Failed to get business database name', [
                'biz_id' => $bizId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Failed to get business database name: ' . $e->getMessage());
        }
    }
}