<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Jobs\RevalidateShiftRulesJob;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\LegalDefaultShiftRules;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

/**
 * "Gesetzliche Standardregeln anlegen" (ArbZG): Anlage, Idempotenz, Rechte.
 */
final class ShiftRuleLegalDefaultsControllerTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    #[Test]
    public function index_delivers_the_legal_default_definitions(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shift-rules.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftWarnings/Index')
                ->has('legalDefaultRules', count(LegalDefaultShiftRules::all()))
                ->where('legalDefaultRules.0.name', 'Gesetzliches Tagesmaximum')
                ->where('legalDefaultRules.0.trigger_type', 'maxWorkingHoursOnDay'));
    }

    #[Test]
    public function all_default_rules_are_created_and_assigned_to_the_selected_contracts(): void
    {
        $this->actingAsAdmin();
        [$user, $contractA] = $this->userWithContract();
        $contractB = UserContract::factory()->create();
        // Bestand der Test-DB darf nicht mit den Standardwerten kollidieren (Idempotenz würde sonst greifen)
        ShiftRule::query()->whereIn('trigger_type', collect(LegalDefaultShiftRules::all())->pluck('trigger_type'))->update(['deleted_at' => now()]);
        $before = ShiftRule::query()->count();
        // Die Vertragszuweisung des Fixtures dispatcht selbst einen Revalidate-Job → Zähler zurücksetzen
        Bus::fake();

        $response = $this->post(route('shift-rules.defaults.store'), [
            'rules' => LegalDefaultShiftRules::keys(),
            'contract_ids' => [$contractA->id, $contractB->id],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($before + 9, ShiftRule::query()->count());

        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliches Tagesmaximum', 'trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 10]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliches Wochenmaximum', 'trigger_type' => 'weeklyMaxHours', 'individual_number_value' => 48]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliche Ruhezeit', 'trigger_type' => 'restTimeBeforeWorkday', 'individual_number_value' => 11]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliche Höchstzahl Arbeitstage in Folge', 'trigger_type' => 'maxConsecWorkingDays', 'individual_number_value' => 6]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Sonntagsarbeit (Ersatzruhetag)', 'trigger_type' => 'workOnSunday', 'default_compensation_days' => 1, 'default_compensation_deadline_days' => 14]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Arbeit am Sondertag (Ersatzruhetag)', 'trigger_type' => 'workOnHoliday', 'default_compensation_days' => 1, 'default_compensation_deadline_days' => 56]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliche freie Sonntage pro Jahr', 'trigger_type' => 'minFreeSundaysPerYear', 'individual_number_value' => 15]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzlicher Wochendurchschnitt', 'trigger_type' => 'averageWeeklyHours', 'individual_number_value' => 48, 'period_weeks' => 24]);
        $this->assertDatabaseHas('shift_rules', ['name' => 'Gesetzliches Nachtarbeitsmaximum', 'trigger_type' => 'nightWorkMaxHours', 'individual_number_value' => 8]);

        $names = collect(LegalDefaultShiftRules::all())->pluck('name');
        $this->assertSame(9, ShiftRule::query()->whereIn('name', $names)->count());
        ShiftRule::query()->whereIn('name', $names)->each(function (ShiftRule $rule) use ($contractA, $contractB): void {
            $this->assertTrue($rule->is_active);
            $this->assertEqualsCanonicalizing(
                [$contractA->id, $contractB->id],
                $rule->contracts()->pluck('user_contracts.id')->map(fn ($id) => (int) $id)->all(),
                "Regel {$rule->name} ist nicht beiden Vorlagen zugeordnet"
            );
        });

        // Neuprüfung einmal für die betroffenen Personen (nicht je Regel)
        Bus::assertDispatchedTimes(RevalidateShiftRulesJob::class, 1);
        Bus::assertDispatched(
            RevalidateShiftRulesJob::class,
            fn (RevalidateShiftRulesJob $job): bool => in_array($user->id, $job->userIds, true)
        );
    }

    #[Test]
    public function existing_rules_with_same_type_and_value_are_not_created_again_but_assigned_to_missing_contracts(): void
    {
        $this->actingAsAdmin();
        [, $contractA] = $this->userWithContract();
        $contractB = UserContract::factory()->create();
        ShiftRule::query()->whereIn('trigger_type', collect(LegalDefaultShiftRules::all())->pluck('trigger_type'))->update(['deleted_at' => now()]);
        $before = ShiftRule::query()->count();

        // Umbenannte Regel mit gleichem Typ/Wert zählt als vorhanden
        $existing = ShiftRule::factory()->create([
            'name' => 'Mein Tagesmaximum',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 10.0,
        ]);
        $existing->contracts()->sync([$contractA->id]);
        // Gleicher Typ, anderer Wert -> gilt NICHT als vorhanden
        ShiftRule::factory()->create(['trigger_type' => 'weeklyMaxHours', 'individual_number_value' => 40.0]);
        // Wochendurchschnitt mit anderem Zeitraum -> gilt NICHT als vorhanden
        ShiftRule::factory()->create(['trigger_type' => 'averageWeeklyHours', 'individual_number_value' => 48.0, 'period_weeks' => 12]);
        // Gelöschte Regel zählt nicht
        $deleted = ShiftRule::factory()->create(['trigger_type' => 'restTimeBeforeWorkday', 'individual_number_value' => 11.0]);
        $deleted->delete();

        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['dailyMax', 'weeklyMax', 'averageWeeklyHours', 'restTime'],
            'contract_ids' => [$contractA->id, $contractB->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        // 3 vorhanden (Tagesmax + 2 Fremdwerte; gelöschte zählt nicht) + 3 neu (Wochenmax 48, Ø 48/24, Ruhezeit 11); Tagesmax nicht doppelt
        $this->assertSame($before + 6, ShiftRule::query()->count());
        $this->assertSame(1, ShiftRule::query()->where('trigger_type', 'maxWorkingHoursOnDay')->count());
        $this->assertSame('Mein Tagesmaximum', $existing->fresh()->name);
        $this->assertEqualsCanonicalizing(
            [$contractA->id, $contractB->id],
            $existing->contracts()->pluck('user_contracts.id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertDatabaseHas('shift_rules', ['trigger_type' => 'weeklyMaxHours', 'individual_number_value' => 48, 'deleted_at' => null]);
        $this->assertDatabaseHas('shift_rules', ['trigger_type' => 'averageWeeklyHours', 'period_weeks' => 24, 'deleted_at' => null]);
        $this->assertSame(1, ShiftRule::query()->where('trigger_type', 'restTimeBeforeWorkday')->count());

        // Zweiter Aufruf ändert nichts mehr
        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['dailyMax', 'weeklyMax', 'averageWeeklyHours', 'restTime'],
            'contract_ids' => [$contractA->id, $contractB->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($before + 6, ShiftRule::query()->count());
    }

    #[Test]
    public function an_inactive_rule_of_the_same_kind_is_reactivated_instead_of_duplicated(): void
    {
        $this->actingAsAdmin();
        [$user, $contractA] = $this->userWithContract();
        $contractB = UserContract::factory()->create();
        ShiftRule::query()->whereIn('trigger_type', collect(LegalDefaultShiftRules::all())->pluck('trigger_type'))->update(['deleted_at' => now()]);

        // Deaktivierte Regel gleichen Typs/Werts, bereits Vorlage A zugeordnet
        $inactive = ShiftRule::factory()->inactive()->create([
            'name' => 'Altes Tagesmaximum',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 10.0,
        ]);
        $inactive->contracts()->sync([$contractA->id]);
        $before = ShiftRule::query()->count();
        Bus::fake();

        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['dailyMax'],
            'contract_ids' => [$contractA->id, $contractB->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        // Keine Dublette: reaktiviert, umbenannter Name bleibt, fehlende Vorlage B ergänzt
        $this->assertSame($before, ShiftRule::query()->count());
        $this->assertSame(1, ShiftRule::query()->where('trigger_type', 'maxWorkingHoursOnDay')->count());
        $inactive->refresh();
        $this->assertTrue($inactive->is_active);
        $this->assertSame('Altes Tagesmaximum', $inactive->name);
        $this->assertEqualsCanonicalizing(
            [$contractA->id, $contractB->id],
            $inactive->contracts()->pluck('user_contracts.id')->map(fn ($id) => (int) $id)->all()
        );

        // Reaktivierung prüft auch die schon zugeordnete Vorlage A neu (Regel war bisher stumm)
        Bus::assertDispatched(
            RevalidateShiftRulesJob::class,
            fn (RevalidateShiftRulesJob $job): bool => in_array($user->id, $job->userIds, true)
        );

        // matches(): inaktiv zählt nur mit $requireActive = false
        $definition = LegalDefaultShiftRules::find('dailyMax');
        $fresh = ShiftRule::factory()->inactive()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 10.0]);
        $this->assertFalse(LegalDefaultShiftRules::matches($fresh, $definition));
        $this->assertTrue(LegalDefaultShiftRules::matches($fresh, $definition, false));
    }

    #[Test]
    public function unknown_rule_keys_and_unknown_contracts_are_rejected(): void
    {
        $this->actingAsAdmin();
        $before = ShiftRule::query()->count();

        $this->post(route('shift-rules.defaults.store'), [
            'rules' => ['dailyMax', 'notARule'],
            'contract_ids' => [999999],
        ])->assertSessionHasErrors(['rules.1', 'contract_ids.0']);

        $this->post(route('shift-rules.defaults.store'), ['rules' => []])
            ->assertSessionHasErrors(['rules']);

        $this->assertSame($before, ShiftRule::query()->count());
    }

    #[Test]
    public function creating_defaults_requires_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $contract = UserContract::factory()->create();
        $before = ShiftRule::query()->count();

        $this->post(route('shift-rules.defaults.store'), [
            'rules' => LegalDefaultShiftRules::keys(),
            'contract_ids' => [$contract->id],
        ])->assertForbidden();

        $this->assertSame($before, ShiftRule::query()->count());
    }
}
