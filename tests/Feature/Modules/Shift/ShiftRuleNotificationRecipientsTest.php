<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleNotificationRecipientService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Actions\ShiftRuleNotificationAction;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Härtung: Regelverstoß-Benachrichtigungen gehen nur an Personen mit Dienstplan-Sicht-/Planungsrecht
 * (can view shift plan / can plan shifts) oder Admins — beim Versand (ShiftRuleNotificationAction)
 * und in der Auswahlliste „Benachrichtigen" des Regel-Dialogs (shift-rules.index).
 */
final class ShiftRuleNotificationRecipientsTest extends FeatureTestCase
{
    private function instanceFor(ShiftRuleViolation $violation): WorkflowInstance
    {
        $instance = new WorkflowInstance();
        $instance->setRelation('subject', $violation);

        return $instance;
    }

    #[Test]
    public function action_notifies_only_users_with_shift_plan_rights(): void
    {
        $admin = $this->actingAsAdmin();
        $viewer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $withoutRights = User::factory()->create();
        $extraWithoutRights = User::factory()->create();

        $rule = ShiftRule::factory()->create(['is_active' => true]);
        $rule->usersToNotify()->attach([$admin->id, $viewer->id, $planner->id, $withoutRights->id]);
        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'status' => 'active',
        ]);

        Notification::fake();
        (new ShiftRuleNotificationAction())->execute(
            $this->instanceFor($violation),
            ['user_ids' => [$extraWithoutRights->id]]
        );

        Notification::assertSentTo($admin, ShiftRuleViolationNotification::class);
        Notification::assertSentTo($viewer, ShiftRuleViolationNotification::class);
        Notification::assertSentTo($planner, ShiftRuleViolationNotification::class);
        Notification::assertNotSentTo($withoutRights, ShiftRuleViolationNotification::class);
        Notification::assertNotSentTo($extraWithoutRights, ShiftRuleViolationNotification::class);
    }

    #[Test]
    public function existing_rule_assignments_stay_untouched(): void
    {
        $withoutRights = User::factory()->create();
        $rule = ShiftRule::factory()->create(['is_active' => true]);
        $rule->usersToNotify()->attach($withoutRights->id);
        $violation = ShiftRuleViolation::factory()->create(['shift_rule_id' => $rule->id]);

        Notification::fake();
        (new ShiftRuleNotificationAction())->execute($this->instanceFor($violation));

        Notification::assertNothingSent();
        // Zuordnung bleibt bestehen — greift, sobald das Recht vergeben wird
        $this->assertTrue($rule->fresh()->usersToNotify->contains($withoutRights));
    }

    #[Test]
    public function eligible_users_query_matches_direct_permission_role_permission_and_admin(): void
    {
        $admin = $this->actingAsAdmin();
        $viewer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $withoutRights = User::factory()->create();

        $ids = app(ShiftRuleNotificationRecipientService::class)->eligibleUsersQuery()->pluck('id')->all();

        $this->assertContains($admin->id, $ids);
        $this->assertContains($viewer->id, $ids);
        $this->assertNotContains($withoutRights->id, $ids);
    }

    #[Test]
    public function rule_dialog_user_list_contains_only_eligible_users(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $withoutRights = User::factory()->create(['last_name' => 'Ohne Recht']);
        $admin = $this->actingAsAdmin();

        $response = $this->get(route('shift-rules.index'))->assertOk();

        $ids = collect($response->inertiaProps('users'))->pluck('id')->all();
        $this->assertContains($admin->id, $ids);
        $this->assertContains($viewer->id, $ids);
        $this->assertNotContains($withoutRights->id, $ids);
    }
}
