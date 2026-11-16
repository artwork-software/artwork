<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion der Schalter in den allgemeinen Schichteinstellungen (Rechte deckt die Rollen-Matrix ab).
 */
final class ShiftSettingsToggleRoutesTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    #[Test]
    public function enabling_granular_permissions_grants_defaults_only_once(): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = false;
        $settings->granular_defaults_granted = false;
        $settings->save();

        $master = Permission::findOrCreate(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value, 'web');
        $role = Role::findOrCreate('Schichtleitung Test', 'web');
        $role->givePermissionTo($master);
        $child = PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT->value;

        $this->patch(route('shift.settings.update.granular-permissions'), ['granular_permissions_enabled' => true])
            ->assertRedirect();

        $settings = $this->freshShiftSettings();
        $this->assertTrue($settings->granular_permissions_enabled);
        $this->assertTrue($settings->granular_defaults_granted);
        $this->assertTrue($role->fresh()->hasPermissionTo($child));

        // Entzogenes Einzelrecht darf ein erneutes Aus/An nicht zurückbringen
        $role->revokePermissionTo($child);
        $this->patch(route('shift.settings.update.granular-permissions'), ['granular_permissions_enabled' => false])
            ->assertRedirect();
        $this->assertFalse($this->freshShiftSettings()->granular_permissions_enabled);
        $this->patch(route('shift.settings.update.granular-permissions'), ['granular_permissions_enabled' => true])
            ->assertRedirect();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($role->fresh()->hasPermissionTo($child));
        $this->assertTrue($this->freshShiftSettings()->granular_permissions_enabled);
    }

    #[Test]
    public function own_roster_uncommitted_visibility_is_toggled(): void
    {
        $route = route('shift.settings.update.own-roster-uncommitted-visibility');

        $this->patch($route, ['hide_uncommitted_shifts_from_own_roster' => true])->assertRedirect();
        $this->assertTrue($this->freshShiftSettings()->hide_uncommitted_shifts_from_own_roster);

        $this->patch($route, ['hide_uncommitted_shifts_from_own_roster' => false])->assertRedirect();
        $this->assertFalse($this->freshShiftSettings()->hide_uncommitted_shifts_from_own_roster);
    }

    #[Test]
    public function shift_confirmation_settings_store_both_flags(): void
    {
        $route = route('shift.settings.update.shift-confirmation');

        $this->patch($route, ['shift_confirmation_enabled' => true, 'shift_confirmation_in_history' => false])
            ->assertRedirect();
        $settings = $this->freshShiftSettings();
        $this->assertTrue($settings->shift_confirmation_enabled);
        $this->assertFalse($settings->shift_confirmation_in_history);

        $this->patch($route, ['shift_confirmation_enabled' => false, 'shift_confirmation_in_history' => true])
            ->assertRedirect();
        $settings = $this->freshShiftSettings();
        $this->assertFalse($settings->shift_confirmation_enabled);
        $this->assertTrue($settings->shift_confirmation_in_history);
    }

    #[Test]
    public function warning_multiple_assignments_is_stored(): void
    {
        $route = route('shift-settings.update-warn-multiple-assignments');

        $this->patch($route, ['warn_multiple_assignments' => true])->assertSuccessful();
        $this->assertTrue($this->freshGeneralSettings()->warn_multiple_assignments);

        $this->patch($route, ['warn_multiple_assignments' => false])->assertSuccessful();
        $this->assertFalse($this->freshGeneralSettings()->warn_multiple_assignments);
    }

    #[Test]
    public function warning_multiple_assignments_handles_missing_or_textual_values(): void
    {
        $route = route('shift-settings.update-warn-multiple-assignments');

        // Fehlender Wert führte zu einem TypeError (null in bool-Property) und damit zu einem 500er
        $this->patch($route, [])->assertSuccessful();
        $this->assertFalse($this->freshGeneralSettings()->warn_multiple_assignments);

        // "false" als Text wurde bisher als true gespeichert
        $this->patch($route, ['warn_multiple_assignments' => 'false'])->assertSuccessful();
        $this->assertFalse($this->freshGeneralSettings()->warn_multiple_assignments);
    }

    private function freshShiftSettings(): ShiftSettings
    {
        app()->forgetInstance(ShiftSettings::class);

        return app(ShiftSettings::class)->refresh();
    }

    private function freshGeneralSettings(): GeneralSettings
    {
        app()->forgetInstance(GeneralSettings::class);

        return app(GeneralSettings::class)->refresh();
    }
}
