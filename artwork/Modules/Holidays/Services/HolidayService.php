<?php

namespace Artwork\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Api\ApiDto;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Repository\HolidayRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use NoahNxT\LaravelOpenHolidaysApi\OpenHolidaysApi as VendorApi;

class HolidayService
{
    public function __construct(
        private readonly HolidayRepository $holidayRepository,
        private readonly VendorApi $holidayApi
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
        ?bool $treatAsSpecialDay = false
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
            treatAsSpecialDay: $treatAsSpecialDay
        );
    }

    public function getAllImported(): Collection
    {
        return $this->holidayRepository->findAllBy('from_api', true);
    }

    public function getAll(int $paginate, array $with = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->holidayRepository->findAll($paginate, $with);
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
    public function getHolidaysFromAPI(
        \Illuminate\Support\Collection $selectedSubdivisions,
        bool $publicHolidays,
        bool $schoolHolidays,
    ): array {
        $responses = [];
        foreach ($selectedSubdivisions as $subdivision) {
            $subdivisionModel = Subdivision::find($subdivision['id']);
            if ($publicHolidays) {
                $data = $this->holidayApi->holidays()->publicHolidays(
                    $subdivisionModel->country_code,
                    'DE',
                    now()->startOfYear()->format('Y-m-d'),
                    now()->addYears(2)->endOfYear()->format('Y-m-d'),
                    $subdivisionModel->country_code . '-' . $subdivisionModel->code,
                )->array();
                $data['country'] = $subdivisionModel->country_code;
                $responses[] = $data;
            }

            if ($schoolHolidays) {
                $data =  $this->holidayApi->holidays()->schoolHolidays(
                    $subdivisionModel->country_code,
                    'DE',
                    now()->startOfYear()->format('Y-m-d'),
                    now()->addYears(2)->endOfYear()->format('Y-m-d'),
                    $subdivisionModel->country_code . '-' . $subdivisionModel->code
                )->array();
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
                if(!is_array($holiday)) {
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
}
