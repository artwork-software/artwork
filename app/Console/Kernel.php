<?php

namespace App\Console;

use Artwork\Core\Console\Commands\CreateMoneySourceExpirationReminderNotificationsCommand;
use Artwork\Core\Console\Commands\DeleteExpiredNotificationsForAllCommand;
use Artwork\Core\Console\Commands\DeleteOldNotificationsCommand;
use Artwork\Core\Console\Commands\ImportSage100ApiDataCommand;
use Artwork\Core\Console\Commands\RemoveExpiredInvitationsCommand;
use Artwork\Core\Console\Commands\RemoveTemporaryRoomsCommand;
use Artwork\Core\Console\Commands\SendDeadlineNotificationsCommand;
use Artwork\Core\Console\Commands\SendNotificationsEmailSummariesCommand;
use Artwork\Core\Console\Commands\SendScheduledNotificationsCommand;
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
