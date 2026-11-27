<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetRankingData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectListData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectDetailData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiBudgetRankingRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiProjectsRequest;
use Artwork\Modules\AiAssistant\Services\AiBudgetRankingService;
use Artwork\Modules\AiAssistant\Services\AiProjectReadService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Projekte fuer artwork-ai (core-api-context-projects; Konzept B1 §3).
 *
 * Gelesen wird ausschliesslich ueber Project::visibleFor($user). Beim Einzelobjekt kommt die
 * bestehende Policy dazu — direkt aufgerufen, nicht ueber authorize(): Gate::before gibt
 * Artwork-Admins sonst jede Abfrage frei (HANDOVER_core §6). Ein unsichtbares Projekt ist ein
 * 404, nie ein 403 — die Existenz fremder Projekte ist selbst eine Information.
 */
class AiProjectApiController extends Controller
{
    public function __construct(
        private readonly AiProjectReadService $service,
        private readonly ProjectPolicy $policy,
        private readonly AiBudgetRankingService $ranking,
    ) {
    }

    public function index(IndexAiProjectsRequest $request): AiProjectListData
    {
        /** @var array{q?: string|null, state?: int|null, from?: string|null, to?: string|null, limit?: int|null} $filters */
        $filters = $request->validated();

        return $this->service->search($request->user(), $filters);
    }

    /**
     * Projekte nach Einnahmen, Kosten oder Ergebnis einer Budgetspalte (core-ai-aggregates). Projekte ohne
     * Budgetrecht erscheinen nur als Zaehler.
     */
    public function budgetRanking(IndexAiBudgetRankingRequest $request): AiBudgetRankingData
    {
        return $this->ranking->ranking($request->user(), [
            'period' => $request->period(),
            'sort' => $request->sort(),
            'direction' => $request->direction(),
            'limit' => $request->limit(),
            'state_id' => $request->stateId(),
            'include_groups' => $request->includeGroups(),
        ]);
    }

    public function show(Request $request, int $project): AiProjectDetailData
    {
        $model = Project::query()
            ->visibleFor($request->user())
            ->whereKey($project)
            ->first();

        abort_if($model === null, Response::HTTP_NOT_FOUND);
        abort_unless($this->policy->view($request->user(), $model), Response::HTTP_NOT_FOUND);

        return $this->service->detail($model, $request->user());
    }
}
