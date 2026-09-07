<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 1a: Der Freigabe-Workflow lässt sich nur mit mindestens einer Genehmiger:in
 * aktivieren, und bei aktivem Workflow ist die direkte Festschreibung gesperrt
 * (Aufheben bleibt erlaubt).
 */
final class ShiftCommitWorkflowGuardTest extends FeatureTestCase
{
    #[Test]
    public function workflow_cannot_be_enabled_without_approvers(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);

        $this->patchJson(route('shift.settings.update.shift-commit-workflow'), [
            'shift_commit_workflow' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shift_commit_workflow']);

        $this->assertFalse(app(GeneralSettings::class)->shift_commit_workflow_enabled);
    }

    #[Test]
    public function workflow_can_be_enabled_with_an_approver(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        ShiftCommitWorkflowUser::create(['user_id' => User::factory()->create()->id]);

        $this->patch(route('shift.settings.update.shift-commit-workflow'), [
            'shift_commit_workflow' => true,
        ])->assertRedirect();

        $this->assertTrue(app(GeneralSettings::class)->shift_commit_workflow_enabled);
    }

    #[Test]
    public function workflow_can_always_be_disabled(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(true);

        $this->patch(route('shift.settings.update.shift-commit-workflow'), [
            'shift_commit_workflow' => false,
        ])->assertRedirect();

        $this->assertFalse(app(GeneralSettings::class)->shift_commit_workflow_enabled);
    }

    #[Test]
    public function direct_commit_is_blocked_while_workflow_is_active(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(true);
        $shift = Shift::factory()->create(['is_committed' => false]);

        $this->postJson(route('shift.change.commit.status', $shift), ['commit' => true])
            ->assertStatus(422);

        $this->assertFalse($shift->fresh()->is_committed);
    }

    #[Test]
    public function lifting_a_commit_stays_possible_while_workflow_is_active(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(true);
        $shift = Shift::factory()->create(['is_committed' => true]);

        $this->postJson(route('shift.change.commit.status', $shift), ['commit' => false])
            ->assertOk();

        $this->assertFalse($shift->fresh()->is_committed);
    }

    #[Test]
    public function direct_commit_works_without_workflow(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        $shift = Shift::factory()->create(['is_committed' => false]);

        $this->postJson(route('shift.change.commit.status', $shift), ['commit' => true])
            ->assertOk();

        $this->assertTrue($shift->fresh()->is_committed);
    }

    private function setWorkflowEnabled(bool $enabled): void
    {
        $settings = app(GeneralSettings::class);
        $settings->shift_commit_workflow_enabled = $enabled;
        $settings->save();
    }


    // ---------------------------------------------------------------------------------------
    // Sammel-Festschreibung einer KW (shifts.commit): Workflow-Guard, Validierung, Gewerks-Scoping
    // ---------------------------------------------------------------------------------------

    private function shiftInWeek37(Craft $craft, string $date = '2026-09-09'): Shift
    {
        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:00:00',
            'is_committed' => false,
            'in_workflow' => false,
            'current_request_id' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function commitWeek(array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('shifts.commit'), array_merge([
            'week_number' => 37,
            'year' => 2026,
        ], $payload));
    }

    #[Test]
    public function bulk_commit_is_blocked_while_workflow_is_active(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(true);
        $craft = Craft::factory()->create();
        $shift = $this->shiftInWeek37($craft);

        $this->commitWeek(['craft_ids' => [$craft->id]])->assertStatus(422);

        $this->assertFalse($shift->fresh()->is_committed);
    }

    #[Test]
    public function bulk_commit_validates_week_year_and_crafts(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);

        $this->postJson(route('shifts.commit'), ['craft_ids' => [Craft::factory()->create()->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['week_number', 'year']);

        $this->commitWeek(['week_number' => 54, 'craft_ids' => [Craft::factory()->create()->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['week_number']);

        $this->commitWeek(['craft_ids' => [999999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['craft_ids.0']);

        $this->commitWeek([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['craft_ids']);
    }

    #[Test]
    public function bulk_commit_rejects_foreign_craft_for_non_admin(): void
    {
        $this->actingAsUserWith(PermissionEnum::CAN_COMMIT_SHIFTS->value);
        $this->setWorkflowEnabled(false);
        $foreignCraft = Craft::factory()->create(['name' => 'Fremdgewerk', 'assignable_by_all' => false]);
        $shift = $this->shiftInWeek37($foreignCraft);

        $this->commitWeek(['craft_ids' => [$foreignCraft->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['craft_ids']);

        $this->assertFalse($shift->fresh()->is_committed);
    }

    #[Test]
    public function bulk_commit_allows_assignable_and_own_planned_crafts_for_non_admin(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_COMMIT_SHIFTS->value);
        $this->setWorkflowEnabled(false);
        $assignable = Craft::factory()->create(['assignable_by_all' => true]);
        $own = Craft::factory()->create(['assignable_by_all' => false]);
        $own->craftShiftPlaner()->attach($user->id);

        $assignableShift = $this->shiftInWeek37($assignable);
        $ownShift = $this->shiftInWeek37($own, '2026-09-11');

        $this->commitWeek(['craft_ids' => [$assignable->id, $own->id]])->assertOk();

        $this->assertTrue($assignableShift->fresh()->is_committed);
        $this->assertTrue($ownShift->fresh()->is_committed);
    }

    #[Test]
    public function bulk_commit_legacy_single_craft_id_is_still_accepted(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        $craft = Craft::factory()->create();
        $shift = $this->shiftInWeek37($craft);

        $this->commitWeek(['craft_id' => $craft->id])->assertOk();

        $this->assertTrue($shift->fresh()->is_committed);
    }

    #[Test]
    public function admin_can_bulk_commit_any_craft(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $shift = $this->shiftInWeek37($craft);

        $this->commitWeek(['craft_ids' => [$craft->id]])->assertOk();

        $this->assertTrue($shift->fresh()->is_committed);
    }
}
