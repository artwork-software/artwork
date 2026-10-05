<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Endpunkte für globale Qualifikationen fest
 * (global-qualification.store/update/delete): Middleware shift-settings-area:general,edit,
 * leere 200-Antworten, und das Pflichtfeld "id" im Update-Body.
 */
final class GlobalQualificationEndpointsTest extends FeatureTestCase
{
    private function useGranularShiftSettingsPermissions(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = $enabled;
        $settings->save();
    }

    private function actingAsShiftSettingsEditor(array $extraPermissions = []): User
    {
        return $this->actingAsUserWith(array_merge(
            [PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value],
            $extraPermissions
        ));
    }

    #[Test]
    public function store_creates_a_global_qualification_and_returns_an_empty_ok(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $response = $this->post(route('global-qualification.store'), [
            'name' => 'Pyrotechnik-Schein',
            'icon' => 'IconFlame',
        ]);

        $response->assertOk();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseHas('global_qualifications', ['name' => 'Pyrotechnik-Schein', 'icon' => 'IconFlame']);
    }

    #[Test]
    public function store_rejects_a_missing_icon_with_a_validation_redirect(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $this->post(route('global-qualification.store'), ['name' => 'Ohne Icon'])
            ->assertSessionHasErrors('icon');

        $this->assertDatabaseMissing('global_qualifications', ['name' => 'Ohne Icon']);
    }

    #[Test]
    public function store_is_forbidden_without_the_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('global-qualification.store'), ['name' => 'Verboten', 'icon' => 'IconX'])
            ->assertForbidden();

        $this->assertDatabaseMissing('global_qualifications', ['name' => 'Verboten']);
    }

    #[Test]
    public function granular_mode_requires_the_general_edit_permission_for_store(): void
    {
        $this->useGranularShiftSettingsPermissions(true);
        $this->actingAsShiftSettingsEditor([PermissionEnum::SHIFT_SETTINGS_GENERAL_VIEW->value]);

        $this->post(route('global-qualification.store'), ['name' => 'Nur Lesen', 'icon' => 'IconX'])
            ->assertForbidden();

        $this->actingAsShiftSettingsEditor([PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT->value]);

        $this->post(route('global-qualification.store'), ['name' => 'Mit Edit', 'icon' => 'IconX'])
            ->assertOk();
        $this->assertDatabaseHas('global_qualifications', ['name' => 'Mit Edit']);
    }

    #[Test]
    public function update_changes_name_and_icon_when_the_body_contains_the_id(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsEditor();
        $qualification = GlobalQualification::factory()->create(['name' => 'Alt', 'icon' => 'IconStar']);

        $this->patch(route('global-qualification.update', $qualification), [
            'id' => $qualification->id,
            'name' => 'Neu',
            'icon' => 'IconBolt',
        ])->assertOk();

        $qualification->refresh();
        $this->assertSame('Neu', $qualification->name);
        $this->assertSame('IconBolt', $qualification->icon);
    }

    #[Test]
    public function update_without_id_in_the_body_fails_validation(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsEditor();
        $qualification = GlobalQualification::factory()->create(['name' => 'Bleibt']);

        $this->patch(route('global-qualification.update', $qualification), ['name' => 'Geändert'])
            ->assertSessionHasErrors('id');

        $this->assertSame('Bleibt', $qualification->refresh()->name);
    }

    #[Test]
    public function update_is_forbidden_without_the_shift_settings_permission(): void
    {
        $qualification = GlobalQualification::factory()->create(['name' => 'Unverändert']);
        $this->actingAs(User::factory()->create());

        $this->patch(route('global-qualification.update', $qualification), [
            'id' => $qualification->id,
            'name' => 'Gehackt',
        ])->assertForbidden();

        $this->assertSame('Unverändert', $qualification->refresh()->name);
    }

    #[Test]
    public function delete_removes_the_global_qualification(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsEditor();
        $qualification = GlobalQualification::factory()->create();

        $this->delete(route('global-qualification.delete', $qualification))->assertOk();

        $this->assertDatabaseMissing('global_qualifications', ['id' => $qualification->id]);
    }

    #[Test]
    public function delete_is_forbidden_without_the_shift_settings_permission(): void
    {
        $qualification = GlobalQualification::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('global-qualification.delete', $qualification))->assertForbidden();

        $this->assertDatabaseHas('global_qualifications', ['id' => $qualification->id]);
    }
}
