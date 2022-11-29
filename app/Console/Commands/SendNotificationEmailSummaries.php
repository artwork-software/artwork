<?php

namespace App\Console\Commands;

use App\Enums\NotificationConstEnum;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationGroupEnum;
use App\Mail\NotificationSummary;
use App\Models\User;
use App\Notifications\ConflictNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailSummaries extends Command
{
    protected $signature = 'app:send-email-notifications {frequency=daily}';

    protected $description = 'Sends summaries of notifications to all users.';

    public function handle(): int
    {

        $frequency = $this->argument('frequency');

        if (is_null(NotificationFrequency::tryFrom($frequency))) {
            $this->error('Argument "frequency" must be type of ' . NotificationFrequency::class);

            return 1;
        }

        User::all()->each(fn (User $user) => $this->sendNotificationSummary($user));
        return 0;
    }

    protected function sendNotificationSummary(User $user): void
    {
        $typesOfUser = $user->notificationSettings()
            ->where('frequency', $this->argument('frequency'))
            ->where('enabled_email', true)
            ->pluck("type");

        $notificationClasses = $typesOfUser->map( function($type) {
            return $type->notificationClass();
        });

        $notifications = $user->notifications()
            ->whereNull('read_at')
            ->whereIn('type', $notificationClasses->unique())
            ->whereDate('created_at', '>=', now()->subWeeks(2))
            ->get()
            ->groupBy(function ($notification) {
               return $notification['data']['groupType'];
            });

        //Falls möglich hier noch events hier klein schreiben im Controller

        $this->info($notifications);

        //Name in der Mail bekommst du so
        $this->info(NotificationGroupEnum::from('events')->title());

        //Mail::to($user)->send(new NotificationSummary($notifications));
    }

}
