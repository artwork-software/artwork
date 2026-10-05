<?php

namespace Tests\Feature\Modules\User\Characterization;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Arbeitsprofil-Endpunkte (Policy updateWorkProfile = "can manage workers"):
 * Profilseite, Arbeitsname/-beschreibung, Schicht-Einsetzbarkeit, Gewerke und Qualifikationen.
 * Ohne Personalverwaltung ist auch das eigene Profil gesperrt.
 */
final class UserWorkProfileEndpointsTest extends FeatureTestCase
{
    private function actingAsWorkerManager(): User
    {
        return $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
    }

    #[Test]
    public function work_profile_page_renders_for_worker_managers(): void
    {
        $target = User::factory()->create();
        $this->actingAsWorkerManager();

        $this->get(route('user.edit.workProfile', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/UserWorkProfilePage')
                ->where('currentTab', 'workProfile')
                ->where('userToEdit.id', $target->id)
                ->has('shiftQualifications')
                ->has('globalQualifications')
                ->has('projectRoles'));
    }

    #[Test]
    public function work_profile_page_is_forbidden_without_worker_management_even_for_the_own_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('user.edit.workProfile', $user))->assertForbidden();
    }

    #[Test]
    public function worker_managers_can_update_work_name_description_and_freelancer_flag(): void
    {
        $target = User::factory()->create();
        $this->actingAsWorkerManager();

        $this->from('/back')
            ->patch(route('user.update.workProfile', $target), [
                'workName' => 'Bühnentechnik',
                'workDescription' => 'Schwerpunkt Licht',
                'is_freelancer' => true,
            ])
            ->assertRedirect('/back');

        $target->refresh();
        $this->assertSame('Bühnentechnik', $target->work_name);
        $this->assertSame('Schwerpunkt Licht', $target->work_description);
        $this->assertTrue($target->is_freelancer);
    }

    #[Test]
    public function work_profile_update_is_forbidden_without_worker_management_even_for_the_own_profile(): void
    {
        $user = User::factory()->create(['work_name' => 'alt']);
        $this->actingAs($user);

        $this->patch(route('user.update.workProfile', $user), ['workName' => 'neu'])->assertForbidden();

        $this->assertSame('alt', $user->fresh()->work_name);
    }

    #[Test]
    public function worker_managers_can_toggle_shift_eligibility(): void
    {
        $target = User::factory()->create(['can_work_shifts' => false]);
        $this->actingAsWorkerManager();

        $this->patch(route('user.update.craftSettings', $target), ['canBeAssignedToShifts' => true])
            ->assertRedirect();
        $this->assertTrue($target->fresh()->can_work_shifts);

        $this->patch(route('user.update.craftSettings', $target), [])->assertRedirect();
        $this->assertFalse($target->fresh()->can_work_shifts);
    }

    #[Test]
    public function shift_eligibility_cannot_be_changed_without_worker_management(): void
    {
        $target = User::factory()->create(['can_work_shifts' => false]);
        $this->actingAs(User::factory()->create());

        $this->patch(route('user.update.craftSettings', $target), ['canBeAssignedToShifts' => true])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->can_work_shifts);
    }

    #[Test]
    public function worker_managers_can_assign_a_craft_once(): void
    {
        $target = User::factory()->create();
        $craft = Craft::factory()->create();
        $this->actingAsWorkerManager();

        $this->patch(route('user.assign.craft', $target), ['craftId' => $craft->id])->assertRedirect();
        $this->patch(route('user.assign.craft', $target), ['craftId' => $craft->id])->assertRedirect();

        $this->assertSame([$craft->id], $target->assignedCrafts()->pluck('crafts.id')->all());
    }

    #[Test]
    public function assigning_an_unknown_craft_is_silently_ignored(): void
    {
        $target = User::factory()->create();
        $this->actingAsWorkerManager();

        $this->patch(route('user.assign.craft', $target), ['craftId' => 999999999])->assertRedirect();

        $this->assertSame(0, $target->assignedCrafts()->count());
    }

    #[Test]
    public function crafts_cannot_be_assigned_without_worker_management(): void
    {
        $target = User::factory()->create();
        $craft = Craft::factory()->create();
        $this->actingAs($target);

        $this->patch(route('user.assign.craft', $target), ['craftId' => $craft->id])->assertForbidden();

        $this->assertSame(0, $target->assignedCrafts()->count());
    }

    #[Test]
    public function worker_managers_can_remove_a_craft(): void
    {
        $target = User::factory()->create();
        $craft = Craft::factory()->create();
        $target->assignedCrafts()->attach($craft);
        $this->actingAsWorkerManager();

        $this->delete(route('user.remove.craft', [$target, $craft]))->assertRedirect();

        $this->assertSame(0, $target->assignedCrafts()->count());
    }

    #[Test]
    public function crafts_cannot_be_removed_without_worker_management(): void
    {
        $target = User::factory()->create();
        $craft = Craft::factory()->create();
        $target->assignedCrafts()->attach($craft);
        $this->actingAs(User::factory()->create());

        $this->delete(route('user.remove.craft', [$target, $craft]))->assertForbidden();

        $this->assertSame(1, $target->assignedCrafts()->count());
    }

    #[Test]
    public function global_qualification_is_toggled_on_and_off(): void
    {
        $target = User::factory()->create();
        $qualification = GlobalQualification::factory()->create();
        $this->actingAsWorkerManager();

        $this->patch(route('user.update.shift-qualification', [$target, $qualification]))->assertRedirect();
        $this->assertTrue($target->globalQualifications()->whereKey($qualification->id)->exists());

        $this->patch(route('user.update.shift-qualification', [$target, $qualification]))->assertRedirect();
        $this->assertFalse($target->globalQualifications()->whereKey($qualification->id)->exists());
    }

    #[Test]
    public function global_qualification_cannot_be_toggled_without_worker_management(): void
    {
        $target = User::factory()->create();
        $qualification = GlobalQualification::factory()->create();
        $this->actingAs($target);

        $this->patch(route('user.update.shift-qualification', [$target, $qualification]))->assertForbidden();

        $this->assertSame(0, $target->globalQualifications()->count());
    }

    #[Test]
    public function craft_bound_shift_qualification_is_toggled_per_craft(): void
    {
        $target = User::factory()->create();
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $this->actingAsWorkerManager();

        $this->patch(route('user.update.craft-shift-qualification', [$target, $craftA, $qualification]))
            ->assertRedirect();
        $this->patch(route('user.update.craft-shift-qualification', [$target, $craftB, $qualification]))
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$craftA->id, $craftB->id],
            $target->shiftQualifications()->pluck('shift_qualifiables.craft_id')->all()
        );

        $this->patch(route('user.update.craft-shift-qualification', [$target, $craftA, $qualification]))
            ->assertRedirect();

        $this->assertSame(
            [$craftB->id],
            $target->shiftQualifications()->pluck('shift_qualifiables.craft_id')->all()
        );
    }

    #[Test]
    public function craft_bound_shift_qualification_cannot_be_toggled_without_worker_management(): void
    {
        $target = User::factory()->create();
        $craft = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $this->actingAs($target);

        $this->patch(route('user.update.craft-shift-qualification', [$target, $craft, $qualification]))
            ->assertForbidden();

        $this->assertSame(0, $target->shiftQualifications()->count());
    }

    #[Test]
    public function compensation_days_page_renders_for_shift_planners(): void
    {
        $target = User::factory()->create();
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->get(route('user.edit.compensationDays', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/UserCompensationDays')
                ->where('currentTab', 'compensationDays')
                ->where('userToEdit.id', $target->id));
    }

    #[Test]
    public function compensation_days_page_is_forbidden_without_shift_planning(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('user.edit.compensationDays', $user))->assertForbidden();
    }
}
