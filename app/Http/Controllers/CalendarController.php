<?php

namespace App\Http\Controllers;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;

use Illuminate\Http\Request;
use Inertia\Inertia;

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


    public function settingIndex(){
        $calendarSettings = app(GeneralCalendarSettings::class);
        return Inertia::render('Settings/Calendar/Index', [
            'calendarSettings' => $calendarSettings,
        ]);
    }

    public function storeSettings(Request $request){
        $calendarSettings = app(GeneralCalendarSettings::class);
        $calendarSettings->start = $request->get('start');
        $calendarSettings->end = $request->get('end');
        $calendarSettings->save();
        return redirect()->back();
    }
}
