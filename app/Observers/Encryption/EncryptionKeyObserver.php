<?php

namespace App\Observers\Encryption;

use App\Facades\{CentralDB, Encryptor};
use Illuminate\Support\Facades\Log;

/**
 * Observer for secure_keys table to trigger re-encryption on key changes.
 */
class EncryptionKeyObserver
{
    /**
     * Handle the created event for secure_keys.
     *
     * @param \stdClass|\App\Models\EncryptionKey $key
     */
    public function created($key): void
    {
        if ($key->is_active) {
            try {
                $oldKey = CentralDB::table('secure_keys')
                    ->where('business_id', $key->business_id)
                    ->where('version', '!=', $key->version)
                    ->where('is_active', false)
                    ->orderBy('updated_at', 'desc')
                    ->first(['version']);

                if ($oldKey) {
                    Encryptor::startReencryption($key->business_id, $oldKey->version, $key->version);
                    Log::info('Re-encryption triggered on key creation', ['biz_id' => $key->business_id]);
                }
            } catch (\Exception $e) {
                Log::error('Re-encryption trigger failed', ['biz_id' => $key->business_id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Handle the updated event for secure_keys.
     *
     * @param \stdClass|\App\Models\EncryptionKey $key
     */
    public function updated($key): void
    {
        if ($key->is_active) {
            try {
                // Check if is_active was changed to true by comparing with previous state
                $previousKey = CentralDB::table('secure_keys')
                    ->where('id', $key->id)
                    ->first(['is_active']);

                if ($previousKey && !$previousKey->is_active) {
                    $oldKey = CentralDB::table('secure_keys')
                        ->where('business_id', $key->business_id)
                        ->where('version', '!=', $key->version)
                        ->where('is_active', false)
                        ->orderBy('updated_at', 'desc')
                        ->first(['version']);

                    if ($oldKey) {
                        Encryptor::startReencryption($key->business_id, $oldKey->version, $key->version);
                        Log::info('Re-encryption triggered on key update', ['biz_id' => $key->business_id]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Re-encryption trigger failed', ['biz_id' => $key->business_id, 'error' => $e->getMessage()]);
            }
        }
    }
}