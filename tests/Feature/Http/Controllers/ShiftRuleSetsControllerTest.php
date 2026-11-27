<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Jobs\RevalidateShiftRulesJob;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\NvBuehneStageShiftRules;
use Artwork\Modules\Shift\Support\ShiftRuleSets;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

/**
 * Regelsätze (ShiftRuleSets) und Zusatzparameter (shift_rules.config): Regelsatz "NV Bühne
 * (Bühnentechnik)" anlegen, Idempotenz, Rechte, Speichern und Ändern einer Regel mit config.
 */
final class ShiftRuleSetsControllerTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    /**
     * Bestand der Test-DB darf nicht mit den Werten des Regelsatzes kollidieren (Idempotenz würde greifen).
     */
    private function clearRulesOfNvSet(): void
    {
        ShiftRule::query()
            ->whereIn('trigger_type', collect(NvBuehneStageShiftRules::all())->pluck('trigger_type'))
            ->update(['deleted_at' => now()]);
    }

    #[Test]
    public function index_delivers_all_rule_sets_and_the_shift_groups(): void
    {
        $this->actingAsAdmin();
        ShiftGroup::create(['name' => 'Endprobe']);

        $this->get(route('shift-rules.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftWarnings/Index')
                ->has('ruleSets', 2)
                ->where('ruleSets.0.key', ShiftRuleSets::LEGAL)
                ->where('ruleSets.1.key', ShiftRuleSets::NV_BUEHNE_STAGE)
                ->has('ruleSets.1.rules', count(NvBuehneStageShiftRules::all()))
                ->where('ruleSets.1.rules.0.trigger_type', 'restTimeBeforeWorkday')
                ->where('ruleSets.1.rules.0.config.skip_same_day', true)
                ->has('shiftGroups')
                ->where('availableRuleTypes', fn ($types) => collect($types)->contains('weeklyPlanCommitDeadline')
                    && collect($types)->contains('restTimeBetweenShiftsOnDay')));
    }

    #[Test]
    public function nv_buehne_rule_set_is_created_with_its_settings_and_assigned_to_the_contract(): void
    {
        $this->actingAsAdmin();
        [, $contract] = $this->userWithContract();
        $this->clearRulesOfNvSet();
        $before = ShiftRule::query()->count();
        $keys = ShiftRuleSets::ruleKeys(ShiftRuleSets::NV_BUEHNE_STAGE);
        // Die Vertragszuweisung des Fixtures dispatcht selbst einen Revalidate-Job: Zähler zurücksetzen
        Bus::fake();

        $this->post(route('shift-rules.defaults.store'), [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => $keys,
            'contract_ids' => [$contract->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame($before + count($keys), ShiftRule::query()->count());

        $weeklyPlan = ShiftRule::query()->where('trigger_type', 'weeklyPlanCommitDeadline')->sole();
        $this->assertSame(['weekday' => 4, 'time' => '14:00', 'weeks_ahead' => 1], $weeklyPlan->config);
        $this->assertTrue($weeklyPlan->is_active);

        $freeWorkday = ShiftRule::query()->where('trigger_type', 'minFreeDaysPerWeek')->sole();
        $this->assertEquals(1.0, $freeWorkday->individual_number_value);
        $this->assertSame(['workdays_only' => true, 'excluded_dates' => ['05-01', '12-24']], $freeWorkday->config);

        $nightRest = ShiftRule::query()->where('trigger_type', 'restTimeBeforeWorkday')->sole();
        $this->assertSame(['skip_same_day' => true], $nightRest->config);
        $this->assertEquals(0.5, $nightRest->default_compensation_days);
        $this->assertSame(14, $nightRest->default_compensation_deadline_days);

        $maxGap = ShiftRule::query()->where('trigger_type', 'maxConsecWorkingDays')->sole();
        $this->assertEquals(12.0, $maxGap->individual_number_value);
        $this->assertSame(42, $maxGap->default_compensation_deadline_days);
        // Regeltyp ohne Zusatzparameter: config bleibt leer
        $this->assertNull($maxGap->config);

        $names = collect(NvBuehneStageShiftRules::all())->pluck('name');
        ShiftRule::query()->whereIn('name', $names)->each(function (ShiftRule $rule) use ($contract): void {
            $this->assertSame(
                [$contract->id],
                $rule->contracts()->pluck('user_contracts.id')->map(fn ($id) => (int) $id)->all(),
                "Regel {$rule->name} ist der Vorlage nicht zugeordnet"
            );
        });

        Bus::assertDispatchedTimes(RevalidateShiftRulesJob::class, 1);
    }

    #[Test]
    public function creating_the_rule_set_twice_does_not_duplicate_rules(): void
    {
        $this->actingAsAdmin();
        $contract = UserContract::factory()->create();
        $this->clearRulesOfNvSet();
        $payload = [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => ShiftRuleSets::ruleKeys(ShiftRuleSets::NV_BUEHNE_STAGE),
            'contract_ids' => [$contract->id],
        ];

        $this->post(route('shift-rules.defaults.store'), $payload)->assertSessionHasNoErrors();
        $afterFirst = ShiftRule::query()->count();

        $otherContract = UserContract::factory()->create();
        $payload['contract_ids'] = [$contract->id, $otherContract->id];
        $this->post(route('shift-rules.defaults.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame($afterFirst, ShiftRule::query()->count());
        $weeklyPlan = ShiftRule::query()->where('trigger_type', 'weeklyPlanCommitDeadline')->sole();
        $this->assertEqualsCanonicalizing(
            [$contract->id, $otherContract->id],
            $weeklyPlan->contracts()->pluck('user_contracts.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    #[Test]
    public function a_rule_with_other_settings_does_not_count_as_the_rule_of_the_set(): void
    {
        $this->actingAsAdmin();
        $this->clearRulesOfNvSet();
        // Gesetzliche Ruhezeit: gleicher Typ und Wert, aber ohne skip_same_day
        $legal = ShiftRule::factory()->create([
            'trigger_type' => 'restTimeBeforeWorkday',
            'individual_number_value' => 11.0,
            'config' => null,
            'is_active' => true,
        ]);

        $this->post(route('shift-rules.defaults.store'), [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => ['nightRestWorkday'],
            'contract_ids' => [],
        ])->assertSessionHasNoErrors();

        $rules = ShiftRule::query()->where('trigger_type', 'restTimeBeforeWorkday')->get();
        $this->assertCount(2, $rules);
        $this->assertNull($legal->fresh()->config);

        // Umgekehrt legt der gesetzliche Satz seine Ruhezeit nicht noch einmal an
        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['restTime'],
            'contract_ids' => [],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, ShiftRule::query()->where('trigger_type', 'restTimeBeforeWorkday')->count());
    }

    #[Test]
    public function rule_keys_must_belong_to_the_selected_set_and_the_set_must_exist(): void
    {
        $this->actingAsAdmin();
        $before = ShiftRule::query()->count();

        // Schlüssel aus dem gesetzlichen Satz im NV-Bühne-Satz
        $this->post(route('shift-rules.defaults.store'), [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => ['dailyMax'],
        ])->assertSessionHasErrors('rules.0');

        // Schlüssel des NV-Bühne-Satzes ohne Angabe des Satzes (Standard: gesetzlich)
        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['weeklyPlan'],
        ])->assertSessionHasErrors('rules.0');

        $this->post(route('shift-rules.defaults.store'), [
            'set' => 'unbekannt',
            'rules' => ['weeklyPlan'],
        ])->assertSessionHasErrors('set');

        $this->assertSame($before, ShiftRule::query()->count());
    }

    #[Test]
    public function creating_a_rule_set_requires_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $before = ShiftRule::query()->count();

        $this->post(route('shift-rules.defaults.store'), [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => ShiftRuleSets::ruleKeys(ShiftRuleSets::NV_BUEHNE_STAGE),
            'contract_ids' => [],
        ])->assertForbidden();

        $this->assertSame($before, ShiftRule::query()->count());
    }

    // --- Regel mit Zusatzparametern speichern und ändern ----------------------------------------

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function rulePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Wochenplan',
            'description' => 'Wochenplan bis Mittwoch',
            'trigger_type' => 'weeklyPlanCommitDeadline',
            'individual_number_value' => null,
            'warning_color' => '#ff6b6b',
            'contract_ids' => [],
            'user_ids' => [],
        ], $overrides);
    }

    #[Test]
    public function storing_a_rule_saves_its_normalized_settings(): void
    {
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), $this->rulePayload([
            'config' => ['weekday' => 3, 'time' => '12:00', 'weeks_ahead' => 2, 'unknown' => 'x'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $rule = ShiftRule::query()->where('name', 'Wochenplan')->sole();
        $this->assertSame(['weekday' => 3, 'time' => '12:00', 'weeks_ahead' => 2], $rule->config);
        $this->assertEquals(0.0, $rule->individual_number_value);
    }

    #[Test]
    public function storing_without_settings_uses_the_defaults_and_invalid_settings_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), $this->rulePayload(['name' => 'Wochenplan Standard']))
            ->assertSessionHasNoErrors();
        $this->assertSame(
            ['weekday' => 4, 'time' => '14:00', 'weeks_ahead' => 1],
            ShiftRule::query()->where('name', 'Wochenplan Standard')->sole()->config
        );

        $this->post(route('shift-rules.store'), $this->rulePayload([
            'name' => 'Wochenplan kaputt',
            'config' => ['weekday' => 9, 'time' => 'mittags'],
        ]))->assertSessionHasErrors(['config.weekday', 'config.time']);
        $this->assertSame(0, ShiftRule::query()->where('name', 'Wochenplan kaputt')->count());

        // Regeltyp mit Pflichtangaben
        $this->post(route('shift-rules.store'), $this->rulePayload([
            'name' => 'Heiligabend',
            'trigger_type' => 'freeDayAfterWorkOnDate',
        ]))->assertSessionHasErrors(['config.worked', 'config.free']);
    }

    #[Test]
    public function a_rule_type_without_settings_stores_no_config(): void
    {
        $this->actingAsAdmin();

        $this->post(route('shift-rules.store'), $this->rulePayload([
            'name' => 'Tagesmaximum Test',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 9,
            'config' => ['weekday' => 3],
        ]))->assertSessionHasNoErrors();

        $this->assertNull(ShiftRule::query()->where('name', 'Tagesmaximum Test')->sole()->config);
    }

    #[Test]
    public function updating_changes_the_settings_only_when_they_are_sent(): void
    {
        $this->actingAsAdmin();
        $rule = ShiftRule::factory()->create([
            'trigger_type' => 'minFreeDaysPerWeek',
            'individual_number_value' => 1.0,
            'config' => ['workdays_only' => true, 'excluded_dates' => ['05-01']],
            'is_active' => true,
        ]);
        $payload = [
            'name' => 'Freier Werktag',
            'description' => 'x',
            'individual_number_value' => 1,
            'warning_color' => '#ff6b6b',
            'contract_ids' => [],
            'user_ids' => [],
        ];

        // Ohne config bleibt sie unberührt
        $this->put(route('shift-rules.update', $rule->id), $payload)->assertSessionHasNoErrors();
        $this->assertSame(['workdays_only' => true, 'excluded_dates' => ['05-01']], $rule->fresh()->config);
        $this->assertSame('Freier Werktag', $rule->fresh()->name);

        $this->put(route('shift-rules.update', $rule->id), $payload + [
            'config' => ['workdays_only' => false, 'excluded_dates' => ['12-24', '05-01']],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['workdays_only' => false, 'excluded_dates' => ['05-01', '12-24']], $rule->fresh()->config);

        $this->put(route('shift-rules.update', $rule->id), $payload + [
            'config' => ['excluded_dates' => ['1. Mai']],
        ])->assertSessionHasErrors('config.excluded_dates.0');
    }
}
