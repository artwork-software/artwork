<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserSourceRepository;
use Artwork\Modules\ExternalUserManagement\Support\OidcProviderPreset;
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
        return $this->repository->create($this->normalizePresetConfig($data));
    }

    public function update(ExternalUserSource $source, array $data): ExternalUserSource
    {
        return $this->repository->update($source, $this->normalizePresetConfig($data));
    }

    /**
     * Presets (Google/Microsoft/Custom) auf dieselbe Config-Struktur wie Custom
     * normalisieren, bevor die Verbindung persistiert wird.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePresetConfig(array $data): array
    {
        if (($data['type'] ?? null) === 'identity_provider' && isset($data['config']) && is_array($data['config'])) {
            $data['config'] = OidcProviderPreset::normalize($data['config']);
        }

        return $data;
    }

    public function delete(ExternalUserSource $source): bool
    {
        return $this->repository->delete($source);
    }
}

