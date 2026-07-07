<?php

namespace Artwork\Modules\ExternalUserManagement\Repository;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalUserSourceRepository extends BaseRepository
{
    public function getNewModelInstance(): ExternalUserSource
    {
        return new ExternalUserSource();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return ExternalUserSource::query();
    }

    public function getAllWithRelations(): Collection
    {
        return $this->getNewModelQuery()
            ->with(['groupMappings', 'externalUsers'])
            ->orderBy('name')
            ->get();
    }

    public function getAllActiveLdapSources(): Collection
    {
        return $this->getNewModelQuery()
            ->where('active', true)
            ->where('type', 'ldap')
            ->get();
    }

    public function getAllActiveIdentityProviderSources(): Collection
    {
        return $this->getNewModelQuery()
            ->where('active', true)
            ->where('type', 'identity_provider')
            ->get();
    }

    /**
     * Aktive Identity-Provider-Quellen für die Login-Seite (nur id + name).
     *
     * @return Collection<int, ExternalUserSource>
     */
    public function getActiveIdentityProviderSourcesForLogin(): Collection
    {
        return $this->getNewModelQuery()
            ->where('active', true)
            ->where('type', 'identity_provider')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findByIdWithRelations(int $id): ?ExternalUserSource
    {
        return $this->getNewModelQuery()
            ->with(['groupMappings', 'externalUsers'])
            ->find($id);
    }

    public function create(array $data): ExternalUserSource
    {
        $source = $this->getNewModelInstance();
        $source->fill($data);
        $this->save($source);

        return $source;
    }

    public function update(
        Model|Pivot|CanSubstituteBaseModel $source,
        array $data
    ): Model|Pivot|CanSubstituteBaseModel {
        /** @var ExternalUserSource $source */
        $source->fill($data);
        $this->save($source);

        return $source;
    }
}

