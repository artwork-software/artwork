<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserGroupMappingRepository;
use Illuminate\Database\Eloquent\Collection;

class ExternalUserGroupMappingService
{
    public function __construct(
        private readonly ExternalUserGroupMappingRepository $repository
    ) {
    }

    public function getAllBySourceId(int $sourceId): Collection
    {
        return $this->repository->getAllBySourceId($sourceId);
    }

    public function getBySourceIdAndGroupDn(int $sourceId, string $groupDn): ?ExternalUserGroupMapping
    {
        return $this->repository->getBySourceIdAndGroupDn($sourceId, $groupDn);
    }

    public function create(array $data): ExternalUserGroupMapping
    {
        return $this->repository->create($data);
    }

    public function update(ExternalUserGroupMapping $mapping, array $data): ExternalUserGroupMapping
    {
        return $this->repository->update($mapping, $data);
    }

    public function delete(ExternalUserGroupMapping $mapping): bool
    {
        return $this->repository->delete($mapping);
    }
}

