<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiAvailableRoomListData;
use Artwork\Modules\AiAssistant\DTOs\AiEventListData;
use Artwork\Modules\AiAssistant\DTOs\AiEventStatsListData;
use Artwork\Modules\AiAssistant\DTOs\AiFreeSlotListData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomOccupancyListData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiAvailableRoomsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiEventStatsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiEventsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiFreeSlotsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRoomOccupancyRequest;
use Artwork\Modules\AiAssistant\Services\AiEventReadService;
use Artwork\Modules\AiAssistant\Services\AiEventStatsService;
use Artwork\Modules\AiAssistant\Services\AiRoomAvailabilityService;

/**
 * Termine, Raumbelegung und freie Zeitfenster fuer artwork-ai
 * (core-api-events-rooms; Konzept B1 §3–§5).
 */
class AiEventApiController extends Controller
{
    public function __construct(
        private readonly AiEventReadService $service,
        private readonly AiEventStatsService $stats,
        private readonly AiRoomAvailabilityService $availability,
    ) {
    }

    /**
     * Terminanzahl je Projekt, Raum oder Terminart (core-ai-event-stats-exports).
     */
    public function stats(IndexAiEventStatsRequest $request): AiEventStatsListData
    {
        return $this->stats->stats(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->groupBy(),
            [
                'room_ids' => $request->roomIds(),
                'project_id' => $request->validated('project_id'),
                'type_id' => $request->validated('type_id'),
                'min_count' => $request->validated('min_count'),
                'max_count' => $request->validated('max_count'),
                'limit' => $request->validated('limit'),
            ]
        );
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

    /**
     * Alle freien Raeume zu einem Zeitfenster (core-ai-aggregates, Entgrenzen P1).
     */
    public function availableRooms(IndexAiAvailableRoomsRequest $request): AiAvailableRoomListData
    {
        return $this->availability->available(
            $request->user(),
            $request->from(),
            $request->to(),
            [
                'room_category_id' => $request->roomCategoryId(),
                'area_id' => $request->areaId(),
                'limit' => $request->limit(),
            ]
        );
    }
}
