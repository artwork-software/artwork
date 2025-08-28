<?php

namespace Tests\Feature\Artwork\Modules\Workflow\Console;

use Tests\TestCase;
use Artwork\Modules\Workflow\Console\Commands\WorkflowManagementCommand;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowDefinitionConfig;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;

class WorkflowManagementCommandTest extends TestCase
{
    use DatabaseTransactions;

    private WorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowService = app(WorkflowService::class);
    }

    #[Test]
    public function list_command_shows_active_workflows()
    {
        // Create workflow definition
        $definition = $this->createWorkflowDefinition();
        
        // Create workflow instance
        $violation = ShiftRuleViolation::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $violation);

        // Run list command
        $this->artisan('workflow:manage list')
            ->expectsTable(
                ['ID', 'Type', 'Subject', 'Current Place', 'Started At'],
                [
                    [
                        $instance->id,
                        'test_workflow',
                        'ShiftRuleViolation #' . $violation->id,
                        'detected',
                        $instance->created_at->format('Y-m-d H:i:s')
                    ]
                ]
            )
            ->assertExitCode(0);
    }

    #[Test]
    public function list_command_filters_by_type()
    {
        // Create different workflow types
        $definition1 = $this->createWorkflowDefinition('test_workflow');
        $definition2 = $this->createWorkflowDefinition('other_workflow');
        
        $violation1 = ShiftRuleViolation::factory()->create();
        $violation2 = ShiftRuleViolation::factory()->create();
        
        $instance1 = $this->workflowService->startWorkflow($definition1, $violation1);
        $instance2 = $this->workflowService->startWorkflow($definition2, $violation2);

        // Filter by specific type
        $this->artisan('workflow:manage list --type=test_workflow')
            ->expectsTable(
                ['ID', 'Type', 'Subject', 'Current Place', 'Started At'],
                [
                    [
                        $instance1->id,
                        'test_workflow',
                        'ShiftRuleViolation #' . $violation1->id,
                        'detected',
                        $instance1->created_at->format('Y-m-d H:i:s')
                    ]
                ]
            )
            ->assertExitCode(0);
    }

    #[Test]
    public function list_command_shows_message_when_no_workflows()
    {
        $this->artisan('workflow:manage list')
            ->expectsOutput('No active workflow instances found.')
            ->assertExitCode(0);
    }

    #[Test]
    public function show_command_displays_workflow_details()
    {
        $definition = $this->createWorkflowDefinition();
        $violation = ShiftRuleViolation::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $violation);

        $this->artisan('workflow:manage show --id=' . $instance->id)
            ->expectsOutput("Workflow Instance #{$instance->id}")
            ->expectsOutput("Type: test_workflow")
            ->expectsOutput("Subject: " . get_class($violation) . " #{$violation->id}")
            ->expectsOutput("Current Place: detected")
            ->expectsOutput("Started: " . $instance->created_at->format('Y-m-d H:i:s'))
            ->expectsOutput("\nAvailable Transitions:")
            ->expectsOutput("- start_review: Bearbeitung starten")
            ->expectsOutput("- resolve: Lösen")
            ->assertExitCode(0);
    }

    #[Test]
    public function show_command_requires_id_parameter()
    {
        $this->artisan('workflow:manage show')
            ->expectsOutput('Please provide workflow instance ID with --id option')
            ->assertExitCode(1);
    }

    #[Test]
    public function show_command_handles_nonexistent_workflow()
    {
        $this->artisan('workflow:manage show --id=999')
            ->expectsOutput('Workflow instance 999 not found')
            ->assertExitCode(1);
    }

    #[Test]
    public function transition_command_executes_valid_transition()
    {
        $definition = $this->createWorkflowDefinition();
        $violation = ShiftRuleViolation::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $violation);

        $this->artisan('workflow:manage transition --id=' . $instance->id . ' --transition=start_review')
            ->expectsOutput("Transition 'start_review' executed successfully")
            ->expectsOutput("New state: in_review")
            ->assertExitCode(0);

        // Verify the transition actually happened
        $instance->refresh();
        $this->assertEquals('in_review', $instance->current_place);
    }

    #[Test]
    public function transition_command_requires_both_parameters()
    {
        $this->artisan('workflow:manage transition --id=1')
            ->expectsOutput('Please provide both --id and --transition options')
            ->assertExitCode(1);

        $this->artisan('workflow:manage transition --transition=start_review')
            ->expectsOutput('Please provide both --id and --transition options')
            ->assertExitCode(1);
    }

    #[Test]
    public function transition_command_handles_invalid_transition()
    {
        $definition = $this->createWorkflowDefinition();
        $violation = ShiftRuleViolation::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $violation);

        $this->artisan('workflow:manage transition --id=' . $instance->id . ' --transition=invalid_transition')
            ->expectsOutput("Failed to execute transition 'invalid_transition'")
            ->assertExitCode(1);
    }

    #[Test]
    public function transition_command_handles_nonexistent_workflow()
    {
        $this->artisan('workflow:manage transition --id=999 --transition=start_review')
            ->expectsOutput('Workflow instance 999 not found')
            ->assertExitCode(1);
    }

    #[Test]
    public function unknown_action_returns_error()
    {
        $this->artisan('workflow:manage unknown_action')
            ->expectsOutput('Unknown action: unknown_action')
            ->assertExitCode(1);
    }

    #[Test]
    public function show_command_displays_completed_workflow()
    {
        $definition = $this->createWorkflowDefinition();
        $violation = ShiftRuleViolation::factory()->create();
        $instance = $this->workflowService->startWorkflow($definition, $violation);
        
        // Complete the workflow
        $this->workflowService->executeTransition($instance, 'resolve');
        $instance->refresh();

        $this->artisan('workflow:manage show --id=' . $instance->id)
            ->expectsOutput("Workflow Instance #{$instance->id}")
            ->expectsOutput("Completed: " . $instance->completed_at->format('Y-m-d H:i:s'))
            ->expectsOutput("\nNo available transitions.")
            ->assertExitCode(0);
    }

    private function createWorkflowDefinition(string $type = 'test_workflow'): WorkflowDefinition
    {
        $definition = WorkflowDefinition::create([
            'name' => 'Test Workflow',
            'type' => $type,
            'description' => 'Test workflow for command testing',
            'is_active' => true
        ]);

        WorkflowDefinitionConfig::create([
            'workflow_definition_id' => $definition->id,
            'config' => [
                'initial_place' => 'detected',
                'places' => [
                    ['name' => 'detected', 'type' => 'start', 'label' => 'Erkannt'],
                    ['name' => 'in_review', 'type' => 'intermediate', 'label' => 'In Bearbeitung'],
                    ['name' => 'resolved', 'type' => 'end', 'label' => 'Gelöst']
                ],
                'transitions' => [
                    [
                        'name' => 'start_review',
                        'label' => 'Bearbeitung starten',
                        'from' => ['detected'],
                        'to' => 'in_review'
                    ],
                    [
                        'name' => 'resolve',
                        'label' => 'Lösen',
                        'from' => ['detected', 'in_review'],
                        'to' => 'resolved'
                    ]
                ]
            ]
        ]);

        return $definition;
    }
}