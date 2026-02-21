<?php

namespace Artwork\Modules\Vacation\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\Vacation\Repository\VacationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

readonly class VacationService
{
    public function __construct(
        private VacationRepository $vacationRepository,
        private VacationConflictService $vacationConflictService,
        private VacationSeriesService $vacationSeriesService,
        private ChangeService $changeService,
        private SchedulingService $schedulingService,
        private NotificationService $notificationService
    ) {
    }

    public function create(
        Vacationer $vacationer,
        Request $request,
        VacationConflictService $vacationConflictService,
        VacationSeriesService $vacationSeriesService,
        ChangeService $changeService,
        SchedulingService $schedulingService,
        NotificationService $notificationService,
        $vacationTypeEnum = \Artwork\Modules\Vacation\Enums\Vacation::NOT_AVAILABLE
    ): Vacation|Model {
        /** @var Vacation $firstVacation */
        $firstVacation = $vacationer->vacations()->create([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => $request->date,
            'full_day' =>  $request->full_day,
            'comment' => $request->comment,
            'is_series' => $request->is_series,
            'type' => $vacationTypeEnum,
            'created_by' => auth()->id(),
        ]);

        $vacationConflictService->checkVacationConflictsOnDay(
            $firstVacation->date,
            $firstVacation->vacationer_type === User::class ? User::find($firstVacation->vacationer_id) : null,
            $firstVacation->vacationer_type === Freelancer::class ?
                Freelancer::find($firstVacation->vacationer_id) : null,
            $notificationService
        );

        if ($request->is_series) {
            $vacationSeries = $vacationSeriesService->create(
                $request->get('series_repeat'),
                $request->get('series_repeat_until')
            );
            $firstVacation->update([
                'series_id' => $vacationSeries->id,
                'is_series' => true
            ]);

            $this->createSeries(
                $request->series_repeat,
                $vacationSeries->id,
                $request->series_repeat_until,
                $request->date,
                $vacationer,
                $request,
                $vacationTypeEnum,
                $vacationConflictService,
                $notificationService
            );
        }

        $this->createHistory($firstVacation, 'Availability added', $changeService);
        $this->announceChanges($firstVacation, $schedulingService);

        return $firstVacation;
    }

    private function createSeries(
        string $frequency,
        int $seriesId,
        string $series_repeat_until,
        string $date,
        Vacationer $vacationer,
        Request $data,
        \Artwork\Modules\Vacation\Enums\Vacation $vacationTypeEnum,
        VacationConflictService $vacationConflictService,
        NotificationService $notificationService
    ): void {
        $series_until = Carbon::parse($series_repeat_until);
        $series_until->addDay();
        $whileEndDate = Carbon::parse($date)->setTimezone(config('app.timezone'));
        if ($frequency === 'daily') {
            while ($whileEndDate->addDay() < $series_until) {
                $date = $whileEndDate->format('Y-m-d');
                $newVacation = $vacationer->vacations()->create([
                    'start_time' => $data->start_time,
                    'end_time' => $data->end_time,
                    'date' => $date,
                    'full_day' =>  $data->full_day,
                    'comment' => $data->comment,
                    'is_series' => true,
                    'series_id' => $seriesId,
                    'type' => $vacationTypeEnum,
                    'created_by' => auth()->id(),
                ]);
                $vacationConflictService->checkVacationConflictsOnDay(
                    $newVacation->date,
                    $newVacation->vacationer_type === User::class ? User::find($newVacation->vacationer_id) : null,
                    $newVacation->vacationer_type === Freelancer::class ?
                        Freelancer::find($newVacation->vacationer_id) : null,
                    $notificationService
                );
            }
        }
        if ($frequency === 'weekly') {
            while ($whileEndDate->addWeek() < $series_until) {
                $date = $whileEndDate->format('Y-m-d');
                $weekly = $vacationer->vacations()->create([
                    'start_time' => $data->start_time,
                    'end_time' => $data->end_time,
                    'date' => $date,
                    'full_day' =>  $data->full_day,
                    'comment' => $data->comment,
                    'is_series' => true,
                    'series_id' => $seriesId,
                    'type' => $vacationTypeEnum,
                    'created_by' => auth()->id(),
                ]);
                $vacationConflictService->checkVacationConflictsOnDay(
                    $weekly->date,
                    $weekly->vacationer_type === User::class ? User::find($weekly->vacationer_id) : null,
                    $weekly->vacationer_type === Freelancer::class ?
                        Freelancer::find($weekly->vacationer_id) : null,
                    $notificationService
                );
            }
        }
    }

    public function update(
        $data,
        Vacation $vacation,
        VacationConflictService $vacationConflictService,
        NotificationService $notificationService
    ): Vacation {
        $vacation->start_time = $data->start_time;
        $vacation->end_time = $data->end_time;
        $vacation->date = $data->date;
        $vacation->full_day = $data->full_day;
        $vacation->comment = $data->comment;
        $vacation->is_series = $data->is_series;

        $this->vacationRepository->save($vacation);

        $vacationConflictService->checkVacationConflictsOnDay(
            $vacation->date,
            $vacation->vacationer_type === User::class ? User::find($vacation->vacationer_id) : null,
            $vacation->vacationer_type === Freelancer::class ?
                Freelancer::find($vacation->vacationer_id) : null,
            $notificationService
        );

        return $vacation;
    }

    public function findVacationsByUserId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, User::class);
    }

    public function findVacationsByFreelancerId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, Freelancer::class);
    }

    public function findVacationWithinInterval(Vacationer $vacationer, string $day): Collection
    {
        return $this->vacationRepository->getVacationWithinInterval($vacationer, $day);
    }

    public function deleteVacationInterval(Vacationer $vacationer, string $day): void
    {
        $this->vacationRepository->delete($this->findVacationWithinInterval($vacationer, $day));
    }

    public function delete(Vacation $vacation): void
    {
        $this->vacationRepository->delete($vacation);
    }

    protected function createHistory(Vacation $vacation, string $translationKey, ChangeService $changeService): void
    {
        $changeService->saveFromBuilder(
            $changeService
                ->createBuilder()
                ->setModelClass(Vacation::class)
                ->setModelId($vacation->id)
                ->setTranslationKey($translationKey)
        );
    }

    protected function announceChanges(Vacation $vacation, SchedulingService $schedulingService): void
    {
        if (!$vacation->vacationer instanceof User) {
            return;
        }
        $schedulingService->create(
            $vacation->vacationer_id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $vacation->vacationer_id
        );
    }

    public function updateVacationOfEntity(
        array $vacationType,
        string $modelClass,
        Vacationer $entityModel,
        string $day
    ): void {
        if ($vacationType['type'] && in_array($modelClass, [User::class, Freelancer::class], true)) {
            $vacations = $entityModel->vacations()->where('date', $day)->get();

            if ($vacations->isNotEmpty()) {
                $this->deleteVacationInterval($entityModel, $day);
            }

            if ($vacationType['type'] !== \Artwork\Modules\Vacation\Enums\Vacation::AVAILABLE->value) {
                $createVacationRequest = new CreateVacationRequest([
                    'date' => $day,
                    'type' => 'vacation',
                    'full_day' => true,
                    'is_series' => false,
                    'comment' => $vacationType['type'],
                ]);

                $this->create(
                    $entityModel,
                    $createVacationRequest,
                    $this->vacationConflictService,
                    $this->vacationSeriesService,
                    $this->changeService,
                    $this->schedulingService,
                    $this->notificationService,
                    $vacationType['type']
                );
            }
        }
    }

    /**
     * Bulk update vacations for an entity across multiple days.
     * Pre-loads all vacations to avoid N+1 queries.
     *
     * @param array $vacationType
     * @param string $modelClass
     * @param Vacationer $entityModel
     * @param array $days
     * @return void
     */
    public function updateVacationsOfEntityBulk(
        array $vacationType,
        string $modelClass,
        Vacationer $entityModel,
        array $days
    ): void {
        if (!$vacationType['type'] || !in_array($modelClass, [User::class, Freelancer::class], true)) {
            return;
        }

        // Pre-load all vacations for all days at once to avoid N+1 queries
        $existingVacations = $entityModel->vacations()
            ->whereIn('date', $days)
            ->get()
            ->groupBy('date');

        foreach ($days as $day) {
            $vacationsForDay = $existingVacations->get($day, collect());

            if ($vacationsForDay->isNotEmpty()) {
                $this->deleteVacationInterval($entityModel, $day);
            }

            if ($vacationType['type'] !== \Artwork\Modules\Vacation\Enums\Vacation::AVAILABLE->value) {
                $createVacationRequest = new CreateVacationRequest([
                    'date' => $day,
                    'type' => 'vacation',
                    'full_day' => true,
                    'is_series' => false,
                    'comment' => $vacationType['type'],
                ]);

                $this->create(
                    $entityModel,
                    $createVacationRequest,
                    $this->vacationConflictService,
                    $this->vacationSeriesService,
                    $this->changeService,
                    $this->schedulingService,
                    $this->notificationService,
                    $vacationType['type']
                );
            }
        }
    }
}
