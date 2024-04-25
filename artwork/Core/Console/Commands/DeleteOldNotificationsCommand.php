<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteOldNotificationsCommand extends Command
{
    protected $signature = 'artwork:delete-old-notifications';

    protected $description = 'Deletes notifications that were archived 7 or more days ago';

    public function handle(): int
    {
        $users = User::all();
        foreach ($users as $user) {
            foreach ($user->notifications as $notification) {
                $archived = Carbon::parse($notification->read_at);
                if ($archived->diffInDays(Carbon::now()) >= 7) {
                    $notification->delete();
                }
            }
        }

        return CommandAlias::SUCCESS;
    }
}
