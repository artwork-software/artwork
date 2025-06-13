<?php

namespace App\Http\Controllers;

use App\Settings\HolidaySettings;
use Carbon\Carbon;
use NoahNxT\LaravelOpenHolidaysApi\OpenHolidaysApi as VendorApi;
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
        return inertia('Settings/Holidays/Index', [
            'holidays' => $this->holidayService->getAll(
                $request->integer('entitiesPerPage', 10),
                ['subdivisions']
            ),
            'subdivisions' => Subdivision::all()->toArray(),
            'settings' => app(HolidaySettings::class),
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
        $this->holidayService->create(
            name: $request->input('name'),
            subdivision: $selected,
            date: Carbon::parse($request->input('date')),
            endDate: Carbon::parse($request->input('end_date')),
            countryCode: 'DE',
            yearly: $request->boolean('yearly'),
            color: $request->input('color'),
            treatAsSpecialDay: $request->boolean('treatAsSpecialDay')
        );
    }

    public function update(HolidayRequest $request, Holiday $holiday): void
    {
        $subdivisions = $request->collect('selectedSubdivisions')->pluck('id')->toArray();
        $holiday->fill($request->only(['name', 'date', 'end_date', 'yearly', 'color', 'treatAsSpecialDay']));
        $holiday->subdivisions()->sync($subdivisions);
        $holiday->save();
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

        $this->holidayService->deleteAllFormApi();
        $responses = $this->holidayService->getHolidaysFormAPI(
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
                true // Set treatAsSpecialDay to true by default
            );
        }
    }
}
