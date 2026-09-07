<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Http\Controllers\ShiftRuleController;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Liste "Offene Verstöße" (shift-rules.pending): Paginierung, Filter, Zähler, Sammel-Ignorieren (Deckel 200), Rechte.
 */
final class ShiftRuleViolationsListTest extends FeatureTestCase
{
    private function planner(): User
    {
        return $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
        ]);
    }

    private function violation(User $user, ShiftRule $rule, array $overrides = []): ShiftRuleViolation
    {
        return ShiftRuleViolation::factory()->create(array_merge([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => Carbon::today()->toDateString(),
            'severity' => 'warning',
            'status' => 'active',
            'violation_data' => ['planned_hours' => 9, 'max_allowed' => 8],
        ], $overrides));
    }

    #[Test]
    public function list_requires_planner_and_shift_settings_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('shift-rules.pending'))->assertForbidden();
    }

    /**
     * Verstoß mit Schicht in eigenem Raum/Gewerk (Room lädt per $with admins+creator, Craft craftShiftPlaner).
     */
    private function violationWithShift(User $user, ShiftRule $rule, int $index): ShiftRuleViolation
    {
        $craft = Craft::factory()->create();
        $user->assignedCrafts()->attach($craft->id);
        $shift = Shift::factory()->create([
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => Carbon::today()->subDays($index)->toDateString(),
            'end_date' => Carbon::today()->subDays($index)->toDateString(),
        ]);

        return $this->violation($user, $rule, [
            'shift_id' => $shift->id,
            'violation_date' => Carbon::today()->subDays($index)->toDateString(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function loggedQueries(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return $queries;
    }

    #[Test]
    public function list_does_not_load_hidden_room_and_craft_relations_and_has_constant_query_count(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create();
        $user = User::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            $this->violationWithShift($user, $rule, $i);
        }

        $few = $this->loggedQueries(fn () => $this->get(route('shift-rules.pending'))->assertOk());

        // Versteckte Eager-Loads der Modelle ($with): Raum-Admins (room_user-Pivot) und Gewerks-Planer*innen
        // (craft_users-Pivot) gehören nicht in die Liste — die Relations-Closures schalten sie ab.
        $pivotQueries = array_values(array_filter(
            $few,
            static fn (string $sql): bool => str_contains($sql, 'pivot_room_id')
                || (str_contains($sql, 'craft_users') && str_contains($sql, 'pivot_craft_id'))
        ));
        $this->assertSame([], $pivotQueries, 'Raum-Admins/Gewerks-Planer*innen werden für die Liste geladen');

        for ($i = 3; $i < 12; $i++) {
            $this->violationWithShift(User::factory()->create(), $rule, $i);
        }
        $many = $this->loggedQueries(fn () => $this->get(route('shift-rules.pending'))->assertOk());

        $this->assertLessThanOrEqual(
            count($few) + 1,
            count($many),
            sprintf('Query-Zahl wächst mit der Zeilenzahl (N+1): %d vs. %d', count($few), count($many))
        );
    }

    #[Test]
    public function partial_reload_skips_filter_master_data_and_saves_queries(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create();
        $user = User::factory()->create();
        $this->violationWithShift($user, $rule, 0);

        // Inertia-XHR braucht die Asset-Version, sonst antwortet die Middleware mit 409
        $inertiaHeaders = [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        ];

        $full = $this->loggedQueries(fn () => $this->get(route('shift-rules.pending'), $inertiaHeaders)
            ->assertOk()
            ->assertJsonStructure(['props' => ['crafts', 'rules', 'users']])
            ->assertJsonPath('props.rules', fn (array $rules): bool => collect($rules)->contains('id', $rule->id))
            ->assertJsonPath('props.users', fn (array $users): bool => collect($users)->contains('id', $user->id)));

        // Paginierung/Filterwechsel laden per only: [...] nur Liste, Zähler und Filterzustand
        $partial = $this->loggedQueries(fn () => $this->get(route('shift-rules.pending', ['page' => 1]), $inertiaHeaders + [
            'X-Inertia-Partial-Component' => 'ShiftWarnings/Violations',
            'X-Inertia-Partial-Data' => 'violations,counters,filters,perPage',
        ])
            ->assertOk()
            ->assertJsonPath('props.violations.total', 1)
            ->assertJsonPath('props.counters.total', 1)
            ->assertJsonMissingPath('props.crafts')
            ->assertJsonMissingPath('props.rules')
            ->assertJsonMissingPath('props.users'));

        $this->assertLessThan(count($full), count($partial), 'Teil-Reload spart keine Stammdaten-Queries');
        // Regel-Stammdaten (select … from shift_rules order by name) dürfen beim Teil-Reload nicht laufen;
        // der Eager-Load der Verstöße (where shift_rules.id in (…)) ist erlaubt.
        $this->assertSame(
            [],
            array_values(array_filter(
                $partial,
                static fn (string $sql): bool => str_contains($sql, 'from `shift_rules`') && str_contains($sql, 'order by `name`')
            )),
            'Teil-Reload lädt die Regel-Stammdaten'
        );
    }

    #[Test]
    public function list_is_paginated_with_default_50_and_accepts_25_or_100(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create();
        $user = User::factory()->create();
        for ($i = 0; $i < 60; $i++) {
            $this->violation($user, $rule, ['violation_date' => Carbon::today()->subDays($i)->toDateString()]);
        }

        $this->get(route('shift-rules.pending'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftWarnings/Violations')
                ->has('violations.data', 50)
                ->where('violations.total', 60)
                ->where('perPage', 50)
                ->where('counters.total', 60)
                ->where('counters.warning', 60)
                ->where('counters.error', 0)
                ->where('filters.status', 'active'));

        $this->get(route('shift-rules.pending', ['per_page' => 25, 'page' => 3]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 10)
                ->where('violations.current_page', 3));

        $this->get(route('shift-rules.pending', ['per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('violations.data', 60));
    }

    #[Test]
    public function list_filters_by_craft_person_rule_severity_status_period_and_sorts(): void
    {
        $this->planner();
        $ruleA = ShiftRule::factory()->create(['name' => 'Regel A']);
        $ruleB = ShiftRule::factory()->create(['name' => 'Regel B']);
        $craft = Craft::factory()->create();
        $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Liste']);
        $anna->assignedCrafts()->attach($craft->id);
        $ben = User::factory()->create(['first_name' => 'Ben', 'last_name' => 'Liste']);

        $annaError = $this->violation($anna, $ruleA, ['severity' => 'error', 'violation_date' => '2026-03-10']);
        $annaWarning = $this->violation($anna, $ruleB, ['severity' => 'warning', 'violation_date' => '2026-03-20']);
        $benResolved = $this->violation($ben, $ruleA, ['severity' => 'error', 'violation_date' => '2026-03-15', 'status' => 'resolved']);
        $benApril = $this->violation($ben, $ruleA, ['severity' => 'error', 'violation_date' => '2026-04-05']);

        // Gewerk (über die Gewerke der Person)
        $this->get(route('shift-rules.pending', ['craft_id' => [$craft->id]]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 2)
                ->where('filters.craft_ids', [$craft->id]));

        // Person
        $this->get(route('shift-rules.pending', ['user_id' => $ben->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 1)
                ->where('violations.data.0.id', $benApril->id));

        // Regel + Schwere
        $this->get(route('shift-rules.pending', ['shift_rule_id' => $ruleB->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 1)
                ->where('violations.data.0.id', $annaWarning->id));
        $this->get(route('shift-rules.pending', ['severity' => 'error']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 2)
                // Zähler folgen den Filtern (außer Status): nur aktive Fehler
                ->where('counters.error', 2)
                ->where('counters.warning', 0)
                ->where('counters.total', 2));

        // Status (bearbeitet) + "alle"
        $this->get(route('shift-rules.pending', ['status' => 'resolved']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 1)
                ->where('violations.data.0.id', $benResolved->id)
                ->where('violations.data.0.status', 'resolved')
                // Zähler bleiben die aktiven, unabhängig vom Statusfilter
                ->where('counters.total', 3));
        $this->get(route('shift-rules.pending', ['status' => 'all']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('violations.data', 4));

        // Zeitraum + Sortierung aufsteigend
        $this->get(route('shift-rules.pending', ['date_from' => '2026-03-01', 'date_to' => '2026-03-31', 'sort' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 2)
                ->where('violations.data.0.id', $annaError->id)
                ->where('violations.data.1.id', $annaWarning->id)
                ->where('filters.sort', 'asc'));

        // Ohne Zeitraum: alle aktiven (Default absteigend: April zuerst)
        $this->get(route('shift-rules.pending'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 3)
                ->where('violations.data.0.id', $benApril->id)
                ->where('violations.data.0.user_crafts', [])
                ->where('violations.data.2.user_crafts', [$craft->name]));
    }

    #[Test]
    public function craft_filter_also_matches_the_craft_of_the_shift(): void
    {
        $this->planner();
        $rule = ShiftRule::factory()->create();
        $craftX = Craft::factory()->create();
        $craftY = Craft::factory()->create();

        // Person OHNE Gewerk X, aber Verstoß an einer Schicht des Gewerks X → über das Schicht-Gewerk gefunden
        $outsider = User::factory()->create(['first_name' => 'Ohne', 'last_name' => 'Gewerk']);
        $shiftInX = Shift::factory()->create(['craft_id' => $craftX->id]);
        $viaShift = $this->violation($outsider, $rule, ['shift_id' => $shiftInX->id]);

        // Person des Gewerks X ohne Schicht → weiterhin über das Gewerk der Person gefunden
        $member = User::factory()->create(['first_name' => 'Mit', 'last_name' => 'Gewerk']);
        $member->assignedCrafts()->attach($craftX->id);
        $viaUser = $this->violation($member, $rule);

        // Weder Person noch Schicht in X → nicht gefunden
        $other = User::factory()->create();
        $other->assignedCrafts()->attach($craftY->id);
        $this->violation($other, $rule, ['shift_id' => Shift::factory()->create(['craft_id' => $craftY->id])->id]);

        // Sortierung: gleiches Datum → ID absteigend (viaUser wurde nach viaShift angelegt)
        $this->get(route('shift-rules.pending', ['craft_id' => [$craftX->id]]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('violations.data', 2)
                ->where('violations.data.0.id', $viaUser->id)
                ->where('violations.data.1.id', $viaShift->id)
                ->where('counters.total', 2));
    }

    #[Test]
    public function bulk_ignore_ignores_selected_active_violations_with_reason(): void
    {
        $planner = $this->planner();
        $rule = ShiftRule::factory()->create();
        $user = User::factory()->create();
        $first = $this->violation($user, $rule);
        $second = $this->violation($user, $rule);
        $alreadyResolved = $this->violation($user, $rule, ['status' => 'resolved']);
        $untouched = $this->violation($user, $rule);

        $this->post(route('shift-rule-violations.bulk-ignore'), [
            'ids' => [$first->id, $second->id, $alreadyResolved->id],
            'ignore_reason' => 'Sammelgrund',
        ])->assertRedirect()->assertSessionHasNoErrors();

        foreach ([$first, $second] as $violation) {
            $violation->refresh();
            $this->assertSame('ignored', $violation->status);
            $this->assertSame('Sammelgrund', $violation->ignore_reason);
            $this->assertSame($planner->id, $violation->resolved_by);
            $this->assertNotNull($violation->resolved_at);
        }
        $this->assertSame('resolved', $alreadyResolved->fresh()->status);
        $this->assertSame('active', $untouched->fresh()->status);
    }

    #[Test]
    public function bulk_ignore_requires_reason_and_caps_at_200_ids(): void
    {
        $this->planner();

        $this->post(route('shift-rule-violations.bulk-ignore'), ['ids' => [1]])
            ->assertSessionHasErrors('ignore_reason');

        $this->assertSame(200, ShiftRuleController::BULK_IGNORE_LIMIT);
        $this->post(route('shift-rule-violations.bulk-ignore'), [
            'ids' => range(1, 201),
            'ignore_reason' => 'zu viele',
        ])->assertSessionHasErrors('ids');
    }

    #[Test]
    public function bulk_ignore_requires_planner_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->post(route('shift-rule-violations.bulk-ignore'), ['ids' => [1], 'ignore_reason' => 'x'])
            ->assertForbidden();
    }
}
