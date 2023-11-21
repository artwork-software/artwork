<?php

namespace Artwork\Modules\Area\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Repositories\AreaRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AreaService
{
    public function __construct(private readonly AreaRepository $areaRepository)
    {

    }

    public function updateByRequest(Area $area, Request $request): Area|Model
    {
        $area->fill($request->only('name'));
        return $this->areaRepository->save($area);
    }

    public function createByRequest(Request $request): Area|Model
    {
        $area = new Area();
        $area->fill($request->only('name'));
        return $this->areaRepository->save($area);
    }

    public function delete(Area $area): bool
    {
        $rooms = $area->rooms()->get();
        foreach ($rooms as $room){
            $room->delete();
        }
        return $this->areaRepository->delete($area);
    }

    public function duplicateByAreaModel(Area $area)
    {

        $new_area = $area->replicate();
        $new_area->name = '(Kopie) ' . $area->name;

        $this->areaRepository->save($new_area);

        foreach ($area->rooms as $room) {
            $new_room = $room->replicate();
            $new_room->name = '(Kopie) ' . $room->name;
            $new_room->created_at = Carbon::now();
            $new_area->rooms()->save($new_room);
            $this->areaRepository->save($new_area);
        }
    }


}
