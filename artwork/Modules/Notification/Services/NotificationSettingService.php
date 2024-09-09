<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Repositories\NotificationSettingRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class NotificationSettingService
{
    public function __construct(private readonly NotificationSettingRepository $notificationSettingRepository)
    {
    }

    /**
     * @throws Throwable
     */
    public function create(array $attributes): NotificationSetting
    {
        $this->notificationSettingRepository->saveOrFail(
            ($notificationSetting = $this->notificationSettingRepository->getNewModelInstance())->fill($attributes)
        );
        return $notificationSetting;
    }

    /**
     * @return array<string, NotificationEnum>
     */
    public function getNotificationEnumCases(): array
    {
        return NotificationEnum::cases();
    }

    public function getEnabledOfUser(int $userId): Collection
    {
        return $this->notificationSettingRepository->getEnabledOfUser($userId);
    }
}
