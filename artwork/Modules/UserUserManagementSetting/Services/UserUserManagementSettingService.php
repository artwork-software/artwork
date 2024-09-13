<?php

namespace Artwork\Modules\UserUserManagementSetting\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserUserManagementSetting\Models\UserUserManagementSetting;
use Artwork\Modules\UserUserManagementSetting\Repositories\UserUserManagementSettingRepository;

class UserUserManagementSettingService
{
    public function __construct(
        private readonly UserUserManagementSettingRepository $userUserManagementSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return [
            'sort_by' => null,
        ];
    }

    public function getFromUser(int|User $user): ?UserUserManagementSetting
    {
        return $this->userUserManagementSettingRepository->getByUser($user);
    }

    public function updateOrCreateIfNecessary(User $user, array $filters): UserUserManagementSetting
    {
        $setting = $this->userUserManagementSettingRepository->getByUser($user);

        if (!$setting instanceof UserUserManagementSetting) {
            /** @var UserUserManagementSetting $setting */
            $setting = $this->userUserManagementSettingRepository->getNewModelInstance();

            $this->userUserManagementSettingRepository->save(
                $setting->fill(
                    [
                        'user_id' => $user->getAttribute('id'),
                        'settings' => $filters,
                    ]
                )
            );

            return $setting;
        }

        $this->userUserManagementSettingRepository->update(
            $setting,
            [
                'user_id' => $user->getAttribute('id'),
                'settings' => $filters,
            ]
        );

        return $setting;
    }

    public function deleteAll(): void
    {
        $this->userUserManagementSettingRepository->deleteAll();
    }
}
