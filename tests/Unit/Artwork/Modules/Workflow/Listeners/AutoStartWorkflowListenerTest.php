<?php

namespace Tests\Unit\Artwork\Modules\Workflow\Listeners;

use Tests\TestCase;
use Artwork\Modules\Workflow\Listeners\AutoStartWorkflowListener;
use Artwork\Modules\Workflow\Events\WorkflowTriggered;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class AutoStartWorkflowListenerTest extends TestCase
{
    use DatabaseTransactions;

    private AutoStartWorkflowListener $listener;
    private $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = Mockery::mock(WorkflowService::class);
        $this->listener = new AutoStartWorkflowListener($this->workflowService);
    }

    #[Test]
    public function starts_workflow_for_shift_rule_violation_creation()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;
        $mockViolation->shouldReceive('hasActiveWorkflow')
            ->once()
            ->with('shift_violation_management')
            ->andReturn(false);

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);
        $mockDefinition->type = 'shift_violation_management';

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->id = 1;

        // Mock the WorkflowDefinition query
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')
            ->once()
            ->with('is_active', true)
            ->andReturnSelf();
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockDefinition);

        WorkflowDefinition::shouldReceive('where')
            ->once()
            ->with('type', 'shift_violation_management')
            ->andReturn($mockQuery);

        $this->workflowService
            ->shouldReceive('startWorkflow')
            ->once()
            ->with($mockDefinition, $mockViolation)
            ->andReturn($mockInstance);

        $event = new WorkflowTriggered($mockViolation, 'created');

        $this->listener->handle($event);
    }

    #[Test]
    public function does_not_start_workflow_if_none_exists()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;

        // Mock the WorkflowDefinition query to return null
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')
            ->once()
            ->with('is_active', true)
            ->andReturnSelf();
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn(null);

        WorkflowDefinition::shouldReceive('where')
            ->once()
            ->with('type', 'shift_violation_management')
            ->andReturn($mockQuery);

        $this->workflowService
            ->shouldNotReceive('startWorkflow');

        Log::shouldReceive('info')
            ->once()
            ->with('No active shift_violation_management workflow found');

        $event = new WorkflowTriggered($mockViolation, 'created');

        $this->listener->handle($event);
    }

    #[Test]
    public function does_not_start_workflow_if_already_active()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;
        $mockViolation->shouldReceive('hasActiveWorkflow')
            ->once()
            ->with('shift_violation_management')
            ->andReturn(true);

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);

        // Mock the WorkflowDefinition query
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')
            ->once()
            ->with('is_active', true)
            ->andReturnSelf();
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockDefinition);

        WorkflowDefinition::shouldReceive('where')
            ->once()
            ->with('type', 'shift_violation_management')
            ->andReturn($mockQuery);

        $this->workflowService
            ->shouldNotReceive('startWorkflow');

        Log::shouldReceive('info')
            ->once()
            ->with('ShiftRuleViolation already has active workflow', [
                'violation_id' => 1
            ]);

        $event = new WorkflowTriggered($mockViolation, 'created');

        $this->listener->handle($event);
    }

    #[Test]
    public function ignores_non_shift_rule_violation_subjects()
    {
        $mockUser = Mockery::mock(User::class);
        $mockUser->id = 1;

        $this->workflowService
            ->shouldNotReceive('startWorkflow');

        $event = new WorkflowTriggered($mockUser, 'created');

        $this->listener->handle($event);
    }

    #[Test]
    public function ignores_non_creation_trigger_types()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;

        $this->workflowService
            ->shouldNotReceive('startWorkflow');

        $event = new WorkflowTriggered($mockViolation, 'updated');

        $this->listener->handle($event);
    }

    #[Test]
    public function logs_successful_workflow_start()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;
        $mockViolation->shouldReceive('hasActiveWorkflow')
            ->once()
            ->with('shift_violation_management')
            ->andReturn(false);

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);
        $mockDefinition->type = 'shift_violation_management';

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->id = 2;

        // Mock the WorkflowDefinition query
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')
            ->once()
            ->with('is_active', true)
            ->andReturnSelf();
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockDefinition);

        WorkflowDefinition::shouldReceive('where')
            ->once()
            ->with('type', 'shift_violation_management')
            ->andReturn($mockQuery);

        $this->workflowService
            ->shouldReceive('startWorkflow')
            ->once()
            ->with($mockDefinition, $mockViolation)
            ->andReturn($mockInstance);

        Log::shouldReceive('info')
            ->once()
            ->with('Started workflow for violation', [
                'violation_id' => 1,
                'workflow_instance_id' => 2,
                'workflow_type' => 'shift_violation_management'
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Workflow trigger processed', Mockery::any());

        $event = new WorkflowTriggered($mockViolation, 'created');

        $this->listener->handle($event);
    }

    #[Test]
    public function logs_and_rethrows_exceptions()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;
        $mockViolation->shouldReceive('hasActiveWorkflow')
            ->once()
            ->with('shift_violation_management')
            ->andReturn(false);

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);

        // Mock the WorkflowDefinition query
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')
            ->once()
            ->with('is_active', true)
            ->andReturnSelf();
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockDefinition);

        WorkflowDefinition::shouldReceive('where')
            ->once()
            ->with('type', 'shift_violation_management')
            ->andReturn($mockQuery);

        $exception = new \Exception('Test exception');

        $this->workflowService
            ->shouldReceive('startWorkflow')
            ->once()
            ->with($mockDefinition, $mockViolation)
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Workflow trigger processing failed', [
                'subject_type' => get_class($mockViolation),
                'subject_id' => 1,
                'trigger_type' => 'created',
                'error' => 'Test exception'
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $event = new WorkflowTriggered($mockViolation, 'created');

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}