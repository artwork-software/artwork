<?php

namespace Artwork\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Repositories\AvailabilityRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityService
{
    public function __construct(private readonly AvailabilityRepository $availabilityRepository)
    {
    }

    //@todo: fix phpcs error - fix complexity
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function create(
        Available $available,
        Request $data,
        NotificationService $notificationService,
        AvailabilityConflictService $availabilityConflictService,
        AvailabilitySeriesService $availabilitySeriesService,
        ChangeService $changeService,
        SchedulingService $schedulingService
    ): Available|Model {
        /** @var Availability $firstAvailable */
        $firstAvailable = $available->availabilities()->create([
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'date' => $data->date,
            'full_day' =>  $data->full_day,
            'comment' => $data->comment,
            'is_series' => $data->is_series,
        ]);

        $availabilityConflictService->checkAvailabilityConflictsOnDay(
            $firstAvailable->date,
            $notificationService,
            $firstAvailable->available_type === User::class ?
                User::find($firstAvailable->available_id) : null,
            $firstAvailable->available_type === Freelancer::class ?
                Freelancer::find($firstAvailable->available_id) : null
        );

        if ($data->is_series) {
            $availableSeries = $availabilitySeriesService->create(
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
                    $availabilityConflictService->checkAvailabilityConflictsOnDay(
                        $newAvailability->date,
                        $notificationService,
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
                    $availabilityConflictService->checkAvailabilityConflictsOnDay(
                        $newAvailability->date,
                        $notificationService,
                        $newAvailability->available_type === User::class ?
                            User::find($newAvailability->available_id) : null,
                        $newAvailability->available_type === Freelancer::class ?
                            Freelancer::find($newAvailability->available_id) : null,
                    );
                }
            }
        }

        $this->createHistory($firstAvailable, $changeService, 'Availability added');
        $this->announceChanges($firstAvailable, $schedulingService);

        return $firstAvailable;
    }

    public function update(
        Request $data,
        Availability $availability,
        NotificationService $notificationService,
        AvailabilityConflictService $availabilityConflictService
    ): Availability|Model {
        $availability->start_time = $data->start_time;
        $availability->end_time = $data->end_time;
        $availability->date = $data->date;
        $availability->full_day = $data->full_day;
        $availability->comment = $data->comment;
        $availability->is_series = $data->is_series;

        $newAvailability = $this->availabilityRepository->save($availability);

        $availabilityConflictService->checkAvailabilityConflictsOnDay(
            $availability->date,
            $notificationService,
            $availability->available_type === User::class ?
                User::find($availability->available_id) : null,
            $availability->available_type === Freelancer::class ?
                Freelancer::find($availability->available_id) : null,
        );

        return $newAvailability;
    }

    public function delete(Availability $availability): void
    {
        $this->availabilityRepository->delete($availability);
    }

    private function createHistory(
        Availability $availability,
        ChangeService $changeService,
        string $translationKey
    ): void {
        $changeService->saveFromBuilder(
            $changeService
                ->createBuilder()
                ->setType('vacation')
                ->setModelClass(Availability::class)
                ->setModelId($availability->id)
                ->setTranslationKey($translationKey)
        );
    }

    private function announceChanges(
        Availability $availability,
        SchedulingService $schedulingService
    ): void {
        if (!$availability->available instanceof User) {
            return;
        }
        $schedulingService->create(
            $availability->available_id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $availability->available_id
        );
    }
}
