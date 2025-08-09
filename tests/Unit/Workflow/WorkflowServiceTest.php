<?php

namespace Tests\Unit\Workflow;

use Artwork\Modules\Workflow\Actions\ActionResolver;
use Artwork\Modules\Workflow\Exceptions\InvalidWorkflowConfigException;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Repositories\WorkflowDefinitionRepository;
use Artwork\Modules\Workflow\Repositories\WorkflowInstanceRepository;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowServiceTest extends TestCase
{
    private WorkflowService $workflowService;
    private WorkflowDefinitionRepository $definitionRepository;
    private WorkflowInstanceRepository $instanceRepository;
    private ActionResolver $actionResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->definitionRepository = app(WorkflowDefinitionRepository::class);
        $this->instanceRepository = app(WorkflowInstanceRepository::class);
        $this->actionResolver = app(ActionResolver::class);

        $this->workflowService = new WorkflowService(
            $this->definitionRepository,
            $this->instanceRepository,
            $this->actionResolver
        );
    }

    public function testCreateDefinitionWithValidConfig(): void
    {
        $config = [
            'places' => [
                ['name' => 'start', 'type' => 'start'],
                ['name' => 'end', 'type' => 'end']
            ],
            'transitions' => [
                ['name' => 'complete', 'from' => ['start'], 'to' => 'end']
            ],
            'initial_place' => 'start'
        ];

        $definition = $this->workflowService->createDefinition(
            'Test Workflow',
            'test',
            $config
        );

        $this->assertInstanceOf(WorkflowDefinition::class, $definition);
        $this->assertEquals('Test Workflow', $definition->name);
        $this->assertEquals('test', $definition->type);
        $this->assertTrue($definition->is_active);
        $this->assertNotNull($definition->currentConfig);
        $this->assertEquals($config, $definition->currentConfig->config);
    }

    public function testCreateDefinitionWithInvalidConfigThrowsException(): void
    {
        $this->expectException(InvalidWorkflowConfigException::class);

        $config = [
            'places' => [],
            'transitions' => []
        ];

        $this->workflowService->createDefinition(
            'Invalid Workflow',
            'test',
            $config
        );
    }

    public function testStartWorkflowCreatesInstance(): void
    {
        $definition = $this->createTestDefinition();
        $event = Event::factory()->create();

        $instance = $this->workflowService->startWorkflow($definition, $event);

        $this->assertInstanceOf(WorkflowInstance::class, $instance);
        $this->assertEquals($definition->currentConfig->id, $instance->workflow_definition_config_id);
        $this->assertEquals(get_class($event), $instance->subject_type);
        $this->assertEquals($event->id, $instance->subject_id);
        $this->assertEquals('start', $instance->current_place);
    }

    public function testExecuteTransitionChangesPlace(): void
    {
        $definition = $this->createTestDefinition();
        $event = Event::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $event);

        $result = $this->workflowService->executeTransition($instance, 'complete');

        $this->assertTrue($result);
        $instance->refresh();
        $this->assertEquals('end', $instance->current_place);
        $this->assertNotNull($instance->completed_at);
    }

    public function testGetAvailableTransitions(): void
    {
        $definition = $this->createTestDefinition();
        $event = Event::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $event);

        $transitions = $this->workflowService->getAvailableTransitions($instance);

        $this->assertCount(1, $transitions);
        $this->assertEquals('complete', $transitions[0]['name']);
    }

    private function createTestDefinition(): WorkflowDefinition
    {
        $config = [
            'places' => [
                ['name' => 'start', 'type' => 'start'],
                ['name' => 'end', 'type' => 'end']
            ],
            'transitions' => [
                ['name' => 'complete', 'from' => ['start'], 'to' => 'end']
            ],
            'initial_place' => 'start'
        ];

        return $this->workflowService->createDefinition(
            'Test Definition',
            'test',
            $config
        );
    }
}
