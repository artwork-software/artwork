<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Services\BudgetManagementAccountService;
use Artwork\Modules\Budget\Services\BudgetManagementCostUnitService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
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
use Throwable;

/**
 * Leert den Papierkorb: Einträge, die länger als --days (Standard 30) im Papierkorb liegen, werden
 * endgültig gelöscht — über DENSELBEN Weg wie "Endgültig löschen" in der Oberfläche (Services/Jobs),
 * nicht per Modell-Pruning, das die Aufräumarbeiten (Schichten, Timelines, Budgetzellen,
 * Dateien …) überspringen würde. Ein fehlschlagender Eintrag wird gemeldet und übersprungen.
 */
class PurgeTrashCommand extends Command
{
    protected $signature = 'artwork:purge-trash
        {--days=30 : Einträge, die mindestens so viele Tage im Papierkorb liegen}
        {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Endgültig löschen, was länger als 30 Tage im Papierkorb liegt';

    private int $failures = 0;

    public function handle(): int
    {
        $cutoff = now()->subDays(max(1, (int) $this->option('days')));
        $dryRun = (bool) $this->option('dry-run');

        // Reihenfolge wie in der Oberfläche sinnvoll: erst Inhalte (Schichten, Termine), dann Projekte,
        // Räume und Areale, zuletzt Stammdaten.
        foreach ($this->categories() as $label => [$query, $purge]) {
            $count = $this->purge($query($cutoff), $purge, $dryRun);
            if ($count > 0) {
                $this->line(sprintf('%s: %d %s', $label, $count, $dryRun ? 'würden gelöscht' : 'gelöscht'));
            }
        }

        if ($this->failures > 0) {
            $this->warn($this->failures . ' Einträge konnten nicht gelöscht werden (siehe Log).');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{0: callable(CarbonInterface): Builder, 1: callable(Model): void}>
     */
    private function categories(): array
    {
        $trashedBefore = static fn (string $modelClass): callable =>
            static fn (CarbonInterface $cutoff): Builder => $modelClass::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff);
        $forceDelete = static fn (Model $model): mixed => $model->forceDelete();

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
            // Wie "Endgültig löschen": ein Job je Projekt (Kaskade kann sehr groß sein)
            'Projekte' => [
                $trashedBefore(Project::class),
                static fn (Project $project): mixed => ForceDeleteProjectJob::dispatch($project->id),
            ],
            'Räume' => [$trashedBefore(Room::class), $forceDelete],
            'Areale' => [$trashedBefore(Area::class), $forceDelete],
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
            ],
            'Kostenstellen' => [
                $trashedBefore(BudgetManagementCostUnit::class),
                static fn (BudgetManagementCostUnit $costUnit): mixed => app()->call(
                    [app(BudgetManagementCostUnitService::class), 'forceDelete'],
                    ['budgetManagementCostUnit' => $costUnit]
                ),
            ],
            'Genres' => [$trashedBefore(Genre::class), $forceDelete],
            'Kategorien' => [$trashedBefore(Category::class), $forceDelete],
            'Bereiche' => [$trashedBefore(Sector::class), $forceDelete],
            'Projektstatus' => [$trashedBefore(ProjectState::class), $forceDelete],
            'Vertragsarten' => [$trashedBefore(ContractType::class), $forceDelete],
            'Unternehmensarten' => [$trashedBefore(CompanyType::class), $forceDelete],
            'Währungen' => [$trashedBefore(Currency::class), $forceDelete],
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
                $purge($model);
                $count++;
            } catch (Throwable $exception) {
                $this->failures++;
                report($exception);
            }
        }

        return $count;
    }
}
