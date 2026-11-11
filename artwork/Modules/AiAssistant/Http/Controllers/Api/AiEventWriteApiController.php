<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\Http\Requests\DeleteAiEventBatchRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiEventBatchRequest;
use Artwork\Modules\AiAssistant\Http\Requests\UpdateAiEventRequest;
use Artwork\Modules\AiAssistant\Services\AiEventWriteService;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;

/**
 * Schreib-Endpunkte fuer artwork-ai (core-write-endpoints-dry-run; Konzept W1).
 *
 * Ohne `dry_run=false` **und** gueltige `action_id` passiert nichts: die Vorschau laeuft in einer
 * Transaktion, die zurueckgerollt wird. Die Ausfuehrung selbst haengt am `ActionGuard`, der bis
 * `core-confirm-actions` jede Ausfuehrung mit 422 ablehnt.
 */
class AiEventWriteApiController extends Controller
{
    public function __construct(private readonly AiEventWriteService $service)
    {
    }

    public function storeBatch(StoreAiEventBatchRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->service->createBatch(
            $request->user(),
            (int) $request->validated('project_id'),
            $request->seriesSpec(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function update(UpdateAiEventRequest $request, int $event): AiWritePreviewData|AiWriteResultData
    {
        return $this->service->update(
            $request->user(),
            $event,
            $request->changes(),
            // Die Termin-ID steht im Pfad und gehoert trotzdem in den Fingerabdruck der Aktion —
            // sonst liesse sich eine bestaetigte Aenderung auf einen anderen Termin umbiegen.
            AiWriteIntent::fromRequest($request, ['event_id' => $event]),
        );
    }

    public function destroyBatch(DeleteAiEventBatchRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->service->deleteBatch(
            $request->user(),
            $request->ids(),
            AiWriteIntent::fromRequest($request),
        );
    }
}
