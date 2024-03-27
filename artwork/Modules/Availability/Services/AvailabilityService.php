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
        private readonly AvailabilitySeriesService $availabilitySeriesService,
        private readonly AvailabilityConflictService $availabilityConflictService
    ) {
        $this->historyService->setModel(Availability::class);
    }

    //@todo: fix phpcs error - fix complexity
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function create(Available $available, $data): Available|Model
    {
        /** @var Availability $firstAvailable */
        $firstAvailable = $available->availabilities()->create([
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'date' => $data->date,
            'full_day' =>  $data->full_day,
            'comment' => $data->comment,
            'is_series' => $data->is_series,
        ]);

        $this->availabilityConflictService->checkAvailabilityConflictsOnDay(
            $firstAvailable->date,
            $firstAvailable->available_type === User::class ?
                User::find($firstAvailable->available_id) : null,
            $firstAvailable->available_type === Freelancer::class ?
                Freelancer::find($firstAvailable->available_id) : null,
        );

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
                    $newAvailability = $available->availabilities()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $availableSeries->id
                    ]);
                    $this->availabilityConflictService->checkAvailabilityConflictsOnDay(
                        $newAvailability->date,
                        $newAvailability->available_type === User::class ?
                            User::find($newAvailability->available_id) : null,
                        $newAvailability->available_type === Freelancer::class ?
                            Freelancer::find($newAvailability->available_id) : null,
                    );
                }
            }
            if ($data->series_repeat === 'weekly') {
                while ($whileEndDate->addWeek() < $series_until) {
                    $date = $whileEndDate->format('Y-m-d');
                    $newAvailability = $available->availabilities()->create([
                        'start_time' => $data->start_time,
                        'end_time' => $data->end_time,
                        'date' => $date,
                        'full_day' =>  $data->full_day,
                        'comment' => $data->comment,
                        'is_series' => true,
                        'series_id' => $availableSeries->id
                    ]);
                    $this->availabilityConflictService->checkAvailabilityConflictsOnDay(
                        $newAvailability->date,
                        $newAvailability->available_type === User::class ?
                            User::find($newAvailability->available_id) : null,
                        $newAvailability->available_type === Freelancer::class ?
                            Freelancer::find($newAvailability->available_id) : null,
                    );
                }
            }
        }

        $this->createHistory($firstAvailable, 'Availability added');
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



        $newAvailability = $this->availabilityRepository->save($availability);


        $this->availabilityConflictService->checkAvailabilityConflictsOnDay(
            $availability->date,
            $availability->available_type === User::class ?
                User::find($availability->available_id) : null,
            $availability->available_type === Freelancer::class ?
                Freelancer::find($availability->available_id) : null,
        );

        return $newAvailability;
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

    protected function createHistory(Availability $availability, string $translationKey): void
    {
        $this->historyService->setTranslationKey($translationKey);
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
