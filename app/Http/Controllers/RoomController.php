<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Collision\Service\CollisionService;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomChangeService;
use Artwork\Modules\Room\Services\RoomFrontendModelService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;

class RoomController extends Controller
{
    public function __construct(
        protected readonly RoomService $roomService,
        protected readonly CollisionService $collisionService,
        private readonly SchedulingService $schedulingService,
        protected readonly RoomChangeService $roomChangeService,
        private readonly RoomFrontendModelService $roomFrontendModelService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllDayFree(Request $request): array
    {
        $period = CarbonPeriod::create(
            Carbon::parse($request->get('start'))->addHours(2),
            Carbon::parse($request->get('end'))
        );

        // Convert the period to an array of dates
        $dates = $period->toArray();
        $roomIds = [];

        foreach ($dates as $date) {
            Debugbar::info($date);
            $events = Event::query()->occursAt(Carbon::parse($date))->get();
            foreach ($events as $event) {
                $roomIds[] = $event->room_id;
            }
        }
        $room_occurrences = array_count_values($roomIds);

        $rooms_with_events_everyDay = [];
        foreach ($room_occurrences as $key => $occurrence) {
            if ($occurrence == count($dates)) {
                $rooms_with_events_everyDay[] = $key;
            }
        }

        $rooms = Room::with('adjoining_rooms', 'main_rooms')
            ->whereNotIn('id', $rooms_with_events_everyDay)
            ->get()
            ->map(
                fn(Room $room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'area' => $room->area,
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
                ]
            );

        return [
            'rooms' => $rooms
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'temporary' => $request->temporary,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'area_id' => $request->area_id,
            'user_id' => $request->user_id,
            'everyone_can_book' => $request->everyone_can_book,
            'order' => Room::max('order') + 1,
        ]);

        if (
            !is_null($request->adjoining_rooms) &&
            !is_null($request->room_attributes) &&
            !is_null($request->room_categories)
        ) {
            $room->adjoining_rooms()->sync($request->adjoining_rooms);
            $room->attributes()->sync($request->room_attributes);
            $room->categories()->sync($request->room_categories);
        }

        return Redirect::route('areas.management');
    }

    public function show(
        Room $room,
    ): Response|ResponseFactory {
        $room->load(['creator']);
        return Inertia::render(
            'Rooms/Show',
            $this->roomFrontendModelService->createShowDto(
                $room,
                Auth::user(),
            )
        );
    }

    public function getMoveRooms(): Response|ResponseFactory
    {
        return inertia('Rooms/RoomReorderManagement', [
            'rooms' => Room::orderBy('position')->get()
        ]);
    }

    public function updateOrderNew(Request $request): RedirectResponse
    {
        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['position' => $room['position']]);
        }

        return Redirect::back();
    }

    public function update(
        Request $request,
        Room $room
    ): RedirectResponse {

        $roomReplicate = $room->replicate();
        $roomReplicate->admins = $room->users()->wherePivot('is_admin', true)->get();
        $roomReplicate->adjoining_rooms = $room->adjoining_rooms()->get();
        $roomReplicate->attributes = $room->attributes()->get();
        $roomReplicate->categories = $room->categories()->get();


        $room->update(
            $request->only(
                'name',
                'description',
                'temporary',
                'start_date',
                'end_date',
                'everyone_can_book'
            )
        );

        if (!is_null($request->room_admins) && !is_null($request->requestable_by)) {
            $room_admins_ids = [];
            foreach ($request->room_admins as $room_admin) {
                $room_admins_ids[$room_admin['id']] = ['is_admin' => true];
            }

            $requestable_by_ids = [];
            foreach ($request->requestable_by as $can_request) {
                $requestable_by_ids[$can_request['id']] = ['can_request' => true];
            }

            $new_users = collect($room_admins_ids + $requestable_by_ids);
            $room->users()->detach();
            $room->users()->sync($new_users);
        }
            $room->adjoining_rooms()->sync($request->adjoining_rooms);
            $room->attributes()->sync($request->room_attributes);
            $room->categories()->sync($request->room_categories);

        $this->roomChangeService->applyChanges(
            $room,
            $roomReplicate
        );

        $roomId = $room->id;
        foreach ($room->users()->wherePivot('is_admin', true)->get() as $user) {
            $this->schedulingService->create($user->id, 'ROOM_CHANGES', 'ROOMS', $roomId);
        }

        return Redirect::back();
    }

    public function duplicate(Room $room): RedirectResponse
    {
        $this->roomService->duplicateByRoomModel($room);

        return Redirect::route('areas.management');
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    public function getTrashed(): Response|ResponseFactory
    {
        return inertia('Trash/Rooms', [
            'trashed_rooms' => Area::withTrashed()->get()->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexWithoutEventsResource::collection($area->trashedRooms)->resolve(),
            ])
        ]);
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return Redirect::route('areas.management');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();

        return Redirect::route('rooms.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->restore();

        return Redirect::route('rooms.trashed');
    }

    /**
     * @return array<int, int>
     */
    public function collisionsCount(Request $request): array
    {
        $params = $request->get('params');
        $startDate = Carbon::parse($params['start'])->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($params['end'])->setTimezone(config('app.timezone'));
        $currentEventId = $params['currentEventId'] ?? null;

        $collisions = [];
        $this->roomService->getAllWithoutTrashed()->each(
            function (Room $room) use (&$collisions, $startDate, $endDate, $currentEventId): void {
                $collisions[$room->id] = $this->collisionService->findCollisionCountForRoom(
                    $room,
                    $startDate,
                    $endDate,
                    $currentEventId
                );
            }
        );
        return $collisions;
    }
}
