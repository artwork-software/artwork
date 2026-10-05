<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Schichtgruppen-Verwaltung fest (shift-groups.index/store/update/destroy):
 * Sichtrecht für die Liste, Bearbeitungsrecht für Schreibzugriffe (granularer Modus), leere
 * 200-Antworten und das Lösen der Gruppe von Schichten beim Löschen (FK ON DELETE SET NULL).
 */
final class ShiftGroupEndpointsTest extends FeatureTestCase
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

    #[Test]
    public function index_renders_the_shift_group_settings_page_with_all_groups(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $group = ShiftGroup::factory()->create(['name' => 'Bühne']);

        $this->get(route('shift-groups.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/ShiftGroups/Index')
                ->where('shiftGroups', fn ($groups) => collect($groups)->contains('id', $group->id)));
    }

    #[Test]
    public function index_is_forbidden_without_the_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('shift-groups.index'))->assertForbidden();
    }

    #[Test]
    public function granular_view_permission_allows_the_index_but_not_writing(): void
    {
        $this->useGranularShiftSettingsPermissions(true);
        $this->actingAsShiftSettingsUser([PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_VIEW->value]);

        $this->get(route('shift-groups.index'))->assertOk();
        $this->post(route('shift-groups.store'), ['name' => 'Gesperrt', 'color' => '#123456'])
            ->assertForbidden();

        $this->assertDatabaseMissing('shift_groups', ['name' => 'Gesperrt']);
    }

    #[Test]
    public function store_creates_a_shift_group(): void
    {
        $this->useGranularShiftSettingsPermissions(true);
        $this->actingAsShiftSettingsUser([PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_EDIT->value]);

        $this->post(route('shift-groups.store'), [
            'name' => 'Technik',
            'color' => '#00ff00',
            'icon' => 'IconTool',
        ])->assertOk();

        $this->assertDatabaseHas('shift_groups', ['name' => 'Technik', 'color' => '#00ff00', 'icon' => 'IconTool']);
    }

    #[Test]
    public function store_rejects_a_color_longer_than_seven_characters(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();

        $this->post(route('shift-groups.store'), ['name' => 'Zu lang', 'color' => '#00ff00ff'])
            ->assertSessionHasErrors('color');

        $this->assertDatabaseMissing('shift_groups', ['name' => 'Zu lang']);
    }

    #[Test]
    public function update_changes_the_group_when_the_body_contains_the_id(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $group = ShiftGroup::factory()->create(['name' => 'Alt', 'color' => '#111111']);

        $this->patch(route('shift-groups.update', $group), [
            'id' => $group->id,
            'name' => 'Neu',
            'color' => '#222222',
            'icon' => null,
        ])->assertOk();

        $group->refresh();
        $this->assertSame('Neu', $group->name);
        $this->assertSame('#222222', $group->color);
        $this->assertNull($group->icon);
    }

    #[Test]
    public function update_without_id_in_the_body_fails_validation(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $group = ShiftGroup::factory()->create(['name' => 'Bleibt']);

        $this->patch(route('shift-groups.update', $group), ['name' => 'Neu', 'color' => '#222222'])
            ->assertSessionHasErrors('id');

        $this->assertSame('Bleibt', $group->refresh()->name);
    }

    #[Test]
    public function destroy_deletes_the_group_and_detaches_it_from_shifts(): void
    {
        $this->useGranularShiftSettingsPermissions(false);
        $this->actingAsShiftSettingsUser();
        $group = ShiftGroup::factory()->create();
        $shift = Shift::factory()->create(['shift_group_id' => $group->id]);

        $this->delete(route('shift-groups.destroy', $group))->assertOk();

        $this->assertDatabaseMissing('shift_groups', ['id' => $group->id]);
        $this->assertNull($shift->refresh()->shift_group_id);
    }

    #[Test]
    public function destroy_is_forbidden_without_the_shift_settings_permission(): void
    {
        $group = ShiftGroup::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('shift-groups.destroy', $group))->assertForbidden();

        $this->assertDatabaseHas('shift_groups', ['id' => $group->id]);
    }
}
