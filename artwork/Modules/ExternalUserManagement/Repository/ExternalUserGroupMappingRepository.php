<?php

namespace Artwork\Modules\ExternalUserManagement\Repository;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalUserGroupMappingRepository extends BaseRepository
{
    public function getNewModelInstance(): ExternalUserGroupMapping
    {
        return new ExternalUserGroupMapping();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return ExternalUserGroupMapping::query();
    }

    public function getAllBySourceId(int $sourceId): Collection
    {
        return $this->getNewModelQuery()
            ->where('source_id', $sourceId)
            ->orderBy('ad_group_name')
            ->get();
    }

    public function getBySourceIdAndGroupDn(int $sourceId, string $groupDn): ?ExternalUserGroupMapping
    {
        return $this->getNewModelQuery()
            ->where('source_id', $sourceId)
            ->where('ad_group_dn', $groupDn)
            ->first();
    }

    public function create(array $data): ExternalUserGroupMapping
    {
        $mapping = $this->getNewModelInstance();
        $mapping->fill($data);
        $this->save($mapping);

        return $mapping;
    }

    public function update(
        Model|Pivot|CanSubstituteBaseModel $mapping,
        array $data
    ): Model|Pivot|CanSubstituteBaseModel {
        /** @var ExternalUserGroupMapping $mapping */
        $mapping->fill($data);
        $this->save($mapping);

        return $mapping;
    }
}

