<?php

namespace Artwork\Modules\IndividualTimes\Services;

use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class IndividualTimeSeriesService
{
    /**
     * @param array $data
     *  - title
     *  - start_date (Y-m-d)
     *  - end_date (Y-m-d)
     *  - start_time (nullable, H:i)
     *  - end_time (nullable, H:i)
     *  - full_day (bool)
     *  - working_time_minutes (int|null)
     *  - frequency ('weekly' etc.)
     *  - interval (int)
     *  - weekdays (array<int>) ISO-Wochentage [1..7]
     *  - created_by (user_id)
     *
     * @param \Illuminate\Support\Collection $timeables
     *  Collection von Modellen, die das Trait HasIndividualTimes benutzen:
     *  User | Freelancer | ServiceProvider
     */
    public function createSeriesForTimeables(array $data, Collection $timeables): IndividualTimeSeries
    {
        // 1. Serie anlegen
        $series = new IndividualTimeSeries([
            'uuid'       => (string) Str::uuid(),
            'title'      => $data['title'] ?? null,
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'frequency'  => $data['frequency'] ?? 'weekly',
            'interval'   => $data['interval'] ?? 1,
            'weekdays'   => $data['weekdays'] ?? [],
            'created_by' => $data['created_by'] ?? null,
        ]);

        $series->save();

        // 2. Alle relevanten Tage der Serie berechnen
        $start = Carbon::parse($series->start_date)->startOfDay();
        $end   = Carbon::parse($series->end_date)->endOfDay();

        $period   = CarbonPeriod::create($start, $end);
        $weekdays = $series->weekdays ?? [];
        $interval = max(1, (int) $series->interval);

        $dates = [];

        // Use the ISO week start (Monday) of the start date as reference
        // so that all days within the same week share the same week number.
        $weekReference = $start->copy()->startOfWeek(Carbon::MONDAY);

        foreach ($period as $date) {
            if (! in_array($date->isoWeekday(), $weekdays, true)) {
                continue;
            }

            // Intervalle z. B. alle 2 Wochen
            $cursorWeekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
            $weeksDiff = (int) round($weekReference->floatDiffInWeeks($cursorWeekStart));

            if ($weeksDiff % $interval === 0) {
                $dates[] = $date->copy();
            }
        }

        // 3. Für jede Person / jeden Dienstleister + jeden Tag einen IndividualTime-Eintrag erzeugen
        foreach ($timeables as $timeable) {
            // Sicherheit: nur Modelle mit HasIndividualTimes-Relation
            if (! method_exists($timeable, 'individualTimes')) {
                continue;
            }

            foreach ($dates as $date) {
                $fullDay = (bool) ($data['full_day'] ?? false);
                $startTime = $fullDay ? null : ($data['start_time'] ?? null);
                $endTime = $fullDay ? null : ($data['end_time'] ?? null);
                $breakMinutes = $data['break_minutes'] ?? 0;
                if ($startTime && $endTime) {
                    $startTimeConverted = \Illuminate\Support\Carbon::parse($date->toDateString() . ' ' . $startTime);
                    $endTimeConverted = \Illuminate\Support\Carbon::parse($date->toDateString() . ' ' . $endTime);
                    $totalMinutes = $startTimeConverted->diffInMinutes($endTimeConverted);
                    $workingTimeInMinutes = max(0, $totalMinutes - $breakMinutes);
                } else {
                    $workingTimeInMinutes = 1440;
                }
                $timeable->individualTimes()->create([
                    'title'                 => $series->title,
                    'start_date'            => $date->toDateString(),
                    'end_date'              => $date->toDateString(),
                    'start_time'            => $startTime,
                    'end_time'              => $endTime,
                    'full_day'              => $fullDay,
                    'working_time_minutes'  => $workingTimeInMinutes,
                    'break_minutes'         => $breakMinutes,
                    'series_uuid'           => $series->uuid,
                ]);
            }
        }

        return $series;
    }
}
