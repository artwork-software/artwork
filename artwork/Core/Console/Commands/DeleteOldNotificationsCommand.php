<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteOldNotificationsCommand extends Command
{
    protected $signature = 'artwork:delete-old-notifications';

    protected $description = 'Deletes archived notifications older than 30 days and unread notifications older than 1 year';

    public function handle(): int
    {
        $users = User::all();
        foreach ($users as $user) {
            foreach ($user->notifications as $notification) {
                if ($notification->read_at !== null) {
                    $archived = Carbon::parse($notification->read_at);
                    if ($archived->diffInDays(Carbon::now()) >= 30) {
                        $notification->delete();
                    }
                } else {
                    $created = Carbon::parse($notification->created_at);
                    if ($created->diffInDays(Carbon::now()) >= 365) {
                        $notification->delete();
                    }
                }
            }
        }

        return CommandAlias::SUCCESS;
    }
}
