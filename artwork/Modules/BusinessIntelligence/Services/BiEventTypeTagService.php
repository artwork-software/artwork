<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventTypeTagRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class BiEventTypeTagService
{
    public function __construct(
        private readonly BiEventTypeTagRepository $biEventTypeTagRepository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->biEventTypeTagRepository->getAll();
    }

    public function getAllWithEventTypes(): Collection
    {
        return $this->biEventTypeTagRepository->getAllWithEventTypes();
    }

    public function create(array $data): BiEventTypeTag
    {
        $tag = $this->biEventTypeTagRepository->getNewModelInstance();
        $tag->fill($data);
        $this->biEventTypeTagRepository->save($tag);
        $this->bumpDashboardCacheVersion();

        return $tag;
    }

    public function update(BiEventTypeTag $tag, array $data): BiEventTypeTag
    {
        $this->biEventTypeTagRepository->update($tag, $data);
        $this->bumpDashboardCacheVersion();

        return $tag->fresh();
    }

    public function delete(BiEventTypeTag $tag): bool
    {
        $deleted = $this->biEventTypeTagRepository->delete($tag);
        $this->bumpDashboardCacheVersion();

        return $deleted;
    }

    public function syncEventTypes(BiEventTypeTag $tag, array $eventTypeIds): void
    {
        $tag->eventTypes()->sync($eventTypeIds);
        $this->bumpDashboardCacheVersion();
    }

    /**
     * Tags und ihre Terminart-Zuordnung bestimmen Vorstellungen, Veranstaltungstage
     * und Auslastung — das Dashboard (10-Min-Cache, Key enthält diese Version)
     * muss die Änderung sofort zeigen, sonst wirkt der Hinweisbanner „nicht
     * zugeordnet" wie ein Bug.
     */
    private function bumpDashboardCacheVersion(): void
    {
        Cache::increment('bi_dashboard_version');
    }
}
