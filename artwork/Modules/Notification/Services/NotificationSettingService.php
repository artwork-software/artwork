<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Notification\Repositories\NotificationSettingRepository;
use Illuminate\Database\Eloquent\Collection;

class NotificationSettingService
{
    public function __construct(private readonly NotificationSettingRepository $notificationSettingRepository)
    {
    }

    public function getEnabledOfUser(int $userId): Collection
    {
        return $this->notificationSettingRepository->getEnabledOfUser($userId);
    }
}
