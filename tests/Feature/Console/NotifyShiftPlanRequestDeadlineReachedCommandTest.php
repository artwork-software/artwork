<?php

namespace Tests\Feature\Console;

use Artwork\Core\Console\Commands\NotifyShiftPlanRequestDeadlineReached;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestDeadlineNotification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\FeatureTestCase;

final class NotifyShiftPlanRequestDeadlineReachedCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Freitag, 01.05.2026 (KW 18) — KW 19 (Mo 04.05.) und KW 20 (Mo 11.05.)
        // liegen innerhalb der 14-Tage-Frist.
        Carbon::setTestNow(Carbon::parse('2026-05-01 06:00:00'));

        $settings = app(GeneralSettings::class);
        $settings->shift_commit_workflow_enabled = true;
        $settings->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Run the command in isolation (without booting the full console kernel, which would
     * eagerly instantiate every command).
     */
    private function runCommand(): int
    {
        $command = $this->app->make(NotifyShiftPlanRequestDeadlineReached::class);
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester->getStatusCode();
    }

    private function craftWithManager(int $deadlineDays = 14): array
    {
        $craft = Craft::factory()->create(['commit_request_deadline_days' => $deadlineDays]);
        $manager = User::factory()->create();
        $craft->managingUsers()->attach($manager->id);

        return [$craft, $manager];
    }

    private function shiftInWeek19(Craft $craft): Shift
    {
        // 2026-05-06 liegt in ISO-KW 19
        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
        ]);
    }

    private function notificationCountFor(User $user): int
    {
        // FeatureTestCase ruft Notification::fake() auf — daher über den Fake zählen.
        return Notification::sent($user, ShiftNotification::class)->count();
    }

    #[Test]
    public function notifies_craft_management_once_when_deadline_reached_without_request(): void
    {
        [$craft, $manager] = $this->craftWithManager();
        $this->shiftInWeek19($craft);

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(1, $this->notificationCountFor($manager));
        $this->assertDatabaseHas('shift_plan_request_deadline_notifications', [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ]);

        // zweiter Lauf am selben/nächsten Tag erinnert nicht erneut
        $this->assertSame(0, $this->runCommand());
        $this->assertSame(1, $this->notificationCountFor($manager));
    }

    #[Test]
    public function does_not_notify_when_pending_request_exists_for_week(): void
    {
        [$craft, $manager] = $this->craftWithManager();
        $this->shiftInWeek19($craft);

        ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
        ]);

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(0, $this->notificationCountFor($manager));
        $this->assertSame(0, ShiftPlanRequestDeadlineNotification::query()->count());
    }

    #[Test]
    public function does_not_notify_when_week_has_no_shifts(): void
    {
        [, $manager] = $this->craftWithManager();

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(0, $this->notificationCountFor($manager));
    }

    #[Test]
    public function does_not_notify_when_deadline_disabled_for_craft(): void
    {
        $craft = Craft::factory()->create(['commit_request_deadline_days' => null]);
        $manager = User::factory()->create();
        $craft->managingUsers()->attach($manager->id);
        $this->shiftInWeek19($craft);

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(0, $this->notificationCountFor($manager));
    }

    #[Test]
    public function does_not_notify_when_workflow_is_disabled(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->shift_commit_workflow_enabled = false;
        $settings->save();

        [$craft, $manager] = $this->craftWithManager();
        $this->shiftInWeek19($craft);

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(0, $this->notificationCountFor($manager));
    }

    #[Test]
    public function falls_back_to_shift_planers_when_no_management_assigned(): void
    {
        $craft = Craft::factory()->create(['commit_request_deadline_days' => 14]);
        $planer = User::factory()->create();
        $craft->craftShiftPlaner()->attach($planer->id);
        $this->shiftInWeek19($craft);

        $this->assertSame(0, $this->runCommand());

        $this->assertSame(1, $this->notificationCountFor($planer));
    }
}
