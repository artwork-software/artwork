<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Regelverwaltung fest (shift-rules.active, .destroy, .validate,
 * .contracts.assign, .contracts.assignments.update, .users.assign) sowie shift-rule-violations.resolve.
 * Alle Routen verlangen "can plan shifts"; schreibende Regel-Routen zusätzlich den Einstellungsbereich
 * rules,edit — /active ist bewusst davon ausgenommen (Schichtplaner-Workflow).
 */
final class ShiftRuleManagementEndpointsTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    private function actingAsRuleEditor(): User
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = false;
        $settings->save();

        return $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
        ]);
    }

    private function actingAsPlannerWithoutSettingsAccess(): User
    {
        return $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
    }

    // --- shift-rules.active ------------------------------------------------------------------

    #[Test]
    public function active_rules_returns_only_active_rules_with_a_reduced_column_set_for_planners(): void
    {
        $this->actingAsPlannerWithoutSettingsAccess();
        $active = ShiftRule::factory()->create(['name' => 'Aktiv']);
        $inactive = ShiftRule::factory()->inactive()->create(['name' => 'Inaktiv']);

        $response = $this->getJson(route('shift-rules.active'))->assertOk();

        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
        $row = collect($response->json())->firstWhere('id', $active->id);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'description', 'warning_color', 'trigger_type'],
            array_keys($row)
        );
    }

    #[Test]
    public function active_rules_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('shift-rules.active'))->assertForbidden();
    }

    // --- shift-rules.destroy -----------------------------------------------------------------

    #[Test]
    public function destroy_soft_deletes_the_rule_and_drops_its_active_automatic_violations_only(): void
    {
        $this->actingAsRuleEditor();
        $rule = ShiftRule::factory()->create();
        $automatic = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'status' => 'active', 'is_manual' => false,
        ]);
        $manual = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'status' => 'active', 'is_manual' => true,
        ]);
        $resolved = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'status' => 'resolved', 'is_manual' => false,
        ]);

        $this->delete(route('shift-rules.destroy', $rule))->assertRedirect()->assertSessionHas('success');

        $this->assertSoftDeleted('shift_rules', ['id' => $rule->id]);
        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $automatic->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $manual->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $resolved->id]);
    }

    #[Test]
    public function destroy_is_forbidden_for_planners_without_rule_settings_access(): void
    {
        $this->actingAsPlannerWithoutSettingsAccess();
        $rule = ShiftRule::factory()->create();

        $this->delete(route('shift-rules.destroy', $rule))->assertForbidden();

        $this->assertNotSoftDeleted('shift_rules', ['id' => $rule->id]);
    }

    #[Test]
    public function granular_mode_requires_the_rules_edit_permission_for_destroy(): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = true;
        $settings->save();
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
            PermissionEnum::SHIFT_SETTINGS_RULES_VIEW->value,
        ]);
        $rule = ShiftRule::factory()->create();

        $this->delete(route('shift-rules.destroy', $rule))->assertForbidden();

        $this->assertNotSoftDeleted('shift_rules', ['id' => $rule->id]);
    }

    // --- shift-rules.contracts.assign / users.assign -----------------------------------------

    #[Test]
    public function assign_contracts_replaces_the_contract_set_of_a_rule(): void
    {
        $this->actingAsRuleEditor();
        $rule = ShiftRule::factory()->create();
        $oldContract = UserContract::factory()->create();
        $newContract = UserContract::factory()->create();
        $rule->contracts()->sync([$oldContract->id]);

        $this->post(route('shift-rules.contracts.assign', $rule), ['contract_ids' => [$newContract->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame([$newContract->id], $rule->contracts()->pluck('user_contracts.id')->all());
    }

    #[Test]
    public function assign_contracts_requires_the_contract_ids_array(): void
    {
        $this->actingAsRuleEditor();
        $rule = ShiftRule::factory()->create();
        $contract = UserContract::factory()->create();
        $rule->contracts()->sync([$contract->id]);

        $this->post(route('shift-rules.contracts.assign', $rule), [])->assertSessionHasErrors('contract_ids');

        $this->assertSame(1, $rule->contracts()->count());
    }

    #[Test]
    public function assign_contracts_is_forbidden_for_planners_without_rule_settings_access(): void
    {
        $this->actingAsPlannerWithoutSettingsAccess();
        $rule = ShiftRule::factory()->create();
        $contract = UserContract::factory()->create();

        $this->post(route('shift-rules.contracts.assign', $rule), ['contract_ids' => [$contract->id]])
            ->assertForbidden();

        $this->assertSame(0, $rule->contracts()->count());
    }

    #[Test]
    public function assign_users_replaces_the_notification_recipients_of_a_rule(): void
    {
        $this->actingAsRuleEditor();
        $rule = ShiftRule::factory()->create();
        $oldRecipient = User::factory()->create();
        $newRecipient = User::factory()->create();
        $rule->usersToNotify()->sync([$oldRecipient->id]);

        $this->post(route('shift-rules.users.assign', $rule), ['user_ids' => [$newRecipient->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame([$newRecipient->id], $rule->usersToNotify()->pluck('users.id')->all());
    }

    #[Test]
    public function assign_users_rejects_unknown_user_ids(): void
    {
        $this->actingAsRuleEditor();
        $rule = ShiftRule::factory()->create();

        $this->post(route('shift-rules.users.assign', $rule), ['user_ids' => [999999999]])
            ->assertSessionHasErrors('user_ids.0');

        $this->assertSame(0, $rule->usersToNotify()->count());
    }

    #[Test]
    public function assign_users_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value);
        $rule = ShiftRule::factory()->create();

        $this->post(route('shift-rules.users.assign', $rule), ['user_ids' => [User::factory()->create()->id]])
            ->assertForbidden();

        $this->assertSame(0, $rule->usersToNotify()->count());
    }

    // --- shift-rules.contracts.assignments.update --------------------------------------------

    #[Test]
    public function update_contract_assignments_replaces_the_rules_of_a_contract(): void
    {
        $this->actingAsRuleEditor();
        $contract = UserContract::factory()->create();
        $oldRule = ShiftRule::factory()->create();
        $newRule = ShiftRule::factory()->create();
        $contract->shiftRules()->sync([$oldRule->id]);

        $this->put(route('shift-rules.contracts.assignments.update', $contract), ['rule_ids' => [$newRule->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame([$newRule->id], $contract->shiftRules()->pluck('shift_rules.id')->all());
    }

    #[Test]
    public function update_contract_assignments_without_rule_ids_clears_all_rules(): void
    {
        $this->actingAsRuleEditor();
        $contract = UserContract::factory()->create();
        $contract->shiftRules()->sync([ShiftRule::factory()->create()->id]);

        $this->put(route('shift-rules.contracts.assignments.update', $contract), [])->assertRedirect();

        $this->assertSame(0, $contract->shiftRules()->count());
    }

    #[Test]
    public function update_contract_assignments_is_forbidden_for_planners_without_rule_settings_access(): void
    {
        $this->actingAsPlannerWithoutSettingsAccess();
        $contract = UserContract::factory()->create();
        $rule = ShiftRule::factory()->create();

        $this->put(route('shift-rules.contracts.assignments.update', $contract), ['rule_ids' => [$rule->id]])
            ->assertForbidden();

        $this->assertSame(0, $contract->shiftRules()->count());
    }

    // --- shift-rules.validate ----------------------------------------------------------------

    #[Test]
    public function validate_returns_json_with_violations_count_and_date_range_for_one_user(): void
    {
        $this->actingAsRuleEditor();
        [$user, $contract] = $this->userWithContract();
        $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $day = Carbon::now()->addDays(5)->startOfDay();
        $this->shiftFor($user, $day, '06:00:00', '18:00:00');

        $response = $this->postJson(route('shift-rules.validate'), [
            'start_date' => $day->toDateString(),
            'end_date' => $day->toDateString(),
            'user_id' => $user->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['violations', 'violationsCount', 'dateRange' => ['start', 'end']])
            ->assertJsonPath('dateRange.start', $day->toDateString())
            ->assertJsonPath('dateRange.end', $day->toDateString());
        $this->assertGreaterThanOrEqual(1, $response->json('violationsCount'));
        $this->assertCount($response->json('violationsCount'), $response->json('violations'));
    }

    #[Test]
    public function validate_rejects_an_end_date_before_the_start_date(): void
    {
        $this->actingAsRuleEditor();

        $this->postJson(route('shift-rules.validate'), [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    #[Test]
    public function validate_is_forbidden_for_planners_without_rule_settings_access(): void
    {
        $this->actingAsPlannerWithoutSettingsAccess();

        $this->postJson(route('shift-rules.validate'), [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ])->assertForbidden();
    }

    // --- shift-rule-violations.resolve -------------------------------------------------------

    #[Test]
    public function resolve_marks_the_violation_resolved_by_the_acting_planner(): void
    {
        $planner = $this->actingAsPlannerWithoutSettingsAccess();
        $violation = ShiftRuleViolation::factory()->create(['shift_id' => null, 'status' => 'active']);

        $this->post(route('shift-rule-violations.resolve', $violation))
            ->assertRedirect()
            ->assertSessionHas('success');

        $violation->refresh();
        $this->assertSame('resolved', $violation->status);
        $this->assertSame($planner->id, (int) $violation->resolved_by);
        $this->assertNotNull($violation->resolved_at);
    }

    #[Test]
    public function resolve_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $violation = ShiftRuleViolation::factory()->create(['shift_id' => null, 'status' => 'active']);

        $this->post(route('shift-rule-violations.resolve', $violation))->assertForbidden();

        $this->assertSame('active', $violation->refresh()->status);
    }
}
