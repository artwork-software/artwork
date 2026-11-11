<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiPersonListData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonWorkloadData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiPeopleRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiPersonWorkloadRequest;
use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Personen und Auslastung fuer die Schichtplanung (ai-shift-planning, Core 1 und 2).
 *
 * Beides nur mit „Schichten planen" (Gate `plan-shifts`, hier mit Guard `web` geprueft).
 */
class AiPeopleApiController extends Controller
{
    public function __construct(private readonly AiPeopleService $service)
    {
    }

    public function index(IndexAiPeopleRequest $request): AiPersonListData
    {
        $this->authorizePlanning($request->user());

        return $this->service->search($request->search(), $request->limit());
    }

    public function workload(ShowAiPersonWorkloadRequest $request, string $type, int $id): AiPersonWorkloadData
    {
        $this->authorizePlanning($request->user());

        return $this->service->workload($this->service->find($type, $id), $request->from(), $request->to());
    }

    private function authorizePlanning(?User $user): void
    {
        abort_unless($user !== null && AiShiftPlanningAccess::mayPlanShifts($user), Response::HTTP_FORBIDDEN);
    }
}
