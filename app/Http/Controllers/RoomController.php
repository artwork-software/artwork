<?php

namespace App\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Enums\NotificationConstEnum;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectIndexAdminResource;
use App\Http\Resources\RoomCalendarResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use App\Models\RoomCategory;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use JetBrains\PhpStorm\NoReturn;

class RoomController extends Controller
{
    // init notification system
    protected ?NotificationController $notificationController = null;
    protected ?\stdClass $notificationData = null;
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->notificationController = new NotificationController();
        $this->notificationData = new \stdClass();
        $this->notificationData->room = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_CHANGED;
        $this->history = new HistoryController('App\Models\Room');

    }

    /**
     * @param Request $request
     * @return array
     */
    public function getAllDayFree(Request $request): array {

        $period = CarbonPeriod::create(Carbon::parse($request->get('start'))->addHours(2), Carbon::parse($request->get('end')));

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

        $rooms = Room::with('adjoining_rooms', 'main_rooms')->whereNotIn('id', $rooms_with_events_everyDay)->get()->map(fn(Room $room) => [
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
        ]);

        return [
          'rooms' => $rooms
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
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

        $room->adjoining_rooms()->sync(collect($request->adjoining_rooms)->pluck("id"));

        $room->attributes()->sync(collect($request->room_attributes)->pluck("id"));

        $room->categories()->sync(collect($request->room_categories)->pluck("id"));

        $this->history->createHistory($room->id, 'Raum wurde erstellt');

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

    /**
     * Display the specified resource.
     *
     * @param Room $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room, Request $request)
    {
        $room->load('creator');
        $projects = Project::query()->with(['adminUsers', 'managerUsers'])->get();

        return inertia('Rooms/Show', [
            'room' => new RoomCalendarResource($room),
            'is_room_admin' => $room->room_admins->contains(Auth::id()),
            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),

            'available_categories' => RoomCategory::all(),
            'roomCategoryIds' => $room->categories()->pluck('room_category_id'),
            'roomCategories' => $room->categories,

            'available_attributes' => RoomAttribute::all(),
            'roomAttributeIds' => $room->attributes()->pluck('room_attribute_id'),
            'roomAttributes' => $room->attributes,

            'available_rooms' => Room::where('id', '!=', $room->id)->get(),
            'adjoiningRoomIds' => $room->adjoining_rooms()->pluck('adjoining_room_id'),
            'adjoiningRooms' => $room->adjoining_rooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Room $room
     * @return RedirectResponse
     */
    public function update(Request $request, Room $room): RedirectResponse
    {

        $oldRoomDescription = $room->description;
        $oldRoomTitle = $room->name;
        $roomAdminsBefore = $room->room_admins()->get();
        $oldAdjoiningRooms = $room->adjoining_rooms()->get();
        $oldRoomAttributes = $room->attributes()->get();
        $oldRoomCategories = $room->categories()->get();
        $oldTemporary = $room->temporary;
        $oldStartDate = $room->start_date;
        $oldEndDate = $room->end_date;


        $room->update($request->only('name', 'description', 'temporary', 'start_date', 'end_date', 'everyone_can_book'));

        $room->room_admins()->sync(
            collect($request->room_admins)
                ->map(function ($room_admin) {
                    $this->authorize('update', User::find($room_admin['id']));
                    return $room_admin['id'];
                })
        );

        $room->adjoining_rooms()->sync($request->adjoining_rooms);
        $room->attributes()->sync($request->room_attributes);
        $room->categories()->sync($request->room_categories);

        $newRoomDescription = $room->description;
        $newRoomTitle = $room->name;
        $newAdjoiningRooms = $room->adjoining_rooms()->get();
        $newRoomAttributes = $room->attributes()->get();
        $roomAdminsAfter = $room->room_admins()->get();
        $newRoomCategories = $room->categories()->get();
        $newRoomTemporary = $room->temporary;
        $newStartDate = $room->start_date;
        $newEndDate = $room->end_date;

        $this->checkAdjoiningRoomChanges($room->id, $oldAdjoiningRooms, $newAdjoiningRooms);
        $this->checkDescriptionChanges($room->id, $oldRoomDescription, $newRoomDescription);
        $this->checkMemberChanges($room, $roomAdminsBefore, $roomAdminsAfter);
        $this->checkTitleChanges($room->id, $oldRoomTitle, $newRoomTitle);
        $this->checkAttributeChanges($room->id, $oldRoomAttributes, $newRoomAttributes);
        $this->checkCategoryChanges($room->id, $oldRoomCategories, $newRoomCategories);
        $this->checkTemporaryChanges($room->id, $oldTemporary, $newRoomTemporary, $oldStartDate, $newStartDate, $oldEndDate, $newEndDate);

        // TODO Sammel Notification
        /*$this->notificationData->title = 'Änderungen an "'. $room->name . '"';
        $this->notificationData->room->id = $room->id;
        $this->notificationData->room->title = $room->name;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $this->notificationController->create($room->room_admins()->get(), $this->notificationData);*/

        return Redirect::back()->with('success', 'Room updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Room $room): RedirectResponse
    {
        Room::create([
            'name' => '(Kopie) ' . $room->name,
            'description' => $room->description,
            'temporary' => $room->temporary,
            'start_date' => $room->start_date,
            'end_date' => $room->end_date,
            'area_id' => $room->area_id,
            'user_id' => Auth::id(),
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Room $room
     * @return RedirectResponse
     */
    public function updateOrder(Request $request): RedirectResponse
    {
        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function getTrashed(): \Inertia\Response|\Inertia\ResponseFactory
    {
        return inertia('Trash/Rooms', [
            'trashed_rooms' => Area::all()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexWithoutEventsResource::collection($area->trashed_rooms)->resolve(),
            ])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Room $room
     * @return RedirectResponse
     */
    public function destroy(Room $room): RedirectResponse
    {

        $room->delete();

        return Redirect::route('areas.management')->with('success', 'Room moved to trash');
    }

    /**
     * @param int $id
     * @return RedirectResponse
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();

        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }

    /**
     * @param int $id
     * @return RedirectResponse
     */
    public function restore(int $id): RedirectResponse
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->restore();

        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }

    /**
     * @param $roomId
     * @param $oldTemporary
     * @param $newTemporary
     * @param $oldStartDate
     * @param $newStartDate
     * @param $oldEndDate
     * @param $newEndDate
     * @return void
     */
    private function checkTemporaryChanges($roomId, $oldTemporary, $newTemporary, $oldStartDate, $newStartDate, $oldEndDate, $newEndDate): void
    {
        if($oldTemporary && !$newTemporary){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum gelöscht');
        }
        if($newTemporary && !$oldTemporary){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum hinzugefügt');
        }

        if($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum geändert');
        }
    }

    /**
     * @param $roomId
     * @param $oldCategories
     * @param $newCategories
     * @return void
     */
    private function checkCategoryChanges($roomId, $oldCategories, $newCategories): void
    {
        $oldCategoryIds = [];
        $oldCategoryNames = [];
        $newCategoryIds = [];

        foreach ($oldCategories as $oldCategory){
            $oldCategoryIds[$oldCategory->id] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory){
            $newCategoryIds[] = $newCategory->id;
            if(!in_array($newCategory->id, $oldCategoryIds)){
                $this->history->createHistory($roomId, 'Kategorie ' . $newCategory->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId){
            if(!in_array($oldCategoryId, $newCategoryIds)){
                $this->history->createHistory($roomId, 'Kategorie ' . $oldCategoryNames[$oldCategoryId] . ' wurde entfernt');
            }
        }
    }

    /**
     * @param $roomId
     * @param $oldAttributes
     * @param $newAttributes
     * @return void
     */
    private function checkAttributeChanges($roomId, $oldAttributes, $newAttributes): void
    {
        $oldAttributeIds = [];
        $oldAttributeNames = [];
        $newAttributeIds = [];

        foreach ($oldAttributes as $oldAttribute){
            $oldAttributeIds[] = $oldAttribute->id;
            $oldAttributeNames[$oldAttribute->id] = $oldAttribute->name;
        }

        foreach ($newAttributes as $newAttribute){
            $newAttributeIds[] = $newAttribute->id;
            if(!in_array($newAttribute->id, $oldAttributeIds)){
                $this->history->createHistory($roomId, 'Attribut ' . $newAttribute->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId){
            if(!in_array($oldAttributeId, $newAttributeIds)){
                $this->history->createHistory($roomId, 'Attribut ' . $oldAttributeNames[$oldAttributeId] . ' wurde entfernt');
            }
        }
    }

    /**
     * @param $roomId
     * @param $oldTitle
     * @param $newTitle
     * @return void
     */
    private function checkTitleChanges($roomId, $oldTitle, $newTitle): void
    {
        if($oldTitle !== $newTitle){
            $this->history->createHistory($roomId, 'Raumname wurde geändert');
        }
    }

    /**
     * @param $roomId
     * @param $oldDescription
     * @param $newDescription
     * @return void
     */
    private function checkDescriptionChanges($roomId, $oldDescription, $newDescription): void
    {
        // check changes in room description
        if($oldDescription !== $newDescription){
            $this->history->createHistory($roomId, 'Beschreibung wurde geändert');
        }
    }

    /**
     * @param $roomId
     * @param $oldAdjoiningRooms
     * @param $newAdjoiningRooms
     * @return void
     */
    private function checkAdjoiningRoomChanges($roomId, $oldAdjoiningRooms, $newAdjoiningRooms): void
    {
        $newAdjoiningRoomIds = [];
        $oldAdjoiningRoomIds = [];
        $oldAdjoiningRoomName = [];

        foreach ($oldAdjoiningRooms as $oldAdjoiningRoom){
            $oldAdjoiningRoomIds[] = $oldAdjoiningRoom->id;
            $oldAdjoiningRoomName[$oldAdjoiningRoom->id] = $oldAdjoiningRoom->name;
        }

        foreach ($newAdjoiningRooms as $newAdjoiningRoom){
            $newAdjoiningRoomIds[] = $newAdjoiningRoom->id;
            if(!in_array($newAdjoiningRoom->id, $oldAdjoiningRoomIds)){
                $this->history->createHistory($roomId, 'Nebenraum ' . $newAdjoiningRoom->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId){
            if(!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds)){
                $this->history->createHistory($roomId, 'Nebenraum ' . $oldAdjoiningRoomName[$oldAdjoiningRoomId]. ' wurde entfernt');
            }
        }
    }

    /**
     * @param Room $room
     * @param $roomAdminsBefore
     * @param $roomAdminsAfter
     * @return void
     */
    private function checkMemberChanges(Room $room, $roomAdminsBefore, $roomAdminsAfter): void
    {
        $roomAdminIdsBefore = [];
        $roomAdminIdsAfter = [];
        foreach ($roomAdminsBefore as $roomAdminBefore){
            $roomAdminIdsBefore[] = $roomAdminBefore->id;
        }

        foreach ($roomAdminsAfter as $roomAdminAfter){
            $roomAdminIdsAfter[] = $roomAdminAfter->id;
            // if added a new room admin, send notification to this user
            if(!in_array($roomAdminAfter->id, $roomAdminIdsBefore)){
                $user = User::find($roomAdminAfter->id);
                $this->notificationData->title = 'Du wurdest zum Raumadmin von "' . $room->name . '" ernannt';
                $this->notificationData->room->id = $room->id;
                $this->notificationData->room->title = $room->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $this->notificationController->create($user, $this->notificationData);
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin hinzugefügt');
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore){
            if(!in_array($roomAdminBefore, $roomAdminIdsAfter)){
                $user = User::find($roomAdminBefore);
                $this->notificationData->title = 'Du wurdest als Raumadmin von "' . $room->name . '" gelöscht';
                $this->notificationData->room->id = $room->id;
                $this->notificationData->room->title = $room->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $this->notificationController->create($user, $this->notificationData);
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin entfernt');
            }
        }
    }

}
