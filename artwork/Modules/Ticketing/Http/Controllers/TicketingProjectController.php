<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingDraftRequest;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingProductionRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingEventsRequest;
use Artwork\Modules\Ticketing\Services\TicketingProductionService;
use Artwork\Modules\Ticketing\Services\TicketingProjectService;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Die Ticketing-Komponente im Projekt. Lesen darf, wer die Komponente sieht (Route); schreiben, wer
 * sie bearbeiten darf; zurückziehen nur mit dem Recht für Termine im Verkauf — Käufer*innen hängen daran.
 * Jede Änderung antwortet mit dem frischen Stand.
 */
class TicketingProjectController extends Controller
{
    public function __construct(
        private readonly TicketingProjectService $projects,
        private readonly TicketingReleaseService $releases,
        private readonly TicketingProductionService $productions,
    ) {
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json($this->projects->payload($project));
    }

    public function saveProduction(Project $project, SaveTicketingProductionRequest $request): JsonResponse
    {
        $this->authorizeEdit($project);

        return $this->respond($project, function () use ($project, $request): void {
            $this->productions->save(
                $project,
                [
                    'title' => $request->validated('title'),
                    'description' => $request->validated('description'),
                    'reduction_type_ids' => $request->validated('reduction_type_ids'),
                ],
                $request->file('hero'),
                $request->boolean('remove_hero'),
                $request->file('images', []),
                $request->removeImageIds(),
                $request->coverImageId(),
            );
        });
    }

    public function saveDraft(Project $project, SaveTicketingDraftRequest $request): JsonResponse
    {
        $this->authorizeEdit($project);
        $events = $this->eventsOf($project, $request->eventIds());

        return $this->respond($project, fn () => $this->releases->saveDraft($events, $request->draft()));
    }

    public function release(Project $project, TicketingEventsRequest $request): JsonResponse
    {
        $this->authorizeEdit($project);
        $events = $this->eventsOf($project, $request->eventIds());

        return $this->respond($project, fn () => $this->releases->release($events, $request->user()));
    }

    public function withdraw(Project $project, TicketingEventsRequest $request): JsonResponse
    {
        $this->authorizeEdit($project);
        abort_unless(
            Gate::allows(PermissionEnum::TICKETING_MOVE_ON_SALE->value),
            403,
            __('Only people with the permission "Change dates on sale" can withdraw dates on sale.'),
        );
        $events = $this->eventsOf($project, $request->eventIds());

        return $this->respond($project, fn () => $this->releases->withdraw($events));
    }

    private function authorizeEdit(Project $project): void
    {
        $this->authorize('writeComponentType', [$project, ProjectTabComponentEnum::TICKETING]);
    }

    /**
     * Die angefragten Termine des Projekts in Terminreihenfolge; ein fremder Termin macht die ganze Anfrage zu 404.
     *
     * @param list<int> $eventIds
     * @return Collection<int, Event>
     */
    private function eventsOf(Project $project, array $eventIds): Collection
    {
        $events = Event::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $eventIds)
            ->with(['room', 'ticketingRelease'])
            ->orderBy('start_time')
            ->get();

        abort_unless($events->count() === count($eventIds), 404);

        return $events;
    }

    /** Was tickets ablehnt oder nicht beantwortet, ist hier eine Meldung an die Person, kein Serverfehler. */
    private function respond(Project $project, \Closure $action): JsonResponse
    {
        try {
            $action();
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->projects->payload($project));
    }
}
