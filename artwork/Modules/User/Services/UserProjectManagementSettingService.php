<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserProjectManagementSetting;
use Artwork\Modules\User\Repositories\UserProjectManagementSettingRepository;

class UserProjectManagementSettingService
{
    public function __construct(
        private readonly UserProjectManagementSettingRepository $userProjectManagementSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return [
            'sort_by' => null,
            'project_filters' => [
                'showProjectGroups' => true,
                'showProjects' => true,
                'showFutureProjects' => true,
            ],
            'project_state_ids' => [],
        ];
    }

    public function getFromUser(int|User $user): ?UserProjectManagementSetting
    {
        return $this->userProjectManagementSettingRepository->getByUser($user);
    }

    public function updateOrCreateIfNecessary(User $user, array $filters): UserProjectManagementSetting
    {
        $setting = $this->userProjectManagementSettingRepository->getByUser($user);

        if (!$setting instanceof UserProjectManagementSetting) {
            /** @var UserProjectManagementSetting $setting */
            $setting = $this->userProjectManagementSettingRepository->getNewModelInstance();

            $this->userProjectManagementSettingRepository->save(
                $setting->fill(
                    [
                        'user_id' => $user->getAttribute('id'),
                        'settings' => $filters,
                    ]
                )
            );

            return $setting;
        }

        $this->userProjectManagementSettingRepository->update(
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
        $this->userProjectManagementSettingRepository->deleteAll();
    }
}
