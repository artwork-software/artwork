<?php

namespace Artwork\Modules\ServiceProvider\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Collection;

readonly class ServiceProviderRepository extends BaseRepository
{
    public function getWorkers(): Collection
    {
        return ServiceProvider::query()->canWorkShifts()->get();
    }

    public function getShiftsWithEventOrderedByStartAscending(int|ServiceProvider $serviceProvider): Collection
    {
        return $serviceProvider
            ->shifts()
            ->with(['event', 'event.project', 'event.room'])
            ->orderedByStart()
            ->get();
    }
}
