<?php

namespace Artwork\Modules\ServiceProvider\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection as SupportCollection;

class ServiceProviderRepository extends BaseRepository
{
    public function __construct(private readonly ServiceProvider $serviceProvider)
    {
    }

    public function getNewModelInstance(): ServiceProvider
    {
        return $this->serviceProvider->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->serviceProvider->newModelQuery();
    }


    public function findWorker(int $workerId): ServiceProvider|null
    {
        return ServiceProvider::query()->canWorkShifts()->where('id', $workerId)->first();
    }


    public function getWorkers(): Collection
    {
        // Im Konstruktor kann das zu circluar dependency führen, deswegen über den Container
        $workerService = app(\Artwork\Modules\Worker\Services\WorkerService::class);
        return $workerService->getWorkersForShiftPlan(ServiceProvider::class);
    }

    public function getShiftsWithEventOrderedByStartAscending(int|ServiceProvider $serviceProvider): Collection
    {
        return $serviceProvider
            ->shifts()
            ->with(['event', 'event.project', 'event.room'])
            ->orderedByStart()
            ->get();
    }

    public function scoutSearch(string $query): SupportCollection
    {
        return $this
            ->getNewModelInstance()
            ->search($query)
            ->get()
            ->map(
                fn(ServiceProvider $serviceProvider) => [
                    'id' => $serviceProvider->getAttribute('id'),
                    'provider_name' => $serviceProvider->getAttribute('provider_name'),
                    'profile_photo_url' => $serviceProvider->getAttribute('profile_photo_url'),
                    'manager_type' => $serviceProvider::class
                ]
            );
    }
}
