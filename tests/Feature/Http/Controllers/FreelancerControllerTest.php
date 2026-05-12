<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Freelancer\Models\Freelancer;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * FreelancerController uses Meilisearch in show(). Only test methods without search.
 */
final class FreelancerControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_create_freelancer(): void
    {
        $this->post(route('freelancer.add'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_create_freelancer(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('freelancer.add'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302, 409]);
        $this->assertDatabaseHas('freelancers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('freelancer.add'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function admin_can_update_freelancer(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->patch(route('freelancer.update', $fl), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'position' => 'Engineer',
            'business' => 'Biz',
            'phone_number' => '123',
            'street' => 'Some St',
            'zip_code' => '12345',
            'location' => 'City',
            'note' => 'note',
        ]);

        $response->assertOk();
        $this->assertSame('New', $fl->fresh()->first_name);
    }

    #[Test]
    public function update_validates_required_fields(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->patch(route('freelancer.update', $fl), []);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function admin_can_destroy_freelancer(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->delete(route('freelancer.destroy', $fl));

        $response->assertRedirect();
        $this->assertDatabaseMissing('freelancers', ['id' => $fl->id]);
    }

    #[Test]
    public function guest_cannot_destroy_freelancer(): void
    {
        $fl = Freelancer::factory()->create();
        $this->delete(route('freelancer.destroy', $fl))
            ->assertRedirect(route('login'));
    }
}
