<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdateContainerCommand extends Command
{

    protected $signature = 'artwork:container-update';

    protected $description = 'Updates the container';

    /**
     * Modelle, deren Meilisearch-Index beim Container-Update angelegt und befüllt wird.
     *
     * @var array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public const SEARCHABLE_MODELS = [
        Department::class,
        MoneySource::class,
        Project::class,
        User::class,
        Freelancer::class,
        ServiceProvider::class,
        InventoryArticle::class,
    ];

    public function handle(): int
    {
        // Muss vor dem Nullen gelesen werden — danach liefert die Config null.
        $database = config('database.connections.mysql.database');

        $this->line('Creating db if not exists');
        config(['database.connections.mysql.database' => null]);
        DB::purge('mysql');
        /** @var Migrator $migrator */
        $migrator = app('migrator');
        $freshConnection = $migrator->resolveConnection('mysql');
        tap($freshConnection->unprepared(
            sprintf('CREATE DATABASE IF NOT EXISTS `%s` ', $database)
        ), function (): void {
            DB::purge('mysql');
        });
        config(['database.connections.mysql.database' => $database]);

        $this->line('Migrating');
        // Ausgabe und Ergebnis sichtbar machen: der Entrypoint ruft den Befehl mit `|| true` auf, ein
        // Migrationsfehler soll trotzdem eindeutig im Container-Log stehen und die Folgeschritte stoppen
        try {
            $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
            $this->output->write(Artisan::output());
        } catch (\Throwable $exception) {
            $this->output->write(Artisan::output());
            $this->error('MIGRATION FAILED – container update aborted: ' . $exception->getMessage());

            throw $exception;
        }
        if ($migrateExitCode !== self::SUCCESS) {
            $this->error('MIGRATION FAILED (exit code ' . $migrateExitCode . ') – container update aborted');

            return self::FAILURE;
        }

        $this->line('Adding meili-indexes');
        $this->syncMeilisearchIndexes();

        if (!Permission::first()) {
            $this->line('Seeding initial data');
            Artisan::call('db:seed:production');
        }

        $this->line('Updating artwork components');
        Artisan::call('artwork:update');

        // Signalisiert allen laufenden Workern, sich nach dem aktuellen Job zu beenden, damit sie mit dem
        // neuen Code neu starten. Das Signal liegt im Cache und erreicht auch den separaten Worker-Container,
        // weil dieser storage/ per Bind-Mount teilt.
        $this->line('Restarting queue workers');
        Artisan::call('queue:restart');

        $this->line('Container update finished');

        return self::SUCCESS;
    }

    /**
     * Legt die Meilisearch-Indizes an und importiert die Datensätze. scout:index bekommt die Modellklasse,
     * damit der Index genau so heißt, wie Scout ihn beim Suchen anspricht (searchableAs() inkl.
     * scout.prefix) und modellbezogene index-settings greifen.
     */
    private function syncMeilisearchIndexes(): void
    {
        foreach (self::SEARCHABLE_MODELS as $model) {
            Artisan::call('scout:index', ['name' => $model]);
            Artisan::call('scout:import', ['model' => $model]);
        }
    }
}
