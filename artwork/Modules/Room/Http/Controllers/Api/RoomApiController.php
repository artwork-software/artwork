<?php

namespace Artwork\Modules\Room\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\Room\Http\Requests\Api\RoomApiIndexRequest;
use Artwork\Modules\Room\Http\Resources\Api\RoomApiResource;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Raumliste für externe Systeme, nur lesend. Zugriff über den Scope calendar:read.
 *
 * Ohne Blättern: Auch große Häuser haben selten mehr als ein paar hundert Räume. Räume im
 * Papierkorb fehlen; ihre Termine liefert auch /api/v1/events nicht.
 */
class RoomApiController extends Controller
{
    public function index(RoomApiIndexRequest $request): AnonymousResourceCollection
    {
        $areaIds = $request->areaIds();

        $rooms = Room::query()
            ->select([
                'id',
                'name',
                'area_id',
                'capacity',
                'color',
                'temporary',
                'start_date',
                'end_date',
                'order',
                'updated_at',
            ])
            ->with(['area:id,name,color', 'categories:room_categories.id,room_categories.name'])
            ->when($areaIds !== [], static fn (Builder $query) => $query->whereIn('area_id', $areaIds))
            ->orderBy('area_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return RoomApiResource::collection($rooms)->additional([
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
