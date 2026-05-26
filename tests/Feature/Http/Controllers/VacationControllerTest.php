<?php

namespace Tests\Feature\Http\Controllers;

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
}
