<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdateContainerCommand extends Command
{

    protected $signature = 'artwork:container-update';

    protected $description = 'Updates the container';

    public function handle(): void
    {
        echo 'Updating db';
        DB::raw(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', env('DB_DATABASE')));
        Artisan::call('migrate --force');
        echo 'Building npm';
        exec('npm run build');
        if (!Permission::first()) {
            Artisan::call('db:seed:production');
        }
    }
}
