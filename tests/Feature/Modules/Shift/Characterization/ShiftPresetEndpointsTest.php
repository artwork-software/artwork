<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Einzelschicht-Vorlagen (single-shift-presets.index/update/destroy)
 * und der Vorlagen-Gruppen (shift-preset-groups.index/store/update/destroy) fest:
 * Bereich "shift-templates" (view/edit), Qualifikations-Sync mit Menge, Redirects mit Erfolgsmeldung
 * bei den Gruppen und leere 200-Antworten bei den Einzelvorlagen.
 */
final class ShiftPresetEndpointsTest extends FeatureTestCase
{
    private function useGranularShiftSettingsPermissions(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = $enabled;
        $settings->save();
    }

    private function actingAsShiftSettingsUser(array $extraPermissions = []): User
    {
        return $this->actingAsUserWith(array_merge(
            [PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value],
            $extraPermissions
        ));
    }

    private function preset(array $attributes = []): SingleShiftPreset
    {
        return SingleShiftPreset::factory()->create(array_merge([
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'break_duration' => 30,
            'craft_id' => Craft::factory()->create()->id,
        ], $attributes));
    }

    // --- single-shift-presets ----------------------------------------------------------------

    #[Test]
    public function preset_index_renders_the_overview_with_presets_crafts_and_qualifications(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset(['name' => 'Frühdienst']);

        $this->get(route('single-shift-presets.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Shift/SingleShiftPresetOverview')
                ->has('crafts')
                ->has('shiftQualifications')
                ->where('presets', fn ($presets) => collect($presets)->contains('id', $preset->id)));
    }

