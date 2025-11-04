<?php

namespace App\Listeners\DataSet;

use App\Events\DataSet\DataSetEvent;
use App\Events\DataSet\DataSetUserEvent;
use Illuminate\Support\Facades\{Log, Session};
use App\Facades\Skeleton;

class ResolveDataSetTokens
{
    /**
     * Handle the event.
     */
    public function handle(DataSetEvent $event): void
    {
        $userId = Skeleton::authUser()?->user_id;
        if (!$userId) {
            Log::warning("[ResolveDataSetTokens] No authenticated user", [
                'businessId' => $event->businessId,
                'key' => $event->key,
            ]);
            return;
        }

        $sessionKey = 'skeleton_tokens_auth_' . $userId;
        $tokenMap = Session::get($sessionKey, []);
        if (empty($tokenMap)) {
            Log::warning("[ResolveDataSetTokens] Empty tokenMap for user", [
                'userId' => $userId,
                'businessId' => $event->businessId,
                'key' => $event->key,
            ]);
            return;
        }

        Log::info("[ResolveDataSetTokens] Received DataSetEvent", [
            'businessId' => $event->businessId,
            'key' => $event->key,
            'userId' => $userId,
            'sessionKey' => $sessionKey,
        ]);

        // Step 1: Resolve table name from either token ID (array key) or logical key (inside)
        $table = null;
        foreach ($tokenMap as $tokenId => $data) {
            if ($tokenId === $event->key || ($data['key'] ?? null) === $event->key) {
                $table = $data['table'] ?? null;
                break;
            }
        }

        if (!$table) {
            Log::warning("[ResolveDataSetTokens] No table found for key", [
                'key' => $event->key,
                'businessId' => $event->businessId,
            ]);
            return;
        }

        Log::info("[ResolveDataSetTokens] Broadcasting DataSetUserEvent", [
            'businessId' => $event->businessId,
            'table' => $table,
            'key' => $event->key,
        ]);

        // Broadcast to the shared business channel with businessId, table, key
        broadcast(new DataSetUserEvent(
            $event->businessId,
            $table,
            $event->key
        ));
    }
}