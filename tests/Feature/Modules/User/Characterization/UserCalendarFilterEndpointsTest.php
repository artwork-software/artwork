<?php

namespace Tests\Feature\Modules\User\Characterization;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Kalender-/Dienstplan-Filter am User (UserCalendarFilterController,
 * UserShiftCalendarFilterController): Einzelwert-Update, Reset, Raum/Termintyp-Filter und
 * Zeitraum des eigenen Einsatzplans. Nur die eigene Person darf ihre Filter ändern.
 */
final class UserCalendarFilterEndpointsTest extends FeatureTestCase
{
    private function userWithFilters(): User
    {
        $user = User::factory()->create();
        $user->calendar_filter()->create([
            'show_free_rooms' => true,
            'show_adjoining_rooms' => true,
            'all_day_free' => true,
            'adjoining_not_loud' => true,
            'adjoining_no_audience' => true,
            'event_types' => [1, 2],
            'rooms' => [3],
            'areas' => [4],
            'room_attributes' => [5],
            'room_categories' => [6],
            'event_properties' => [7],
        ]);
        $user->shift_calendar_filter()->create([
            'event_types' => [1],
            'rooms' => [2],
        ]);

        return $user;
    }

    #[Test]
    public function calendar_filter_single_value_is_updated_and_redirects_back(): void
    {
        $user = User::factory()->create();
        $user->calendar_filter()->create(['show_free_rooms' => false]);
        $this->actingAs($user);

        $this->from('/calendar')
            ->patch(route('user.calendar.filter.single.value.update', $user), [
                'key' => 'show_free_rooms',
                'value' => true,
            ])
            ->assertRedirect('/calendar');

        $this->assertTrue($user->calendar_filter()->first()->show_free_rooms);
    }

    #[Test]
    public function calendar_filter_single_value_of_other_users_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $owner->calendar_filter()->create(['show_free_rooms' => false]);
        $this->actingAs(User::factory()->create());

        $this->patch(route('user.calendar.filter.single.value.update', $owner), [
            'key' => 'show_free_rooms',
            'value' => true,
        ])->assertForbidden();

