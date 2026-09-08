<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Speichern einer Regel (shift-rules.store/update): die Empfänger*innen „Benachrichtigen" werden auf
 * berechtigte Personen (ShiftRuleNotificationRecipientService) eingeschränkt — nicht berechtigte
 * Altzuordnungen überleben das Speichern nicht mehr.
 */
final class ShiftRuleNotificationRecipientSaveTest extends FeatureTestCase
{
    private function rulePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tagesmaximum',
            'description' => 'max. Stunden',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 9,
            'warning_color' => '#ff6b6b',
        ], $overrides);
    }

    #[Test]
    public function updating_a_rule_drops_ineligible_recipients(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $withoutRights = User::factory()->create();
        $admin = $this->actingAsAdmin();

        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8]);
        // Altzuordnung ohne Recht — der Dialog zeigt sie nicht, das Formular schickt sie aber ggf. mit
        $rule->usersToNotify()->attach($withoutRights->id);

        $this->put(route('shift-rules.update', $rule), $this->rulePayload([
            'user_ids' => [$viewer->id, $withoutRights->id, $admin->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $ids = $rule->fresh()->usersToNotify()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($viewer->id, $ids);
        $this->assertContains($admin->id, $ids);
        $this->assertNotContains($withoutRights->id, $ids);
    }

    #[Test]
    public function creating_a_rule_stores_only_eligible_recipients(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $withoutRights = User::factory()->create();
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), $this->rulePayload([
            'name' => 'Neu mit Empfängern',
            'user_ids' => [$planner->id, $withoutRights->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $rule = ShiftRule::query()->where('name', 'Neu mit Empfängern')->firstOrFail();
        $this->assertSame([$planner->id], $rule->usersToNotify()->pluck('users.id')->map(fn ($id) => (int) $id)->all());
    }

    #[Test]
    public function updating_without_recipients_clears_the_assignment(): void
    {
        $withoutRights = User::factory()->create();
        $this->actingAsAdmin();
        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8]);
        $rule->usersToNotify()->attach($withoutRights->id);

        $this->put(route('shift-rules.update', $rule), $this->rulePayload(['user_ids' => []]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, $rule->fresh()->usersToNotify()->count());
    }
}
