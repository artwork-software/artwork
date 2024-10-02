<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Models\Permission;
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
        $this->line('Creating db if not exists');
        config(['database.connections.mysql.database' => null]);
        DB::purge('mysql');
        /** @var Migrator $migrator */
        $migrator = app('migrator');
        $freshConnection = $migrator->resolveConnection('mysql');
        tap($freshConnection->unprepared(sprintf('CREATE DATABASE IF NOT EXISTS `%s` ', env('DB_DATABASE'))), function () {
            DB::purge('mysql');
        });
        config(['database.connections.mysql.database' => env('DB_DATABASE')]);

        $this->line('Migrating');
        Artisan::call('migrate --force');
        $this->line('Building frontend');
        exec('npm run build');
        if (!Permission::first()) {
            $this->line('Seeding initial data');
            Artisan::call('db:seed:production');
        }
    }
}
