<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Services\ProjectComponentValueNormalizer;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectComponentValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Aktueller Wert einer Komponente im Projekt – für den Broadcast-Listener, der nach data.updated
     * nur Kennungen bekommt. Gleiche Sichtregel wie die Tab-Ausgabe: Projekt sehen und die Komponente
     * in einem sichtbaren Tab sehen dürfen (Komponenten-Sichtbeschränkung + Tab-Sichtbarkeit).
     */
    public function value(
        Project $project,
        Component $component,
        ProjectComponentVisibilityService $visibilityService,
    ): JsonResponse {
        $this->authorize('view', $project);

        /** @var User $user */
        $user = Auth::user();
        abort_unless($visibilityService->canSeeInProject($user, $component), 403);

        return response()->json([
            'project_value' => ProjectComponentValue::query()
                ->where('project_id', $project->id)
                ->where('component_id', $component->id)
                ->first(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Gibt den gespeicherten Wert im Format von project_value zurück: der Speichernde übernimmt ihn
     * lokal (der Broadcast geht toOthers und trägt nur Kennungen).
     */
    public function update(
        Request $request,
        Project $project,
        Component $component,
        ProjectComponentVisibilityService $visibilityService,
        ProjectComponentValueNormalizer $normalizer,
    ): JsonResponse {
        /** @var \Artwork\Modules\User\Models\User $user */
        $user = $request->user();

        // Schreibrecht im Projekt + Komponenten-Einstellung (Spiegel von canEditComponent() im Frontend).
        abort_unless($user->can('writeComponent', [$project, $component]), 403);
        // Gleiche Sichtregel wie value(): Komponenten in für die Person unsichtbaren Tabs sind nicht
        // beschreibbar, auch wenn Projekt-Schreibrecht besteht.
        abort_unless($visibilityService->canSeeInProject($user, $component), 403);

        $request->validate(['data' => ['present', 'nullable', 'array']]);

        // Gleiche Typprüfung wie beim externen Zugriff (ExternalComponentValueService)
        $valueInput = $normalizer->normalize($component, $request->input('data'));

        // Unique-Index (project_id, component_id): parallele Autosaves erzeugen keine Duplikate mehr.
        $value = ProjectComponentValue::query()->updateOrCreate(
            ['project_id' => $project->id, 'component_id' => $component->id],
            ['data' => $valueInput]
        );

        // Nur bei echter Änderung senden (Fokuswechsel ohne Änderung erzeugten sonst je Feld einen
        // Broadcast und bei jedem Betrachter einen Nachlade-Request). toOthers: der Speichernde
        // übernimmt die Antwort; ein eigenes Nachladen könnte inzwischen weiter Getipptes überschreiben.
        if ($value->wasRecentlyCreated || $value->wasChanged('data')) {
            // SafeBroadcast: ein WebSocket-Ausfall macht den bereits gespeicherten Wert nicht zur 500
            // (Checkbox/DropDown würden sonst zurückspringen und Nutzer:innen wiederholen)
            SafeBroadcast::send(new UpdateProjectComponentData($value, $project->id), toOthers: true);
        }

        return response()->json(['project_value' => $value]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectComponentValue $projectComponentValue): void
    {
        //
    }
}
