<?php

namespace Artwork\Modules\Vacation\Services;

use App\Http\Controllers\SchedulingController;
use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\Vacation\Repository\VacationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VacationService
{
    public function __construct(
        private readonly VacationRepository $vacationRepository,
        private readonly NewHistoryService $historyService,
        private readonly SchedulingController $scheduler, //@todo refactor
        private readonly VacationSeriesService $vacationSeriesService
    ) {
        $this->historyService->setModel(Vacation::class);
    }

    public function create(Vacationer $vacationer, $data): Vacation|Model
    {
        $fristVacation = $vacationer->vacations()->create([
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'date' => $data->date,
            'full_day' =>  $data->full_day,
            'comment' => $data->comment,
            'is_serie' => $data->is_serie,
        ]);

        if ($data->is_series) {
            $vacationSeries = $this->vacationSeriesService->create(
                frequency: $data->series_repeat,
                until: $data->series_repeat_until
            );
            $fristVacation->update([
                'series_id' => $vacationSeries->id
            ]);

            $series_until = \Illuminate\Support\Carbon::parse($data->series_repeat_until);
            $series_until->addDay();
            $whileEndDate = Carbon::parse($data->date)->setTimezone(config('app.timezone'));
            if ($data->series_repeat === 'daily') {
                while ($whileEndDate->addDay() < $series_until) {
                    $date = $whileEndDate->format('Y-m-d');
                    $vacationer->vacations()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $vacationSeries->id
                    ]);
                }
            }
            if ($data->series_repeat === 'weekly') {
                while ($whileEndDate->addWeek() < $series_until) {
                    $date = $whileEndDate->format('Y-m-d');
                    $vacationer->vacations()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $vacationSeries->id
                    ]);
                }
            }
        }


        $this->createHistory($fristVacation, 'Verfügbarkeit hinzugefügt');
        $this->announceChanges($fristVacation);

        return $fristVacation;
    }

    public function update($data, Vacation $vacation): Vacation
    {
        $vacation->start_time = $data->start_time;
        $vacation->end_time = $data->end_time;
        $vacation->date = $data->date;
        $vacation->full_day = $data->full_day;
        $vacation->comment = $data->comment;
        $vacation->is_series = $data->is_series;



        return $this->vacationRepository->save($vacation);
        /*$oldFrom = $vacation->from;
        $oldUntil = $vacation->until;

        $vacation->from = $from;
        $vacation->until = $until;
        $vacation = $this->vacationRepository->save($vacation);

        $newFrom = $vacation->from;
        $newUntil = $vacation->until;

        if ($oldFrom !== $newFrom) {
            $this->createHistory(
                $vacation,
                'Verfügbarkeit geändert von ' . Carbon::parse($oldFrom)
        ->format('d.m.Y') . ' zu ' . Carbon::parse($newFrom)->format('d.m.Y')
            );
        }

        if ($oldUntil !== $newUntil) {
            $this->createHistory(
                $vacation,
                'Verfügbarkeit geändert bis: ' . Carbon::parse($oldUntil)
        ->format('d.m.Y') . ' zu ' . Carbon::parse($newUntil)->format('d.m.Y')
            );
        }
        $this->announceChanges($vacation);

        return $vacation;*/
    }

    public function findVacationsByUserId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, User::class);
    }

    public function findVacationsByFreelancerId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, Freelancer::class);
    }

    public function findVacationWithinInterval(Vacationer $vacationer, Carbon $from, Carbon $until): Collection
    {
        return $this->vacationRepository->getVacationWithinInterval($vacationer, $from, $until);
    }

    public function deleteVacationInterval(Vacationer $vacationer, Carbon $from, Carbon $until): void
    {
        $this->vacationRepository->delete($this->findVacationWithinInterval($vacationer, $from, $until));
    }

    public function delete(Vacation $vacation): void
    {
        $this->vacationRepository->delete($vacation);
    }

    protected function createHistory(Vacation $vacation, string $text): void
    {
        $this->historyService->setHistoryText($text);
        $this->historyService->setModelId($vacation->id);
        $this->historyService->setType('vacation');
        $this->historyService->create();
    }

    protected function announceChanges(Vacation $vacation): void
    {
        if (!$vacation->vacationer instanceof User) {
            return;
        }
        $this->scheduler->create(
            $vacation->vacationer_id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $vacation->vacationer_id
        );
    }
}
