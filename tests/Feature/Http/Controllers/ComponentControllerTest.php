<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ComponentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('component.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('component.index'))->assertOk();
    }

    #[Test]
    public function admin_can_show_component(): void
    {
        $this->actingAsAdmin();
        $component = Component::query()->forceCreate([
            'name' => 'X',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);

        $this->get(route('component.show', $component))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('component.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('component.store'), [
            'name' => 'NewComp',
            'type' => 'TextField',
            'data' => [],
            'permission_type' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('components', ['name' => 'NewComp']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $component = Component::query()->forceCreate([
            'name' => 'Old',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);

        $response = $this->patch(route('component.update', $component), [
            'name' => 'New',
            'data' => [],
            'permission_type' => null,
        ]);

        $response->assertOk();
        $this->assertSame('New', $component->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $component = Component::query()->forceCreate([
            'name' => 'X',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);

        $response = $this->delete(route('component.destroy', $component));

        $response->assertOk();
        $this->assertDatabaseMissing('components', ['id' => $component->id]);
    }
}
