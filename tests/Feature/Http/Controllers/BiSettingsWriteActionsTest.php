<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * BI-Einstellungen: Reihenfolge der Besucher*innen-Kategorien sowie BI-Felder
 * bearbeiten, sortieren und löschen.
 */
final class BiSettingsWriteActionsTest extends FeatureTestCase
{
    private function createBiField(array $attributes = []): Component
    {
        return Component::factory()->create(array_merge([
            'type' => 'TextField',
            'data' => [],
            'is_bi_field' => true,
            'bi_order' => 1,
        ], $attributes));
    }

    // ---------- Besucher*innen-Kategorien ----------

    #[Test]
    public function audience_categories_are_reordered_by_the_given_ids(): void
    {
        $this->actingAsAdmin();
        $first = BiAudienceCategory::factory()->create(['pricing_type' => 'full', 'position' => 0]);
        $second = BiAudienceCategory::factory()->create(['pricing_type' => 'reduced', 'position' => 1]);
        $third = BiAudienceCategory::factory()->create(['pricing_type' => 'free', 'position' => 2]);

        $this->postJson(route('bi.audience-categories.order'), [
            'ordered_ids' => [$third->id, $first->id, $second->id],
        ])->assertOk();

        $this->assertSame(0, (int) $third->fresh()->position);
        $this->assertSame(1, (int) $first->fresh()->position);
        $this->assertSame(2, (int) $second->fresh()->position);
    }

    #[Test]
    public function reordering_audience_categories_validates_the_ids(): void
    {
        $this->actingAsAdmin();
        $category = BiAudienceCategory::factory()->create(['pricing_type' => 'full', 'position' => 3]);

        $this->postJson(route('bi.audience-categories.order'), [])->assertUnprocessable();
        $this->postJson(route('bi.audience-categories.order'), ['ordered_ids' => [999999999]])
            ->assertUnprocessable();

        $this->assertSame(3, (int) $category->fresh()->position);
    }

    // ---------- BI-Felder bearbeiten ----------

    #[Test]
    public function a_bi_field_can_be_renamed_and_its_users_replaced(): void
    {
        $this->actingAsAdmin();
        $field = $this->createBiField(['name' => 'Alt']);
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $field->users()->attach($oldUser->id, ['can_write' => true]);

        $this->patch(route('bi.settings.update', $field), [
            'name' => 'Neu',
            'data' => [],
            'permission_type' => 'someSeeSomeEdit',
            'users' => [['user_id' => $newUser->id, 'can_write' => false]],
            'departments' => [],
        ])->assertRedirect();

        $field->refresh();
        $this->assertSame('Neu', $field->name);
        $this->assertSame('someSeeSomeEdit', $field->permission_type);
        $this->assertSame([$newUser->id], $field->users()->pluck('users.id')->all());
    }

    #[Test]
    public function a_broken_user_entry_is_rejected_without_dropping_existing_assignments(): void
    {
        $this->actingAsAdmin();
        $field = $this->createBiField(['name' => 'Alt']);
        $user = User::factory()->create();
        $field->users()->attach($user->id, ['can_write' => true]);

        $this->patchJson(route('bi.settings.update', $field), [
            'name' => 'Neu',
            'users' => [['can_write' => true]],
        ])->assertUnprocessable()->assertJsonValidationErrors('users.0.user_id');

        $this->assertSame('Alt', $field->fresh()->name);
        $this->assertSame([$user->id], $field->users()->pluck('users.id')->all());
    }

    #[Test]
    public function updating_a_non_bi_component_via_the_bi_settings_is_rejected(): void
    {
        $this->actingAsAdmin();
        $tabComponent = Component::factory()->create(['name' => 'Tab-Feld', 'type' => 'TextField', 'is_bi_field' => false]);

        $this->patch(route('bi.settings.update', $tabComponent), ['name' => 'Gekapert'])->assertNotFound();

        $this->assertSame('Tab-Feld', $tabComponent->fresh()->name);
    }

    #[Test]
    public function updating_a_bi_field_requires_a_name(): void
    {
        $this->actingAsAdmin();
        $field = $this->createBiField(['name' => 'Alt']);

        $this->patchJson(route('bi.settings.update', $field), ['name' => ''])->assertUnprocessable();

        $this->assertSame('Alt', $field->fresh()->name);
    }

    // ---------- BI-Felder sortieren ----------

    #[Test]
    public function bi_fields_are_reordered_and_other_components_are_left_alone(): void
    {
        $this->actingAsAdmin();
        $first = $this->createBiField(['bi_order' => 1]);
        $second = $this->createBiField(['bi_order' => 2]);
        $tabComponent = Component::factory()->create(['type' => 'TextField', 'is_bi_field' => false, 'bi_order' => null]);

        $this->post(route('bi.settings.update-order'), [
            'ordered_ids' => [$second->id, $first->id, $tabComponent->id],
        ])->assertRedirect();

        $this->assertSame(1, (int) $second->fresh()->bi_order);
        $this->assertSame(2, (int) $first->fresh()->bi_order);
        $this->assertNull($tabComponent->fresh()->bi_order);
    }

    #[Test]
    public function reordering_bi_fields_validates_the_ids(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('bi.settings.update-order'), ['ordered_ids' => [999999999]])->assertUnprocessable();
        $this->postJson(route('bi.settings.update-order'), [])->assertUnprocessable();
    }

    // ---------- BI-Felder löschen ----------

    #[Test]
    public function deleting_a_bi_field_removes_it_with_its_values_and_assignments(): void
    {
        $this->actingAsAdmin();
        $field = $this->createBiField();
        $field->users()->attach(User::factory()->create()->id, ['can_write' => true]);
        ProjectComponentValue::factory()->create(['component_id' => $field->id, 'data' => ['text' => 'x']]);

        $this->delete(route('bi.settings.destroy', $field))->assertRedirect();

        $this->assertDatabaseMissing('components', ['id' => $field->id]);
        $this->assertDatabaseMissing('project_component_values', ['component_id' => $field->id]);
        $this->assertDatabaseMissing('component_user', ['component_id' => $field->id]);
    }

    #[Test]
    public function deleting_a_bi_field_used_in_a_print_layout_and_a_folder_does_not_fail(): void
    {
        $this->actingAsAdmin();
        $field = $this->createBiField();
        PrintLayoutComponents::factory()->create(['component_id' => $field->id]);
        DisclosureComponents::factory()->create(['component_id' => $field->id]);

        $this->delete(route('bi.settings.destroy', $field))->assertRedirect();

        $this->assertDatabaseMissing('components', ['id' => $field->id]);
        $this->assertDatabaseMissing('print_layout_components', ['component_id' => $field->id]);
        $this->assertDatabaseMissing('disclosure_components', ['component_id' => $field->id]);
    }

    #[Test]
    public function deleting_a_non_bi_component_via_the_bi_settings_is_rejected(): void
    {
        $this->actingAsAdmin();
        $tabComponent = Component::factory()->create(['type' => 'TextField', 'is_bi_field' => false]);

        $this->delete(route('bi.settings.destroy', $tabComponent))->assertNotFound();

        $this->assertDatabaseHas('components', ['id' => $tabComponent->id]);
    }
}
