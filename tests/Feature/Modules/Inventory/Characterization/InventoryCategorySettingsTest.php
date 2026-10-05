<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Kategorie-Einstellungen im Inventar: Einstellungsseite, Kategorie mit
 * Eigenschaften und Unterkategorien anlegen bzw. ändern, Unterkategorie löschen (Artikel bleiben
 * ohne Unterkategorie erhalten). Alles hängt an "inventory.settings".
 */
final class InventoryCategorySettingsTest extends FeatureTestCase
{
    private function actingAsSettingsUser(): User
    {
        return $this->actingAsUserWith(PermissionEnum::INVENTORY_SETTINGS->value);
    }

    #[Test]
    public function the_category_settings_page_is_rendered(): void
    {
        $this->actingAsSettingsUser();
        InventoryCategory::factory()->create();

        $this->get(route('inventory-management.settings.category'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('InventorySetting/Categories')
                ->has('categories.data')
                ->has('properties')
                ->has('rooms')
                ->has('manufacturers'));
    }

    #[Test]
    public function a_category_is_created_with_properties_and_sub_categories(): void
    {
        $this->actingAsSettingsUser();
        $categoryProperty = InventoryArticleProperties::factory()->create();
        $subProperty = InventoryArticleProperties::factory()->create();

        $this->post(route('inventory-management.settings.categories.create'), [
            'name' => 'Tontechnik',
            'properties' => [['id' => $categoryProperty->id, 'defaultValue' => true]],
            'subcategories' => [
                ['name' => 'Mikrofone', 'properties' => [['id' => $subProperty->id, 'defaultValue' => 'SM58']]],
            ],
        ])->assertOk();

        $category = InventoryCategory::query()->where('name', 'Tontechnik')->sole();
        $this->assertSame([$categoryProperty->id], $category->properties->pluck('id')->all());
        $this->assertSame('true', $category->properties->first()->pivot->value);

        $subCategory = $category->subCategories()->sole();
        $this->assertSame('Mikrofone', $subCategory->name);
        $this->assertSame('SM58', $subCategory->properties->sole()->pivot->value);
    }

    #[Test]
    public function a_category_name_is_limited_to_55_characters(): void
    {
        $this->actingAsSettingsUser();

        $this->post(route('inventory-management.settings.categories.create'), ['name' => str_repeat('x', 56)])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function updating_a_category_syncs_properties_and_sub_categories(): void
    {
        $this->actingAsSettingsUser();
        $category = InventoryCategory::factory()->create(['name' => 'Alt']);
        $oldProperty = InventoryArticleProperties::factory()->create();
        $newProperty = InventoryArticleProperties::factory()->create();
        $category->properties()->attach($oldProperty->id, ['value' => '', 'position' => 0]);
        $kept = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        $removed = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);

        $this->patch(route('inventory-management.settings.categories.update', $category), [
            'id' => $category->id,
            'name' => 'Neu',
            'properties' => [['id' => $newProperty->id]],
            'subcategories' => [
                ['id' => $kept->id, 'name' => 'Umbenannt'],
                ['name' => 'Ganz neu'],
            ],
        ])->assertOk();

        $category->refresh();
        $this->assertSame('Neu', $category->name);
        $this->assertSame([$newProperty->id], $category->properties->pluck('id')->all());
        $this->assertSame('Umbenannt', $kept->fresh()->name);
        $this->assertModelMissing($removed);
        $this->assertEqualsCanonicalizing(
            ['Umbenannt', 'Ganz neu'],
            $category->subCategories()->pluck('name')->all()
        );
    }

    #[Test]
    public function updating_a_category_without_sub_categories_removes_all_of_them(): void
    {
        $this->actingAsSettingsUser();
        $category = InventoryCategory::factory()->create();
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);

        $this->patch(route('inventory-management.settings.categories.update', $category), [
            'id' => $category->id,
            'name' => $category->name,
        ])->assertOk();

        $this->assertModelMissing($subCategory);
        // Artikel bleiben erhalten, die Unterkategorie wird per FK auf null gesetzt
        $this->assertNotSoftDeleted($article);
        $this->assertNull($article->fresh()->inventory_sub_category_id);
    }

    #[Test]
    public function deleting_a_sub_category_keeps_its_articles_in_the_category(): void
    {
        $this->actingAsSettingsUser();
        $category = InventoryCategory::factory()->create();
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        $property = InventoryArticleProperties::factory()->create();
        $subCategory->properties()->attach($property->id, ['value' => '', 'position' => 0]);
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);

        $this->delete(route('inventory-management.settings.categories.subcategories.delete', $subCategory))
            ->assertOk();

        $this->assertModelMissing($subCategory);
        $this->assertDatabaseMissing('inventory_category_property_values', [
            'inventory_category_propertyable_type' => $subCategory->getMorphClass(),
            'inventory_category_propertyable_id' => $subCategory->id,
        ]);
        $article->refresh();
        $this->assertSame($category->id, $article->inventory_category_id);
        $this->assertNull($article->inventory_sub_category_id);
    }

    #[Test]
    public function users_without_the_settings_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());
        $category = InventoryCategory::factory()->create(['name' => 'Bleibt']);
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);

        $this->get(route('inventory-management.settings.category'))->assertForbidden();
        $this->post(route('inventory-management.settings.categories.create'), ['name' => 'X'])->assertForbidden();
        $this->patch(route('inventory-management.settings.categories.update', $category), [
            'id' => $category->id,
            'name' => 'X',
        ])->assertForbidden();
        $this->delete(route('inventory-management.settings.categories.subcategories.delete', $subCategory))
            ->assertForbidden();
        $this->delete(route('inventory-management.settings.categories.delete', $category))->assertForbidden();

        $this->assertSame('Bleibt', $category->fresh()->name);
        $this->assertModelExists($subCategory);
        $this->assertDatabaseMissing('inventory_categories', ['name' => 'X']);
    }
}
