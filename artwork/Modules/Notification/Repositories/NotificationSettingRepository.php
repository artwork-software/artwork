<?php

namespace Artwork\Modules\Notification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;

class NotificationSettingRepository extends BaseRepository
{
    public function __construct(private readonly NotificationSetting $notificationSetting)
    {
    }

    public function getNewModelInstance(array $attributes = []): NotificationSetting
    {
        return $this->notificationSetting->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->notificationSetting->newModelQuery();
    }

    public function getEnabledOfUser(int $userId): Collection
    {
        return $this->getNewModelQuery()
            ->where('user_id', $userId)
            ->where('enabled_email', true)
            ->get();
    }
}
