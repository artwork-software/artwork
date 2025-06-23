<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserProjectManagementSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class UserProjectManagementSettingRepository extends BaseRepository
{
    public function __construct(
        private readonly UserProjectManagementSetting $userProjectManagementSetting
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->userProjectManagementSetting->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->userProjectManagementSetting->newModelQuery();
    }

    public function getByUser(int|User $user): ?UserProjectManagementSetting
    {
        /** @var UserProjectManagementSetting $userProjectManagementSetting */
        $userProjectManagementSetting = $this->getNewModelQuery()
            ->where('user_id', $user instanceof User ? $user->getAttribute('id') : $user)
            ->first();

        return $userProjectManagementSetting;
    }

    public function deleteAll(): void
    {
        $this->getNewModelQuery()->delete();
    }
}
