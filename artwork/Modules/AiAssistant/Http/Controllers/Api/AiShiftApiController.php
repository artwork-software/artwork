<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiEventShiftsData;
use Artwork\Modules\AiAssistant\DTOs\AiScheduleData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPlanData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiShiftPlanRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiScheduleRequest;
use Artwork\Modules\AiAssistant\Services\AiShiftReadService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eigener Einsatzplan und Dienstplan-Ausschnitt fuer artwork-ai
 * (core-api-schedule-shifts; Konzept B1 §3, §6).
 *
 * `me/schedule` kennt keinen Parameter fuer fremde Personen und liest nur die eigenen Daten des
 * Token-Users. `shift-plan` verlangt dasselbe Recht wie die Oberflaeche („Schichtplan einsehen",
 * `routes/web.php:1050-1081`) — geprueft mit dem Guard `web`, weil `can()` unter `auth:api` fuer
 * jedes Hausrecht still `false` liefert (HANDOVER_core §2).
 */
class AiShiftApiController extends Controller
{
    public function __construct(private readonly AiShiftReadService $service)
    {
    }

    public function schedule(ShowAiScheduleRequest $request): AiScheduleData
    {
        return $this->service->schedule(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->validated('limit'),
        );
    }

    public function shiftPlan(IndexAiShiftPlanRequest $request): AiShiftPlanData
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->mayViewShiftPlan($user), Response::HTTP_FORBIDDEN);

        return $this->service->shiftPlan(
            $user,
            $request->weekStart(),
            $request->weekEnd(),
            (string) $request->validated('week'),
            $request->craftId(),
            $request->validated('limit'),
        );
    }

    /**
     * Schichten eines Termins mit Besetzung (ai-shift-planning, Erweiterung 2). Rechte wie beim
     * Dienstplan-Ausschnitt; unbekannter Termin → 404.
     */
    public function eventShifts(Request $request, int $event): AiEventShiftsData
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->mayViewShiftPlan($user), Response::HTTP_FORBIDDEN);

        $model = Event::query()->visibleFor($user)->with(['room', 'project'])->find($event);
        abort_if($model === null, Response::HTTP_NOT_FOUND);

        return $this->service->eventShifts($user, $model);
    }

    /**
     * Wie `->can('can view shift plan')` an den Dienstplan-Routen; die Rollenabkuerzung
     * entspricht dem Admin-Durchgriff aus `Gate::before`.
     */
    private function mayViewShiftPlan(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->checkPermissionTo(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web');
    }
}
