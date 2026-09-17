<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Liefert die Daten der Spezialkomponente "Sage-Rechnungsübersicht":
 * alle Sage-Buchungen, die einer Zelle in der Budgettabelle des Projekts zugeordnet sind,
 * sortiert nach Kostenstelle, Sachkonto und Belegdatum.
 */
class ProjectTabSageInvoiceOverviewService
{
    public function __construct(
        private readonly SageApiSettingsService $sageApiSettingsService,
    ) {
    }

    /**
     * @return array{
     *     sage_enabled: bool,
     *     access: array{budget: bool, sage: bool},
     *     rows: array<int, array<string, mixed>>,
     *     total: float
     * }
     */
    public function buildPayload(Project $project, User $user): array
    {
        $sageEnabled = $this->sageApiSettingsService->isEnabled();
        $access = [
            'budget' => $this->hasBudgetAccess($project, $user),
            'sage' => $user->can(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value),
        ];

        if (!$sageEnabled || !$access['budget'] || !$access['sage']) {
            return [
                'sage_enabled' => $sageEnabled,
                'access' => $access,
                'rows' => [],
                'total' => 0.0,
            ];
        }

        $bookings = $this->loadBookings($project);
        $this->attachAccountTitles($bookings);

        return [
            'sage_enabled' => true,
            'access' => $access,
            'rows' => $bookings->values()->toArray(),
            'total' => round(
                $bookings->sum(static fn (SageAssignedData $booking): float => (float) $booking->buchungsbetrag),
                2
            ),
        ];
    }

    /**
     * Budgetzugriff spiegelt hasBudgetAccess() im Frontend: Admin/globale Budgetverwaltung
     * (via Gate::before bzw. Permission) oder Budgetrecht im Projektteam.
     */
    private function hasBudgetAccess(Project $project, User $user): bool
    {
        if ($user->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value)) {
            return true;
        }

        return $project->access_budget()->where('users.id', $user->id)->exists();
    }

    /**
     * @return Collection<int, SageAssignedData>
     */
    private function loadBookings(Project $project): Collection
    {
        return SageAssignedData::query()
            ->whereNull('parent_booking_id')
            ->whereHas(
                'columnCell.subPositionRow.subPosition.mainPosition.table',
                static function (Builder $query) use ($project): void {
                    $query->where('project_id', $project->id);
                }
            )
            ->with([
                'findChildren' => static function ($query): void {
                    $query->orderBy('belegdatum')->orderBy('id');
                },
                'comments' => static function ($query): void {
                    $query->orderBy('created_at', 'desc');
                },
                'comments.user',
            ])
            // Nummern liegen als Text vor: erst numerisch, dann textuell sortieren,
            // damit "4000" vor "12000" nicht lexikografisch verrutscht.
            ->orderByRaw('CAST(kst_stelle AS UNSIGNED), kst_stelle, CAST(sa_kto AS UNSIGNED), sa_kto')
            ->orderBy('belegdatum')
            ->orderBy('id')
            ->get();
    }

    /**
     * Namen zu Sachkonten aus den Budget-Stammdaten (Konten) nachschlagen.
     * Kein Treffer → sa_kto_title bleibt null, das Frontend zeigt nur die Nummer.
     *
     * @param Collection<int, SageAssignedData> $bookings
     */
    private function attachAccountTitles(Collection $bookings): void
    {
        $all = $bookings->flatMap(
            static fn (SageAssignedData $booking): array => [$booking, ...$booking->findChildren->all()]
        );

        $accountNumbers = $all
            ->map(static fn (SageAssignedData $booking): string => trim((string) $booking->sa_kto))
            ->filter()
            ->unique()
            ->values();

        $titles = $accountNumbers->isEmpty()
            ? collect()
            : BudgetManagementAccount::query()
                ->whereIn('account_number', $accountNumbers)
                ->pluck('title', 'account_number');

        $all->each(static function (SageAssignedData $booking) use ($titles): void {
            $booking->setAttribute('sa_kto_title', $titles->get(trim((string) $booking->sa_kto)));
        });
    }
}
