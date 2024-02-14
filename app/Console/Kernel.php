<?php

namespace App\Console;

use App\Console\Commands\CreateMoneySourceExpirationReminderNotificationsCommand;
use App\Console\Commands\DailyDeleteCalendarExportPDFs;
use App\Console\Commands\DeadLine;
use App\Console\Commands\DeleteExpiredNotificationForAll;
use App\Console\Commands\DeleteNotifications;
use App\Console\Commands\NotificationScheduling;
use App\Console\Commands\RemoveExpiredInvitations;
use App\Console\Commands\RemoveTempRooms;
use App\Console\Commands\SendNotificationEmailSummaries;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('model:prune')->daily();
        $schedule->command(NotificationScheduling::class)->everyTenMinutes();
        $schedule->command(DeadLine::class)->dailyAt('09:00');
        $schedule->command(RemoveTempRooms::class)->dailyAt('08:00')->runInBackground();
        $schedule->command(DeleteNotifications::class)->dailyAt('07:00');
        $schedule->command(DeleteExpiredNotificationForAll::class)->everyFiveMinutes()->runInBackground();
        $schedule->command(DailyDeleteCalendarExportPDFs::class)->dailyAt('01:00')->runInBackground();
        $schedule->command(SendNotificationEmailSummaries::class, ['daily'])
            ->dailyAt('9:00');
        $schedule->command(SendNotificationEmailSummaries::class, ['weekly_once'])
            ->weekly()
            ->mondays()
            ->at('9:00');
        $schedule->command(SendNotificationEmailSummaries::class, ['weekly_twice'])
            ->days([Schedule::MONDAY, Schedule::THURSDAY])
            ->at('9:00');
        $schedule->command(CreateMoneySourceExpirationReminderNotificationsCommand::class)
            ->dailyAt('01:00')
            ->runInBackground();
        $schedule->command(RemoveExpiredInvitations::class)->dailyAt('01:00')->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
