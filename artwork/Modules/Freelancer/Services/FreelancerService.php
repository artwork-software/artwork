<?php

namespace Artwork\Modules\Freelancer\Services;

use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Carbon\Carbon;

readonly class FreelancerService
{
    public function __construct(private FreelancerRepository $freelancerRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFreelancersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false
    ): array {
        $freelancersWithPlannedWorkingHours = [];

        /** @var Freelancer $freelancer */
        foreach ($this->freelancerRepository->getWorkers() as $freelancer) {
            $desiredFreelancerResource = $desiredResourceClass::make($freelancer);

            if ($desiredFreelancerResource instanceof FreelancerShiftPlanResource) {
                $desiredFreelancerResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $freelancerData = [
                'freelancer' => $desiredFreelancerResource,
                'plannedWorkingHours' => $freelancer->plannedWorkingHours($startDate, $endDate),
            ];

            if ($addVacationsAndAvailabilities) {
                $freelancerData['vacations'] = $freelancer->getVacationDays();
                $freelancerData['availabilities'] = $this->freelancerRepository
                    ->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                        $freelancer,
                        $startDate,
                        $endDate
                    );
            }

            $freelancersWithPlannedWorkingHours[] = $freelancerData;
        }

        return $freelancersWithPlannedWorkingHours;
    }
}
