<?php

namespace Artwork\Modules\Shift\Services;

use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\User\Services\UserService;
use Exception;
use Throwable;

class ShiftWorkerService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly FreelancerRepository $freelancerRepository,
        private readonly ServiceProviderRepository $serviceProviderRepository
    ) {
    }

    /**
     * @return array<int, MinimalShiftPlanEventResource>
     * @throws Throwable
     */
    public function getResolvedWorkerShiftPlanResourcesByIdsAndTypes(
        array $workerIdsAndTypes
    ): array {
        $workerData = [];

        foreach ($workerIdsAndTypes as $desiredWorker) {
            $workerData[] = $this->getResolvedWorkerShiftPlanResourceByIdAndType(
                $desiredWorker['id'],
                $desiredWorker['type']
            );
        }

        return $workerData;
    }

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function getResolvedWorkerShiftPlanResourceByIdAndType(
        int $workerId,
        int $workerType
    ): array {
        [$startDate, $endDate] = $this->userService->getUserShiftCalendarFilterDatesOrDefault(
            $this->userService->getAuthUser()
        );

        $worker = $this->getWorkerByIdAndType($workerId, $workerType);

        $resource = match (true) {
            $workerType === 0 => (UserShiftPlanResource::make($worker)),
            $workerType === 1 => (FreelancerShiftPlanResource::make($worker)),
            $workerType === 2 => (ServiceProviderShiftPlanResource::make($worker)),
            default => throw new Exception(
                'Invalid worker type (should be user, freelancer or service_provider)'
            ),
        };

        return $resource
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->resolve();
    }

    /**
     * @throws Exception
     */
    public function getWorkerByIdAndType(
        int $workerId,
        int $workerType
    ): User|Freelancer|ServiceProvider {
        return match (true) {
            $workerType === 0 => $this->userRepository->findWorker($workerId),
            $workerType === 1 => $this->freelancerRepository->findWorker($workerId),
            $workerType === 2 => $this->serviceProviderRepository->findWorker($workerId),
            default => throw new Exception(
                'Invalid worker type (should be user, freelancer or service_provider)'
            ),
        };
    }
}
