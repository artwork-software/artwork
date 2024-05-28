<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdateContainerCommand extends Command
{

    protected $signature = 'container-update';

    protected $description = 'Updates the container';

    public function handle(): void
    {
        DB::raw(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', env('DB_DATABASE')));
        Artisan::call('migrate --force');
        exec('npm run dev');
        exec('npm run prod');
        if (!Permission::first()) {
            Artisan::call('db:seed:production');
        }
    }
}
