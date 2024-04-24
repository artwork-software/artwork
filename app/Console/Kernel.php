<?php

namespace App\Console;

use App\Console\Commands\CreateMoneySourceExpirationReminderNotificationsCommand;
use App\Console\Commands\SendDeadlineNotificationsCommand;
use App\Console\Commands\DeleteExpiredNotificationsForAllCommand;
use App\Console\Commands\DeleteOldNotificationsCommand;
use App\Console\Commands\ImportSage100ApiDataCommand;
use App\Console\Commands\SendScheduledNotificationsCommand;
use App\Console\Commands\RemoveExpiredInvitationsCommand;
use App\Console\Commands\RemoveTemporaryRoomsCommand;
use App\Console\Commands\SendNotificationsEmailSummariesCommand;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    public function __construct(
        private readonly SageApiSettingsService $sageApiSettingsService,
        Application $app,
        Dispatcher $events
    ) {
        parent::__construct($app, $events);
    }


    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('model:prune')->daily();
        $schedule->command(SendScheduledNotificationsCommand::class)->everyTenMinutes();
        $schedule->command(SendDeadlineNotificationsCommand::class)->dailyAt('09:00');
        $schedule->command(RemoveTemporaryRoomsCommand::class)->dailyAt('08:00')->runInBackground();
        $schedule->command(DeleteOldNotificationsCommand::class)->dailyAt('07:00');
        $schedule->command(DeleteExpiredNotificationsForAllCommand::class)->everyFiveMinutes()->runInBackground();
        $schedule->command(SendNotificationsEmailSummariesCommand::class, ['daily'])
            ->dailyAt('9:00');
        $schedule->command(SendNotificationsEmailSummariesCommand::class, ['weekly_once'])
            ->weekly()
            ->mondays()
            ->at('9:00');
        $schedule->command(SendNotificationsEmailSummariesCommand::class, ['weekly_twice'])
            ->days([Schedule::MONDAY, Schedule::THURSDAY])
            ->at('9:00');
        $schedule->command(CreateMoneySourceExpirationReminderNotificationsCommand::class)
            ->dailyAt('01:00')
            ->runInBackground();
        $schedule->command(RemoveExpiredInvitationsCommand::class)->dailyAt('01:00')->runInBackground();

        $sageApiSettings = $this->sageApiSettingsService->getFirst();
        if (!is_null($sageApiSettings) && $sageApiSettings->enabled) {
            $schedule->command(ImportSage100ApiDataCommand::class)
                ->dailyAt($sageApiSettings->fetchTime ?? '08:00')
                ->runInBackground();
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
