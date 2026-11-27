<?php

namespace Artwork\Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Project\Exports\ProjectPlanWordWriter;
use Artwork\Modules\Project\Http\Requests\ProjectArtistPlanRequest;
use Artwork\Modules\Project\Http\Requests\ProjectEventPlanRequest;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\Plans\ProjectPlanLogoService;
use Artwork\Modules\Project\Services\Plans\ProjectPlanService;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Veranstaltungsplan und Künstlerplan eines Projekts als Word-Dokument (Export-Dialog im Projektkopf).
 * Zugriff wie das Projekt selbst (Middleware CanViewProject); Team und Künstleraufenthalte nur, wenn
 * die Person die jeweilige Komponente im Projekt sehen darf.
 */
class ProjectPlanExportController extends Controller
{
    private const WORD_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function __construct(
        private readonly ProjectPlanService $planService,
        private readonly ProjectPlanWordWriter $wordWriter,
        private readonly ProjectPlanLogoService $logoService,
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function options(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($this->planService->options($project, $request->user()));
    }

    /**
     * @throws AuthorizationException
     */
    public function eventPlan(ProjectEventPlanRequest $request, Project $project): BinaryFileResponse
    {
        $this->authorize('view', $project);

        $plan = $this->planService->eventPlan(
            $project,
            $request->user(),
            $this->venue($request->validated('venue_room_id')),
            $request->additionalRoomIds(),
            $request->withAdmission()
        );

        $path = $this->withLogo(
            $request,
            fn (?string $logoPath): string => $this->wordWriter->writeEventPlan($plan, $logoPath)
        );

        return $this->download($path, 'Veranstaltungsplan', $project);
    }

    /**
     * @throws AuthorizationException
     */
    public function artistPlan(ProjectArtistPlanRequest $request, Project $project): BinaryFileResponse
    {
        $this->authorize('view', $project);
        $user = $request->user();

        $residency = null;
        $residencyId = $request->validated('artist_residency_id');
        if ($residencyId !== null) {
            // Hotelangaben stammen aus den Künstleraufenthalten: nur mit Sicht auf diese Komponente
            abort_unless($this->planService->canSeeArtistResidencies($user), 403);
            $residency = ArtistResidency::query()->find($residencyId);
        }

        $this->planService->rememberArtistPlanEventTypes($user, $request->eventTypeIds());

        $plan = $this->planService->artistPlan(
            $project,
            $user,
            $this->venue($request->validated('venue_room_id')),
            $residency,
            $request->eventIds(),
            $request->additionalRoomIds(),
            $request->withAdmission()
        );

        $suffix = $plan['artist_name'] !== null ? $plan['artist_name'] . ' ' : '';

        $path = $this->withLogo(
            $request,
            fn (?string $logoPath): string => $this->wordWriter->writeArtistPlan($plan, $logoPath)
        );

        return $this->download($path, 'Künstlerplan ' . $suffix, $project);
    }

    /**
     * Schreibt das Dokument mit dem gewünschten Logo (eigene Datei, sonst Tool-Einstellungen) und
     * räumt eine dafür umgewandelte Bilddatei wieder ab.
     *
     * @param callable(string|null): string $write
     */
    private function withLogo(ProjectEventPlanRequest $request, callable $write): string
    {
        $logo = $request->withLogo() ? $this->logoService->resolve($request->file('logo')) : null;

        try {
            return $write($logo['path'] ?? null);
        } finally {
            $this->logoService->cleanUp($logo);
        }
    }

    private function venue(mixed $roomId): ?Room
    {
        return $roomId !== null ? Room::query()->without(['admins', 'creator'])->find((int) $roomId) : null;
    }

    private function download(string $path, string $prefix, Project $project): BinaryFileResponse
    {
        $name = trim(preg_replace('/\s+/u', ' ', $prefix . ' ' . $project->name) ?? '');
        // Zeichen, die in Dateinamen nicht erlaubt sind
        $name = trim(str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $name)) ?: 'Plan';

        return response()
            ->download($path, mb_substr($name, 0, 150) . '.docx', ['Content-Type' => self::WORD_MIME_TYPE])
            ->deleteFileAfterSend();
    }
}
