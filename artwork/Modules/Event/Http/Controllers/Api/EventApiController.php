<?php

namespace Artwork\Modules\Event\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Http\Requests\Api\EventApiIndexRequest;
use Artwork\Modules\Event\Http\Resources\Api\EventApiCollection;
use Artwork\Modules\Event\Services\EventApiService;

/**
 * Termine (Raumbelegungen) für externe Systeme, nur lesend. Zugriff über den Scope calendar:read.
 */
class EventApiController extends Controller
{
    public function __construct(
        private readonly EventApiService $eventApiService
    ) {
    }

    public function index(EventApiIndexRequest $request): EventApiCollection
    {
        $events = $this->eventApiService->paginate(
            $request->rangeStart(),
            $request->rangeEnd(),
            $request->roomIds(),
            $request->areaIds(),
            $request->updatedSince(),
            $request->perPage()
        );

        return (new EventApiCollection($events))->additional([
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
