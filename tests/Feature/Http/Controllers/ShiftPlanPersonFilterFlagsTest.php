<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Personenfilter-Flags des Schichtplans (user_filters): „Freelancer einbinden" und
 * „nur Personen mit offenen Regelverstößen" werden pro Filtertyp persistiert und lassen
 * die übrigen Filterwerte unberührt.
 */
final class ShiftPlanPersonFilterFlagsTest extends FeatureTestCase
{
    #[Test]
    public function show_freelancers_flag_is_persisted_on_shift_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(route('update.user.calendar.filter.show-freelancers', $user), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_freelancers' => false,
        ])->assertSuccessful();

        $filter = $user->userFilters()->shiftFilter()->first();
        $this->assertNotNull($filter);
        $this->assertFalse($filter->show_freelancers);

        $this->patch(route('update.user.calendar.filter.show-freelancers', $user), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_freelancers' => true,
        ])->assertSuccessful();

        $this->assertTrue($user->userFilters()->shiftFilter()->first()->show_freelancers);
    }

    #[Test]
    public function show_freelancers_defaults_to_true_and_keeps_other_filter_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'craft_ids' => [3, 7],
            'show_only_users_with_open_violations' => true,
        ]);

        $this->assertTrue($user->userFilters()->shiftFilter()->first()->show_freelancers);

        $this->patch(route('update.user.calendar.filter.show-freelancers', $user), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_freelancers' => false,
        ])->assertSuccessful();

        $filter = $user->userFilters()->shiftFilter()->first();
        $this->assertFalse($filter->show_freelancers);
        $this->assertSame([3, 7], $filter->craft_ids);
        $this->assertTrue($filter->show_only_users_with_open_violations);
        $this->assertCount(1, $user->userFilters()->shiftFilter()->get());
    }

    #[Test]
    public function show_freelancers_flag_rejects_unknown_filter_types(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(route('update.user.calendar.filter.show-freelancers', $user), [
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
            'show_freelancers' => false,
        ])->assertUnprocessable();
    }

    #[Test]
    public function show_freelancers_flag_cannot_be_set_for_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $this->patch(route('update.user.calendar.filter.show-freelancers', $other), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_freelancers' => false,
        ])->assertForbidden();

        $this->assertNull($other->userFilters()->shiftFilter()->first());
    }

    #[Test]
    public function open_violations_flag_leaves_show_freelancers_untouched(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(route('update.user.calendar.filter.show-freelancers', $user), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_freelancers' => false,
        ])->assertSuccessful();

        $this->patch(route('update.user.calendar.filter.open-violations', $user), [
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'show_only_users_with_open_violations' => true,
        ])->assertSuccessful();

        $filter = $user->userFilters()->shiftFilter()->first();
        $this->assertFalse($filter->show_freelancers);
        $this->assertTrue($filter->show_only_users_with_open_violations);
    }
}
