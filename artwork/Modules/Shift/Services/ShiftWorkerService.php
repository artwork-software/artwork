<?php

namespace Artwork\Modules\Shift\Services;

use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Exception;
use Throwable;

class ShiftWorkerService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly FreelancerRepository $freelancerRepository,
        private readonly ServiceProviderRepository $serviceProviderRepository,
        private readonly WorkingHourService $workingHourService,
        private readonly ShiftUserRepository $shiftUserRepository,
        private readonly ShiftFreelancerRepository $shiftFreelancerRepository,
        private readonly ShiftServiceProviderRepository $shiftServiceProviderRepository
    ) {
    }

    /**
     * @return array<int, MinimalShiftPlanEventResource>
     * @throws Throwable
     */
    public function getResolvedWorkerShiftPlanResourcesByIdsAndTypesWithPlannedWorkingHours(
        array $workerIdsAndTypes,
    ): array {
        $workerData = [];

        foreach ($workerIdsAndTypes as $desiredWorker) {
            $workerData[] = $this->getResolvedWorkerShiftPlanResourceByIdAndTypeWithPlannedWorkingHours(
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
    public function getResolvedWorkerShiftPlanResourceByIdAndTypeWithPlannedWorkingHours(
        int $workerId,
        int $workerType,
    ): array {
        [$startDate, $endDate] = $this->userService->getUserShiftCalendarFilterDatesOrDefault(
            $this->userService->getAuthUser()
        );

        /** @var User|Freelancer|ServiceProvider $worker */
        $worker = $this->getWorkerByIdAndType($workerId, $workerType);

        $workerData = [
            'type' => $worker->getAttribute('type'),
            'plannedWorkingHours' => $this->workingHourService->convertMinutesInHours(
                $this->workingHourService->calculateShiftTime($worker, $startDate, $endDate)
            ),
            'dayServices' => $worker->getAttribute('dayServices')->groupBy('pivot.date'),
            'individual_times' => $worker->individualTimes()->individualByDateRange($startDate, $endDate)->get(),
            'shift_comments' => $worker->getShiftPlanCommentsForPeriod($startDate, $endDate),
        ];

        if ($workerType === 0 || $workerType === 1) {
            $workerData = array_merge(
                [
                    'vacations' => $worker->getVacationDays(),
                    'availabilities' => $workerType === 0 ?
                        $this->userRepository->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                            $worker,
                            $startDate,
                            $endDate
                        ) : $this->freelancerRepository->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                            $worker,
                            $startDate,
                            $endDate
                        ),
                ],
                $workerData
            );
        }

        return match (true) {
            $workerType === 0 => array_merge(
                [
                    'user' => (UserShiftPlanResource::make($worker))
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->resolve(),
                    'expectedWorkingHours' => (
                        $this->workingHourService->convertMinutesInHours(
                            ($worker->getAttribute('weekly_working_hours') / 7) *
                            ($startDate->diffInDays($endDate) + 1) * 60
                        )
                    ),
                    'weeklyWorkingHours' => $this->workingHourService->calculateWeeklyWorkingHours(
                        $worker,
                        $startDate,
                        $endDate
                    ),
                ],
                $workerData
            ),
            $workerType === 1 => array_merge(
                [
                    'freelancer' => (FreelancerShiftPlanResource::make($worker))
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->resolve(),
                ],
                $workerData
            ),
            $workerType === 2 => array_merge(
                [
                    'service_provider' => (ServiceProviderShiftPlanResource::make($worker))
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->resolve(),
                ],
                $workerData
            ),
            default => throw new Exception(
                'Invalid worker type (should be user, freelancer or service_provider)'
            ),
        };
    }

    /**
     * @throws Exception
     */
    public function getWorkerByIdAndType(
        int $workerId,
        int $workerType,
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

    public function updateShiftWorkerQualificationToDefault(Shift $shift, int $oldShiftQualificationId): void
    {
        $shiftUsers = $shift
            ->users()
            ->wherePivot('shift_qualification_id', $oldShiftQualificationId)
            ->get();

        foreach ($shiftUsers as $shiftUser) {
            $this->shiftUserRepository->update(
                $shiftUser->pivot,
                ['shift_qualification_id' => 1]
            );
        }

        $shiftFreelancers = $shift
            ->freelancer()
            ->where('shift_qualification_id', $oldShiftQualificationId)
            ->get();

        foreach ($shiftFreelancers as $shiftFreelancer) {
            $this->shiftFreelancerRepository->update(
                $shiftFreelancer->pivot,
                ['shift_qualification_id' => 1]
            );
        }

        $shiftServiceProviders = $shift
            ->serviceProvider()
            ->where('shift_qualification_id', $oldShiftQualificationId)
            ->get();

        foreach ($shiftServiceProviders as $shiftServiceProvider) {
            $this->shiftServiceProviderRepository->update(
                $shiftServiceProvider->pivot,
                ['shift_qualification_id' => 1]
            );
        }
    }
}
