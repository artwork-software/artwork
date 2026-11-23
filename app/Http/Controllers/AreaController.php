<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Room\Http\Resources\RoomIndexResource;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class AreaController extends Controller
{
    public function __construct(
        private readonly AreaService $areaService,
        private readonly RoomService $roomService
    ) {
        $this->authorizeResource(Area::class);
    }

    public function index(): Response|ResponseFactory
    {
        // Räume samt Relationen für alle Areale in wenigen Queries laden; vorher je Areal
        // eine Raum-Query und je Raum vier weitere (Admins, Kategorien, Attribute,
        // Nebenräume) in RoomIndexResource — 144 Queries bei 25 Räumen.
        $areas = Area::query()
            ->with([
                'rooms' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'rooms.categories',
                'rooms.attributes',
                // Nebenräume liest die Seite nur als IDs — ohne Room::$with (Admins, Ersteller)
                'rooms.adjoining_rooms' => fn ($query) => $query->without(['admins', 'creator']),
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return inertia('Areas/AreaManagement', [
            'areas' => $areas->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'color' => $area->color ?? '#000000',
                // showContent declares if the area should be showing all details when loading the page
                'showContent' => true,
                'rooms' => RoomIndexResource::collection($area->rooms)->resolve(),
            ]),
            'opened_areas' => User::where('id', Auth::id())->first()->opened_areas,
            'room_categories' => RoomCategory::all(),
            'room_attributes' => RoomAttribute::all()
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // varchar(7)-Spalte: ungültige Werte würden im Strict-Mode einen SQL-Fehler (500) werfen
        $request->validate([
            // ohne Namen (auch nur Leerzeichen) scheiterte das Anlegen mit einem Datenbankfehler (500)
            'name' => ['required', 'filled', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->areaService->createByRequest($request);
        return Redirect::route('areas.management');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $request->validate([
            'name' => ['sometimes', 'filled', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->areaService->updateByRequest($area, $request);
        return Redirect::route('areas.management');
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $this->authorize('update', Area::class);

        $validated = $request->validate([
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => ['integer', 'distinct', 'exists:areas,id'],
        ]);

        $this->areaService->saveOrder($validated['areas']);

        return Redirect::back();
    }

    public function duplicate(Area $area, RoomService $roomService): RedirectResponse
    {
        $this->authorize('create', Area::class);

        $this->areaService->duplicateByAreaModel($area, $roomService);
        return Redirect::route('areas.management');
    }

    public function destroy(Area $area): RedirectResponse
    {
        foreach ($area->rooms as $room) {
            $this->roomService->delete($room);
        }
        $this->areaService->delete($area);
        return Redirect::route('areas.management');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorize('forceDelete', Area::class);

        $area = Area::onlyTrashed()->findOrFail($id);
        $area->forceDelete();
        return Redirect::route('areas.trashed');
    }

    public function forceDeleteAll(): RedirectResponse
    {
        $this->authorize('forceDelete', Area::class);

        Area::onlyTrashed()->each(function ($area): void {
            $area->forceDelete();
        });
        return Redirect::route('areas.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('restore', Area::class);

        /** @var Area $area */
        $area = Area::onlyTrashed()->findOrFail($id);
        $area->restore();
        foreach ($area->trashedRooms as $room) {
            $room->restore();
        }
        return Redirect::route('areas.trashed');
    }

    public function getTrashed(Request $request): Response|ResponseFactory
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedAreas = Area::onlyTrashed()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->with(['rooms' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'area_id'])])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
        $trashInfo = app(\Artwork\Modules\Trash\Services\TrashInfoService::class)->forModels($trashedAreas->getCollection());
        $trashedAreas->through(fn ($area) => [
            'id' => $area->id,
            'name' => $area->name,
            'room_names' => $area->rooms->pluck('name')->values()->all(),
            'trash' => $trashInfo[$area->id] ?? null,
        ]);

        return inertia('Trash/Areas', [
            'trashed_areas' => $trashedAreas
        ]);
    }
}
