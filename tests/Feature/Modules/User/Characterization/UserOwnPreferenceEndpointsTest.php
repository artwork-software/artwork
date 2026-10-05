<?php

namespace Tests\Feature\Modules\User\Characterization;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Ansichts-/Präferenz-Endpunkte am User (Policy updateOwnPreferences):
 * nur die eigene Person darf ihre Einstellungen schreiben (Admins passieren via Gate::before).
 */
final class UserOwnPreferenceEndpointsTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: string, 4: mixed}>
     */
    public static function preferenceEndpoints(): array
    {
        return [
            'compact mode' => ['post', 'user.compact.mode.toggle', ['compact_mode' => true], 'compact_mode', true],
            'project team names' => [
                'post',
                'user.show.project.team.names.toggle',
                ['show_project_team_names' => true],
                'show_project_team_names',
                true,
            ],
            'at a glance' => ['patch', 'user.update.at_a_glance', ['at_a_glance' => true], 'at_a_glance', true],
            'bulk column size' => [
                'patch',
                'user.bulk-column-size.update',
                ['bulk_column_size' => ['1' => 200, '2' => 120]],
                'bulk_column_size',
                ['1' => 200, '2' => 120],
            ],
            'bulk description' => [
                'patch',
                'user.update.show_description_in_bulk',
                ['show_description_in_bulk' => true],
                'show_description_in_bulk',
                true,
            ],
            'bulk sort id' => ['patch', 'user.update_bulk_sort_id', ['bulk_sort_id' => 3], 'bulk_sort_id', 3],
            'shift plan user sort' => [
                'patch',
                'user.update.shiftPlanUserSortBy',
                ['sortBy' => 'ALPHABETICALLY_NAME_DESCENDING'],
                'shift_plan_user_sort_by_id',
                'ALPHABETICALLY_NAME_DESCENDING',
            ],
            'shift tab sort' => [
                'patch',
                'user.update.shift_tab_sort',
                ['sortBy' => 'ROOM_NAME_DESC'],
                'sort_type_shift_tab',
                'ROOM_NAME_DESC',
            ],
            'shown shift qualifications' => [
                'patch',
                'user.update.show_shift-qualifications',
                ['show_qualifications' => [1, 2]],
                'show_qualifications',
                [1, 2],
            ],
            'user overview height' => [
                'patch',
                'user.update.userOverviewHeight',
                ['drawer_height' => 555],
                'drawer_height',
                555,
            ],
            'calendar go-to stepper' => [
                'patch',
                'user.calendar.go.to.stepper',
                ['goto_mode' => 'week'],
                'goto_mode',
                'week',
            ],
            'opened crafts' => [
                'patch',
                'user.update.open.crafts',
                ['opened_crafts' => [4, 7]],
                'opened_crafts',
                [4, 7],
            ],
            'checklist style' => [
                'patch',
                'user.checklist.style',
                ['checklist_style' => 'kanban'],
                'checklist_style',
                'kanban',
            ],
        ];
    }

    #[Test]
    #[DataProvider('preferenceEndpoints')]
    public function users_can_update_their_own_preference(
        string $method,
        string $routeName,
        array $payload,
        string $column,
        mixed $expected
    ): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json(strtoupper($method), route($routeName, $user), $payload);

        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            'Unerwarteter Status ' . $response->getStatusCode()
        );
        $this->assertEquals($expected, $user->fresh()->getAttribute($column));
    }

    #[Test]
    #[DataProvider('preferenceEndpoints')]
    public function preferences_of_other_users_cannot_be_changed(
        string $method,
        string $routeName,
        array $payload,
        string $column,
        mixed $expected
    ): void {
        $owner = User::factory()->create();
        $before = $owner->fresh()->getAttribute($column);
        $this->actingAs(User::factory()->create());

        $this->json(strtoupper($method), route($routeName, $owner), $payload)->assertForbidden();

        $this->assertEquals($before, $owner->fresh()->getAttribute($column));
        $this->assertNotEquals($expected, $before);
    }

    #[Test]
    public function bulk_column_size_update_redirects_back(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from('/somewhere')
            ->patch(route('user.bulk-column-size.update', $user), ['bulk_column_size' => ['1' => 160]])
            ->assertRedirect('/somewhere');
    }

    #[Test]
    public function shift_plan_user_sort_rejects_unknown_values_and_accepts_null(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.update.shiftPlanUserSortBy', $user), ['sortBy' => 'NOPE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sortBy');

        $this->patchJson(route('user.update.shiftPlanUserSortBy', $user), ['sortBy' => null])->assertOk();
        $this->assertNull($user->fresh()->shift_plan_user_sort_by_id);
    }

    #[Test]
    public function shift_tab_sort_rejects_unknown_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.update.shift_tab_sort', $user), ['sortBy' => 'NOPE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sortBy');
    }

    #[Test]
    public function chat_popup_position_can_be_set_by_the_user_themselves(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.chat.popup-settings', $user), ['chat_popup_position' => 'top-left'])
            ->assertOk();

        $this->assertSame('top-left', $user->fresh()->chat_popup_position);
    }

    #[Test]
    public function chat_popup_position_of_others_requires_the_worker_management_permission(): void
    {
        $owner = User::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->patchJson(route('user.chat.popup-settings', $owner), ['chat_popup_position' => 'top-left'])
            ->assertForbidden();
        $this->assertSame('bottom-right', $owner->fresh()->chat_popup_position);

        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $this->patchJson(route('user.chat.popup-settings', $owner), ['chat_popup_position' => 'middle-left'])
            ->assertOk();
        $this->assertSame('middle-left', $owner->fresh()->chat_popup_position);
    }

    #[Test]
    public function chat_popup_position_is_validated_against_the_allowed_positions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.chat.popup-settings', $user), ['chat_popup_position' => 'center'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('chat_popup_position');
    }

    #[Test]
    public function shift_time_preset_toggle_always_writes_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.shift-time-preset.toggle'), ['is_time_preset_open' => true])->assertOk();
        $this->assertTrue($user->fresh()->is_time_preset_open);

        $this->patchJson(route('user.shift-time-preset.toggle'), [])->assertOk();
        $this->assertFalse($user->fresh()->is_time_preset_open);
    }

    #[Test]
    public function checklist_status_is_written_to_the_authenticated_user_regardless_of_the_route_user(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $otherBefore = $other->fresh()->opened_checklists;
        $this->actingAs($actor);

        $this->patchJson(route('user.checklists.update', $other), ['opened_checklists' => [11, 12]])
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $this->assertSame([11, 12], $actor->fresh()->opened_checklists);
        $this->assertSame($otherBefore, $other->fresh()->opened_checklists);
    }

    #[Test]
    public function area_status_is_written_to_the_authenticated_user_regardless_of_the_route_user(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $otherBefore = $other->fresh()->opened_areas;
        $this->actingAs($actor);

        $this->from('/settings')
            ->patch(route('user.areas.update', $other), ['opened_areas' => [5]])
            ->assertRedirect('/settings');

        $this->assertSame([5], $actor->fresh()->opened_areas);
        $this->assertSame($otherBefore, $other->fresh()->opened_areas);
    }
}
