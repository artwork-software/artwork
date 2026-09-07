<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Services\ShiftWeekStatusService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Seite „Wochenstatus": Gewerke × Kalenderwochen mit Freigabe-/Festschreibungsstatus,
 * offenen Änderungen, Verstößen, Besetzung und Anfragefrist.
 */
class ShiftWeekStatusController extends Controller
{
    public const DEFAULT_WEEKS = 8;

    public function __construct(
        private readonly ShiftWeekStatusService $shiftWeekStatusService,
        private readonly GeneralSettings $generalSettings,
    ) {
    }

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'weeks' => ['nullable', 'integer', 'min:1', 'max:' . ShiftWeekStatusService::MAX_WEEKS],
            'craft_ids' => ['nullable', 'array'],
            'craft_ids.*' => ['integer'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $from = isset($validated['from'])
            ? Carbon::createFromFormat('Y-m-d', $validated['from'])
            : Carbon::today();
        $from = $from->startOfWeek(CarbonInterface::MONDAY)->startOfDay();

        $weeks = (int) ($validated['weeks'] ?? self::DEFAULT_WEEKS);
        $weeks = max(1, min(ShiftWeekStatusService::MAX_WEEKS, $weeks));
        $to = $from->copy()->addWeeks($weeks)->subDay();

        $visibleCrafts = $this->visibleCrafts($user);
        $visibleCraftIds = $visibleCrafts->pluck('id')->map(fn ($id) => (int) $id)->all();

        $requestedCraftIds = array_map('intval', $validated['craft_ids'] ?? []);
        $selectedCraftIds = $requestedCraftIds === []
            ? $visibleCraftIds
            : array_values(array_intersect($requestedCraftIds, $visibleCraftIds));

        $status = $this->shiftWeekStatusService->compute($from, $to, $selectedCraftIds);

        $isAdmin = $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
        $canCommit = $isAdmin || $user->can(PermissionEnum::CAN_COMMIT_SHIFTS->value);
        $canApproveRequests = $isAdmin || $user->can('approve-shift-plan-requests');
        $canPlan = $isAdmin || $user->can(PermissionEnum::SHIFT_PLANNER->value);
        // Link „Offene Verstöße" (shift-rules.pending): can plan shifts + shift-settings-area:rules,view
        $canSeeViolations = $isAdmin || (
            $canPlan
            && $user->can(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value)
            && (
                $user->can(PermissionEnum::SHIFT_SETTINGS_RULES_VIEW->value)
                || $user->can(PermissionEnum::SHIFT_SETTINGS_RULES_EDIT->value)
            )
        );

        return Inertia::render('Shifts/WeekStatus', [
            'weeks' => $status['weeks'],
            'rows' => (object) $status['rows'],
            'summary' => (object) $status['summary'],
            'crafts' => $visibleCrafts
                ->map(fn (Craft $craft) => [
                    'id' => (int) $craft->id,
                    'name' => $craft->name,
                    'abbreviation' => $craft->abbreviation,
                    'color' => $craft->color,
                    'commit_request_deadline_days' => $craft->commit_request_deadline_days,
                ])
                ->values(),
            'filters' => [
                'from' => $status['from'],
                'to' => $status['to'],
                'weeks' => $weeks,
                'craft_ids' => $selectedCraftIds,
            ],
            'maxWeeks' => ShiftWeekStatusService::MAX_WEEKS,
            'workflowEnabled' => (bool) $this->generalSettings->shift_commit_workflow_enabled,
            'canCommit' => $canCommit,
            'canPlan' => $canPlan,
            'canApproveRequests' => $canApproveRequests,
            'canSeeViolations' => $canSeeViolations,
        ]);
    }

    /**
     * Sichtbare Gewerke wie in ShiftPlanRequestController::requests(): Admins alle,
     * sonst Gewerke mit assignable_by_all oder eigener Planungszuständigkeit (craft_users).
     *
     * @return Collection<int, Craft>
     */
    private function visibleCrafts(User $user): Collection
    {
        $query = Craft::query()
            ->select(['id', 'name', 'abbreviation', 'color', 'position', 'assignable_by_all', 'commit_request_deadline_days'])
            ->without(['craftShiftPlaner'])
            ->orderBy('position')
            ->orderBy('name');

        if (! $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            $query->where(function ($sub) use ($user): void {
                $sub->where('assignable_by_all', true)
                    ->orWhereHas('craftShiftPlaner', fn ($planers) => $planers->where('user_id', $user->id));
            });
        }

        return $query->get();
    }
}
