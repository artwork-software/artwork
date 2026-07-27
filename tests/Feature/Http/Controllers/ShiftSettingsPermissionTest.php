<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftSettingsPermissionTest extends FeatureTestCase
{
    #[Test]
    public function the_master_permission_keeps_edit_access_in_simple_mode(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($this->permission(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT));
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = false;
        $settings->save();

        $this->actingAs($user)
            ->patch(route('shift.settings.update.allow-shift-overbooking'), ['allow_shift_overbooking' => true])
            ->assertRedirect();
    }

    #[Test]
    public function granular_mode_requires_the_matching_edit_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($this->permission(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT));
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = true;
        $settings->save();

        $this->actingAs($user)
            ->patch(route('shift.settings.update.allow-shift-overbooking'), ['allow_shift_overbooking' => true])
            ->assertForbidden();

        $user->givePermissionTo($this->permission(PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT));

        $this->actingAs($user)
            ->patch(route('shift.settings.update.allow-shift-overbooking'), ['allow_shift_overbooking' => true])
            ->assertRedirect();
    }

    private function permission(PermissionEnum $permission): Permission
    {
        return Permission::findOrCreate($permission->value, 'web');
    }
}