    #[Test]
    public function preset_index_is_forbidden_without_the_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('single-shift-presets.index'))->assertForbidden();
    }

    #[Test]
    public function preset_update_changes_fields_and_syncs_qualifications_with_quantity(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset(['name' => 'Alt']);
        $oldQualification = ShiftQualification::factory()->create();
        $preset->shiftsQualifications()->sync([$oldQualification->id => ['quantity' => 1]]);
        $newQualification = ShiftQualification::factory()->create();

        $this->put(route('single-shift-presets.update', $preset), [
            'name' => 'Spätdienst',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'break_duration' => 45,
            'shift_qualifications' => [['id' => $newQualification->id, 'quantity' => 3]],
        ])->assertOk();

        $preset->refresh();
        $this->assertSame('Spätdienst', $preset->name);
        $this->assertSame(45, (int) $preset->break_duration);
        $this->assertDatabaseMissing('single_shift_preset_qualifications', [
            'single_shift_preset_id' => $preset->id,
            'shift_qualification_id' => $oldQualification->id,
        ]);
        $this->assertDatabaseHas('single_shift_preset_qualifications', [
            'single_shift_preset_id' => $preset->id,
            'shift_qualification_id' => $newQualification->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function preset_update_with_empty_break_falls_back_to_the_legal_minimum_break(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset(['start_time' => '08:00:00', 'end_time' => '16:00:00', 'break_duration' => 10]);

        $this->put(route('single-shift-presets.update', $preset), ['break_duration' => null])->assertOk();

        // 8 Stunden Dauer → mehr als 6 Stunden → 30 Minuten gesetzliche Mindestpause
        $this->assertSame(30, (int) $preset->refresh()->break_duration);
    }

    #[Test]
    public function preset_update_without_qualifications_keeps_existing_qualifications(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset();
        $qualification = ShiftQualification::factory()->create();
        $preset->shiftsQualifications()->sync([$qualification->id => ['quantity' => 2]]);

        $this->put(route('single-shift-presets.update', $preset), ['name' => 'Nur Name'])->assertOk();

        $this->assertDatabaseHas('single_shift_preset_qualifications', [
            'single_shift_preset_id' => $preset->id,
            'shift_qualification_id' => $qualification->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function granular_view_permission_allows_the_preset_index_but_not_update(): void
    {
        $this->useGranularShiftSettingsPermissions(true);
        $this->actingAsShiftSettingsUser([PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW->value]);
        $preset = $this->preset(['name' => 'Unverändert']);

        $this->get(route('single-shift-presets.index'))->assertOk();
        $this->put(route('single-shift-presets.update', $preset), ['name' => 'Geändert'])->assertForbidden();

        $this->assertSame('Unverändert', $preset->refresh()->name);
    }

    #[Test]
    public function preset_destroy_deletes_the_preset_and_its_qualification_links(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset();
        $qualification = ShiftQualification::factory()->create();
        $preset->shiftsQualifications()->sync([$qualification->id => ['quantity' => 1]]);

        $this->delete(route('single-shift-presets.destroy', $preset))->assertOk();

        $this->assertDatabaseMissing('single_shift_presets', ['id' => $preset->id]);
        $this->assertDatabaseMissing('single_shift_preset_qualifications', ['single_shift_preset_id' => $preset->id]);
    }

    #[Test]
    public function preset_destroy_is_forbidden_without_the_shift_settings_permission(): void
    {
        $preset = $this->preset();
        $this->actingAs(User::factory()->create());

        $this->delete(route('single-shift-presets.destroy', $preset))->assertForbidden();

        $this->assertDatabaseHas('single_shift_presets', ['id' => $preset->id]);
    }

    // --- shift-preset-groups -----------------------------------------------------------------

    #[Test]
    public function group_index_renders_groups_and_all_presets(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset();
        $group = ShiftPresetGroup::factory()->create(['name' => 'Wochenende']);
        $group->presets()->sync([$preset->id]);

        $this->get(route('shift-preset-groups.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/ShiftPresetGroups')
                ->where('groups', fn ($groups) => collect($groups)
                    ->contains(fn ($row) => $row['id'] === $group->id && $row['presets_count'] === 1))
                ->where('presets', fn ($presets) => collect($presets)->contains('id', $preset->id)));
    }

    #[Test]
    public function group_store_creates_a_group_with_presets_and_redirects_back(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $presetA = $this->preset();
        $presetB = $this->preset();

        $this->from('/shift-preset-groups')
            ->post(route('shift-preset-groups.store'), [
                'name' => 'Premiere',
                'preset_ids' => [$presetA->id, $presetB->id],
            ])
            ->assertRedirect('/shift-preset-groups')
            ->assertSessionHas('success');

        $group = ShiftPresetGroup::query()->where('name', 'Premiere')->firstOrFail();
        $this->assertEqualsCanonicalizing([$presetA->id, $presetB->id], $group->presets()->pluck('single_shift_presets.id')->all());
    }

    #[Test]
    public function group_store_rejects_unknown_preset_ids(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();

        $this->post(route('shift-preset-groups.store'), ['name' => 'Kaputt', 'preset_ids' => [999999999]])
            ->assertSessionHasErrors('preset_ids.0');

        $this->assertDatabaseMissing('shift_preset_groups', ['name' => 'Kaputt']);
    }

    #[Test]
    public function group_store_is_forbidden_with_only_the_granular_view_permission(): void
    {
        $this->useGranularShiftSettingsPermissions(true);
        $this->actingAsShiftSettingsUser([PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW->value]);

        $this->post(route('shift-preset-groups.store'), ['name' => 'Gesperrt'])->assertForbidden();

        $this->assertDatabaseMissing('shift_preset_groups', ['name' => 'Gesperrt']);
    }

    #[Test]
    public function group_update_renames_and_replaces_the_preset_set(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $oldPreset = $this->preset();
        $newPreset = $this->preset();
        $group = ShiftPresetGroup::factory()->create(['name' => 'Alt']);
        $group->presets()->sync([$oldPreset->id]);

        $this->patch(route('shift-preset-groups.update', $group), [
            'name' => 'Neu',
            'preset_ids' => [$newPreset->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Neu', $group->refresh()->name);
        $this->assertSame([$newPreset->id], $group->presets()->pluck('single_shift_presets.id')->all());
    }

    #[Test]
    public function group_update_without_preset_ids_detaches_all_presets(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $group = ShiftPresetGroup::factory()->create();
        $group->presets()->sync([$this->preset()->id]);

        $this->patch(route('shift-preset-groups.update', $group), ['name' => 'Leer'])->assertRedirect();

        $this->assertSame(0, $group->presets()->count());
    }

    #[Test]
    public function group_destroy_deletes_the_group_but_keeps_its_presets(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $preset = $this->preset();
        $group = ShiftPresetGroup::factory()->create();
        $group->presets()->sync([$preset->id]);

        $this->delete(route('shift-preset-groups.destroy', $group))->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('shift_preset_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('shift_preset_group_assignments', ['shift_preset_group_id' => $group->id]);
        $this->assertDatabaseHas('single_shift_presets', ['id' => $preset->id]);
    }

    #[Test]
    public function group_destroy_is_forbidden_without_the_shift_settings_permission(): void
    {
        $group = ShiftPresetGroup::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('shift-preset-groups.destroy', $group))->assertForbidden();

        $this->assertDatabaseHas('shift_preset_groups', ['id' => $group->id]);
    }
}
