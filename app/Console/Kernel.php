<?php

namespace App\Console;

use Artwork\Core\Console\Commands\CreateMoneySourceExpirationReminderNotificationsCommand;
use Artwork\Core\Console\Commands\DeleteExpiredNotificationsForAllCommand;
use Artwork\Core\Console\Commands\DeleteOldNotificationsCommand;
use Artwork\Core\Console\Commands\ImportSage100ApiDataCommand;
use Artwork\Core\Console\Commands\NotifyCraftIfShiftDeadlineReached;
use Artwork\Core\Console\Commands\RemoveExpiredInvitationsCommand;
use Artwork\Core\Console\Commands\RemoveTemporaryRoomsCommand;
use Artwork\Core\Console\Commands\SendDeadlineNotificationsCommand;
use Artwork\Core\Console\Commands\SendNotificationsEmailSummariesCommand;
use Artwork\Core\Console\Commands\SendScheduledNotificationsCommand;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

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
        $schedule->command(NotifyCraftIfShiftDeadlineReached::class)->dailyAt('07:00');
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

    /**
     * @throws ReflectionException
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        $this->load(dirname(__DIR__, 2) . '/artwork/Core/Console/Commands', true);

        require base_path('routes/console.php');
    }

    /**
     * @throws ReflectionException
     */
    protected function load($paths, bool $fromArtworkCore = false): void
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        if (!$fromArtworkCore) {
            $namespace = $this->app->getNamespace();
        } else {
            $namespace = 'Artwork\\';
        }

        foreach (Finder::create()->in($paths)->files() as $file) {
            $command = $this->commandClassFromFile($file, $namespace, $fromArtworkCore);

            if (
                is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()
            ) {
                Artisan::starting(function ($artisan) use ($command): void {
                    $artisan->resolve($command);
                });
            }
        }
    }

    protected function commandClassFromFile(SplFileInfo $file, string $namespace, bool $fromArtworkCore = false): string
    {
        if (!$fromArtworkCore) {
            return $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
            );
        }

        return str_replace(
            'artwork\\',
            '',
            $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after(
                    $file->getRealPath(),
                    str_replace('app/', '', realpath(app_path()) . DIRECTORY_SEPARATOR)
                )
            )
        );
    }
}
