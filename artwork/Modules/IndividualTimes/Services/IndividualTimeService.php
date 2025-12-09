<?php

namespace Artwork\Modules\IndividualTimes\Services;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Repositories\IndividualTimeRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndividualTimeService
{
    public function __construct(
        private readonly IndividualTimeRepository $individualTimeRepository,
    ) {
    }

    public function findById(int $id)
    {
        return $this->individualTimeRepository->find($id);
    }

    public function updateForModel(
        $modelInstance,
        $individualTime,
        ?string $title,
        ?string $startTime,
        ?string $endTime,
        string $date,
        ?int $breakMinutes = 0
    ): bool {
        $isFullDay = false;
        if (!method_exists($modelInstance, 'individualTimes')) {
            throw new ModelNotFoundException("Model does not support individual times");
        }

        if (!$individualTime) {
            // Defensive guard: do not call update on null, signal a not-found case to the caller
            throw new ModelNotFoundException("Individual time not found for update");
        }

        if ($startTime && $endTime) {
            $startDateForConvert = Carbon::parse($date . ' ' . $startTime);

            [$startTimeConverted, $endTimeConverted] = $this->processTimes(
                $startDateForConvert,
                $startTime,
                $endTime,
                Carbon::parse($date)
            );
            $totalMinutes = $startTimeConverted->diffInMinutes($endTimeConverted);
            $workingTimeInMinutes = max(0, $totalMinutes - ($breakMinutes ?? 0));
        } else {
            $startTimeConverted = Carbon::parse($date);
            $endTimeConverted = Carbon::parse($date);
            $workingTimeInMinutes = 1440;
            $isFullDay = true;
        }

        // Wenn die Zeit zu einer Serie gehört, entferne die series_uuid beim Update
        $updateData = [
            'title' => $title,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_date' => $startTimeConverted->format('Y-m-d'),
            'end_date' => $endTimeConverted->format('Y-m-d'),
            'full_day' => $isFullDay,
            'working_time_minutes' => $workingTimeInMinutes,
            'break_minutes' => $breakMinutes ?? 0,
        ];
        if (!empty($individualTime->series_uuid)) {
            $updateData['series_uuid'] = null;
        }
        return $individualTime->update($updateData);
    }

    public function createForModel(
        $modelInstance,
        ?string $title,
        ?string $startTime,
        ?string $endTime,
        string $date,
        ?int $breakMinutes = 0
    ): IndividualTime {
        $isFullDay = false;
        if (!method_exists($modelInstance, 'individualTimes')) {
            throw new ModelNotFoundException("Model does not support individual times");
        }

        if ($startTime && $endTime) {
            $startDateForConvert = Carbon::parse($date . ' ' . $startTime);

            [$startTimeConverted, $endTimeConverted] = $this->processTimes(
                $startDateForConvert,
                $startTime,
                $endTime,
                Carbon::parse($date)
            );
            $totalMinutes = $startTimeConverted->diffInMinutes($endTimeConverted);
            $workingTimeInMinutes = max(0, $totalMinutes - ($breakMinutes ?? 0));
        } else {
            $startTimeConverted = Carbon::parse($date);
            $endTimeConverted = Carbon::parse($date);
            $workingTimeInMinutes = 1440;
            $isFullDay = true;
        }

        $individualTimeObject = [
            'title' => $title,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_date' => $startTimeConverted->format('Y-m-d'),
            'end_date' => $endTimeConverted->format('Y-m-d'),
            'full_day' => $isFullDay,
            'working_time_minutes' => $workingTimeInMinutes,
            'break_minutes' => $breakMinutes ?? 0,
        ];

        return $this->individualTimeRepository->createNewIndividualTime($modelInstance, $individualTimeObject);
    }

    public function deleteForModel($modelInstance, string $date): void
    {
        if (!method_exists($modelInstance, 'individualTimes')) {
            throw new ModelNotFoundException("Model does not support individual times");
        }

        $individualTime = $modelInstance->individualTimes()->where('start_date', $date)->get();
        $individualTime->each(function ($individualTime): void {
            $individualTime->delete();
        });
    }

    /**
     * @param Carbon $startDate
     * @param string|null $startTime
     * @param string|null $endTime
     * @param Carbon|null $endDate
     * @return Carbon[]
     */
    public function processTimes(Carbon $startDate, ?string $startTime, ?string $endTime, ?Carbon $endDate): array
    {
        $endDay = clone $startDate;
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        if ($endDate && !$endDate->isSameDay($startDate)) {
            $endDay = clone $endDate;
        } elseif ($endTime->lte($startTime)) {
            $endDay->addDay();
        }
        $startDate->setTimeFromTimeString($startTime->toTimeString());
        $endDay->setTimeFromTimeString($endTime->toTimeString());
        return [$startDate, $endDay];
    }
}
