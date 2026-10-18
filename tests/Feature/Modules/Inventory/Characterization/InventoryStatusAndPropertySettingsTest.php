<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Inventar-Einstellungen für Artikelstatus und Eigenschaften: Seiten,
 * Anlegen/Ändern/Sortieren/Löschen. Pflicht-Eigenschaften (is_deletable=false) sind nicht
 * löschbar. Alles hängt an "inventory.settings".
 */
final class InventoryStatusAndPropertySettingsTest extends FeatureTestCase
{
    private function actingAsSettingsUser(): User
    {
        return $this->actingAsUserWith(PermissionEnum::INVENTORY_SETTINGS->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Leistung',
            'tooltip_text' => 'in Watt',
            'type' => 'number',
            'is_filterable' => true,
            'show_in_list' => false,
            'is_required' => false,
            'select_values' => null,
            'across_articles' => false,
            'individual_value' => true,
        ], $overrides);
    }

    #[Test]
    public function the_status_settings_page_lists_statuses(): void
    {
        $this->actingAsSettingsUser();

        $this->get(route('inventory-management.settings.status'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('InventorySetting/ArticleStatusSettings')
                ->has('statuses'));
    }

    #[Test]
    public function a_status_name_and_color_can_be_changed(): void
    {
        $this->actingAsSettingsUser();
        $status = InventoryArticleStatus::factory()->create(['color' => '#000000']);

        $this->put(route('inventory.article-status.update', $status), ['name' => 'In Reparatur', 'color' => '#ff0000'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_article_statuses', [
            'id' => $status->id,
            'name' => 'In Reparatur',
            'color' => '#ff0000',
        ]);
    }

    #[Test]
    public function a_status_update_requires_name_and_color(): void
    {
        $this->actingAsSettingsUser();
        $status = InventoryArticleStatus::factory()->create(['color' => '#000000']);

        $this->put(route('inventory.article-status.update', $status), [])
            ->assertSessionHasErrors(['name', 'color']);
    }

    #[Test]
    public function statuses_are_reordered_starting_at_zero(): void
    {
        $this->actingAsSettingsUser();
        $first = InventoryArticleStatus::factory()->create(['color' => '#000000', 'order' => 0]);
        $second = InventoryArticleStatus::factory()->create(['color' => '#000000', 'order' => 1]);

        $this->post(route('inventory.article-status.reorder'), ['ids' => [$second->id, $first->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $second->fresh()->order);
        $this->assertSame(1, (int) $first->fresh()->order);
    }

    #[Test]
    public function the_property_settings_page_lists_properties_paginated(): void
    {
        $this->actingAsSettingsUser();

        $this->get(route('inventory-management.settings.properties'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('InventorySetting/Properties')
                ->has('properties.data'));
    }

    #[Test]
    public function a_property_is_created(): void
    {
        $this->actingAsSettingsUser();

        $this->post(route('inventory-management.settings.properties.create'), $this->propertyPayload())
            ->assertOk();

        $property = InventoryArticleProperties::query()->where('name', 'Leistung')->sole();
        $this->assertSame('number', $property->type);
        $this->assertTrue($property->is_filterable);
        $this->assertTrue($property->individual_value);
    }

    #[Test]
    public function a_property_needs_type_and_flags(): void
    {
        $this->actingAsSettingsUser();

        $this->post(route('inventory-management.settings.properties.create'), ['name' => 'Ohne Typ'])
            ->assertSessionHasErrors(['type', 'is_filterable', 'show_in_list', 'is_required']);

        $this->assertDatabaseMissing('inventory_article_properties', ['name' => 'Ohne Typ']);
    }

    #[Test]
    public function a_property_is_updated(): void
    {
        $this->actingAsSettingsUser();
        $property = InventoryArticleProperties::factory()->create(['type' => 'string']);

        $this->patch(
            route('inventory-management.settings.properties.update', $property),
            $this->propertyPayload(['id' => $property->id, 'name' => 'Gewicht', 'show_in_list' => true])
        )->assertOk();

        $property->refresh();
        $this->assertSame('Gewicht', $property->name);
        $this->assertSame('number', $property->type);
        $this->assertTrue($property->show_in_list);
    }

    #[Test]
    public function properties_are_reordered_from_the_page_offset(): void
    {
        $this->actingAsSettingsUser();
        $first = InventoryArticleProperties::factory()->create();
        $second = InventoryArticleProperties::factory()->create();

        $this->post(route('inventory-management.settings.properties.reorder'), [
            'ids' => [$second->id, $first->id],
            'start' => 50,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(50, $second->fresh()->order);
        $this->assertSame(51, $first->fresh()->order);
    }

    #[Test]
    public function a_deletable_property_is_deleted(): void
    {
        $this->actingAsSettingsUser();
        $property = InventoryArticleProperties::factory()->create(['is_deletable' => true]);

        $this->delete(route('inventory-management.settings.properties.delete', $property))->assertOk();

        $this->assertModelMissing($property);
    }

    #[Test]
    public function a_mandatory_property_cannot_be_deleted(): void
    {
        $this->actingAsSettingsUser();
        $property = InventoryArticleProperties::factory()->create(['is_deletable' => false]);

        $this->delete(route('inventory-management.settings.properties.delete', $property))->assertForbidden();

        $this->assertModelExists($property);
    }

    #[Test]
    public function users_without_the_settings_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());
        $status = InventoryArticleStatus::factory()->create(['color' => '#000000', 'name' => 'Unverändert']);
        $property = InventoryArticleProperties::factory()->create(['is_deletable' => true]);

        $this->get(route('inventory-management.settings.status'))->assertForbidden();
        $this->put(route('inventory.article-status.update', $status), ['name' => 'X', 'color' => '#ffffff'])
            ->assertForbidden();
        $this->post(route('inventory.article-status.reorder'), ['ids' => [$status->id]])->assertForbidden();
        $this->get(route('inventory-management.settings.properties'))->assertForbidden();
        $this->post(route('inventory-management.settings.properties.create'), $this->propertyPayload())
            ->assertForbidden();
        $this->patch(
            route('inventory-management.settings.properties.update', $property),
            $this->propertyPayload(['id' => $property->id])
        )->assertForbidden();
        $this->post(route('inventory-management.settings.properties.reorder'), ['ids' => [$property->id]])
            ->assertForbidden();
        $this->delete(route('inventory-management.settings.properties.delete', $property))->assertForbidden();

        $this->assertSame('Unverändert', $status->fresh()->name);
        $this->assertModelExists($property);
    }
}
