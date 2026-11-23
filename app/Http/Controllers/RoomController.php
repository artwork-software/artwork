<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Change\Changes\Room\MemberChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Room\Collision\Service\CollisionService;
use Artwork\Modules\Room\Exports\RoomListExport;
use Artwork\Modules\Room\Http\Requests\UpdateRoomUserRequest;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomBulkService;
use Artwork\Modules\Room\Services\RoomChangeService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Room\Services\RoomFrontendModelService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly CollisionService $collisionService,
        private readonly SchedulingService $schedulingService,
        private readonly RoomChangeService $roomChangeService,
        private readonly RoomFrontendModelService $roomFrontendModelService,
        private readonly Redirector $redirector,
        private readonly UserService $userService,
        private readonly MemberChange $memberChange,
        private readonly ChangeService $changeService,
        private readonly RoomBulkService $roomBulkService,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Room::class);

        $request->validate(array_merge($this->roomFieldRules($request, true), [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]));

        $room = Room::create([
            'name' => $request->name,
            'color' => $request->color,
            'description' => $request->description,
            'temporary' => $request->boolean('temporary'),
            // Zeitraum nur für temporäre Räume speichern
            'start_date' => $request->boolean('temporary') ? $request->start_date : null,
            'end_date' => $request->boolean('temporary') ? $request->end_date : null,
            'area_id' => $request->area_id,
            'user_id' => $request->user_id ?? $request->user()->id,
            'everyone_can_book' => $request->boolean('everyone_can_book'),
            'relevant_for_disposition' => $request->boolean('relevant_for_disposition', true),
            'capacity' => $request->capacity,
            'street' => $request->street,
            'zip_code' => $request->zip_code,
            'city' => $request->city,
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

    public function show(Room $room): Response|ResponseFactory
    {
        $room->load(['creator']);

        return Inertia::render(
            'Rooms/Show',
            $this->roomFrontendModelService->createShowDto(
                $room,
                $this->userService->getAuthUser(),
            )
        );
    }

    public function getMoveRooms(): Response|ResponseFactory
    {
        $this->authorize('viewAny', Room::class);

        return inertia('Rooms/RoomReorderManagement', [
            'rooms' => Room::with('area:id,name,color')->orderBy('position')->orderBy('id')->get(),
            'areas' => Area::query()->orderBy('position')->orderBy('id')->get(['id', 'name', 'color']),
        ]);
    }

    public function updateOrderNew(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Room::class);

        $validated = $request->validate([
            'rooms' => ['required', 'array'],
            'rooms.*.id' => ['required', 'integer'],
            'rooms.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['rooms'] as $room) {
            Room::findOrFail($room['id'])->update(['position' => $room['position']]);
        }

        return Redirect::back();
    }

    public function updateRoomUsers(Room $room, UpdateRoomUserRequest $request): RedirectResponse
    {
        $this->authorize('update', $room);

        // Vorher-Stand für Verlauf/Benachrichtigungen (vorher nur über update() – das Zugriffs-Modal lief daran vorbei)
        $roomBefore = $room->replicate();
        $roomBefore->admins = $room->admins()->get();
        $departmentsBefore = $room->departments()->get()->keyBy('id');
        $affectedUserIds = array_merge(
            $room->users()->pluck('users.id')->all(),
            $room->effectiveAdminIds(),
        );

        $room->users()->detach();
        foreach ($request->roomUsers() as $user) {
            $room->users()->attach($user['id'], [
                'is_admin' => $user['is_admin'],
                'can_request' => $user['can_request'],
            ]);
            $affectedUserIds[] = $user['id'];
        }

        if ($request->usesTeamPayload()) {
            $room->departments()->sync(collect($request->roomDepartments())->mapWithKeys(
                static fn (array $department): array => [$department['id'] => [
                    'is_admin' => $department['is_admin'],
                    'can_request' => $department['can_request'],
                ]]
            )->all());
        }

        $room->unsetRelation('admins');
        $room->unsetRelation('adminDepartments');
        $room->unsetRelation('departments');
        $this->memberChange->change($room, $roomBefore);
        $this->recordAdminDepartmentChanges($room, $departmentsBefore);

        // Raum-Admin-Status beeinflusst den gecachten canSeeIncomingRequests-Flag – auch für Team-Mitglieder
        User::forgetCachedShareDataForIds(array_unique(array_merge($affectedUserIds, $room->effectiveAdminIds())));

        return $this->redirector->back();
    }

    /**
     * Verlaufseinträge, wenn ein Team als Raumadmin hinzukommt oder wegfällt.
     * @param \Illuminate\Support\Collection<int, Department> $departmentsBefore
     */
    private function recordAdminDepartmentChanges(Room $room, \Illuminate\Support\Collection $departmentsBefore): void
    {
        $adminIdsBefore = $departmentsBefore
            ->filter(fn (Department $d) => (bool) $d->pivot?->getAttribute('is_admin'))
            ->keys()
            ->all();
        $adminsAfter = $room->adminDepartments()->get()->keyBy('id');

        foreach ($adminsAfter as $id => $department) {
            if (!in_array($id, $adminIdsBefore, true)) {
                $this->recordRoomChange($room, 'Added as room admin', $department->name);
            }
        }
        foreach ($adminIdsBefore as $id) {
            if (!$adminsAfter->has($id)) {
                $this->recordRoomChange($room, 'Removed as room admin', $departmentsBefore[$id]->name);
            }
        }
    }

    private function recordRoomChange(Room $room, string $translationKey, string $name): void
    {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($room->id)
                ->setTranslationKey($translationKey)
                ->setTranslationKeyPlaceholderValues([$name])
        );
    }

    public function update(
        Request $request,
        Room $room
    ): RedirectResponse {
        $this->authorize('update', $room);

        $request->validate(array_merge($this->roomFieldRules($request, false, $room), [
            'area_id' => ['sometimes', 'integer', Rule::exists('areas', 'id')->whereNull('deleted_at')],
        ]));

        // Verschieben in ein anderes Areal ändert die Raumstruktur des Hauses: nur mit Raumverwaltung,
        // nicht schon als Admin des einzelnen Raums
        $targetArea = $request->filled('area_id') && $request->integer('area_id') !== (int) $room->area_id
            ? Area::query()->findOrFail($request->integer('area_id'))
            : null;
        if ($targetArea !== null) {
            $this->authorize('create', Room::class);
        }

        $roomReplicate = $room->replicate();
        $roomReplicate->admins = $room->users()->wherePivot('is_admin', true)->get();
        $roomReplicate->adjoining_rooms = $room->adjoining_rooms()->get();
        $roomReplicate->attributes = $room->attributes()->get();
        $roomReplicate->categories = $room->categories()->get();

        $attributes = $request->only(
            'name',
            'color',
            'description',
            'temporary',
            'start_date',
            'end_date',
            'everyone_can_book',
            'relevant_for_disposition',
            'capacity',
            'street',
            'zip_code',
            'city'
        );
        // Zeitraum gehört nur zu temporären Räumen; beim Abwählen nicht als Altlast stehen lassen
        if ($request->has('temporary') && !$request->boolean('temporary')) {
            $attributes['start_date'] = null;
            $attributes['end_date'] = null;
        }
        $room->update($attributes);
        if ($targetArea !== null) {
            $this->roomBulkService->moveRoom($room, $targetArea);
        }

        if (!is_null($request->room_admins) && !is_null($request->requestable_by)) {
            $room_admins_ids = [];
            foreach ($request->room_admins as $room_admin) {
                $room_admins_ids[$room_admin['id']] = ['is_admin' => true];
            }

            $requestable_by_ids = [];
            foreach ($request->requestable_by as $can_request) {
                $requestable_by_ids[$can_request['id']] = ['can_request' => true];
            }

            $previousUserIds = $room->users()->pluck('users.id')->all();

            $new_users = collect($room_admins_ids + $requestable_by_ids);
            $room->users()->detach();
            $room->users()->sync($new_users);

            // Raum-Admin-Status beeinflusst den gecachten canSeeIncomingRequests-Flag
            User::forgetCachedShareDataForIds(array_unique([...$previousUserIds, ...$new_users->keys()->all()]));
        }

        // nur mitgeschickte Relationen synchronisieren: sync(null) hätte sonst alle Nebenräume,
        // Eigenschaften und Kategorien entfernt, sobald ein Formular sie nicht mitsendet
        if ($request->has('adjoining_rooms')) {
            $room->adjoining_rooms()->sync($request->input('adjoining_rooms') ?? []);
        }
        if ($request->has('room_attributes')) {
            $room->attributes()->sync($request->input('room_attributes') ?? []);
        }
        if ($request->has('room_categories')) {
            $room->categories()->sync($request->input('room_categories') ?? []);
        }

        $this->roomChangeService->applyChanges(
            $room,
            $roomReplicate
        );

        $roomId = $room->id;
        foreach ($room->effectiveAdminIds() as $adminId) {
            $this->schedulingService->create($adminId, 'ROOM_CHANGES', 'ROOMS', $roomId);
        }

        return Redirect::back();
    }

    public function duplicate(Room $room): RedirectResponse
    {
        $this->authorize('create', Room::class);

        $this->roomService->duplicateByRoomModel($room);

        return Redirect::route('areas.management');
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Room::class);

        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    public function getTrashed(Request $request): Response|ResponseFactory
    {
        $this->authorize('viewAny', Room::class);

        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        // Eine Zeile je Raum; die Suche trifft Raum- und Arealnamen
        $trashedRooms = Room::onlyTrashed()
            ->with(['area' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'deleted_at'])])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas(
                            'area',
                            fn ($area) => $area->withTrashed()->where('name', 'like', '%' . $search . '%')
                        );
                });
            })
            ->orderByDesc('deleted_at')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
        $trashInfo = app(\Artwork\Modules\Trash\Services\TrashInfoService::class)->forModels($trashedRooms->getCollection());
        $trashedRooms->through(fn (Room $room) => [
            'id' => $room->id,
            'name' => $room->name,
            'temporary' => (bool) $room->temporary,
            'start_date' => $room->temporary ? $room->start_date?->format('d.m.Y') : null,
            'end_date' => $room->temporary ? $room->end_date?->format('d.m.Y') : null,
            'area_name' => $room->area?->name,
            'area_trashed' => $room->area === null || $room->area->trashed(),
            'trash' => $trashInfo[$room->id] ?? null,
        ]);

        return inertia('Trash/Rooms', [
            'trashed_rooms' => $trashedRooms
        ]);
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', Room::class);

        $room->delete();

        return Redirect::route('areas.management');
    }

    /**
     * Mehrere Räume in einem Request in den Papierkorb legen. Alles oder nichts.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Room::class);

        return response()->json([
            'deleted' => $this->roomBulkService->moveToTrash($this->selectedRooms($request)),
        ]);
    }

    /**
     * Sammeländerung für mehrere Räume: Kategorien oder Eigenschaften zuweisen bzw. entfernen
     * oder in ein anderes Areal verschieben. Alles oder nichts.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->authorize('create', Room::class);

        $rooms = $this->selectedRooms($request);
        $validated = $request->validate([
            'action' => ['required', 'in:add_categories,remove_categories,add_attributes,remove_attributes,move'],
            'category_ids' => ['required_if:action,add_categories,remove_categories', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:room_categories,id'],
            'attribute_ids' => ['required_if:action,add_attributes,remove_attributes', 'array', 'min:1'],
            'attribute_ids.*' => ['integer', 'distinct', 'exists:room_attributes,id'],
            'area_id' => [
                'required_if:action,move',
                'integer',
                Rule::exists('areas', 'id')->whereNull('deleted_at'),
            ],
        ]);

        match ($validated['action']) {
            'add_categories' => $this->roomBulkService->changeCategories($rooms, $validated['category_ids'], true),
            'remove_categories' => $this->roomBulkService->changeCategories($rooms, $validated['category_ids'], false),
            'add_attributes' => $this->roomBulkService->changeAttributes($rooms, $validated['attribute_ids'], true),
            'remove_attributes' => $this->roomBulkService->changeAttributes(
                $rooms,
                $validated['attribute_ids'],
                false
            ),
            'move' => $this->roomBulkService->moveToArea($rooms, Area::query()->findOrFail($validated['area_id'])),
        };

        return response()->json(['updated' => $rooms->count()]);
    }

    /**
     * Raumliste als Excel. Mit "ids" (kommagetrennt) nur diese Räume, sonst alle.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Room::class);

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) $request->query('ids', ''))
        ))));

        $rooms = Room::query()
            ->select('rooms.*')
            ->join('areas', 'areas.id', '=', 'rooms.area_id')
            ->whereNull('areas.deleted_at')
            ->when($ids !== [], static fn ($query) => $query->whereIn('rooms.id', $ids))
            ->with(['area:id,name', 'categories', 'attributes', 'adminDepartments:id,name'])
            ->with(['adjoining_rooms' => static fn ($query) => $query->without(['admins', 'creator'])])
            ->orderBy('areas.position')
            ->orderBy('areas.id')
            ->orderBy('rooms.position')
            ->orderBy('rooms.id')
            ->get();

        return (new RoomListExport($rooms))->download(
            __('Rooms') . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * @return EloquentCollection<int, Room>
     */
    private function selectedRooms(Request $request): EloquentCollection
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $rooms = Room::query()->whereIn('id', $validated['ids'])->get();
        abort_unless($rooms->count() === count($validated['ids']), 404);

        return $rooms;
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorize('forceDelete', Room::class);

        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();
        return Redirect::route('rooms.trashed');
    }

    public function forceDeleteAll(): RedirectResponse
    {
        $this->authorize('forceDelete', Room::class);

        Room::onlyTrashed()->each(function ($room): void {
            $room->forceDelete();
        });
        return Redirect::route('rooms.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('restore', Room::class);

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
        try {
            $startDate = Carbon::parse($params['start'] ?? null)->setTimezone(config('app.timezone'));
            $endDate = Carbon::parse($params['end'] ?? null)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            abort(422, 'Invalid date parameters.');
        }
        $currentEventId = isset($params['currentEventId']) ? (int) $params['currentEventId'] : null;

        $roomIds = Room::query()->setEagerLoads([])->orderBy('id')->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $this->collisionService->findCollisionCountsForRooms($roomIds, $startDate, $endDate, $currentEventId);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('search');
        $rooms = Room::query()
            ->where('name', 'like', "%{$search}%")
            ->orWhereHas('area', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with([
                'area',
                'admins:id',
                'requestableBy:id',
                'adminDepartments.users:id',
                'requestableByDepartments.users:id',
            ])
            ->get();

        $normalized = $rooms->map(fn(Room $room) => [
            'id' => $room->id,
            'name' => $room->name,
            'area' => $room->area,
            'temporary' => $room->temporary,
            'start_date' => $room->start_date,
            'end_date' => $room->end_date,
            'admins' => $room->effectiveAdminIds(),
            'everyone_can_book' => $room->everyone_can_book,
            'requestable_by' => $room->effectiveRequestableByIds(),
        ]);

        return response()->json($normalized);
    }

    /**
     * Gemeinsame Regeln für Raum anlegen/bearbeiten. Ohne Prüfung führten leerer Name oder
     * ungültige Daten zu SQL-Fehlern (500), und ein temporärer Raum ohne Enddatum wurde nie
     * automatisch entfernt. Beim Bearbeiten nur vorhandene Felder prüfen (Teilupdates).
     *
     * @return array<string, array<int, string>>
     */
    /**
     * @param Room|null $room bestehender Raum beim Bearbeiten
     */
    private function roomFieldRules(Request $request, bool $creating, ?Room $room = null): array
    {
        $presence = $creating ? 'required' : 'sometimes';
        $endDateRules = ['nullable', 'date'];
        // Altbestand: temporäre Räume ohne Enddatum bleiben bearbeitbar (z. B. umbenennen); Pflicht wird das
        // Enddatum beim Anlegen und wenn ein Raum neu temporär wird
        $legacyTemporaryWithoutEnd = $room !== null && $room->temporary && $room->end_date === null;
        if (!$legacyTemporaryWithoutEnd) {
            $endDateRules[] = 'required_if_accepted:temporary';
        }
        if ($request->filled('start_date')) {
            $endDateRules[] = 'after_or_equal:start_date';
        }

        return [
            'name' => [$presence, 'string', 'max:255'],
            // varchar(7)-Spalte: ungültige Werte würden im Strict-Mode einen SQL-Fehler (500) werfen
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string'],
            'temporary' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => $endDateRules,
            'everyone_can_book' => ['sometimes', 'boolean'],
            'relevant_for_disposition' => ['sometimes', 'boolean'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
        ];
    }
}
