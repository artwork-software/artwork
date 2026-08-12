<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class IndividualTimeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('add.update.individualTimesAndShiftPlanComment'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_individual_time(): void
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [
                [
                    'title' => 'Lunch',
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                    'start_date' => '2026-01-01',
                    'break_minutes' => 0,
                ],
            ],
            'shift_comment' => [
                'comment' => 'Test',
                'date' => '2026-01-01',
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('individual_times', [
            'title' => 'Lunch',
            'timeable_id' => $user->id,
        ]);
    }

    #[Test]
    public function admin_can_store_individual_time_without_shift_comment(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [
                [
                    'title' => 'Single save',
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                    'start_date' => '2026-01-01',
                    'break_minutes' => 0,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('shift_comment', null);
        $this->assertDatabaseHas('individual_times', [
            'title' => 'Single save',
            'timeable_id' => $user->id,
        ]);
        $this->assertDatabaseCount('shift_plan_comments', 0);
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $user = User::factory()->create();
        $it = IndividualTime::query()->forceCreate([
            'timeable_type' => User::class,
            'timeable_id' => $user->id,
            'title' => 'X',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'break_minutes' => 0,
            'working_time_minutes' => 60,
            'full_day' => false,
        ]);

        $this->delete(route('delete.individualTimes', $it))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_single(): void
    {
        $admin = $this->actingAsAdmin();
        $it = IndividualTime::query()->forceCreate([
            'timeable_type' => User::class,
            'timeable_id' => $admin->id,
            'title' => 'Old',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'break_minutes' => 0,
            'working_time_minutes' => 60,
            'full_day' => false,
        ]);

        $response = $this->patch(route('individual-times.update-single', $it), [
            'title' => 'New Title',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'break_minutes' => 15,
        ]);

        $response->assertRedirect();
        $this->assertSame('New Title', $it->fresh()->title);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $admin = $this->actingAsAdmin();
        $it = IndividualTime::query()->forceCreate([
            'timeable_type' => User::class,
            'timeable_id' => $admin->id,
            'title' => 'X',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'break_minutes' => 0,
            'working_time_minutes' => 60,
            'full_day' => false,
        ]);

        $response = $this->delete(route('delete.individualTimes', $it));

        $response->assertOk();
        $this->assertDatabaseMissing('individual_times', ['id' => $it->id]);
    }
}
