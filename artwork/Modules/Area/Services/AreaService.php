<?php

namespace Artwork\Modules\Area\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Repositories\AreaRepository;
use Artwork\Modules\Room\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AreaService
{
    public function __construct(
        private readonly AreaRepository $areaRepository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->areaRepository->getAll();
    }

    public function updateByRequest(Area $area, Request $request): Area|Model
    {
        $area->fill($request->only('name', 'color'));
        return $this->areaRepository->save($area);
    }

    public function createByRequest(Request $request): Area|Model
    {
        $area = new Area();
        $area->fill($request->only('name', 'color'));
        $area->position = $this->nextPosition();
        return $this->areaRepository->save($area);
    }

    /**
     * Speichert die Reihenfolge der Areale in der übergebenen Reihenfolge der IDs.
     *
     * @param array<int, int> $areaIds
     */
    public function saveOrder(array $areaIds): void
    {
        foreach (array_values($areaIds) as $index => $areaId) {
            Area::query()->whereKey($areaId)->update(['position' => $index + 1]);
        }
    }

    private function nextPosition(): int
    {
        return (int) Area::withTrashed()->max('position') + 1;
    }

    public function delete(Area $area): bool
    {
        return $this->areaRepository->delete($area);
    }

    public function duplicateByAreaModel(Area $area, RoomService $roomService): void
    {
        $new_area = $area->replicate();
        $new_area->name = __('(Copy)') . ' ' . $area->name;
        $new_area->position = $this->nextPosition();

        $this->areaRepository->save($new_area);

        foreach ($area->rooms as $room) {
            $new_room = $roomService->duplicateByRoomModelWithoutArea($room);
            $new_room->created_at = Carbon::now();
            $new_area->rooms()->save($new_room);
        }
        $this->areaRepository->save($new_area);
    }
}
