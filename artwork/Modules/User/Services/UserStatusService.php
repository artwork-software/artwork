<?php

namespace Artwork\Modules\User\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserStatusService
{
    public function markOnline(int $userId): void
    {
        if (Redis::exists("user_status:{$userId}")) {
            Redis::expire("user_status:{$userId}", 600); // TTL reset
        } else {
            Redis::setex("user_status:{$userId}", 600, now()->toDateTimeString());
        }
    }


    public function getStatus(int $userId): string
    {
        if (!Redis::exists("user_status:{$userId}")) {
            return 'offline';
        }

        $ttl = Redis::ttl("user_status:{$userId}");

        if ($ttl === -2) {
            return 'offline';
        }


        if ($ttl === -1) {
            return 'online';
        }

        return $ttl < 300 ? 'away' : 'online';
    }

    public function markOffline(int $userId): void
    {
        Redis::del("user_status:{$userId}");
    }
}