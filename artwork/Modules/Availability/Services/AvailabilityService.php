<?php

namespace Artwork\Modules\Availability\Services;

use App\Http\Controllers\SchedulingController;
use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Repositories\AvailabilityRepository;
use Artwork\Modules\Availability\Repositories\AvailabilitySeriesRepository;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\Vacation\Repository\VacationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly NewHistoryService $historyService,
        private readonly SchedulingController $scheduler, //@todo refactor
        private readonly AvailabilitySeriesService $availabilitySeriesService
    ) {
        $this->historyService->setModel(Availability::class);
    }

    public function create(Available $available, $data): Available|Model
    {

        $firstAvailable = $available->availabilities()->create([
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'date' => $data->date,
            'full_day' =>  $data->full_day,
            'comment' => $data->comment,
            'is_series' => $data->is_series,
        ]);

        if ($data->is_series) {
            $availableSeries = $this->availabilitySeriesService->create(
                frequency: $data->series_repeat,
                until: $data->series_repeat_until
            );
            $firstAvailable->update([
                'series_id' => $availableSeries->id
            ]);

            $series_until = Carbon::parse($data->series_repeat_until);
            $series_until->addDay();
            $whileEndDate = Carbon::parse($data->date)->setTimezone(config('app.timezone'));
            if ($data->series_repeat === 'daily') {
                while ($whileEndDate->addDay() < $series_until) {
                    $date = $whileEndDate->format('Y-m-d');
                    $available->availabilities()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $availableSeries->id
                    ]);
                }
            }
            if ($data->series_repeat === 'weekly') {
                while ($whileEndDate->addWeek() < $series_until) {
                    $date = $whileEndDate->format('Y-m-d');
                    $available->availabilities()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $availableSeries->id
                    ]);
                }
            }
        }





        $this->createHistory($firstAvailable, 'Verfügbarkeit hinzugefügt');
        $this->announceChanges($firstAvailable);

        return $firstAvailable;
    }

    public function update($data, Availability $availability): Availability|Model
    {
        $availability->start_time = $data->start_time;
        $availability->end_time = $data->end_time;
        $availability->date = $data->date;
        $availability->full_day = $data->full_day;
        $availability->comment = $data->comment;
        $availability->is_series = $data->is_series;

        return $this->availabilityRepository->save($availability);

        /*$oldFrom = $availability->from;
        $oldUntil = $availability->until;

        $availability->from = $from;
        $availability->until = $until;
        $vacation = $this->availabilityRepository->save($availability);

        $newFrom = $availability->from;
        $newUntil = $availability->until;

        if ($oldFrom !== $newFrom) {
            $this->createHistory(
                $availability,
                'Verfügbarkeit geändert von ' . Carbon::parse($oldFrom)
                    ->format('d.m.Y') . ' zu ' . Carbon::parse($newFrom)->format('d.m.Y')
            );
        }

        if ($oldUntil !== $newUntil) {
            $this->createHistory(
                $availability,
                'Verfügbarkeit geändert bis: ' . Carbon::parse($oldUntil)
                    ->format('d.m.Y') . ' zu ' . Carbon::parse($newUntil)->format('d.m.Y')
            );
        }
        $this->announceChanges($availability);

        return $vacation;*/
    }

    public function findVacationsByUserId(int $id): Collection
    {
        return $this->availabilityRepository->getByIdAndModel($id, User::class);
    }

    public function findVacationsByFreelancerId(int $id): Collection
    {
        return $this->availabilityRepository->getByIdAndModel($id, Freelancer::class);
    }

    public function findVacationWithinInterval(Available $available, Carbon $from, Carbon $until): Collection
    {
        return $this->availabilityRepository->getVacationWithinInterval($available, $from, $until);
    }

    public function deleteVacationInterval(Available $available, Carbon $from, Carbon $until): void
    {
        $this->availabilityRepository->delete($this->findVacationWithinInterval($available, $from, $until));
    }

    public function delete(Availability $availability): void
    {
        $this->availabilityRepository->delete($availability);
    }

    protected function createHistory(Availability $availability, string $text): void
    {
        $this->historyService->setHistoryText($text);
        $this->historyService->setModelId($availability->id);
        $this->historyService->setType('vacation');
        $this->historyService->create();
    }

    protected function announceChanges(Availability $availability): void
    {
        if (!$availability->available instanceof User) {
            return;
        }
        $this->scheduler->create(
            $availability->available_id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $availability->available_id
        );
    }
}
