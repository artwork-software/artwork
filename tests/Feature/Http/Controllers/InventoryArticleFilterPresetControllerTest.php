<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Inventory\Models\InventoryArticleFilterPreset;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class InventoryArticleFilterPresetControllerTest extends FeatureTestCase
{
    #[Test]
    public function store_creates_preset_for_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('inventory.filter-presets.store'), [
                'name' => 'Mein Preset',
                'filters' => [],
                'tag_ids' => [],
            ])
            ->assertOk();

        $this->assertDatabaseHas('inventory_article_filter_presets', [
            'name' => 'Mein Preset',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function store_rejects_unknown_category_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('inventory.filter-presets.store'), [
                'name' => 'Phantom',
                'inventory_category_id' => 999999,
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function foreign_users_can_neither_update_nor_delete_presets(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $preset = InventoryArticleFilterPreset::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Preset',
            'is_default' => false,
            'filters' => [],
            'tag_ids' => [],
        ]);

        $this->actingAs($other)
            ->putJson(route('inventory.filter-presets.update', $preset), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($other)
            ->deleteJson(route('inventory.filter-presets.destroy', $preset))
            ->assertForbidden();

        $this->assertDatabaseHas('inventory_article_filter_presets', [
            'id' => $preset->id,
            'name' => 'Owner Preset',
        ]);
    }
}
