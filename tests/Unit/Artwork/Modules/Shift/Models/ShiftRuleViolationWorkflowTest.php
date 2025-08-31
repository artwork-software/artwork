<?php

namespace Tests\Unit\Artwork\Modules\Shift\Models;

use Tests\TestCase;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Contracts\WorkflowSubject;
use Artwork\Modules\Workflow\Events\WorkflowTriggered;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class ShiftRuleViolationWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function implementsWorkflowSubjectInterface(): void
    {
        $violation = new ShiftRuleViolation();
        
        $this->assertInstanceOf(WorkflowSubject::class, $violation);
    }

    #[Test]
    public function canHaveShiftViolationWorkflows(): void
    {
        $violation = new ShiftRuleViolation();
        
        $this->assertTrue($violation->canHaveWorkflow('shift_violation_approval'));
        $this->assertTrue($violation->canHaveWorkflow('shift_violation_management'));
        $this->assertFalse($violation->canHaveWorkflow('other_workflow_type'));
    }

    #[Test]
    public function hasWorkflowsTraitMethods(): void
    {
        $violation = new ShiftRuleViolation();
        
        $this->assertTrue(method_exists($violation, 'workflowInstances'));
        $this->assertTrue(method_exists($violation, 'getActiveWorkflows'));
        $this->assertTrue(method_exists($violation, 'hasActiveWorkflow'));
        $this->assertTrue(method_exists($violation, 'getWorkflowSubjectInfo'));
    }

    #[Test]
    public function hasTriggersWorkflowsTraitMethods(): void
    {
        $violation = new ShiftRuleViolation();
        
        $this->assertTrue(method_exists($violation, 'triggerWorkflow'));
        $this->assertTrue(method_exists($violation, 'triggerCustomWorkflow'));
    }

    #[Test]
    public function canTriggerCustomWorkflow(): void
    {
        Event::fake();

        $violation = new ShiftRuleViolation();
        $violation->id = 123;

        $violation->triggerCustomWorkflow('manual_review', ['reason' => 'manager_request']);

        Event::assertDispatched(WorkflowTriggered::class, function ($event) use ($violation) {
            return $event->triggerType === 'manual_review' &&
                   $event->subject === $violation &&
                   $event->context['reason'] === 'manager_request';
        });
    }

    #[Test]
    public function returnsCorrectWorkflowSubjectInfo(): void
    {
        $shiftRule = ShiftRule::factory()->create(['name' => 'Test Rule']);
        $shift = Shift::factory()->create();
        $user = User::factory()->create();

        $violation = ShiftRuleViolation::create([
            'shift_rule_id' => $shiftRule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => now(),
            'violation_data' => ['test' => 'data'],
            'severity' => 'warning',
            'status' => 'active'
        ]);

        $info = $violation->getWorkflowSubjectInfo();

        $this->assertIsArray($info);
        $this->assertEquals($violation->id, $info['id']);
        $this->assertEquals(ShiftRuleViolation::class, $info['type']);
        $this->assertStringContainsString($violation->id, $info['title']);
    }

    #[Test]
    public function canResolveViolation(): void
    {
        $user = User::factory()->create();
        $violation = ShiftRuleViolation::factory()->create([
            'status' => 'active'
        ]);

        $violation->resolve($user->id);

        $this->assertEquals('resolved', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($user->id, $violation->resolved_by);
    }

    #[Test]
    public function canIgnoreViolation(): void
    {
        $user = User::factory()->create();
        $violation = ShiftRuleViolation::factory()->create([
            'status' => 'active'
        ]);

        $violation->ignore($user->id);

        $this->assertEquals('ignored', $violation->status);
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($user->id, $violation->resolved_by);
    }

    #[Test]
    public function isResolvedReturnsCorrectStatus(): void
    {
        $unresolved = ShiftRuleViolation::factory()->create([
            'status' => 'active'
        ]);

        $resolved = ShiftRuleViolation::factory()->create([
            'status' => 'resolved',
            'resolved_at' => now()
        ]);

        $this->assertFalse($unresolved->isResolved());
        $this->assertTrue($resolved->isResolved());
    }

    #[Test]
    public function returnsCorrectViolationMessage(): void
    {
        $shiftRule = ShiftRule::factory()->create([
            'description' => 'Maximum 8 hours per day'
        ]);

        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $shiftRule->id
        ]);

        $this->assertEquals('Maximum 8 hours per day', $violation->getViolationMessage());
    }

    #[Test]
    public function returnsCorrectWarningColor(): void
    {
        $shiftRule = ShiftRule::factory()->create([
            'warning_color' => '#ff6b6b'
        ]);

        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $shiftRule->id
        ]);

        $this->assertEquals('#ff6b6b', $violation->getWarningColor());
    }

    #[Test]
    public function returnsDefaultWarningColorIfNoneSet(): void
    {
        $shiftRule = ShiftRule::factory()->create([
            'warning_color' => ''
        ]);

        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $shiftRule->id
        ]);

        // Test that empty string falls back to default
        $this->assertEquals('#ff0000', $violation->getWarningColor());
    }
}