<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

final class ShiftRuleControllerTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    private function activeViolation(?User $user = null): ShiftRuleViolation
    {
        $user = $user ?? User::factory()->create();
        $rule = ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'default_compensation_deadline_days' => 30,
        ]);

        return ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'active',
            'is_manual' => false,
        ]);
    }

    private function processPayload(array $overrides = []): array
    {
        return array_merge([
            'compensation_days' => 1.0,
            'compensation_deadline' => Carbon::now()->addDays(20)->toDateString(),
            'compensation_reason' => 'Ausgleich',
            'for_holiday' => false,
        ], $overrides);
    }

    // --- processViolation -------------------------------------------------------------------

    #[Test]
    public function processing_an_active_violation_resolves_it_and_books_compensation_days(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();

        $response = $this->put(
            route('shift-rule-violations.process', ['violation' => $violation->id]),
            $this->processPayload(['compensation_days' => 1.5])
        );

        $response->assertRedirect();
        $violation->refresh();
        $this->assertSame('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertNotNull($violation->resolved_by);
        $this->assertEquals(1.5, (float) $violation->compensation_days);
        // 1,5 Tage = ein ganzer + ein halber Ersatzfreitag
        $this->assertSame(2, CompensationDayOff::where('violation_id', $violation->id)->count());
        $this->assertEquals(1.5, (float) CompensationDayOff::where('violation_id', $violation->id)->sum('value'));
    }

    #[Test]
    public function resolved_violation_without_granted_day_can_be_reprocessed(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();
        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), $this->processPayload());
        $this->assertSame(1, CompensationDayOff::where('violation_id', $violation->id)->count());

        $response = $this->put(
            route('shift-rule-violations.process', ['violation' => $violation->id]),
            $this->processPayload(['compensation_days' => 2.0, 'compensation_reason' => 'Korrigiert'])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $violation->refresh();
        $this->assertSame('resolved', $violation->status);
        $this->assertEquals(2.0, (float) $violation->compensation_days);
        $this->assertSame('Korrigiert', $violation->compensation_reason);
        // alte ungewährte Tage ersetzt: jetzt 2 ganze Tage
        $this->assertSame(2, CompensationDayOff::where('violation_id', $violation->id)->count());
        $this->assertEquals(2.0, (float) CompensationDayOff::where('violation_id', $violation->id)->sum('value'));
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ShiftRuleViolation::class,
            'subject_id' => $violation->id,
            'event' => 'reprocessed',
        ]);
    }

    #[Test]
    public function resolved_violation_with_granted_day_cannot_be_reprocessed(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();
        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), $this->processPayload());
        CompensationDayOff::where('violation_id', $violation->id)->update([
            'granted_at' => Carbon::now(),
            'granted_date' => Carbon::now()->addDays(5)->toDateString(),
        ]);

        $response = $this->put(
            route('shift-rule-violations.process', ['violation' => $violation->id]),
            $this->processPayload(['compensation_days' => 2.0])
        );

        $response->assertStatus(422);
        $this->assertEquals(1.0, (float) $violation->fresh()->compensation_days);
        $this->assertSame(1, CompensationDayOff::where('violation_id', $violation->id)->count());
    }

    #[Test]
    public function processing_requires_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $violation = $this->activeViolation();

        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), $this->processPayload())
            ->assertForbidden();
    }

    // --- ignoreViolation --------------------------------------------------------------------

    #[Test]
    public function ignoring_requires_a_reason(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();

        $this->post(route('shift-rule-violations.ignore', ['violation' => $violation->id]), [])
            ->assertSessionHasErrors('ignore_reason');

        $this->assertSame('active', $violation->fresh()->status);
    }

    #[Test]
    public function ignoring_with_reason_sets_status_and_note(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();

        $this->post(route('shift-rule-violations.ignore', ['violation' => $violation->id]), [
            'ignore_reason' => 'Einmalige Ausnahme abgesprochen',
        ])->assertRedirect();

        $violation->refresh();
        $this->assertSame('ignored', $violation->status);
        $this->assertSame('Einmalige Ausnahme abgesprochen', $violation->ignore_reason);
        $this->assertSame($planner->id, $violation->resolved_by);
        $this->assertNotNull($violation->resolved_at);
    }

    // --- storeManualViolation ---------------------------------------------------------------

    #[Test]
    public function manual_violation_is_created_with_creator(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $user = User::factory()->create();
        $rule = ShiftRule::factory()->create();

        $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => $user->id,
            'shift_rule_id' => $rule->id,
            'violation_date' => Carbon::now()->addDays(2)->toDateString(),
            'reason' => 'Manuell erfasst',
            'severity' => 'warning',
        ])->assertRedirect();

        $this->assertDatabaseHas('shift_rule_violations', [
            'user_id' => $user->id,
            'shift_rule_id' => $rule->id,
            'is_manual' => 1,
            'status' => 'active',
            'created_by_user_id' => $planner->id,
            'reason' => 'Manuell erfasst',
        ]);
    }

    #[Test]
    public function manual_violation_validates_required_fields(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->post(route('shift-rule-violations.manual.store'), [
            'reason' => 'ohne Person und Regel',
        ])->assertSessionHasErrors(['user_id', 'shift_rule_id', 'violation_date']);
    }

    #[Test]
    public function manual_violation_requires_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $rule = ShiftRule::factory()->create();

        $this->post(route('shift-rule-violations.manual.store'), [
            'user_id' => User::factory()->create()->id,
            'shift_rule_id' => $rule->id,
            'violation_date' => Carbon::now()->toDateString(),
        ])->assertForbidden();
    }

    // --- violationHistory -------------------------------------------------------------------

    #[Test]
    public function history_endpoint_lists_violation_and_compensation_day_entries(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $violation = $this->activeViolation();
        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), $this->processPayload());

        $response = $this->getJson(route('shift-rules.violations.history', ['violation' => $violation->id]));

        $response->assertOk();
        $response->assertJsonPath('violation.status', 'resolved');
        $response->assertJsonPath('violation.can_reprocess', true);
        $response->assertJsonPath('violation.has_granted_compensation', false);
        $response->assertJsonPath('violation.resolved_by_user.first_name', $planner->first_name);

        $entries = collect($response->json('entries'));
        $this->assertTrue($entries->contains(fn ($entry) => $entry['subject_type'] === 'violation'));
        $this->assertTrue($entries->contains(fn ($entry) => $entry['subject_type'] === 'compensation_day_off'));
        // Statuswechsel active -> resolved ist als Feldänderung enthalten
        $this->assertTrue($entries->contains(function ($entry) {
            return collect($entry['changes'] ?? [])->contains(
                fn ($change) => $change['field'] === 'status' && $change['new'] === 'resolved'
            );
        }));
    }

    #[Test]
    public function history_endpoint_requires_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $violation = $this->activeViolation();

        $this->getJson(route('shift-rules.violations.history', ['violation' => $violation->id]))
            ->assertForbidden();
    }

    // --- Vertragszuordnung ------------------------------------------------------------------

    #[Test]
    public function contract_assignments_page_delivers_contracts_with_shift_rules(): void
    {
        $this->actingAsAdmin();
        [, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);

        $response = $this->get(route('shift-rules.contracts.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ShiftWarnings/ContractAssignments')
            ->has('contracts')
            ->has('rules')
            ->where('contracts', fn ($contracts) => collect($contracts)->contains(
                fn ($item) => $item['id'] === $contract->id
                    && collect($item['shift_rules'] ?? [])->pluck('id')->contains($rule->id)
            ))
        );
    }

    #[Test]
    public function contract_assignments_page_is_forbidden_without_permission(): void
    {
        $this->actingAs(User::factory()->create());
        UserContract::factory()->create();

        $this->get(route('shift-rules.contracts.index'))->assertForbidden();
    }

    #[Test]
    public function rule_without_value_can_be_created_without_a_number(): void
    {
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), [
            'name' => 'Sonntagsarbeit',
            'description' => 'Verstoß je Sonntag mit Schicht',
            'trigger_type' => 'workOnSunday',
            'individual_number_value' => null,
            'warning_color' => '#ff6b6b',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shift_rules', ['name' => 'Sonntagsarbeit', 'trigger_type' => 'workOnSunday', 'individual_number_value' => 0]);
    }

    #[Test]
    public function rule_with_value_still_requires_the_number(): void
    {
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), [
            'name' => 'Tagesmaximum',
            'description' => 'max. Stunden',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => null,
            'warning_color' => '#ff6b6b',
        ])->assertSessionHasErrors('individual_number_value');
    }
}
