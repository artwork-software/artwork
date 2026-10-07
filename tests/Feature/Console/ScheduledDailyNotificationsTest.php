<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tägliche Meldungs-Jobs laufen im Hintergrund: ein langer Lauf (viele Meldungen, langsames Reverb)
 * darf die übrigen Aufgaben desselben schedule:run-Ticks nicht blockieren.
 */
final class ScheduledDailyNotificationsTest extends TestCase
{
    #[Test]
    #[DataProvider('dailyNotificationCommands')]
    public function daily_notification_commands_run_in_background(string $signature): void
    {
        $this->app->make(Kernel::class)->bootstrap();

        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, $signature));

        $this->assertCount(1, $events);
        $this->assertTrue($events->sole()->runInBackground);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function dailyNotificationCommands(): iterable
    {
        yield 'Gewerke-Deadline' => ['artwork:notify-craft-if-shift-deadline-reached'];
        yield 'Dienstplan-Anfrage-Deadline' => ['artwork:notify-shift-plan-request-deadline-reached'];
    }
}
