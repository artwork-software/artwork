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

    public function handle(): void
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
        Artisan::call('migrate --force');

        $this->line('Adding meili-indexes');
        foreach (
            [
                'departments' => Department::class,
                'moneysources' => MoneySource::class,
                'projects' => Project::class,
                'users' => User::class,
                'freelancers' => Freelancer::class,
                'serviceproviders' => ServiceProvider::class,
                'inventoryarticles' => InventoryArticle::class
            ] as $key => $model
        ) {
            Artisan::call(sprintf('scout:index %s', $key));
            Artisan::call(sprintf('scout:import %s', str_replace('\\', '\\\\', $model)));
        }
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
    }
}
