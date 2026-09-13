<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
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
