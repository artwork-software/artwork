<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
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
}
