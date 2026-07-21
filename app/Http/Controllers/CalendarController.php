<?php

namespace App\Http\Controllers;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Room\Services\RoomAttributeService;
use Artwork\Modules\Room\Services\RoomCategoryService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getCalendarFilterDefinitions(
        FilterService $filterService,
    ): array {
        return $filterService->getCalendarFilterDefinitions();
    }


    public function settingIndex(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        $calendarSettings = app(GeneralCalendarSettings::class);

        return Inertia::render('Settings/Calendar/Index', [
            'calendarSettings' => $calendarSettings,
        ]);
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $validated = $request->validate([
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'day_remarks_enabled' => ['sometimes', 'boolean'],
            'day_remarks_mandatory' => ['sometimes', 'boolean'],
        ]);

        $calendarSettings = app(GeneralCalendarSettings::class);
        $calendarSettings->start = $validated['start'];
        $calendarSettings->end = $validated['end'];
        $calendarSettings->day_remarks_enabled = $request->boolean('day_remarks_enabled');
        $calendarSettings->day_remarks_mandatory = $request->boolean('day_remarks_mandatory');
        $calendarSettings->save();

        return redirect()->back();
    }
}
