<?php

namespace Artwork\Modules\ExternalUserManagement\Repository;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalUserRepository extends BaseRepository
{
    public function getNewModelInstance(): ExternalUser
    {
        return new ExternalUser();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return ExternalUser::query();
    }

    public function findBySourceAndIdentification(int $sourceId, string $identification): ?ExternalUser
    {
        return $this->getNewModelQuery()
            ->where('source_id', $sourceId)
            ->where('identification', $identification)
            ->first();
    }

    public function findByUserId(int $userId): Collection
    {
        return $this->getNewModelQuery()
            ->where('user_id', $userId)
            ->get();
    }

    public function findBySourceIdAndUserId(int $sourceId, int $userId): ?ExternalUser
    {
        return $this->getNewModelQuery()
            ->where('source_id', $sourceId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): ExternalUser
    {
        $externalUser = $this->getNewModelInstance();
        $externalUser->fill($data);
        $this->save($externalUser);

        return $externalUser;
    }

    public function update(
        Model|Pivot|CanSubstituteBaseModel $externalUser,
        array $data
    ): Model|Pivot|CanSubstituteBaseModel {
        /** @var ExternalUser $externalUser */
        $externalUser->fill($data);
        $this->save($externalUser);

        return $externalUser;
    }

    public function findOrCreateBySourceAndIdentification(int $sourceId, string $identification, array $attributes = []): ExternalUser
    {
        $externalUser = $this->findBySourceAndIdentification($sourceId, $identification);

        if (!$externalUser) {
            $externalUser = $this->create([
                'source_id' => $sourceId,
                'identification' => $identification,
                ...$attributes
            ]);
        }

        return $externalUser;
    }
}

