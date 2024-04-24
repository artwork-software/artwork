<?php

namespace Artwork\Modules\Vacation\Services;

use App\Http\Controllers\SchedulingController;
use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NotificationService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\Vacation\Repository\VacationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

readonly class VacationService
{
    public function __construct(private VacationRepository $vacationRepository)
    {
    }

    public function create(
        Vacationer $vacationer,
        Request $request,
        VacationConflictService $vacationConflictService,
        VacationSeriesService $vacationSeriesService,
        ChangeService $changeService,
        SchedulingController $schedulingController,
        NotificationService $notificationService
    ): Vacation|Model {
        /** @var Vacation $firstVacation */
        $firstVacation = $vacationer->vacations()->create([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => $request->date,
            'full_day' =>  $request->full_day,
            'comment' => $request->comment,
            'is_serie' => $request->is_series,
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
                'series_id' => $vacationSeries->id
            ]);

            $this->createSeries(
                $request->series_repeat,
                $vacationSeries->id,
                $request->series_repeat_until,
                $request->date,
                $vacationer,
                $request,
                $vacationConflictService,
                $notificationService
            );
        }

        $this->createHistory($firstVacation, 'Availability added', $changeService);
        $this->announceChanges($firstVacation, $schedulingController);

        return $firstVacation;
    }

    private function createSeries(
        string $frequency,
        int $seriesId,
        string $series_repeat_until,
        string $date,
        Vacationer $vacationer,
        Request $data,
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
                    'series_id' => $seriesId
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
                    'series_id' => $seriesId
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

    protected function announceChanges(Vacation $vacation, SchedulingController $schedulingController): void
    {
        if (!$vacation->vacationer instanceof User) {
            return;
        }
        $schedulingController->create(
            $vacation->vacationer_id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $vacation->vacationer_id
        );
    }
}
