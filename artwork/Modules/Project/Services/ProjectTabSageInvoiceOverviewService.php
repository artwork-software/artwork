<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Liefert die Daten der Spezialkomponente "Sage-Rechnungsübersicht":
 * alle Sage-Buchungen des Projekts – sowohl die einer Budgetzelle zugeordneten (sage_assigned_data)
 * als auch die projektbezogenen, noch nicht zugeordneten (sage_not_assigned_data, Block
 * „Projektbezogene Sage-Daten" im Budget-Tab) – sortiert nach Kostenstelle, KTO und Belegdatum.
 */
class ProjectTabSageInvoiceOverviewService
{
    public const SOURCE_ASSIGNED = 'assigned';
    public const SOURCE_UNASSIGNED = 'unassigned';

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

        $bookings = $this->loadAssignedBookings($project)
            ->each(fn (SageAssignedData $booking) => $this->markSource($booking, self::SOURCE_ASSIGNED))
            ->concat(
                $this->loadUnassignedBookings($project)
                    ->each(fn (SageNotAssignedData $booking) => $this->markSource($booking, self::SOURCE_UNASSIGNED))
            );

        $this->attachAccountNumbers($bookings);
        $this->attachAccountTitles($bookings);
        $bookings = $this->sortBookings($bookings);

        return [
            'sage_enabled' => true,
            'access' => $access,
            'rows' => $bookings->values()->toArray(),
            'total' => round(
                $bookings->sum(static fn (Model $booking): float => (float) $booking->buchungsbetrag),
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
     * Buchungen, die einer Zelle in der Budgettabelle des Projekts zugeordnet sind.
     *
     * @return Collection<int, SageAssignedData>
     */
    private function loadAssignedBookings(Project $project): Collection
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
            ->get();
    }

    /**
     * Projektbezogene Buchungen, die (noch) keiner Budgetzeile zugeordnet werden konnten –
     * dieselben Datensätze wie der Block „Projektbezogene Sage-Daten" im Budget-Tab.
     *
     * @return Collection<int, SageNotAssignedData>
     */
    private function loadUnassignedBookings(Project $project): Collection
    {
        return SageNotAssignedData::query()
            ->whereNull('parent_booking_id')
            ->where('project_id', $project->id)
            ->with([
                'findChildren' => static function ($query): void {
                    $query->orderBy('belegdatum')->orderBy('id');
                },
            ])
            ->get();
    }

    /**
     * Herkunft der Zeile plus ein tabellenübergreifend eindeutiger Schlüssel:
     * IDs aus sage_assigned_data und sage_not_assigned_data können kollidieren.
     */
    private function markSource(Model $booking, string $source): void
    {
        $booking->setAttribute('source', $source);
        $booking->setAttribute('row_key', $source . '-' . $booking->getKey());
    }

    /**
     * KTO wie überall in artwork: Sachkonto (sa_kto), sonst Soll-Konto (kto_soll).
     * Sage liefert das Sachkonto oft leer, das Konto steht dann im Soll-Konto.
     *
     * @param SupportCollection<int, Model> $bookings
     */
    private function attachAccountNumbers(SupportCollection $bookings): void
    {
        $this->withChildren($bookings)->each(static function (Model $booking): void {
            $saKto = trim((string) $booking->sa_kto);
            $booking->setAttribute('kto', $saKto !== '' ? $saKto : trim((string) $booking->kto_soll));
        });
    }

    /**
     * Namen zu Konten aus den Budget-Stammdaten (Konten) nachschlagen.
     * Kein Treffer → kto_title bleibt null, das Frontend zeigt nur die Nummer.
     *
     * @param SupportCollection<int, Model> $bookings
     */
    private function attachAccountTitles(SupportCollection $bookings): void
    {
        $all = $this->withChildren($bookings);

        $accountNumbers = $all
            ->map(static fn (Model $booking): string => (string) $booking->kto)
            ->filter()
            ->unique()
            ->values();

        $titles = $accountNumbers->isEmpty()
            ? collect()
            : BudgetManagementAccount::query()
                ->whereIn('account_number', $accountNumbers)
                ->pluck('title', 'account_number');

        $all->each(static function (Model $booking) use ($titles): void {
            $booking->setAttribute('kto_title', $titles->get((string) $booking->kto));
        });
    }

    /**
     * Kostenstelle → KTO → Belegdatum → ID. Nummern liegen als Text vor: erst numerisch,
     * dann textuell vergleichen, damit "4000" vor "12000" nicht lexikografisch verrutscht.
     *
     * @param SupportCollection<int, Model> $bookings
     * @return SupportCollection<int, Model>
     */
    private function sortBookings(SupportCollection $bookings): SupportCollection
    {
        return $bookings->sort(static function (Model $a, Model $b): int {
            return self::compareNumber((string) $a->kst_stelle, (string) $b->kst_stelle)
                ?: self::compareNumber((string) $a->kto, (string) $b->kto)
                ?: strcmp((string) $a->belegdatum, (string) $b->belegdatum)
                ?: strcmp((string) $a->source, (string) $b->source)
                ?: ($a->getKey() <=> $b->getKey());
        })->values();
    }

    private static function compareNumber(string $a, string $b): int
    {
        $a = trim($a);
        $b = trim($b);

        return ((int) $a <=> (int) $b) ?: strcmp($a, $b);
    }

    /**
     * @param SupportCollection<int, Model> $bookings
     * @return SupportCollection<int, Model>
     */
    private function withChildren(SupportCollection $bookings): SupportCollection
    {
        return $bookings->flatMap(
            static fn (Model $booking): array => [$booking, ...$booking->findChildren->all()]
        );
    }
}
