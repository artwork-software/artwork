<?php

namespace Artwork\Modules\User\Services;

use App\Events\UserStatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserStatusService
{
    public function markOnline(int $userId): void
    {
        // check if Redis is available
        if (!config('app.use_chat_module')){
            //Log::error('Redis is not available');
            return;
        }


        if (Redis::exists("user_status:{$userId}")) {
            Redis::expire("user_status:{$userId}", 600); // TTL reset
        } else {
            Redis::setex("user_status:{$userId}", 600, now()->toDateTimeString());
        }

        event(new UserStatusUpdated($userId, 'online'));

    }


    public function getStatus(int $userId): string
    {
        // check if Redis is available
        if (!config('app.use_chat_module')){
            //Log::error('Redis is not available');
            return 'offline';
        }



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

        event(new UserStatusUpdated($userId,
            $ttl < 300 ? 'away' : 'online'
        ));

        return $ttl < 300 ? 'away' : 'online';
    }

    public function markOffline(int $userId): void
    {
        // check if Redis is available
        if (!config('app.use_chat_module')){
            //Log::error('Redis is not available');
            return;
        }


        // check if Redis is available
        if (!Redis::isAvailable()) {
            //Log::error('Redis is not available');
            return;
        }

        Redis::del("user_status:{$userId}");
    }
}