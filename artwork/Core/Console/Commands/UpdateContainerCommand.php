<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateContainerCommand extends Command
{

    protected $signature = 'artwork:container-update';

    protected $description = 'Updates the container';

    public function handle(): void
    {
        if(!env('APP_KEY')) {
            Artisan::call('key:generate --force');
        }

        Artisan::call('migrate --force');

        if(!Permission::first()) {
            Artisan::call('db:seed:production');
        }
    }
}
