<?php

namespace Artwork\Modules\UserProjectManagementSetting\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserProjectManagementSetting\Models\UserProjectManagementSetting;
use Artwork\Modules\UserProjectManagementSetting\Repositories\UserProjectManagementSettingRepository;

class UserProjectManagementSettingService
{
    public function __construct(
        private readonly UserProjectManagementSettingRepository $userProjectManagementSettingRepository
    ) {
    }

    public function getFromUser(int|User $user): ?UserProjectManagementSetting
    {
        return $this->userProjectManagementSettingRepository->getByUser($user);
    }

    public function updateOrCreateIfNecessary(int|User $user, array $filters): UserProjectManagementSetting
    {
        $setting = $this->userProjectManagementSettingRepository->getByUser($user);

        if (!$setting instanceof UserProjectManagementSetting) {
            $setting = $this->userProjectManagementSettingRepository->getNewModelInstance();
        }

        $this->userProjectManagementSettingRepository->save(
            $setting->fill(
                [
                    'user_id' => $user->getAttribute('id'),
                    'settings' => $filters
                ]
            )
        );

        return $setting;
    }
}
