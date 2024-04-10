<?php

namespace Artwork\Modules\Shift\Services;

use App\Http\Resources\FreelancerDropResource;
use App\Http\Resources\ServiceProviderDropResource;
use App\Http\Resources\UserDropResource;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

readonly class ShiftService
{
    use ReceivesNewHistoryServiceTrait;

    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftsQualificationsService $shiftsQualificationsService,
        private ShiftUserService $shiftUserService,
        private ShiftFreelancerService $shiftFreelancerService,
        private ShiftServiceProviderService $shiftServiceProviderService
    ) {
    }

    public function getById(int $shiftId): Shift|null
    {
        return $this->shiftRepository->getById($shiftId);
    }

    public function createFromShiftPresetShiftForEvent(PresetShift $presetShift, int $eventId): Shift
    {
        $shift = new Shift([
            'event_id' => $eventId,
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description,
            'is_committed' => false
        ]);

        $this->shiftRepository->save($shift);
        return $shift;
    }

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift): void
    {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'All scheduled employees have been removed from shift',
            [
                $shift->craft->abbreviation,
                $shift->event->eventName
            ],
            'shift'
        );
    }

    public function delete(Shift $shift): bool
    {
        foreach ($shift->shiftsQualifications as $shiftsQualification) {
            $this->shiftsQualificationsService->delete($shiftsQualification);
        }

        foreach ($shift->users as $user) {
            $this->shiftUserService->delete($user->pivot);
        }

        foreach ($shift->freelancer as $freelancer) {
            $this->shiftFreelancerService->delete($freelancer->pivot);
        }

        foreach ($shift->serviceProvider as $serviceProvider) {
            $this->shiftServiceProviderService->delete($serviceProvider->pivot);
        }

        return $this->shiftRepository->delete($shift);
    }

    public function deleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->delete($shift);
        }
    }

    public function restoreShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $shift->restore();
            $shift->shiftsQualifications()->onlyTrashed()->each(
                fn($shiftsQualification) => $this->shiftsQualificationsService->restore($shiftsQualification)
            );

            // restore shift users and freelancers from pivot table
            $shift->users()->each(
                fn($user) => $this->shiftUserService->restore($user->pivot)
            );

            $shift->freelancer()->each(
                fn($freelancer) => $this->shiftFreelancerService->restore($freelancer->pivot)
            );

            $shift->serviceProvider()->each(
                fn($serviceProvider) => $this->shiftServiceProviderService->restore($serviceProvider->pivot)
            );
        }
    }

    public function forceDelete(Shift $shift): bool
    {
        //relations are deleted on cascade
        return $this->shiftRepository->forceDelete($shift);
    }

    public function forceDeleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->forceDelete($shift);
        }
    }

    public function getShiftsForProjectTab(
        Project $project,
        array $loadedProjectInformation,
        ShiftQualificationService $shiftQualificationService
    ): array {
        $shiftRelevantEventTypes = $project->shiftRelevantEventTypes()->pluck('event_type_id');
        $shiftRelevantEvents = $project->events()
            ->whereIn('event_type_id', $shiftRelevantEventTypes)
            ->with(['timelines', 'shifts', 'event_type', 'room'])
            ->get();

        $eventsWithRelevant = [];
        foreach ($shiftRelevantEvents as $event) {
            $timeline = $event->timelines()->get()->toArray();

            foreach ($timeline as &$singleTimeLine) {
                $singleTimeLine['description_without_html'] = strip_tags($singleTimeLine['description']);
            }

            usort($timeline, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });


            foreach ($event->shifts as $shift) {
                $shift->load('shiftsQualifications');
            }

            $eventsWithRelevant[$event->id] = [
                'event' => $event,
                'timeline' => $timeline,
                'shifts' => $event->shifts,
                'event_type' => $event->event_type,
                'room' => $event->room,
            ];
        }
        rsort($eventsWithRelevant);

        $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->limit(1)->first();
        $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->limit(1)->first();
        if ($firstEventInProject && $lastEventInProject) {
            //get the start of day of the firstEventInProject
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            //get the end of day of the lastEventInProject
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        //get the diff of startDate and endDate in days, +1 to include the current date
        $diffInDays = $startDate->diffInDays($endDate) + 1;

        $usersWithPlannedWorkingHours = [];
        foreach (User::query()->where('can_work_shifts', true)->get() as $user) {
            $usersWithPlannedWorkingHours[] = [
                'user' => UserDropResource::make($user),
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * $diffInDays,
                'vacations' => $user->hasVacationDays(),
            ];
        }

        $freelancersWithPlannedWorkingHours = [];
        foreach (Freelancer::query()->where('can_work_shifts', true)->get() as $freelancer) {
            $freelancersWithPlannedWorkingHours[] = [
                'freelancer' => FreelancerDropResource::make($freelancer),
                'plannedWorkingHours' => $freelancer->plannedWorkingHours($startDate, $endDate),
            ];
        }

        $serviceProvidersWithPlannedWorkingHours = [];
        foreach (
            ServiceProvider::query()
                ->where('can_work_shifts', true)
                ->without(['contacts'])
                ->get() as $service_provider
        ) {
            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => ServiceProviderDropResource::make($service_provider),
                'plannedWorkingHours' => $service_provider->plannedWorkingHours($startDate, $endDate),
            ];
        }
        $loadedProjectInformation['ShiftTab'] = [
            'usersForShifts' => $usersWithPlannedWorkingHours,
            'freelancersForShifts' => $freelancersWithPlannedWorkingHours,
            'serviceProvidersForShifts' => $serviceProvidersWithPlannedWorkingHours,
            'eventsWithRelevant' => $eventsWithRelevant,
            'crafts' => Craft::all(),
            'currentUserCrafts' => Auth::user()->crafts
                ->merge(Craft::query()->where('assignable_by_all', '=', true)->get()),
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending(),
        ];

        return $loadedProjectInformation;
    }
}
