<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserUserManagementSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class UserUserManagementSettingRepository extends BaseRepository
{
    public function __construct(
        private readonly UserUserManagementSetting $userUserManagementSetting
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->userUserManagementSetting->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->userUserManagementSetting->newModelQuery();
    }

    public function getByUser(int|User $user): ?UserUserManagementSetting
    {
        /** @var UserUserManagementSetting $userUserManagementSetting */
        $userUserManagementSetting = $this->getNewModelQuery()
            ->where('user_id', $user instanceof User ? $user->getAttribute('id') : $user)
            ->first();

        return $userUserManagementSetting;
    }

    public function deleteAll(): void
    {
        $this->getNewModelQuery()->delete();
    }
}
