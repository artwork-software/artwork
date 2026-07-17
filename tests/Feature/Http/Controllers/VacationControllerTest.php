<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class VacationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $user = User::factory()->create();
        $this->post(route('user.vacation.add', $user), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $vacation = Vacation::factory()->create();
        $this->delete(route('delete.vacation', $vacation))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_vacation(): void
    {
        $this->actingAsAdmin();
        $vacation = Vacation::factory()->create();

        $response = $this->delete(route('delete.vacation', $vacation));

        $response->assertRedirect();
    }

    /**
     * Verfügbarkeitsstatus (Arbeitsfrei/Frei/…) ist Planungssache: ohne Planungs- bzw.
     * Verfügbarkeits-Permission darf niemand ihn setzen — auch nicht für sich selbst.
     */
    #[Test]
    public function user_without_permission_cannot_set_own_availability_status(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->patchJson(route('user.check.vacation', $user), [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertForbidden();

        $this->assertDatabaseMissing('vacations', [
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_set_availability_status_of_others(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $target = User::factory()->create();
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $payload = [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ];

        $this->patchJson(route('user.check.vacation', $target), $payload)->assertForbidden();
        $this->patchJson(route('freelancer.check.vacation', $freelancer), $payload)->assertForbidden();
        $this->patchJson(route('service_provider.check.vacation', $serviceProvider), $payload)
            ->assertForbidden();
    }

    #[Test]
    public function shift_planner_can_set_availability_status(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $target = User::factory()->create();

        $this->patchJson(route('user.check.vacation', $target), [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertSuccessful();

        $this->assertDatabaseHas('vacations', [
            'vacationer_id' => $target->id,
            'vacationer_type' => User::class,
            'comment' => 'OFF_WORK',
        ]);
    }
}
