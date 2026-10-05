<?php

namespace Tests\Feature\Modules\User\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Dienstplan-Einstellungen "Arbeitsverträge" (UserContractController) und
 * "Arbeitszeitmuster" (UserWorkTimePatternController, schreibend) sowie das Entfernen eines
 * Arbeitszeit-Zeitraums am User (UserContractAssignController@destroyWorkTime).
 * Gate: Middleware EnsureShiftSettingsAreaPermission bzw. "can manage workers".
 */
final class UserContractAndWorkTimePatternSettingsTest extends FeatureTestCase
{
    private function useGranularPermissions(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = $enabled;
        $settings->save();
    }

    private function actingAsShiftSettingsEditor(string ...$extraPermissions): User
    {
        return $this->actingAsUserWith([PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value, ...$extraPermissions]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contractPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Vollzeit Bühne',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 3,
            'description' => 'Tarifvertrag NV Bühne',
            'free_sundays_per_season' => 12,
            'days_off_first_26_weeks' => 1.5,
        ], $overrides);
    }

    #[Test]
    public function contract_settings_page_lists_contracts(): void
    {
        $this->useGranularPermissions(false);
        $contract = UserContract::factory()->create();
        $this->actingAsShiftSettingsEditor();

        $this->get(route('user-contract-settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/UserContractSettings/Index')
                ->where('contracts', fn ($contracts) => collect($contracts)->contains('id', $contract->id)));
    }

    #[Test]
    public function contract_settings_page_is_forbidden_without_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('user-contract-settings.index'))->assertForbidden();
    }

    #[Test]
    public function contract_settings_page_needs_the_area_permission_in_granular_mode(): void
    {
        $this->useGranularPermissions(true);
        $this->actingAsShiftSettingsEditor();

        $this->get(route('user-contract-settings.index'))->assertForbidden();

        $this->actingAsShiftSettingsEditor(PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_VIEW->value);
        $this->get(route('user-contract-settings.index'))->assertOk();
    }

    #[Test]
    public function contract_is_created_and_redirects_to_the_settings_page(): void
    {
        $this->useGranularPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $this->post(route('user-contract-settings.store'), $this->contractPayload())
            ->assertRedirect(route('user-contract-settings.index'))
            ->assertSessionHas('success');

        $contract = UserContract::query()->where('name', 'Vollzeit Bühne')->sole();
        $this->assertSame(2, (int) $contract->free_full_days_per_week);
        $this->assertTrue($contract->special_day_rule_active);
        $this->assertSame(1.5, $contract->days_off_first_26_weeks);
    }

    #[Test]
    public function contract_creation_validates_required_fields(): void
    {
        $this->useGranularPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $this->postJson(route('user-contract-settings.store'), ['name' => 'unvollständig'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'free_full_days_per_week',
                'free_half_days_per_week',
                'compensation_period',
                'free_sundays_per_season',
                'days_off_first_26_weeks',
            ]);
    }

    #[Test]
    public function contract_creation_is_forbidden_without_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('user-contract-settings.store'), $this->contractPayload())->assertForbidden();

        $this->assertFalse(UserContract::query()->where('name', 'Vollzeit Bühne')->exists());
    }

    #[Test]
    public function contract_creation_needs_the_edit_permission_in_granular_mode(): void
    {
        $this->useGranularPermissions(true);
        $this->actingAsShiftSettingsEditor(PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_VIEW->value);

        $this->post(route('user-contract-settings.store'), $this->contractPayload())->assertForbidden();

        $this->actingAsShiftSettingsEditor(PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_EDIT->value);
        $this->post(route('user-contract-settings.store'), $this->contractPayload())
            ->assertRedirect(route('user-contract-settings.index'));
    }

    #[Test]
    public function contract_is_updated_and_needs_the_id_in_the_payload(): void
    {
        $this->useGranularPermissions(false);
        $contract = UserContract::factory()->create(['name' => 'Alt']);
        $this->actingAsShiftSettingsEditor();

        $this->patchJson(route('user-contract-settings.update', $contract), $this->contractPayload(['name' => 'Neu']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');

        $this->patch(
            route('user-contract-settings.update', $contract),
            $this->contractPayload(['id' => $contract->id, 'name' => 'Neu'])
        )->assertRedirect(route('user-contract-settings.index'));

        $this->assertSame('Neu', $contract->fresh()->name);
    }

    #[Test]
    public function contract_update_is_forbidden_without_shift_settings_permission(): void
    {
        $contract = UserContract::factory()->create(['name' => 'Alt']);
        $this->actingAs(User::factory()->create());

        $this->patch(
            route('user-contract-settings.update', $contract),
            $this->contractPayload(['id' => $contract->id, 'name' => 'Neu'])
        )->assertForbidden();

        $this->assertSame('Alt', $contract->fresh()->name);
    }

    #[Test]
    public function contract_is_deleted(): void
    {
        $this->useGranularPermissions(false);
        $contract = UserContract::factory()->create();
        $this->actingAsShiftSettingsEditor();

        $this->delete(route('user-contract-settings.destroy', $contract))
            ->assertRedirect(route('user-contract-settings.index'));

        $this->assertModelMissing($contract);
    }

    #[Test]
    public function contract_deletion_is_forbidden_without_shift_settings_permission(): void
    {
        $contract = UserContract::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('user-contract-settings.destroy', $contract))->assertForbidden();

        $this->assertModelExists($contract);
    }

    #[Test]
    public function work_time_pattern_is_created(): void
    {
        $this->useGranularPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $this->post(route('shift.work-time-pattern.store'), [
            'name' => '40h-Woche',
            'monday' => '08:00',
            'friday' => '06:30',
        ])
            ->assertRedirect(route('shift.work-time-pattern'))
            ->assertSessionHas('success');

        $pattern = UserWorkTimePattern::query()->where('name', '40h-Woche')->sole();
        $this->assertSame('08:00', $pattern->monday->format('H:i'));
        $this->assertSame('06:30', $pattern->friday->format('H:i'));
        $this->assertSame('00:00', $pattern->fresh()->sunday->format('H:i'));
    }

    #[Test]
    public function work_time_pattern_creation_validates_time_format(): void
    {
        $this->useGranularPermissions(false);
        $this->actingAsShiftSettingsEditor();

        $this->postJson(route('shift.work-time-pattern.store'), ['name' => 'x', 'monday' => '8 Stunden'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('monday');
    }

    #[Test]
    public function work_time_pattern_creation_is_forbidden_without_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('shift.work-time-pattern.store'), ['name' => '40h-Woche'])->assertForbidden();

        $this->assertFalse(UserWorkTimePattern::query()->where('name', '40h-Woche')->exists());
    }

    #[Test]
    public function work_time_pattern_creation_needs_the_edit_permission_in_granular_mode(): void
    {
        $this->useGranularPermissions(true);
        $this->actingAsShiftSettingsEditor(PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_VIEW->value);

        $this->post(route('shift.work-time-pattern.store'), ['name' => '40h-Woche'])->assertForbidden();

        $this->actingAsShiftSettingsEditor(PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_EDIT->value);
        $this->post(route('shift.work-time-pattern.store'), ['name' => '40h-Woche'])
            ->assertRedirect(route('shift.work-time-pattern'));
    }

    #[Test]
    public function work_time_pattern_is_updated_and_needs_the_id_in_the_payload(): void
    {
        $this->useGranularPermissions(false);
        $pattern = UserWorkTimePattern::factory()->create(['name' => 'Alt']);
        $this->actingAsShiftSettingsEditor();

        $this->patchJson(route('shift.work-time-pattern.update', $pattern), ['name' => 'Neu'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');

        $this->patch(route('shift.work-time-pattern.update', $pattern), [
            'id' => $pattern->id,
            'name' => 'Neu',
            'monday' => '07:00',
        ])->assertRedirect(route('shift.work-time-pattern'));

        $pattern->refresh();
        $this->assertSame('Neu', $pattern->name);
        $this->assertSame('07:00', $pattern->monday->format('H:i'));
    }

    #[Test]
    public function work_time_pattern_update_is_forbidden_without_shift_settings_permission(): void
    {
        $pattern = UserWorkTimePattern::factory()->create(['name' => 'Alt']);
        $this->actingAs(User::factory()->create());

        $this->patch(route('shift.work-time-pattern.update', $pattern), ['id' => $pattern->id, 'name' => 'Neu'])
            ->assertForbidden();

        $this->assertSame('Alt', $pattern->fresh()->name);
    }

    #[Test]
    public function deleting_a_work_time_pattern_detaches_it_from_user_work_times(): void
    {
        $this->useGranularPermissions(false);
        $pattern = UserWorkTimePattern::factory()->create();
        $workTime = UserWorkTime::factory()->create(['work_time_pattern_id' => $pattern->id]);
        $this->actingAsShiftSettingsEditor();

        $this->delete(route('shift.work-time-pattern.destroy', $pattern))
            ->assertRedirect(route('shift.work-time-pattern'));

        $this->assertModelMissing($pattern);
        $this->assertModelExists($workTime);
        $this->assertNull($workTime->fresh()->work_time_pattern_id);
    }

    #[Test]
    public function work_time_pattern_deletion_is_forbidden_without_shift_settings_permission(): void
    {
        $pattern = UserWorkTimePattern::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('shift.work-time-pattern.destroy', $pattern))->assertForbidden();

        $this->assertModelExists($pattern);
    }

    #[Test]
    public function worker_managers_can_remove_a_work_time_period_of_a_user(): void
    {
        $target = User::factory()->create();
        $workTime = UserWorkTime::factory()->create(['user_id' => $target->id]);
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $this->from('/users/' . $target->id . '/work-time-pattern')
            ->delete(route('shift.work-time-pattern.work-time.destroy', [$target, $workTime]))
            ->assertRedirect('/users/' . $target->id . '/work-time-pattern')
            ->assertSessionHas('success');

        $this->assertModelMissing($workTime);
    }

    #[Test]
    public function work_time_period_of_another_user_returns_not_found(): void
    {
        $target = User::factory()->create();
        $foreignWorkTime = UserWorkTime::factory()->create();
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $this->delete(route('shift.work-time-pattern.work-time.destroy', [$target, $foreignWorkTime]))
            ->assertNotFound();

        $this->assertModelExists($foreignWorkTime);
    }

    #[Test]
    public function work_time_period_removal_is_forbidden_without_worker_management_even_for_the_own_user(): void
    {
        $user = User::factory()->create();
        $workTime = UserWorkTime::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->delete(route('shift.work-time-pattern.work-time.destroy', [$user, $workTime]))->assertForbidden();

        $this->assertModelExists($workTime);
    }
}
