<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class WorkerControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_search_workers(): void
    {
        $this->postJson(route('worker.scoutSearch'), ['query' => 'foo'])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_search_workers(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('worker.scoutSearch'), ['query' => 'nobody']);

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function authenticated_user_can_search_workers(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('worker.scoutSearch'), ['query' => 'someone']);

        $response->assertOk();
        $this->assertIsArray($response->json());
    }
}
