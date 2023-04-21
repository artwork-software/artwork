<?php

namespace App\Http\Controllers;

use App\Builders\EventBuilder;
use App\Http\Resources\CalendarEventResource;
use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\Debugbar\Facades\Debugbar;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected ?Carbon $startDate = null;
    protected ?Carbon $endDate = null;

    public function __construct()
    {
    }

    /**
     * Returns all fields that can be filtered by in the calendar
     * @return array
     */
    public function getFilters(): array
    {
        return [
            'projects' => Project::all()->map(fn(Project $project) => [
                'id' => $project->id,
                'label' => $project->name,
                'access_budget' => $project->access_budget
            ]),

            'rooms' => Room::with('adjoining_rooms', 'main_rooms')->get()->map(fn(Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area' => $room->area,
                'room_admins' => $room->room_admins,
                'everyone_can_book' => $room->everyone_can_book,
                'label' => $room->name,
                'adjoining_rooms' => $room->adjoining_rooms->map(fn(Room $adjoining_room) => [
                    'id' => $adjoining_room->id,
                    'label' => $adjoining_room->name
                ]),
                'main_rooms' => $room->main_rooms->map(fn(Room $main_room) => [
                    'id' => $main_room->id,
                    'label' => $main_room->name
                ]),
                'categories' => $room->categories,
                'attributes' => $room->attributes
            ]),

            'roomCategories' => RoomCategory::all()->map(fn(RoomCategory $roomCategory) => [
                'id' => $roomCategory->id,
                'name' => $roomCategory->name,
            ]),

            'roomAttributes' => RoomAttribute::all()->map(fn(RoomAttribute $roomAttribute) => [
                'id' => $roomAttribute->id,
                'name' => $roomAttribute->name,
            ]),

            'eventTypes' => EventType::all()->map(fn(EventType $eventType) => [
                'id' => $eventType->id,
                'name' => $eventType->name,
            ]),

            'areas' => Area::all()->map(fn(Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
            ]),
        ];
    }

    private function get_events_of_day($date_of_day, $room, $projectId = null): array
    {

        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        foreach ($room->events as $event) {
            if(in_array($today, $event->days_of_event)) {
                if(!empty($projectId)){
                    if($event->project_id === $projectId ){
                        $eventsToday[] = $event;
                    }
                } else {
                    $eventsToday[] = $event;
                }
            }
        }

        return $eventsToday;
    }

    public function createCalendarData($type='', ?Project $project = null, ?Room $room = null){

        $calendarType = 'individual';
        $selectedDate = null;
        $this->startDate = Carbon::now()->startOfDay();

        if($type === 'dashboard'){
            $this->endDate = Carbon::now()->endOfDay();
        }else{
            $this->endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        if(!empty($project)){
            $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->first();
            $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->first();
            if(!empty($firstEventInProject) && !empty($lastEventInProject)){
                $this->startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
                $this->endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
            } else {
                $this->setDefaultDates();
            }

        } else {
            $this->setDefaultDates();
        }
        $startDay = $this->startDate->format('Y-m-d');
        $endDay = $this->endDate->format('Y-m-d');


        if($startDay && $endDay){
            if($startDay !== $endDay){
                $calendarType = 'individual';
            }else{
                $calendarType = 'daily';
                $selectedDate = $startDay;
            }
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $periodArray = [];

        foreach ($calendarPeriod as $period) {
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend()
            ];
        }

        $eventsWithoutRooms = [];

        if(!empty($room)){
            $better = collect($calendarPeriod)
                ->mapWithKeys(fn($date) => [
                    $date->format('d.m.') => CalendarEventResource::collection($this->get_events_of_day($date, $room, @$project->id))
                ]);
        }else{
            $better = Room::query()
                ->unless(is_null(request('roomIds')),
                    fn (Builder $builder) => $builder->whereIn('id', request('roomIds')))
                ->with(['events.room', 'events.project', 'events.creator', 'events' => function($query) use($project, $room) {
                    $this->filterEvents($query, $room, $project);
            }])
                ->get()
                ->map(fn($room) => collect($calendarPeriod)
                    ->mapWithKeys(fn($date) => [
                        $date->format('d.m.') => CalendarEventResource::collection($this->get_events_of_day($date, $room, @$project->id))
                    ]));

            $eventsWithoutRooms = CalendarEventResource::collection(Event::where('room_id', null)->get())->resolve();
        }

        return [
            'days' => $periodArray,
            'dateValue' => [$this->startDate->format('Y-m-d'),$this->endDate->format('Y-m-d')],
            // only used for dashboard -> default Dashboard should show Vuecal-Daily calendar with current day
            'calendarType' => $calendarType,
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which date to load
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $better,
            'eventsWithoutRoom' => $eventsWithoutRooms,
        ];
    }

    public function getEventsOfDay() {
        $all_events = Event::query();
        //->whereOccursBetween(Carbon::parse(request('start')), Carbon::parse(request('end')));
        $filteredEvents = $this->filterEvents($all_events, null ,null);
        $array = $filteredEvents->get();
        DebugBar::info($array);
        return $array;
    }

    private function filterEvents($query, ?Room $room, ?Project $project) {
        $isLoud = request('isLoud');
        $isNotLoud = request('isNotLoud');
        $hasAudience = request('hasAudience');
        $hasNoAudience = request('hasNoAudience');
        $showAdjoiningRooms = request('showAdjoiningRooms');
        $eventTypeIds = request('eventTypeIds');
        $roomIds = request('roomIds');
        $areaIds = request('areaIds');
        $roomAttributeIds = request('roomAttributeIds');
        $roomCategoryIds = request('roomCategoryIds');


        return $query
            ->when($project, fn (EventBuilder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn (EventBuilder $builder) => $builder->where('room_id', $room->id))
            ->unless(empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds), fn (EventBuilder $builder) => $builder
                ->whereHas('room', fn (Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn (Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn (Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn (Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn (Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                )
            )
            ->unless(empty($eventTypeIds), fn (EventBuilder $builder) => $builder->whereIn('event_type_id', $eventTypeIds))
            ->unless(is_null($hasAudience), fn (EventBuilder $builder) => $builder->where('audience', true))
            ->unless(is_null($hasNoAudience), fn (EventBuilder $builder) => $builder->where('audience', null)->orWhere('audience', false))
            ->unless(is_null($isLoud), fn (EventBuilder $builder) => $builder->where('is_loud', true))
            ->unless(is_null($isNotLoud), fn (EventBuilder $builder) => $builder->where('is_loud', false)->orWhere('is_loud', null));
    }

    public function filterRooms() {
        return Room::query()
            ->unless(is_null(request('roomIds')),
                fn (Builder $builder) => $builder->whereIn('id', request('roomIds')))
            ->unless(is_null(request('roomAttributeIds')),
                fn (Builder $builder) => $builder->whereHas('attributes', function($query) {
                    $query->whereIn('room_attributes.id', request('roomAttributeIds'));
                }))
            ->unless(is_null(request('roomCategoryIds')),
                fn (Builder $builder) => $builder->whereHas('categories', function($query) {
                    $query->whereIn('room_categories.id', request('roomCategoryIds'));
                }))
            ->get();
    }

    private function setDefaultDates(){
        if(\request('startDate')){
            $this->startDate = Carbon::create(\request('startDate'))->startOfDay();
        }
        if(\request('endDate')){
            $this->endDate = Carbon::create(\request('endDate'))->endOfDay();
        }
    }
}
