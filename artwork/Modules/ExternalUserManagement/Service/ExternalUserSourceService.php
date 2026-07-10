<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserSourceRepository;
use Illuminate\Database\Eloquent\Collection;

class ExternalUserSourceService
{
    public function __construct(
        private readonly ExternalUserSourceRepository $repository
    ) {
    }

    public function getAllWithRelations(): Collection
    {
        return $this->repository->getAllWithRelations();
    }

    public function getAllActiveLdapSources(): Collection
    {
        return $this->repository->getAllActiveLdapSources();
    }

    public function findByIdWithRelations(int $id): ?ExternalUserSource
    {
        return $this->repository->findByIdWithRelations($id);
    }

    public function create(array $data): ExternalUserSource
    {
        return $this->repository->create($data);
    }

    public function update(ExternalUserSource $source, array $data): ExternalUserSource
    {
        return $this->repository->update($source, $data);
    }

    public function delete(ExternalUserSource $source): bool
    {
        return $this->repository->delete($source);
    }
}

