<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CraftControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_craft(): void
    {
        $this->post(route('craft.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_craft(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('craft.store'), [
            'name' => 'Lighting',
            'abbreviation' => 'LX',
            'color' => '#abcdef',
            'notify_days' => 7,
            'universally_applicable' => true,
            'assignable_by_all' => true,
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crafts', ['name' => 'Lighting']);
    }

    #[Test]
    public function admin_can_store_craft_with_managers(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $response = $this->post(route('craft.store'), [
            'name' => 'Sound',
            'abbreviation' => 'SD',
            'universally_applicable' => false,
            'assignable_by_all' => true,
            'users' => [],
            'managersToBeAssigned' => [
                ['manager_id' => $user->id, 'manager_type' => User::class],
                ['manager_id' => $freelancer->id, 'manager_type' => Freelancer::class],
                ['manager_id' => $serviceProvider->id, 'manager_type' => ServiceProvider::class],
            ],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $craft = Craft::query()->where('name', 'Sound')->firstOrFail();
        $this->assertEqualsCanonicalizing([$user->id], $craft->managingUsers()->pluck('users.id')->all());
        $this->assertEqualsCanonicalizing(
            [$freelancer->id],
            $craft->managingFreelancers()->pluck('freelancers.id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$serviceProvider->id],
            $craft->managingServiceProviders()->pluck('service_providers.id')->all()
        );
    }

    #[Test]
    public function store_craft_rejects_unknown_manager_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('craft.store'), [
            'name' => 'Sound',
            'abbreviation' => 'SD',
            'universally_applicable' => false,
            'assignable_by_all' => true,
            'users' => [],
            'managersToBeAssigned' => [
                ['manager_id' => 1, 'manager_type' => Craft::class],
            ],
        ]);

        $response->assertSessionHasErrors('managersToBeAssigned.0.manager_type');
        $this->assertDatabaseMissing('crafts', ['name' => 'Sound']);
    }

    #[Test]
    public function admin_can_update_craft_managers(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $previousManager = User::factory()->create();
        $craft->managingUsers()->attach($previousManager->id);
        $freelancer = Freelancer::factory()->create();

        $response = $this->patch(route('craft.update', $craft), [
            'name' => $craft->name,
            'abbreviation' => 'UPD',
            'universally_applicable' => false,
            'assignable_by_all' => true,
            'users' => [],
            'managersToBeAssigned' => [
                ['manager_id' => $freelancer->id, 'manager_type' => Freelancer::class],
            ],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $craft->managingUsers()->pluck('users.id')->all());
        $this->assertSame([$freelancer->id], $craft->managingFreelancers()->pluck('freelancers.id')->all());
    }

    #[Test]
    public function admin_can_update_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('craft.update', $craft), [
            'name' => 'New',
            'abbreviation' => 'NEW',
            'color' => '#222',
            'notify_days' => 3,
            'universally_applicable' => false,
            'assignable_by_all' => false,
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertSame('New', $craft->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $response = $this->delete(route('craft.delete', $craft));

        $response->assertRedirect();
        $this->assertDatabaseMissing('crafts', ['id' => $craft->id]);
    }

    #[Test]
    public function admin_can_reorder_crafts(): void
    {
        $this->actingAsAdmin();
        $a = Craft::factory()->create(['position' => 1]);
        $b = Craft::factory()->create(['position' => 2]);

        $response = $this->post(route('craft.reorder'), [
            'crafts' => [
                ['id' => $a->id, 'position' => 2],
                ['id' => $b->id, 'position' => 1],
            ],
        ]);

        $response->assertOk();
    }
}
