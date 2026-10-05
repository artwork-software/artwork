<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetManagementAccountService;
use Artwork\Modules\Budget\Services\BudgetManagementCostUnitService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Leert den Papierkorb: Einträge, die länger als --days (Standard 30) im Papierkorb liegen, werden
 * endgültig gelöscht — über DENSELBEN Weg wie "Endgültig löschen" in der Oberfläche (Services/Jobs),
 * nicht per Modell-Pruning, das die Aufräumarbeiten (Schichten, Timelines, Budgetzellen,
 * Dateien …) überspringen würde. Ein fehlschlagender Eintrag wird gemeldet und übersprungen.
 *
 * Schutz lebender Daten: Einträge, deren endgültiges Löschen noch genutzte Daten verändern würde,
 * werden übersprungen und nur gemeldet — Räume mit lebenden Terminen/Schichten, Projekte mit lebenden
 * Terminen, Konten/Kostenstellen, deren Nummer wieder vergeben ist, Stammdaten, auf die Verträge zeigen.
 */
class PurgeTrashCommand extends Command
{
    protected $signature = 'artwork:purge-trash
        {--days=30 : Einträge, die mindestens so viele Tage im Papierkorb liegen}
        {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Endgültig löschen, was länger als 30 Tage im Papierkorb liegt';

    private int $failures = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $cutoff = now()->subDays(max(1, (int) $this->option('days')));
        $dryRun = (bool) $this->option('dry-run');

        // Reihenfolge wie in der Oberfläche sinnvoll: erst Inhalte (Schichten, Termine), dann Projekte,
        // Räume und Areale, zuletzt Stammdaten.
        foreach ($this->categories() as $label => $category) {
            [$query, $purge] = $category;
            $guard = $category[2] ?? null;

            $candidates = $query($cutoff);
            $eligible = $guard !== null ? $guard(clone $candidates) : $candidates;

            if ($guard !== null) {
                $skippedHere = (clone $candidates)->count() - (clone $eligible)->count();
                if ($skippedHere > 0) {
                    $this->skipped += $skippedHere;
                    $this->warn(sprintf('%s: %d übersprungen (noch in Verwendung)', $label, $skippedHere));
                }
            }

            $count = $this->purge($eligible, $purge, $dryRun);
            if ($count > 0) {
                $this->line(sprintf('%s: %d %s', $label, $count, $dryRun ? 'würden gelöscht' : 'gelöscht'));
            }
        }

        if ($this->failures > 0) {
            $this->warn($this->failures . ' Einträge konnten nicht gelöscht werden (siehe Log).');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * [Abfrage, Löschen, optional Schutzfilter (nur Einträge, deren Löschen keine lebenden Daten berührt)]
     *
     * @return array<string, array{
     *     0: callable(CarbonInterface): Builder,
     *     1: callable(Model): void,
     *     2?: callable(Builder): Builder
     * }>
     */
    private function categories(): array
    {
        $trashedBefore = static fn (string $modelClass): callable =>
            static fn (CarbonInterface $cutoff): Builder => $modelClass::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff);
        $forceDelete = static fn (Model $model): mixed => $model->forceDelete();
        $unreferencedBy = static fn (string $table, string $column, string $ownTable): callable =>
            static fn (Builder $query): Builder => $query->whereNotExists(static fn ($sub) => $sub
                ->from($table)
                ->whereColumn($table . '.' . $column, $ownTable . '.id'));

        return [
            'Schichten' => [
                static fn (CarbonInterface $cutoff): Builder => Shift::onlyTrashed()
                    ->whereNull('event_id')
                    ->where('deleted_at', '<=', $cutoff),
                static fn (Shift $shift): mixed => app(ShiftService::class)->forceDelete($shift),
            ],
            // Termine eines gelöschten Projekts gehen mit dem Projekt (ForceDeleteProjectJob)
            'Termine' => [
                static fn (CarbonInterface $cutoff): Builder => Event::onlyTrashed()
                    ->where('deleted_at', '<=', $cutoff)
                    ->where(static fn (Builder $query) => $query
                        ->whereNull('project_id')
                        ->orWhereHas('project')),
                static fn (Event $event): mixed => app()->call(
                    [app(EventService::class), 'forceDeleteAll'],
                    ['events' => [$event]]
                ),
            ],
            // Wie "Endgültig löschen": ein Job je Projekt (Kaskade kann sehr groß sein). Projekte mit noch
            // lebenden Terminen (z. B. fehlgeschlagener Lösch-Job) nicht anfassen.
            'Projekte' => [
                $trashedBefore(Project::class),
                static fn (Project $project): mixed => ForceDeleteProjectJob::dispatch($project->id),
                static fn (Builder $query): Builder => $query->whereDoesntHave('events'),
            ],
            // Räume mit lebenden Terminen/Schichten: FK „set null“ würde sie aus dem Raumkalender werfen
            'Räume' => [
                $trashedBefore(Room::class),
                $forceDelete,
                static fn (Builder $query): Builder => $query->whereDoesntHave('events')->whereDoesntHave('shifts'),
            ],
            // Area::forceDeleting löscht seine Räume hart — nicht, solange lebende Räume daran hängen
            'Areale' => [
                $trashedBefore(Area::class),
                $forceDelete,
                static fn (Builder $query): Builder => $query->whereDoesntHave('rooms'),
            ],
            'Artikel' => [
                $trashedBefore(InventoryArticle::class),
                static fn (InventoryArticle $article): mixed => app(InventoryArticleService::class)
                    ->forceDelete($article),
            ],
            'CRM-Kontakte' => [$trashedBefore(CrmContact::class), $forceDelete],
            'Sage-Datensätze' => [
                $trashedBefore(SageNotAssignedData::class),
                static fn (SageNotAssignedData $data): mixed => app(SageNotAssignedDataService::class)
                    ->forceDelete($data),
            ],
            'Konten' => [
                $trashedBefore(BudgetManagementAccount::class),
                static fn (BudgetManagementAccount $account): mixed => app()->call(
                    [app(BudgetManagementAccountService::class), 'forceDelete'],
                    ['budgetManagementAccount' => $account]
                ),
                // Endgültiges Löschen setzt alle Budgetzellen mit dieser Kontonummer zurück → nicht, wenn
                // die Nummer inzwischen wieder an ein lebendes Konto vergeben ist
                static fn (Builder $query): Builder => $query->whereNotExists(static fn ($sub) => $sub
                    ->from('budget_management_accounts as live')
                    ->whereColumn('live.account_number', 'budget_management_accounts.account_number')
                    ->whereNull('live.deleted_at')),
            ],
            'Kostenstellen' => [
                $trashedBefore(BudgetManagementCostUnit::class),
                static fn (BudgetManagementCostUnit $costUnit): mixed => app()->call(
                    [app(BudgetManagementCostUnitService::class), 'forceDelete'],
                    ['budgetManagementCostUnit' => $costUnit]
                ),
                static fn (Builder $query): Builder => $query->whereNotExists(static fn ($sub) => $sub
                    ->from('budget_management_cost_units as live')
                    ->whereColumn('live.cost_unit_number', 'budget_management_cost_units.cost_unit_number')
                    ->whereNull('live.deleted_at')),
            ],
            // Budget-Papierkorb: gelöschte Budget-Vorlagen (Projekt-Budgettabellen gehen mit dem Projekt)
            'Budget-Vorlagen' => [
                static fn (CarbonInterface $cutoff): Builder => Table::onlyTrashed()
                    ->where('is_template', true)
                    ->where('deleted_at', '<=', $cutoff),
                static fn (Table $table): mixed => app()->call(
                    [app(TableService::class), 'forceDelete'],
                    ['table' => $table]
                ),
            ],
            'Genres' => [$trashedBefore(Genre::class), $forceDelete],
            'Kategorien' => [$trashedBefore(Category::class), $forceDelete],
            'Bereiche' => [$trashedBefore(Sector::class), $forceDelete],
            'Projektstatus' => [$trashedBefore(ProjectState::class), $forceDelete],
            // Verträge (auch im Papierkorb) verweisen ohne Kaskade darauf → Löschen würde scheitern
            'Vertragsarten' => [
                $trashedBefore(ContractType::class),
                $forceDelete,
                $unreferencedBy('contracts', 'contract_type_id', 'contract_types'),
            ],
            'Unternehmensarten' => [
                $trashedBefore(CompanyType::class),
                $forceDelete,
                $unreferencedBy('contracts', 'company_type_id', 'company_types'),
            ],
            'Währungen' => [
                $trashedBefore(Currency::class),
                $forceDelete,
                $unreferencedBy('contracts', 'currency_id', 'currencies'),
            ],
            'Verwertungsgesellschaften' => [$trashedBefore(CollectingSociety::class), $forceDelete],
        ];
    }

    /**
     * @param callable(Model): void $purge
     */
    private function purge(Builder $query, callable $purge, bool $dryRun): int
    {
        if ($dryRun) {
            return $query->count();
        }

        $count = 0;
        // IDs vorab lesen: jedes Löschen verändert die Ergebnismenge (chunk() würde Einträge überspringen)
        foreach ($query->pluck($query->getModel()->getKeyName()) as $id) {
            $model = (clone $query)->find($id);
            if ($model === null) {
                continue;
            }

            try {
                // Je Eintrag eine Transaktion: bricht die Kaskade mittendrin ab, bleibt nichts halb gelöscht
                DB::transaction(static fn () => $purge($model));
                $count++;
            } catch (Throwable $exception) {
                $this->failures++;
                report($exception);
            }
        }

        return $count;
    }
}
