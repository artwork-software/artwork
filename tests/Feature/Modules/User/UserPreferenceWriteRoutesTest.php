<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion der persönlichen Ansichts-Einstellungen (Rechte deckt die Rollen-Matrix ab).
 */
final class UserPreferenceWriteRoutesTest extends FeatureTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: mixed, 4: mixed}>
     */
    public static function validPreferenceProvider(): array
    {
        return [
            'Sprungmodus' => ['patch', 'user.calendar.go.to.stepper', 'goto_mode', 'week', 'week'],
            'Kompaktmodus' => ['post', 'user.compact.mode.toggle', 'compact_mode', true, true],
            'Teamnamen' => ['post', 'user.show.project.team.names.toggle', 'show_project_team_names', true, true],
            'Auf einen Blick' => ['patch', 'user.update.at_a_glance', 'at_a_glance', true, true],
            'Beschreibung im Bulk' => [
                'patch', 'user.update.show_description_in_bulk', 'show_description_in_bulk', true, true,
            ],
            'Bulk-Sortierung' => ['patch', 'user.update_bulk_sort_id', 'bulk_sort_id', 2, 2],
            'Qualifikationen' => [
                'patch', 'user.update.show_shift-qualifications', 'show_qualifications', [3, 5], [3, 5],
            ],
            'Übersichtshöhe' => ['patch', 'user.update.userOverviewHeight', 'drawer_height', 512, 512],
            'Checklisten-Stil' => ['patch', 'user.checklist.style', 'checklist_style', 'kanban', 'kanban'],
            'Offene Gewerke' => ['patch', 'user.update.open.crafts', 'opened_crafts', [4, 7], [4, 7]],
            'Keine offenen Gewerke' => ['patch', 'user.update.open.crafts', 'opened_crafts', [], []],
        ];
    }

    #[Test]
    #[DataProvider('validPreferenceProvider')]
    public function valid_preference_is_stored(
        string $method,
        string $routeName,
        string $field,
        mixed $value,
        mixed $expected
    ): void {
        $this->json($method, route($routeName, $this->user), [$field => $value])->assertSuccessful();

        $this->assertEquals($expected, $this->user->fresh()->{$field});
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: mixed}>
     */
    public static function invalidPreferenceProvider(): array
    {
        return [
            'Sprungmodus leer' => ['patch', 'user.calendar.go.to.stepper', 'goto_mode', null],
            'Sprungmodus unbekannt' => ['patch', 'user.calendar.go.to.stepper', 'goto_mode', 'year'],
            'Kompaktmodus leer' => ['post', 'user.compact.mode.toggle', 'compact_mode', null],
            'Teamnamen leer' => ['post', 'user.show.project.team.names.toggle', 'show_project_team_names', null],
            'Auf einen Blick Text' => ['patch', 'user.update.at_a_glance', 'at_a_glance', 'vielleicht'],
            'Beschreibung im Bulk leer' => [
                'patch', 'user.update.show_description_in_bulk', 'show_description_in_bulk', null,
            ],
            'Bulk-Sortierung Text' => ['patch', 'user.update_bulk_sort_id', 'bulk_sort_id', 'abc'],
            'Bulk-Sortierung unbekannt' => ['patch', 'user.update_bulk_sort_id', 'bulk_sort_id', 9],
            'Qualifikationen kein Array' => [
                'patch', 'user.update.show_shift-qualifications', 'show_qualifications', 'abc',
            ],
            'Übersichtshöhe Text' => ['patch', 'user.update.userOverviewHeight', 'drawer_height', 'hoch'],
            'Checklisten-Stil unbekannt' => ['patch', 'user.checklist.style', 'checklist_style', 'grid'],
            'Checklisten-Stil leer' => ['patch', 'user.checklist.style', 'checklist_style', null],
            'Offene Gewerke kein Array' => ['patch', 'user.update.open.crafts', 'opened_crafts', 'x'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPreferenceProvider')]
    public function invalid_preference_is_rejected_instead_of_server_error_or_garbage(
        string $method,
        string $routeName,
        string $field,
        mixed $value
    ): void {
        $before = $this->user->fresh()->getRawOriginal($field);

        $this->json($method, route($routeName, $this->user), [$field => $value])
            ->assertStatus(422)
            ->assertJsonValidationErrors($field);

        $this->assertSame($before, $this->user->fresh()->getRawOriginal($field));
    }

    #[Test]
    public function fractional_overview_height_is_rounded(): void
    {
        $this->patchJson(route('user.update.userOverviewHeight', $this->user), ['drawer_height' => 412.6])
            ->assertSuccessful();

        $this->assertSame(413, (int) $this->user->fresh()->drawer_height);
    }

    #[Test]
    public function boolean_preference_can_be_switched_off_again(): void
    {
        $this->user->update(['compact_mode' => true, 'at_a_glance' => true]);

        $this->postJson(route('user.compact.mode.toggle', $this->user), ['compact_mode' => false])->assertSuccessful();
        $this->patchJson(route('user.update.at_a_glance', $this->user), ['at_a_glance' => false])->assertSuccessful();

        $fresh = $this->user->fresh();
        $this->assertFalse($fresh->compact_mode);
        $this->assertFalse($fresh->at_a_glance);
    }

    #[Test]
    public function preferences_of_other_users_cannot_be_changed(): void
    {
        $other = User::factory()->create(['goto_mode' => 'day']);

        $this->patchJson(route('user.calendar.go.to.stepper', $other), ['goto_mode' => 'month'])->assertForbidden();

        $this->assertSame('day', $other->fresh()->goto_mode);
    }

    #[Test]
    public function shift_plan_project_period_is_enabled_and_disabled(): void
    {
        $project = Project::factory()->create();
        $this->user->shift_plan_settings()->firstOrCreate();
        $route = route('user.calendar_settings.toggle_calendar_settings_use_project_period_shift_plan');

        $this->patch($route, ['use_project_time_period' => true, 'project_id' => $project->id])
            ->assertRedirect(route('shifts.plan'));
        $settings = $this->user->shift_plan_settings()->first();
        $this->assertTrue((bool) $settings->use_project_time_period);
        $this->assertSame($project->id, (int) $settings->time_period_project_id);

        $this->patch($route, ['use_project_time_period' => false, 'project_id' => 0, 'is_axios' => true])
            ->assertSuccessful();
        $settings = $this->user->shift_plan_settings()->first();
        $this->assertFalse((bool) $settings->use_project_time_period);
        $this->assertSame(0, (int) $settings->time_period_project_id);
    }

    #[Test]
    public function shift_plan_project_period_is_saved_for_daily_view_separately(): void
    {
        $project = Project::factory()->create();
        $this->user->shift_plan_settings()->firstOrCreate();
        $this->user->shift_plan_daily_settings()->firstOrCreate();

        $this->patch(route('user.calendar_settings.toggle_calendar_settings_use_project_period_shift_plan'), [
            'use_project_time_period' => true,
            'project_id' => $project->id,
            'is_daily_view' => true,
        ])->assertRedirect();

        $this->assertTrue((bool) $this->user->shift_plan_daily_settings()->first()->use_project_time_period);
        $this->assertFalse((bool) $this->user->shift_plan_settings()->first()->use_project_time_period);
    }

    #[Test]
    public function shift_plan_project_period_rejects_unknown_project(): void
    {
        $this->patchJson(route('user.calendar_settings.toggle_calendar_settings_use_project_period_shift_plan'), [
            'use_project_time_period' => true,
            'project_id' => 99999999,
        ])->assertStatus(422)->assertJsonValidationErrors('project_id');
    }
}
