<?php

namespace Artwork\Modules\SubEvents\Services;

use Artwork\Modules\SubEvents\Models\SubEvent;
use Artwork\Modules\SubEvents\Repositories\SubEventRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class SubEventService
{
    public function __construct(private SubEventRepository $subEventRepository)
    {
    }

    public function delete(SubEvent $subEvent): bool
    {
        return $this->subEventRepository->delete($subEvent);
    }

    public function deleteSubEvents(Collection|array $subEvents): void
    {
        foreach ($subEvents as $subEvent) {
            $this->delete($subEvent);
        }
    }

    public function restoreSubEvents(Collection|array $subEvents): void
    {
        foreach ($subEvents as $subEvent) {
            $this->restore($subEvent);
        }
    }

    public function restore(SubEvent $subEvent): bool
    {
        return $this->subEventRepository->restore($subEvent);
    }

    public function forceDeleteSubEvents(Collection|array $subEvents): void
    {
        foreach ($subEvents as $subEvent) {
            $this->forceDelete($subEvent);
        }
    }

    public function forceDelete(SubEvent $subEvent): bool
    {
        return $this->subEventRepository->forceDelete($subEvent);
    }
}
