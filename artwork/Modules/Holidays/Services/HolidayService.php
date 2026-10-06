<?php

namespace Artwork\Modules\Holidays\Services;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Holidays\Api\ApiDto;
use Artwork\Modules\Holidays\Api\OpenHolidaysApi;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Repository\HolidayRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class HolidayService
{
    public function __construct(
        private readonly HolidayRepository $holidayRepository,
        private readonly OpenHolidaysApi $holidayApi
    ) {
    }

    public function create(
        string $name,
        Subdivision|array $subdivision,
        Carbon $date,
        Carbon $endDate,
        string $countryCode,
        bool $yearly,
        ?int $rota = 0,
        ?string $remote_identifier = null,
        ?bool $from_api = false,
        ?string $color = null,
        ?bool $treatAsSpecialDay = false,
        ?string $type = null
    ): Holiday {
        return $this->holidayRepository->create(
            name: $name,
            subdivision: $subdivision,
            date: $date,
            endDate: $endDate,
            countryCode: $countryCode,
            yearly: $yearly,
            rota: $rota,
            remote_identifier: $remote_identifier,
            from_api: $from_api,
            color: $color,
            treatAsSpecialDay: $treatAsSpecialDay,
            type: $type
        );
    }

    /**
     * OpenHolidays-Typ -> Feiertagstyp: "Public" -> public, "School" -> school, alles andere
     * (Bank, Optional, ...) wird wie ein gesetzlicher Feiertag geführt, aber nicht als Sondertag.
     */
    public static function typeFromApi(?string $apiType): string
    {
        return match (strtolower((string) $apiType)) {
            'school' => Holiday::TYPE_SCHOOL,
            default => Holiday::TYPE_PUBLIC,
        };
    }

    /**
     * OpenHolidays-Typ, der beim Import als Sondertag gilt: nur "Public" (gesetzlicher Feiertag).
     * Schulferien ("School"), Bank-/optionale Feiertage bleiben ohne Flag.
     */
    public static function isSpecialDayType(?string $type): bool
    {
        return strcasecmp((string) $type, 'Public') === 0;
    }

    public function getAllImported(): Collection
    {
        return $this->holidayRepository->findAllBy('from_api', true);
    }

    public function getAll(
        int $paginate,
        array $with = [],
        ?string $type = null
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        return $this->holidayRepository->findAll($paginate, $with, $type);
    }

    public function deleteAllFromApi(): void
    {
        $holidays = $this->getAllImported();

        foreach ($holidays as $holiday) {
            $holiday->subdivisions()->detach();
            $holiday->delete();
        }
    }

    /**
     * @param \Illuminate\Support\Collection $selectedSubdivisions
     * @param bool $publicHolidays
     * @param bool $schoolHolidays
     * @return string[]
     */
    /**
     * Feiertagsnamen in der Instanzsprache (gespeichert wird einmal für alle Nutzer:innen).
     */
    private function holidayLanguage(): string
    {
        return strtoupper((string) config('app.instance_locale', 'de'));
    }

    public function getHolidaysFromAPI(
        \Illuminate\Support\Collection $selectedSubdivisions,
        bool $publicHolidays,
        bool $schoolHolidays,
    ): array {
        $responses = [];
        foreach ($selectedSubdivisions as $subdivision) {
            $subdivisionModel = Subdivision::find($subdivision['id']);
            if ($publicHolidays) {
                $data = $this->holidayApi->getRawHolidays(
                    now()->startOfYear(),
                    now()->addYears(2)->endOfYear(),
                    $subdivisionModel,
                    $this->holidayLanguage(),
                );
                $data['country'] = $subdivisionModel->country_code;
                $responses[] = $data;
            }

            if ($schoolHolidays) {
                $data = $this->holidayApi->getRawSchoolHolidays(
                    now()->startOfYear(),
                    now()->addYears(2)->endOfYear(),
                    $subdivisionModel,
                    $this->holidayLanguage(),
                );
                $data['country'] = $subdivisionModel->country_code;
                $responses[] = $data;
            }
        }
        return $responses;
    }

    /**
     * @param array $responses
     * @param \Illuminate\Support\Collection $selectedSubdivisions
     * @return string[]
     */
    public function mergeHolidays(
        array $responses,
        \Illuminate\Support\Collection $selectedSubdivisions
    ): array {
        $mergedHolidays = [];

        foreach ($responses as $holidays) {
            foreach ($holidays as $holiday) {
                if (!is_array($holiday)) {
                    continue; //country information
                }
                $name = $holiday['name'][0]['text'];
                $startDate = $holiday['startDate'];
                $endDate = $holiday['endDate'];
                $key = $name . '-' . $startDate . '-' . $endDate;
                if (!isset($mergedHolidays[$key])) {
                    $mergedHolidays[$key] = [
                        'id' => $holiday['id'],
                        'startDate' => $holiday['startDate'],
                        'endDate' => $holiday['endDate'],
                        'type' => $holiday['type'],
                        'name' => $holiday['name'][0]['text'],
                        'regionalScope' => $holiday['regionalScope'],
                        'temporalScope' => $holiday['temporalScope'],
                        'nationwide' => $holiday['nationwide'],
                        'country' => $holidays['country'],
                        'subdivisions' => [],
                    ];
                }

                if (isset($holiday['subdivisions'])) {
                    foreach ($holiday['subdivisions'] as $subdivision) {
                        $isSelectedSubdivision = $selectedSubdivisions->contains('code', $subdivision['shortName']);

                        if (
                            $isSelectedSubdivision &&
                            !collect($mergedHolidays[$key]['subdivisions'])
                                ->firstWhere('code', $subdivision['shortName'])
                        ) {
                            $mergedHolidays[$key]['subdivisions'][] =
                                Subdivision::where('code', $subdivision['shortName'])->first();
                        }
                    }
                }
            }
        }

        return array_values($mergedHolidays);
    }

    /**
     * Feiertage eines Zeitraums je Tag (Y-m-d); mehrtägige Einträge erscheinen an jedem ihrer Tage.
     * @return SupportCollection<string, SupportCollection<int, CalendarHolidayDTO>>
     */
    public function getCalendarHolidaysByDate(Carbon $start, Carbon $end): SupportCollection
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->startOfDay();
        $holidaysByDate = collect();

        foreach ($this->getCalendarHolidaysForRange($rangeStart, $rangeEnd) as $holiday) {
            $first = Carbon::parse($holiday->date)->max($rangeStart);
            $last = Carbon::parse($holiday->end_date)->min($rangeEnd);
            foreach (CarbonPeriod::create($first, $last) as $day) {
                $key = $day->toDateString();
                if (!$holidaysByDate->has($key)) {
                    $holidaysByDate[$key] = collect();
                }
                $holidaysByDate[$key]->push($holiday);
            }
        }

        return $holidaysByDate;
    }

    /**
     * Feiertage eines Zeitraums für Kalenderansichten. Jährliche Einträge werden in jedes betroffene Jahr projiziert
     * (auch über den Jahreswechsel), damit sie unter dem Datum des angezeigten Jahres erscheinen.
     * @return SupportCollection<CalendarHolidayDTO>
     */
    public function getCalendarHolidaysForRange(Carbon $start, Carbon $end): SupportCollection
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->startOfDay();

        $holidays = Holiday::select(['id','name','date','end_date','color','yearly','treatAsSpecialDay'])
            ->where(function (Builder $q) use ($rangeStart, $rangeEnd): void {
                $q->where('yearly', true)
                    ->orWhere(function (Builder $fixed) use ($rangeStart, $rangeEnd): void {
                        $fixed->where('date', '<=', $rangeEnd->toDateString())
                            ->where(function (Builder $ends) use ($rangeStart): void {
                                $ends->where('end_date', '>=', $rangeStart->toDateString())
                                    ->orWhere(function (Builder $single) use ($rangeStart): void {
                                        $single->whereNull('end_date')
                                            ->where('date', '>=', $rangeStart->toDateString());
                                    });
                            });
                    });
            })
            ->with(['subdivisions' => fn($q) => $q->select('name')])
            ->get();

        $result = collect();
        foreach ($holidays as $holiday) {
            $holidayStart = $holiday->date->copy()->startOfDay();
            $holidayEnd = ($holiday->end_date ?? $holiday->date)->copy()->startOfDay();
            if ($holidayEnd->lt($holidayStart)) {
                $holidayEnd = $holidayStart->copy();
            }

            if (!$holiday->yearly) {
                $result->push($this->toCalendarHolidayDto($holiday, $holidayStart, $holidayEnd));
                continue;
            }

            $lengthInDays = (int) $holidayStart->diffInDays($holidayEnd);
            // Vorjahr mitnehmen: ein Block ab z. B. 30.12. reicht in den Januar des Zeitraums hinein
            for ($year = $rangeStart->year - 1; $year <= $rangeEnd->year; $year++) {
                $projectedStart = Holiday::yearlyStartIn($holidayStart, $year);
                if ($projectedStart === null) {
                    continue;
                }
                $projectedEnd = $projectedStart->copy()->addDays($lengthInDays);
                if ($projectedStart->lte($rangeEnd) && $projectedEnd->gte($rangeStart)) {
                    $result->push($this->toCalendarHolidayDto($holiday, $projectedStart, $projectedEnd));
                }
            }
        }

        return $result;
    }

    private function toCalendarHolidayDto(Holiday $holiday, Carbon $start, Carbon $end): CalendarHolidayDTO
    {
        return new CalendarHolidayDTO(
            name: $holiday->name,
            date: $start->toDateString(),
            end_date: $end->toDateString(),
            color: $holiday->color,
            subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
            treatAsSpecialDay: (bool) $holiday->treatAsSpecialDay,
        );
    }
}
