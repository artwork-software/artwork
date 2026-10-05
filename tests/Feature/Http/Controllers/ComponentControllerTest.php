<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\DisclosureComponents;
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

    /**
     * disclosure_components.disclosure_id hat kein ON DELETE CASCADE: ein Ordner mit Inhalt darf nicht am
     * Fremdschlüssel scheitern. Die enthaltenen Komponenten bleiben in der Bibliothek erhalten.
     */
    #[Test]
    public function admin_can_destroy_non_empty_folder(): void
    {
        $this->actingAsAdmin();
        $folder = Component::query()->forceCreate([
            'name' => 'Ordner',
            'type' => 'DisclosureComponent',
            'data' => ['label' => 'Ordner'],
            'special' => false,
            'sidebar_enabled' => true,
        ]);
        $child = Component::query()->forceCreate([
            'name' => 'Inhalt',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);
        $placement = DisclosureComponents::query()->create([
            'disclosure_id' => $folder->id,
            'component_id' => $child->id,
            'order' => 1,
        ]);

        $response = $this->delete(route('component.destroy', $folder));

        $response->assertOk();
        $this->assertDatabaseMissing('components', ['id' => $folder->id]);
        $this->assertDatabaseMissing('disclosure_components', ['id' => $placement->id]);
        $this->assertDatabaseHas('components', ['id' => $child->id]);
    }
}
