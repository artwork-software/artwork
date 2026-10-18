<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Services\ProjectComponentValueService;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectComponentValueController extends Controller
{
    public function __construct(
        private readonly ProjectComponentValueService $componentValueService,
    ) {
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
     * Gibt den gespeicherten Wert im Format von project_value zurück: der Speichernde übernimmt ihn
     * lokal (der Broadcast geht toOthers und trägt nur Kennungen).
     */
    public function update(
        Request $request,
        Project $project,
        Component $component,
        ProjectComponentVisibilityService $visibilityService,
    ): JsonResponse {
        /** @var \Artwork\Modules\User\Models\User $user */
        $user = $request->user();

        // Schreibrecht im Projekt + Komponenten-Einstellung (Spiegel von canEditComponent() im Frontend).
        abort_unless($user->can('writeComponent', [$project, $component]), 403);
        // Gleiche Sichtregel wie value(): Komponenten in für die Person unsichtbaren Tabs sind nicht
        // beschreibbar, auch wenn Projekt-Schreibrecht besteht.
        abort_unless($visibilityService->canSeeInProject($user, $component), 403);

        $request->validate(['data' => ['present', 'nullable', 'array']]);

        // toOthers: ein eigenes Nachladen könnte inzwischen weiter Getipptes überschreiben.
        $value = $this->componentValueService->updateValue(
            $project,
            $component,
            $request->input('data'),
            toOthers: true,
        );

        return response()->json(['project_value' => $value]);
    }
}
