<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserShiftPlanQualificationSortTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_qualification_sort_setting(): void
    {
        $user = User::factory()->create();

        $this->patch(
            route('user.update.sort_workers_by_qualification', $user),
            ['sort_workers_by_qualification' => false]
        )->assertRedirect(route('login'));
    }

    #[Test]
    public function user_can_toggle_qualification_sort_setting(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(
            route('user.update.sort_workers_by_qualification', $user),
            ['sort_workers_by_qualification' => false]
        )->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->sort_workers_by_qualification);
    }

    #[Test]
    public function user_cannot_update_another_users_qualification_sort_setting(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['sort_workers_by_qualification' => true]);
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.sort_workers_by_qualification', $otherUser),
            ['sort_workers_by_qualification' => false]
        )->assertForbidden();

        $this->assertTrue($otherUser->fresh()->sort_workers_by_qualification);
    }

    #[Test]
    public function qualification_sort_setting_defaults_to_true(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->fresh()->sort_workers_by_qualification);
    }

    #[Test]
    public function non_boolean_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.sort_workers_by_qualification', $user),
            ['sort_workers_by_qualification' => 'not-a-boolean']
        )->assertUnprocessable()->assertJsonValidationErrors('sort_workers_by_qualification');
    }

    #[Test]
    public function user_can_update_closed_qualification_groups(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(
            route('user.update.closed_qualification_groups', $user),
            ['closed_qualification_groups' => ['3_7', '3_none']]
        )->assertSessionHasNoErrors();

        $this->assertSame(['3_7', '3_none'], $user->fresh()->closed_qualification_groups);
    }

    #[Test]
    public function non_array_closed_groups_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.closed_qualification_groups', $user),
            ['closed_qualification_groups' => 'not-an-array']
        )->assertUnprocessable()->assertJsonValidationErrors('closed_qualification_groups');
    }

    #[Test]
    public function user_cannot_update_another_users_closed_qualification_groups(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['closed_qualification_groups' => []]);
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.closed_qualification_groups', $otherUser),
            ['closed_qualification_groups' => ['3_7']]
        )->assertForbidden();

        $this->assertSame([], $otherUser->fresh()->closed_qualification_groups);
    }

    #[Test]
    public function user_can_toggle_show_qualification_duplicates_setting(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(
            route('user.update.show_qualification_duplicates', $user),
            ['show_qualification_duplicates' => false]
        )->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->show_qualification_duplicates);
    }

    #[Test]
    public function show_qualification_duplicates_defaults_to_true(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->fresh()->show_qualification_duplicates);
    }

    #[Test]
    public function user_cannot_update_another_users_show_qualification_duplicates_setting(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['show_qualification_duplicates' => true]);
        $this->actingAs($user);

        $this->patchJson(
            route('user.update.show_qualification_duplicates', $otherUser),
            ['show_qualification_duplicates' => false]
        )->assertForbidden();

        $this->assertTrue($otherUser->fresh()->show_qualification_duplicates);
    }

    #[Test]
    public function user_cannot_update_another_users_modal_backdrop_setting(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['show_modal_backdrop' => true]);
        $this->actingAs($user);

        $this->patchJson(
            route('user.modal.backdrop.update', $otherUser),
            ['show_modal_backdrop' => false]
        )->assertForbidden();

        $this->assertTrue($otherUser->fresh()->show_modal_backdrop);
    }
}
