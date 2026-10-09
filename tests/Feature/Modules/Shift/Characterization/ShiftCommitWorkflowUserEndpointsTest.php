<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Personenliste des Festschreibungs-Workflows fest
 * (shift.settings.update.shift-commit-workflow-users und shift.settings.remove.shift-commit-workflow-user):
 * Eingabeformate (Liste, Map id => bool, Komma-String), Duplikat-Schutz und Entfernen.
 */
final class ShiftCommitWorkflowUserEndpointsTest extends FeatureTestCase
{
    private function actingAsGeneralShiftSettingsEditor(): User
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = false;
        $settings->save();

        return $this->actingAsUserWith(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value);
    }

    #[Test]
    public function store_adds_users_from_a_plain_id_list(): void
    {
        $this->actingAsGeneralShiftSettingsEditor();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), [
            'users' => [$first->id, $second->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('shift_commit_workflow_users', ['user_id' => $first->id]);
        $this->assertDatabaseHas('shift_commit_workflow_users', ['user_id' => $second->id]);
    }

    #[Test]
    public function store_only_takes_truthy_entries_from_an_id_map(): void
    {
        $this->actingAsGeneralShiftSettingsEditor();
        $selected = User::factory()->create();
        $notSelected = User::factory()->create();

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), [
            'users' => [$selected->id => true, $notSelected->id => false],
        ])->assertRedirect();

        $this->assertDatabaseHas('shift_commit_workflow_users', ['user_id' => $selected->id]);
        $this->assertDatabaseMissing('shift_commit_workflow_users', ['user_id' => $notSelected->id]);
    }

    #[Test]
    public function store_does_not_duplicate_users_already_in_the_workflow(): void
    {
        $this->actingAsGeneralShiftSettingsEditor();
        $existing = User::factory()->create();
        ShiftCommitWorkflowUser::factory()->create(['user_id' => $existing->id]);

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), [
            'users' => [$existing->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, ShiftCommitWorkflowUser::query()->where('user_id', $existing->id)->count());
    }

    #[Test]
    public function store_requires_the_users_field(): void
    {
        $this->actingAsGeneralShiftSettingsEditor();

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), [])
            ->assertSessionHasErrors('users');
    }

    #[Test]
    public function store_is_forbidden_without_the_shift_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create();

        $this->patch(route('shift.settings.update.shift-commit-workflow-users'), ['users' => [$target->id]])
            ->assertForbidden();

        $this->assertDatabaseMissing('shift_commit_workflow_users', ['user_id' => $target->id]);
    }

    #[Test]
    public function destroy_removes_the_workflow_user_entry(): void
    {
        $this->actingAsGeneralShiftSettingsEditor();
        $entry = ShiftCommitWorkflowUser::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->delete(route('shift.settings.remove.shift-commit-workflow-user', $entry))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('shift_commit_workflow_users', ['id' => $entry->id]);
    }

    #[Test]
    public function destroy_is_forbidden_without_the_shift_settings_permission(): void
    {
        $entry = ShiftCommitWorkflowUser::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->actingAs(User::factory()->create());

        $this->delete(route('shift.settings.remove.shift-commit-workflow-user', $entry))->assertForbidden();

        $this->assertDatabaseHas('shift_commit_workflow_users', ['id' => $entry->id]);
    }

    #[Test]
    public function granular_mode_requires_general_edit_to_remove_a_workflow_user(): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = true;
        $settings->save();
        $entry = ShiftCommitWorkflowUser::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
            PermissionEnum::SHIFT_SETTINGS_GENERAL_VIEW->value,
        ]);

        $this->delete(route('shift.settings.remove.shift-commit-workflow-user', $entry))->assertForbidden();

        $this->assertDatabaseHas('shift_commit_workflow_users', ['id' => $entry->id]);
    }
}
