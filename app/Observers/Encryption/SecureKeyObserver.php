<?php

namespace App\Observers\Encryption;

use App\Facades\Encryptor;
use App\Models\Encryption\SecureKey;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Observer for the SecureKey model to trigger re-encryption and clear cache.
 */
class SecureKeyObserver
{
    /**
     * Handle the "created" event.
     *
     * @param SecureKey $key
     */
    public function created(SecureKey $key): void
    {
        if ($key->is_active) {
            try {
                Cache::forget("secure_key_{$key->business_id}");
                $oldKey = SecureKey::where('business_id', $key->business_id)
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
     * Handle the "updated" event.
     *
     * @param SecureKey $key
     */
    public function updated(SecureKey $key): void
    {
        if ($key->is_active) {
            try {
                Cache::forget("secure_key_{$key->business_id}");
                if ($key->getOriginal('is_active') === false) {
                    $oldKey = SecureKey::where('business_id', $key->business_id)
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