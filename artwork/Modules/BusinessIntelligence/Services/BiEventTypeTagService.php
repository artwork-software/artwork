<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventTypeTagRepository;
use Illuminate\Database\Eloquent\Collection;

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

        return $tag;
    }

    public function update(BiEventTypeTag $tag, array $data): BiEventTypeTag
    {
        $this->biEventTypeTagRepository->update($tag, $data);

        return $tag->fresh();
    }

    public function delete(BiEventTypeTag $tag): bool
    {
        return $this->biEventTypeTagRepository->delete($tag);
    }

    public function syncEventTypes(BiEventTypeTag $tag, array $eventTypeIds): void
    {
        $tag->eventTypes()->sync($eventTypeIds);
    }
}
