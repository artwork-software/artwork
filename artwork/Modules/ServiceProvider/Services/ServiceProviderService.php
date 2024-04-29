<?php

namespace Artwork\Modules\ServiceProvider\Services;

use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Carbon\Carbon;

readonly class ServiceProviderService
{
    public function __construct(private ServiceProviderRepository $serviceProviderRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceProvidersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass
    ): array {
        $serviceProvidersWithPlannedWorkingHours = [];

        /** @var ServiceProvider $serviceProvider */
        foreach ($this->serviceProviderRepository->getWorkers() as $serviceProvider) {
            $desiredServiceProviderResource = $desiredResourceClass::make($serviceProvider);

            if ($desiredServiceProviderResource instanceof ServiceProviderShiftPlanResource) {
                $desiredServiceProviderResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => $desiredServiceProviderResource,
                'plannedWorkingHours' => $serviceProvider->plannedWorkingHours($startDate, $endDate),
            ];
        }

        return $serviceProvidersWithPlannedWorkingHours;
    }
}
