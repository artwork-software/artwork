<?php

namespace Artwork\Modules\Holidays\Import;

use App\Settings\HolidaySettings;
use Artwork\Modules\Holidays\Api\ApiDto;
use Artwork\Modules\Holidays\Api\HolidayApi;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;

class HolidayImport
{
    use Queueable;

    public function __construct()
    {
    }

    public function handle(
        HolidayService $holidayService
    ): void {
        $settings = app(HolidaySettings::class);
        $subdivisions = Subdivision::whereIn('id', $settings->subdivisions)->get();
        $responses = $holidayService->getHolidaysFromAPI(
            selectedSubdivisions: $subdivisions,
            publicHolidays: $settings->public_holidays,
            schoolHolidays: $settings->school_holidays,
        );

        $mergedHolidays = $holidayService->mergeHolidays(
            responses: $responses,
            selectedSubdivisions: $subdivisions,
        );

        // Erst nach erfolgreichem Abruf ersetzen: fällt die Schnittstelle aus, bleiben die bisherigen Feiertage
        DB::transaction(function () use ($holidayService, $mergedHolidays): void {
            $holidayService->deleteAllFromApi();

            foreach ($mergedHolidays as $holiday) {
                $holidayService->create(
                    $holiday['name'],
                    collect($holiday['subdivisions'])->pluck('id')->toArray(),
                    Carbon::parse($holiday['startDate']),
                    Carbon::parse($holiday['endDate']),
                    $holiday['country'],
                    false,
                    0,
                    $holiday['id'],
                    true,
                    '#333',
                    // Nur gesetzliche Feiertage ("Public") sind Sondertage; Schulferien nie.
                    HolidayService::isSpecialDayType($holiday['type'] ?? null),
                    HolidayService::typeFromApi($holiday['type'] ?? null)
                );
            }
        });
    }
}
