<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Tag-Einstellungen im Inventar (Tag-Gruppen und Tags anlegen, ändern,
 * sortieren, löschen). Alle Endpunkte hängen an der Berechtigung "inventory.settings".
 */
final class InventoryTagSettingsTest extends FeatureTestCase
{
    private function actingAsSettingsUser(): User
    {
        return $this->actingAsUserWith(PermissionEnum::INVENTORY_SETTINGS->value);
    }

    #[Test]
    public function the_tag_settings_page_lists_groups_and_tags(): void
    {
        $this->actingAsSettingsUser();
        $group = InventoryTagGroup::factory()->create(['position' => 1]);
        InventoryTag::factory()->create(['inventory_tag_group_id' => $group->id, 'color' => '#ff0000']);

        $this->get(route('settings.inventory-tags.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('InventorySetting/TagGroupIndex')
                ->has('tagGroups')
                ->has('tags'));
    }

    #[Test]
    public function a_new_tag_group_is_appended_at_the_next_position(): void
    {
        $this->actingAsSettingsUser();
        $maxPosition = (int) InventoryTagGroup::max('position');

        $this->post(route('settings.inventory-tag-groups.store'), ['name' => 'Licht'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_tag_groups', [
            'name' => 'Licht',
            'position' => $maxPosition + 1,
        ]);
    }

    #[Test]
    public function a_tag_group_needs_a_name(): void
    {
        $this->actingAsSettingsUser();

        $this->post(route('settings.inventory-tag-groups.store'), [])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function a_restricted_tag_is_stored_with_its_allowed_users(): void
    {
        $this->actingAsSettingsUser();
        $group = InventoryTagGroup::factory()->create();
        $allowedUser = User::factory()->create();

        $this->post(route('settings.inventory-tags.store'), [
            'name' => 'Nur Tonabteilung',
            'color' => '#123456',
            'has_restricted_permissions' => true,
            'permission_mode' => 'restricted_edit',
            'inventory_tag_group_id' => $group->id,
            'user_ids' => [$allowedUser->id],
        ])->assertRedirect()->assertSessionHas('success');

        $tag = InventoryTag::query()->where('name', 'Nur Tonabteilung')->sole();
        $this->assertTrue($tag->has_restricted_permissions);
        $this->assertSame($group->id, $tag->inventory_tag_group_id);
        $this->assertSame(1, $tag->position);
        $this->assertSame([$allowedUser->id], $tag->allowedUsers->pluck('id')->all());
    }

    #[Test]
    public function an_unrestricted_tag_ignores_given_users(): void
    {
        $this->actingAsSettingsUser();
        $user = User::factory()->create();

        $this->post(route('settings.inventory-tags.store'), [
            'name' => 'Offen',
            'color' => '#000000',
            'has_restricted_permissions' => false,
            'user_ids' => [$user->id],
        ])->assertRedirect();

        $tag = InventoryTag::query()->where('name', 'Offen')->sole();
        $this->assertCount(0, $tag->allowedUsers);
    }

    #[Test]
    public function updating_a_tag_to_unrestricted_drops_its_allowed_users(): void
    {
        $this->actingAsSettingsUser();
        $user = User::factory()->create();
        $tag = InventoryTag::factory()->create([
            'color' => '#111111',
            'has_restricted_permissions' => true,
        ]);
        $tag->allowedUsers()->attach($user->id);

        $this->patch(route('settings.inventory-tags.update', $tag), [
            'id' => $tag->id,
            'name' => 'Umbenannt',
            'color' => '#222222',
            'has_restricted_permissions' => false,
        ])->assertRedirect()->assertSessionHas('success');

        $tag->refresh();
        $this->assertSame('Umbenannt', $tag->name);
        $this->assertSame('#222222', $tag->color);
        $this->assertFalse($tag->has_restricted_permissions);
        $this->assertCount(0, $tag->allowedUsers);
    }

    #[Test]
    public function tag_groups_are_reordered_starting_at_position_one(): void
    {
        $this->actingAsSettingsUser();
        $first = InventoryTagGroup::factory()->create(['position' => 1]);
        $second = InventoryTagGroup::factory()->create(['position' => 2]);

        $this->post(route('settings.inventory-tag-groups.reorder'), ['ordered_ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    #[Test]
    public function tags_are_reordered_starting_at_position_one(): void
    {
        $this->actingAsSettingsUser();
        $first = InventoryTag::factory()->create(['color' => '#000000', 'position' => 1]);
        $second = InventoryTag::factory()->create(['color' => '#000000', 'position' => 2]);

        $this->post(route('settings.inventory-tags.reorder'), ['ordered_ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    #[Test]
    public function deleting_a_tag_group_keeps_its_tags_without_group(): void
    {
        $this->actingAsSettingsUser();
        $group = InventoryTagGroup::factory()->create();
        $tag = InventoryTag::factory()->create(['color' => '#000000', 'inventory_tag_group_id' => $group->id]);

        $this->delete(route('settings.inventory-tag-groups.destroy', $group))->assertOk();

        $this->assertModelMissing($group);
        $this->assertModelExists($tag);
        $this->assertNull($tag->fresh()->inventory_tag_group_id);
    }

    #[Test]
    public function a_tag_is_deleted(): void
    {
        $this->actingAsSettingsUser();
        $tag = InventoryTag::factory()->create(['color' => '#000000']);

        $this->delete(route('settings.inventory-tags.destroy', $tag))->assertOk();

        $this->assertModelMissing($tag);
    }

    #[Test]
    public function a_tag_group_can_be_renamed(): void
    {
        $this->actingAsSettingsUser();
        $group = InventoryTagGroup::factory()->create(['name' => 'Ton', 'position' => 3]);

        $this->put(route('settings.inventory-tag-groups.update', $group), ['id' => $group->id, 'name' => 'Audio'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $group->refresh();
        $this->assertSame('Audio', $group->name);
        $this->assertSame(3, $group->position);
    }

    #[Test]
    public function renaming_a_tag_group_needs_a_name(): void
    {
        $this->actingAsSettingsUser();
        $group = InventoryTagGroup::factory()->create(['name' => 'Ton']);

        $this->put(route('settings.inventory-tag-groups.update', $group), ['id' => $group->id, 'name' => ''])
            ->assertSessionHasErrors('name');
        $this->assertSame('Ton', $group->refresh()->name);
    }

    #[Test]
    public function users_without_the_settings_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());
        $group = InventoryTagGroup::factory()->create();
        $tag = InventoryTag::factory()->create(['color' => '#000000']);

        $this->get(route('settings.inventory-tags.index'))->assertForbidden();
        $this->post(route('settings.inventory-tag-groups.store'), ['name' => 'X'])->assertForbidden();
        $this->post(route('settings.inventory-tags.store'), ['name' => 'X', 'color' => '#000000'])
            ->assertForbidden();
        $this->patch(route('settings.inventory-tags.update', $tag), [
            'id' => $tag->id,
            'name' => 'X',
            'color' => '#000000',
        ])->assertForbidden();
        $this->post(route('settings.inventory-tag-groups.reorder'), ['ordered_ids' => [$group->id]])
            ->assertForbidden();
        $this->post(route('settings.inventory-tags.reorder'), ['ordered_ids' => [$tag->id]])->assertForbidden();
        $this->put(route('settings.inventory-tag-groups.update', $group), ['id' => $group->id, 'name' => 'X'])
            ->assertForbidden();
        $this->delete(route('settings.inventory-tag-groups.destroy', $group))->assertForbidden();
        $this->delete(route('settings.inventory-tags.destroy', $tag))->assertForbidden();

        $this->assertModelExists($group);
        $this->assertModelExists($tag);
    }
}
