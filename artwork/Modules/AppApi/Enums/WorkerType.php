<?php

namespace Artwork\Modules\AppApi\Enums;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;

/**
 * The three kinds of people a shift can be staffed with, as the app wire
 * names them (shift_workers is a morph pivot over these models).
 */
enum WorkerType: string
{
    case User = 'user';
    case Freelancer = 'freelancer';
    case ServiceProvider = 'service_provider';

    public static function of(User|Freelancer|ServiceProvider $worker): self
    {
        return match (true) {
            $worker instanceof User => self::User,
            $worker instanceof Freelancer => self::Freelancer,
            default => self::ServiceProvider,
        };
    }

    public static function displayName(User|Freelancer|ServiceProvider $worker): string
    {
        return $worker instanceof User ? $worker->full_name : $worker->name;
    }
}
