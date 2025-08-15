<?php

namespace Tests\Unit\Artwork\Modules\Workflow\Services;

use Tests\TestCase;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowDefinitionConfig;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Actions\ActionResolver;
use Artwork\Modules\Workflow\Repositories\WorkflowDefinitionRepository;
use Artwork\Modules\Workflow\Repositories\WorkflowInstanceRepository;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Workflow\Exceptions\InvalidWorkflowConfigException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class WorkflowServiceTest extends TestCase
{
    use DatabaseTransactions;

    private WorkflowService $workflowService;
    private $workflowDefinitionRepository;
    private $workflowInstanceRepository;
    private $actionResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowDefinitionRepository = Mockery::mock(WorkflowDefinitionRepository::class);
        $this->workflowInstanceRepository = Mockery::mock(WorkflowInstanceRepository::class);
        $this->actionResolver = Mockery::mock(ActionResolver::class);

        $this->workflowService = new WorkflowService(
            $this->workflowDefinitionRepository,
            $this->workflowInstanceRepository,
            $this->actionResolver
        );
    }

    #[Test]
    public function can_create_workflow_definition()
    {
        $config = [
            'initial_place' => 'start',
            'places' => [
                ['name' => 'start', 'type' => 'start'],
                ['name' => 'end', 'type' => 'end']
            ],
            'transitions' => [
                ['name' => 'complete', 'from' => ['start'], 'to' => 'end']
            ]
        ];

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);
        $mockDefinition->shouldReceive('saveConfig')
            ->once()
            ->with($config);

        $this->workflowDefinitionRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Test Workflow',
                'type' => 'test_type',
                'is_active' => true
            ])
            ->andReturn($mockDefinition);

        $result = $this->workflowService->createDefinition('Test Workflow', 'test_type', $config);

        $this->assertSame($mockDefinition, $result);
    }

    #[Test]
    public function throws_exception_for_invalid_config()
    {
        $this->expectException(InvalidWorkflowConfigException::class);
        $this->expectExceptionMessage('Missing required config key: places');

        $invalidConfig = [
            'initial_place' => 'start',
            'transitions' => []
        ];

        $this->workflowService->createDefinition('Invalid Workflow', 'invalid', $invalidConfig);
    }

    #[Test]
    public function throws_exception_for_invalid_initial_place()
    {
        $this->expectException(InvalidWorkflowConfigException::class);
        $this->expectExceptionMessage('Initial place not found in places');

        $invalidConfig = [
            'initial_place' => 'nonexistent',
            'places' => [
                ['name' => 'start', 'type' => 'start'],
                ['name' => 'end', 'type' => 'end']
            ],
            'transitions' => []
        ];

        $this->workflowService->createDefinition('Invalid Workflow', 'invalid', $invalidConfig);
    }

    #[Test]
    public function can_start_workflow()
    {
        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockViolation->id = 1;

        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->id = 1;
        $mockConfig->shouldReceive('getInitialPlace')
            ->once()
            ->andReturn('detected');

        $mockDefinition = Mockery::mock(WorkflowDefinition::class);
        $mockDefinition->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockDefinition->shouldReceive('hasReachedMaxInstances')
            ->once()
            ->andReturn(false);
        $mockDefinition->currentConfig = $mockConfig;

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        
        $this->workflowInstanceRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'workflow_definition_config_id' => 1,
                'subject_type' => get_class($mockViolation),
                'subject_id' => 1,
                'current_place' => 'detected'
            ])
            ->andReturn($mockInstance);

        $result = $this->workflowService->startWorkflow($mockDefinition, $mockViolation);

        $this->assertSame($mockInstance, $result);
    }

    #[Test]
    public function throws_exception_when_workflow_not_runnable()
    {
        $this->expectException(InvalidWorkflowConfigException::class);
        $this->expectExceptionMessage('Workflow definition is not runnable');

        $mockViolation = Mockery::mock(ShiftRuleViolation::class);
        $mockDefinition = Mockery::mock(WorkflowDefinition::class);
        $mockDefinition->shouldReceive('isRunnable')
            ->once()
            ->andReturn(false);

        $this->workflowService->startWorkflow($mockDefinition, $mockViolation);
    }

    #[Test]
    public function can_get_available_transitions()
    {
        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->shouldReceive('getTransitions')
            ->once()
            ->andReturn([
                ['name' => 'start_review', 'from' => ['detected'], 'to' => 'in_review'],
                ['name' => 'resolve', 'from' => ['in_review'], 'to' => 'resolved'],
                ['name' => 'dismiss', 'from' => ['detected'], 'to' => 'dismissed']
            ]);

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->current_place = 'detected';
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockInstance->workflowDefinitionConfig = $mockConfig;

        $transitions = $this->workflowService->getAvailableTransitions($mockInstance);

        $this->assertCount(2, $transitions);
        $this->assertEquals('start_review', $transitions[0]['name']);
        $this->assertEquals('dismiss', $transitions[1]['name']);
    }

    #[Test]
    public function returns_empty_array_for_non_runnable_instance()
    {
        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(false);

        $transitions = $this->workflowService->getAvailableTransitions($mockInstance);

        $this->assertEmpty($transitions);
    }

    #[Test]
    public function can_execute_transition()
    {
        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->shouldReceive('getTransitions')
            ->once()
            ->andReturn([
                [
                    'name' => 'start_review',
                    'from' => ['detected'],
                    'to' => 'in_review',
                    'actions' => []
                ]
            ]);
        $mockConfig->shouldReceive('getPlaces')
            ->once()
            ->andReturn([
                ['name' => 'in_review', 'type' => 'intermediate']
            ]);

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->current_place = 'detected';
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockInstance->shouldReceive('transitionTo')
            ->once()
            ->with('in_review', 'start_review');
        $mockInstance->workflowDefinitionConfig = $mockConfig;

        $result = $this->workflowService->executeTransition($mockInstance, 'start_review');

        $this->assertTrue($result);
    }

    #[Test]
    public function cannot_execute_invalid_transition()
    {
        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->shouldReceive('getTransitions')
            ->once()
            ->andReturn([
                ['name' => 'different_transition', 'from' => ['detected'], 'to' => 'in_review']
            ]);

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->current_place = 'detected';
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockInstance->workflowDefinitionConfig = $mockConfig;

        $result = $this->workflowService->executeTransition($mockInstance, 'nonexistent_transition');

        $this->assertFalse($result);
    }

    #[Test]
    public function cannot_execute_transition_from_wrong_place()
    {
        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->shouldReceive('getTransitions')
            ->once()
            ->andReturn([
                ['name' => 'start_review', 'from' => ['different_place'], 'to' => 'in_review']
            ]);

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->current_place = 'detected';
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockInstance->workflowDefinitionConfig = $mockConfig;

        $result = $this->workflowService->executeTransition($mockInstance, 'start_review');

        $this->assertFalse($result);
    }

    #[Test]
    public function completes_workflow_when_reaching_end_state()
    {
        $mockConfig = Mockery::mock(WorkflowDefinitionConfig::class);
        $mockConfig->shouldReceive('getTransitions')
            ->once()
            ->andReturn([
                [
                    'name' => 'resolve',
                    'from' => ['detected'],
                    'to' => 'resolved',
                    'actions' => []
                ]
            ]);
        $mockConfig->shouldReceive('getPlaces')
            ->once()
            ->andReturn([
                ['name' => 'resolved', 'type' => 'end']
            ]);

        $mockInstance = Mockery::mock(WorkflowInstance::class);
        $mockInstance->current_place = 'detected';
        $mockInstance->shouldReceive('isRunnable')
            ->once()
            ->andReturn(true);
        $mockInstance->shouldReceive('transitionTo')
            ->once()
            ->with('resolved', 'resolve');
        $mockInstance->shouldReceive('complete')
            ->once();
        $mockInstance->workflowDefinitionConfig = $mockConfig;

        $result = $this->workflowService->executeTransition($mockInstance, 'resolve');

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}