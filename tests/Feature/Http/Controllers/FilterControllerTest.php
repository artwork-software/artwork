<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class FilterControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_filters_index(): void
    {
        $this->getJson('/filters')
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_access_filters_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/filters');

        $response->assertOk();
    }

    #[Test]
    public function authenticated_user_can_access_filters_index(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/filters');

        $response->assertOk();
    }
}
