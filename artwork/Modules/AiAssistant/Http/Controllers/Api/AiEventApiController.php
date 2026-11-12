<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiEventListData;
use Artwork\Modules\AiAssistant\DTOs\AiFreeSlotListData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomOccupancyListData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiEventsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiFreeSlotsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRoomOccupancyRequest;
use Artwork\Modules\AiAssistant\Services\AiEventReadService;

/**
 * Termine, Raumbelegung und freie Zeitfenster fuer artwork-ai
 * (core-api-events-rooms; Konzept B1 §3–§5).
 */
class AiEventApiController extends Controller
{
    public function __construct(private readonly AiEventReadService $service)
    {
    }

    public function index(IndexAiEventsRequest $request): AiEventListData
    {
        return $this->service->search(
            $request->user(),
            $request->from(),
            $request->to(),
            [
                'room_ids' => $request->roomIds(),
                'project_id' => $request->validated('project_id'),
                'type_id' => $request->validated('type_id'),
                'limit' => $request->validated('limit'),
            ]
        );
    }

    public function occupancy(IndexAiRoomOccupancyRequest $request): AiRoomOccupancyListData
    {
        return $this->service->occupancy(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->roomIds()
        );
    }

    public function freeSlots(IndexAiFreeSlotsRequest $request): AiFreeSlotListData
    {
        return $this->service->freeSlots(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->roomIds(),
            $request->minHours(),
            $request->weekdays(),
            $request->dayWindow()
        );
    }
}