        $this->assertFalse($owner->calendar_filter()->first()->show_free_rooms);
    }

    #[Test]
    public function filter_single_value_rejects_columns_outside_the_filter_fields(): void
    {
        $user = $this->userWithFilters();
        $other = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.calendar.filter.single.value.update', $user), [
            'key' => 'user_id',
            'value' => $other->id,
        ])->assertUnprocessable();
        $this->patchJson(route('user.calendar.filter.single.value.update', $user), [
            'key' => 'no_such_column',
            'value' => 1,
        ])->assertUnprocessable();
        $this->patchJson(route('user.shift.calendar.filter.single.value.update', $user), [
            'key' => 'user_id',
            'value' => $other->id,
        ])->assertUnprocessable();

        $this->assertSame($user->id, $user->calendar_filter()->first()->user_id);
        $this->assertSame($user->id, $user->shift_calendar_filter()->first()->user_id);
    }

    #[Test]
    public function calendar_filter_single_value_stores_id_lists_as_sent_by_the_function_bar(): void
    {
        $user = $this->userWithFilters();
        $this->actingAs($user);

        $this->from('/calendar')->patch(route('user.calendar.filter.single.value.update', $user), [
            'key' => 'event_types',
            'value' => [3, 4],
        ])->assertRedirect('/calendar');
        $this->assertSame([3, 4], $user->calendar_filter()->first()->event_types);

        $this->patch(route('user.calendar.filter.single.value.update', $user), [
            'key' => 'event_types',
            'value' => null,
        ]);
        $this->assertNull($user->calendar_filter()->first()->event_types);
    }

    #[Test]
    public function calendar_filter_reset_clears_all_flags_and_id_lists(): void
    {
        $user = $this->userWithFilters();
        $this->actingAs($user);

        $this->from('/calendar')
            ->delete(route('reset.user.calendar.filter', $user))
            ->assertRedirect('/calendar');

        $filter = $user->calendar_filter()->first();
        $this->assertFalse($filter->show_free_rooms);
        $this->assertFalse($filter->show_adjoining_rooms);
        $this->assertFalse($filter->all_day_free);
        $this->assertFalse($filter->adjoining_not_loud);
        $this->assertFalse($filter->adjoining_no_audience);
        $this->assertNull($filter->event_types);
        $this->assertNull($filter->rooms);
        $this->assertNull($filter->areas);
        $this->assertNull($filter->room_attributes);
        $this->assertNull($filter->room_categories);
        $this->assertNull($filter->event_properties);
    }

    #[Test]
    public function calendar_filter_reset_of_other_users_is_forbidden(): void
    {
        $owner = $this->userWithFilters();
        $this->actingAs(User::factory()->create());

        $this->delete(route('reset.user.calendar.filter', $owner))->assertForbidden();

        $this->assertSame([1, 2], $owner->calendar_filter()->first()->event_types);
    }

    #[Test]
    public function shift_calendar_filter_update_only_writes_event_types_and_rooms(): void
    {
        $user = $this->userWithFilters();
        $this->actingAs($user);

        $this->patchJson(route('update.user.shift.calendar.filter', $user), [
            'event_types' => [9],
            'rooms' => [8, 7],
            'start_date' => '2030-01-01',
        ])->assertOk();

        $filter = $user->shift_calendar_filter()->first();
        $this->assertSame([9], $filter->event_types);
        $this->assertSame([8, 7], $filter->rooms);
        $this->assertNull($filter->start_date);
    }

    #[Test]
    public function shift_calendar_filter_update_of_other_users_is_forbidden(): void
    {
        $owner = $this->userWithFilters();
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('update.user.shift.calendar.filter', $owner), ['rooms' => [99]])
            ->assertForbidden();

        $this->assertSame([2], $owner->shift_calendar_filter()->first()->rooms);
    }

    #[Test]
    public function shift_calendar_filter_single_value_is_updated(): void
    {
        $user = $this->userWithFilters();
        $this->actingAs($user);

        $this->patchJson(route('user.shift.calendar.filter.single.value.update', $user), [
            'key' => 'rooms',
            'value' => [5, 6],
        ])->assertOk();

        $this->assertSame([5, 6], $user->shift_calendar_filter()->first()->rooms);
    }

    #[Test]
    public function shift_calendar_filter_single_value_of_other_users_is_forbidden(): void
    {
        $owner = $this->userWithFilters();
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('user.shift.calendar.filter.single.value.update', $owner), [
            'key' => 'rooms',
            'value' => json_encode([5]),
        ])->assertForbidden();

        $this->assertSame([2], $owner->shift_calendar_filter()->first()->rooms);
    }

    #[Test]
    public function shift_calendar_filter_reset_clears_event_types_and_rooms(): void
    {
        $user = $this->userWithFilters();
        $this->actingAs($user);

        $this->from('/shifts')
            ->delete(route('reset.user.shift.calendar.filter', $user))
            ->assertRedirect('/shifts');

        $filter = $user->shift_calendar_filter()->first();
        $this->assertNull($filter->event_types);
        $this->assertNull($filter->rooms);
    }

    #[Test]
    public function shift_calendar_filter_reset_of_other_users_is_forbidden(): void
    {
        $owner = $this->userWithFilters();
        $this->actingAs(User::factory()->create());

        $this->delete(route('reset.user.shift.calendar.filter', $owner))->assertForbidden();

        $this->assertSame([1], $owner->shift_calendar_filter()->first()->event_types);
    }

    #[Test]
    public function worker_shift_plan_period_is_stored_as_dates_and_can_be_cleared(): void
    {
        $user = User::factory()->create();
        $user->workerShiftPlanFilter()->create();
        $this->actingAs($user);

        $this->patchJson(route('update.user.worker.shift-plan.filters.update', $user), [
            'start_date' => '2031-03-04T10:00:00',
            'end_date' => '2031-03-10',
        ])->assertOk();

        $filter = $user->workerShiftPlanFilter()->first();
        $this->assertSame('2031-03-04', $filter->start_date->format('Y-m-d'));
        $this->assertSame('2031-03-10', $filter->end_date->format('Y-m-d'));

        $this->patchJson(route('update.user.worker.shift-plan.filters.update', $user), [])->assertOk();

        $filter->refresh();
        $this->assertNull($filter->start_date);
        $this->assertNull($filter->end_date);
    }

    #[Test]
    public function worker_shift_plan_period_of_other_users_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $owner->workerShiftPlanFilter()->create(['start_date' => '2031-01-01', 'end_date' => '2031-01-07']);
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('update.user.worker.shift-plan.filters.update', $owner), [
            'start_date' => '2040-01-01',
        ])->assertForbidden();

        $this->assertSame('2031-01-01', $owner->workerShiftPlanFilter()->first()->start_date->format('Y-m-d'));
    }

    #[Test]
    public function shown_crafts_are_stored_in_the_shift_filter_and_empty_lists_become_null(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('user.update.show_crafts', $user), ['craft_ids' => [3, 0, 5]])->assertOk();

        $filter = $user->userFilters()->where('filter_type', 'shift_filter')->sole();
        $this->assertSame([3, 5], $filter->craft_ids);

        $this->patchJson(route('user.update.show_crafts', $user), ['craft_ids' => []])->assertOk();

        $this->assertNull($filter->fresh()->craft_ids);
        $this->assertSame(1, $user->userFilters()->where('filter_type', 'shift_filter')->count());
    }

    #[Test]
    public function shown_crafts_of_other_users_cannot_be_changed(): void
    {
        $owner = User::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('user.update.show_crafts', $owner), ['craft_ids' => [3]])->assertForbidden();

        $this->assertSame(0, $owner->userFilters()->count());
    }
}
