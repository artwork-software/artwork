<?php

namespace Artwork\Modules\ServiceProvider\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Collection;

class ServiceProviderRepository extends BaseRepository
{
    public function findWorker(int $workerId): ServiceProvider|null
    {
        return ServiceProvider::query()->canWorkShifts()->where('id', $workerId)->first();
    }


    public function getWorkers(): Collection
    {
        return ServiceProvider::query()->canWorkShifts()->with(
            'dayServices',
            'shifts',
            'shifts.event',
            'shifts.event.room'
        )->get();
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
