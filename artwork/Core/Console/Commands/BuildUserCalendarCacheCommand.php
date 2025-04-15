<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Event\Cache\Jobs\BuildUserCalendarCache;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;

class BuildUserCalendarCacheCommand extends Command
{
    protected $signature = 'calendar:cache:build {--from=} {--to=} {--user=}';
    protected $description = 'Initialisiert Kalender-Cache für Benutzer';

    public function handle(): void
    {
        $users = $this->option('user') ? [User::find($this->option('user'))] : User::all();
        $from = $this->option('from');
        $to = $this->option('to');

        foreach ($users as $user) {
            BuildUserCalendarCache::dispatch($user, $from, $to);
        }
    }
}
