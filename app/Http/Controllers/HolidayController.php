<?php

namespace App\Http\Controllers;

use App\Settings\HolidaySettings;
use Carbon\Carbon;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Requests\HolidayRequest;
use Artwork\Modules\Holidays\Services\HolidayFrontendService;
use Artwork\Modules\Holidays\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;


class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayFrontendService $holidayFrontendService,
        private readonly HolidayService $holidayService
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $typeFilter = (string) $request->input('type', '');
        if (!in_array($typeFilter, Holiday::TYPES, true)) {
            $typeFilter = null;
        }

        return inertia('Settings/Holidays/Index', [
            'holidays' => $this->holidayService->getAll(
                $request->integer('entitiesPerPage', 10),
                ['subdivisions'],
                $typeFilter
            ),
            'subdivisions' => Subdivision::all()->toArray(),
            'settings' => app(HolidaySettings::class),
            'typeFilter' => $typeFilter,
            'holidayTypes' => Holiday::TYPES,
        ]);
    }

    public function destroy(Holiday $holiday): void
    {
        $holiday->subdivisions()->detach();
        $holiday->delete();
    }

    public function show(Holiday $holiday): JsonResponse
    {
        return response()->json($this->holidayFrontendService->createShowDto($holiday)->toArray());
    }

    public function store(HolidayRequest $request): void
    {
        $selected = $request->collect('selectedSubdivisions')->pluck('id')->toArray();
        $type = Holiday::normalizeType($request->input('type'));
        $this->holidayService->create(
            name: $request->input('name'),
            subdivision: $selected,
            date: Carbon::parse($request->input('date')),
            endDate: Carbon::parse($request->input('end_date') ?: $request->input('date')),
            countryCode: 'DE',
            yearly: $request->boolean('yearly'),
            color: $request->input('color'),
            // Ohne explizite Angabe gilt der Typ-Default (nur gesetzliche Feiertage sind Sondertage)
            treatAsSpecialDay: $request->has('treatAsSpecialDay')
                ? $request->boolean('treatAsSpecialDay')
                : Holiday::defaultTreatAsSpecialDayFor($type),
            type: $type
        );
    }

    public function update(HolidayRequest $request, Holiday $holiday): void
    {
        $subdivisions = $request->collect('selectedSubdivisions')->pluck('id')->toArray();
        $holiday->fill($request->only(['name', 'date', 'end_date', 'yearly', 'color', 'treatAsSpecialDay']));
        if ($request->filled('type')) {
            $holiday->type = Holiday::normalizeType($request->input('type'));
        }
        $holiday->subdivisions()->sync($subdivisions);
        $holiday->save();
    }

    /**
     * Nur das Sondertag-Flag eines Eintrags ändern (Dienstplaner:innen mit "can plan shifts").
     * Name/Datum/Typ bleiben unberührt; das Flag ist die einzige Wahrheit für den SpecialDayService.
     */
    public function updateTreatAsSpecialDay(Request $request, Holiday $holiday): JsonResponse
    {
        $validated = $request->validate([
            'treatAsSpecialDay' => ['required', 'boolean'],
        ]);

        $holiday->treatAsSpecialDay = (bool) $validated['treatAsSpecialDay'];
        $holiday->save();

        return response()->json([
            'id' => $holiday->id,
            'treatAsSpecialDay' => (bool) $holiday->treatAsSpecialDay,
        ]);
    }

    public function batchUpdateTreatAsSpecialDay(Request $request): void
    {
        $holidays = $request->input('holidays', []);

        foreach ($holidays as $holidayId => $treatAsSpecialDay) {
            $holiday = Holiday::find($holidayId);
            if ($holiday) {
                $holiday->treatAsSpecialDay = $treatAsSpecialDay;
                $holiday->save();
            }
        }
    }

    public function create(Request $request): void
    {
        $selectedSubdivisions = $request->collect('selectedSubdivisions');
        $schoolHolidays = $request->boolean('school_holidays');
        $publicHolidays = $request->boolean('public_holidays');
        $color = $request->get('color');

        $settings = app(HolidaySettings::class);
        $settings->subdivisions = $selectedSubdivisions->pluck('id')->toArray();
        $settings->public_holidays = $publicHolidays;
        $settings->school_holidays = $schoolHolidays;
        $settings->save();

        $this->holidayService->deleteAllFromApi();
        $responses = $this->holidayService->getHolidaysFromAPI(
            selectedSubdivisions: $selectedSubdivisions,
            publicHolidays: $publicHolidays,
            schoolHolidays: $schoolHolidays
        );

        $mergedHolidays = $this->holidayService->mergeHolidays(
            responses: $responses,
            selectedSubdivisions: $selectedSubdivisions,
        );


        foreach ($mergedHolidays as $holiday) {
            $this->holidayService->create(
                $holiday['name'],
                collect($holiday['subdivisions'])->pluck('id')->toArray(),
                Carbon::parse($holiday['startDate']),
                Carbon::parse($holiday['endDate']),
                $holiday['nationwide'] ? 'DE' : 'DE',
                false,
                0,
                $holiday['id'],
                true,
                $color,
                // DP-04: Nur gesetzliche Feiertage (OpenHolidays type "Public") sind Sondertage;
                // Schulferien ("School") senken das Tagessoll nie.
                HolidayService::isSpecialDayType($holiday['type'] ?? null),
                HolidayService::typeFromApi($holiday['type'] ?? null)
            );
        }
    }
}
