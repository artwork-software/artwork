<?php

namespace Artwork\Modules\Event\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Settings\EventSettings;
use Artwork\Modules\Event\Http\Resources\Api\EventStatusApiResource;
use Artwork\Modules\Event\Models\EventStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Terminstatus für externe Systeme, nur lesend. Zugriff über den Scope calendar:read.
 *
 * Damit kann ein angebundenes System festlegen, welche Status als Belegung zählen, ohne die Namen
 * erst an gelieferten Terminen ablesen zu müssen. status_enabled sagt, ob die Instanz Terminstatus
 * überhaupt verwendet; ist es false, liefert /api/v1/events immer status: null.
 */
class EventStatusApiController extends Controller
{
    public function index(EventSettings $eventSettings): AnonymousResourceCollection
    {
        $statuses = EventStatus::query()
            ->select(['id', 'name', 'color', 'order', 'default'])
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return EventStatusApiResource::collection($statuses)->additional([
            'status_enabled' => (bool) $eventSettings->enable_status,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
