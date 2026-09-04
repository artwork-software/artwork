<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 1a: Benachrichtigungen nach Festschreibung gehen nur noch an die
 * Schichtbesetzung und die Planer:innen des Gewerks (nicht an alle
 * Gewerksmitglieder); die Pausen-Warnung geht an Planer:innen statt Admins;
 * die Regelverstoß-Benachrichtigung verlinkt auf die existierende Schichtplan-Route.
 */
final class ShiftPostCommitNotificationRecipientsTest extends FeatureTestCase
{
    #[Test]
    public function editing_a_committed_shift_notifies_worker_and_craft_planner_only(): void
    {
        $this->actingAsAdmin();
        [$shift, $worker, $planner, $member] = $this->committedShiftWithCraftPeople();

        $this->patch(route('event.shift.update', $shift), [
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '12:00:00',
            'end' => '16:00:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
        ]);

        Notification::assertSentTo($worker, ShiftNotification::class);
        Notification::assertSentTo($planner, ShiftNotification::class);
        Notification::assertNotSentTo($member, ShiftNotification::class);
    }

    #[Test]
    public function deleting_a_committed_shift_notifies_worker_and_craft_planner_only(): void
    {
        $this->actingAsAdmin();
        [$shift, $worker, $planner, $member] = $this->committedShiftWithCraftPeople();

        $this->deleteJson(route('shifts.destroy', $shift));

        Notification::assertSentTo($worker, ShiftNotification::class);
        Notification::assertSentTo($planner, ShiftNotification::class);
        Notification::assertNotSentTo($member, ShiftNotification::class);
    }

    #[Test]
    public function craft_managers_are_the_fallback_when_no_planner_is_set(): void
    {
        $this->actingAsAdmin();
        [$shift, $worker, , $member] = $this->committedShiftWithCraftPeople(withPlanner: false);
        $manager = User::factory()->create();
        $shift->craft->managingUsers()->attach($manager->id);

        $this->deleteJson(route('shifts.destroy', $shift));

        Notification::assertSentTo($manager, ShiftNotification::class);
        Notification::assertNotSentTo($member, ShiftNotification::class);
    }

    #[Test]
    public function short_break_warning_goes_to_craft_planner_not_admin(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $planner = User::factory()->create();
        $craft->craftShiftPlaner()->attach($planner->id);
        $shift = Shift::factory()->create(['craft_id' => $craft->id]);

        app(ShiftService::class)->createInfringementNotification($shift);

        Notification::assertSentTo($planner, ShiftNotification::class);
        Notification::assertNotSentTo($admin, ShiftNotification::class);
    }

    #[Test]
    public function short_break_warning_falls_back_to_admins_without_craft_planner(): void
    {
        $admin = $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        // Auth-User wird vom NotificationService übersprungen — zweiter Admin als Empfänger
        $otherAdmin = $this->adminUser(User::factory()->create());

        app(ShiftService::class)->createInfringementNotification($shift);

        Notification::assertSentTo($otherAdmin, ShiftNotification::class);
        Notification::assertNotSentTo($admin, ShiftNotification::class);
    }

    #[Test]
    public function rule_violation_notification_links_to_existing_shift_plan_route(): void
    {
        $violation = ShiftRuleViolation::factory()->create(['violation_date' => '2026-06-08']);

        $notification = new ShiftRuleViolationNotification($violation, 'Testmeldung');

        $expected = route('shifts.plan', ['start_date' => '2026-06-08', 'end_date' => '2026-06-14']);
        $this->assertSame($expected, $notification->getShiftPlanUrl());
        $this->assertSame($expected, $notification->toArray()->shift_plan_url);
        $this->assertSame('Testmeldung', $notification->toArray()->message);
        $this->assertContains('database', $notification->via(User::factory()->create()));
    }

    /**
     * @return array{0: Shift, 1: User, 2: User, 3: User}
     */
    private function committedShiftWithCraftPeople(bool $withPlanner = true): array
    {
        $craft = Craft::factory()->create();
        $planner = User::factory()->create();
        if ($withPlanner) {
            $craft->craftShiftPlaner()->attach($planner->id);
        }

        // Gewerksmitglied ohne Planungsrolle — durfte bisher jede Änderung sehen
        $member = User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($member->id);

        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'is_committed' => true,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ]);

        $worker = User::factory()->create();
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
        ]);

        return [$shift, $worker, $planner, $member];
    }
}
