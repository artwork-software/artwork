<?php

namespace Artwork\Modules\User\Services;

use App\Events\UserStatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class UserStatusService
{
    private const TTL_SECONDS = 600; // 10 Minuten
    private const KEY_PREFIX  = 'user_status:';

    public function markOnline(int $userId): void
    {
        if (!$this->redisAvailable()) {
            return;
        }

        $key = self::KEY_PREFIX . $userId;

        // Setzt Wert + TTL in einem Schritt (immer frisch)
        Redis::setex($key, self::TTL_SECONDS, now()->toDateTimeString());

        event(new UserStatusUpdated($userId, 'online'));
    }

    public function getStatus(int $userId): string
    {
        if (!$this->redisAvailable()) {
            return 'offline';
        }

        $key = self::KEY_PREFIX . $userId;

        if (!Redis::exists($key)) {
            return 'offline';
        }

        $ttl = Redis::ttl($key);

        // -2 = Key existiert nicht (Race Condition), -1 = keine TTL gesetzt
        if ($ttl === -2) {
            return 'offline';
        }

        if ($ttl === -1) {
            // Key hat keine TTL -> behandeln wir als "online"
            event(new UserStatusUpdated($userId, 'online'));
            return 'online';
        }

        $status = ($ttl < (self::TTL_SECONDS / 2)) ? 'away' : 'online';

        event(new UserStatusUpdated($userId, $status));

        return $status;
    }

    public function markOffline(int $userId): void
    {
        if (!$this->redisAvailable()) {
            return;
        }

        Redis::del(self::KEY_PREFIX . $userId);

        // Optional: Wenn gewünscht, hier ein Offline-Event absetzen:
        // event(new UserStatusUpdated($userId, 'offline'));
    }

    /**
     * Prüft, ob Redis erreichbar ist (und das Modul überhaupt genutzt werden soll).
     */
    private function redisAvailable(): bool
    {
        if (!config('app.use_chat_module')) {
            return false;
        }

        try {
            // PING wirft bei Nicht-Erreichbarkeit eine Exception
            $pong = Redis::ping();

            // PhpRedis kann true oder "+PONG" liefern, Predis "PONG"
            return $pong === true || $pong === 'PONG' || $pong === '+PONG';
        } catch (Throwable $e) {
            Log::warning('Redis not available: ' . $e->getMessage());
            return false;
        }
    }
}
