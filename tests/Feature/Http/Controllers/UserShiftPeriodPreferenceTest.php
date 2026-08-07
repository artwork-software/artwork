<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserShiftPeriodPreferenceTest extends FeatureTestCase
{
    #[Test]
    public function user_can_toggle_shift_period_on_start_date_change(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.shift_period_on_start_date_change', $user),
            ['shift_period_on_start_date_change' => true]
        )->assertSuccessful();
        $this->assertTrue($user->fresh()->shift_period_on_start_date_change);

        $this->patchJson(
            route('user.update.shift_period_on_start_date_change', $user),
            ['shift_period_on_start_date_change' => false]
        )->assertSuccessful();
        $this->assertFalse($user->fresh()->shift_period_on_start_date_change);
    }

    #[Test]
    public function user_cannot_change_foreign_preference(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.shift_period_on_start_date_change', $otherUser),
            ['shift_period_on_start_date_change' => true]
        )->assertForbidden();
        $this->assertFalse($otherUser->fresh()->shift_period_on_start_date_change);
    }

    #[Test]
    public function guest_cannot_change_preference(): void
    {
        $user = User::factory()->create();

        $this->patchJson(
            route('user.update.shift_period_on_start_date_change', $user),
            ['shift_period_on_start_date_change' => true]
        )->assertUnauthorized();
    }
}
