<?php

namespace Tests\Feature\Artwork\Modules\Workflow;

use Tests\TestCase;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowDefinitionConfig;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Services\WorkflowService;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;

class CompleteWorkflowIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private WorkflowService $workflowService;
    private ShiftRuleService $shiftRuleService;
    private WorkflowDefinition $workflowDefinition;
    private ShiftRule $shiftRule;
    private User $user;
    private UserContract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = app(WorkflowService::class);
        $this->shiftRuleService = app(ShiftRuleService::class);

        $this->createWorkflowDefinition();
        $this->createTestData();
    }

    private function createWorkflowDefinition(): void
    {
        $this->workflowDefinition = WorkflowDefinition::create([
            'name' => 'Shift Violation Management',
            'type' => 'shift_violation_management',
            'description' => 'Test workflow for shift violations',
            'is_active' => true
        ]);

        WorkflowDefinitionConfig::create([
            'workflow_definition_id' => $this->workflowDefinition->id,
            'config' => [
                'initial_place' => 'detected',
                'places' => [
                    ['name' => 'detected', 'type' => 'start', 'label' => 'Erkannt'],
                    ['name' => 'in_review', 'type' => 'intermediate', 'label' => 'In Bearbeitung'],
                    ['name' => 'resolved', 'type' => 'end', 'label' => 'Gelöst'],
                    ['name' => 'dismissed', 'type' => 'end', 'label' => 'Verworfen']
                ],
                'transitions' => [
                    [
                        'name' => 'start_review',
                        'label' => 'Bearbeitung starten',
                        'from' => ['detected'],
                        'to' => 'in_review',
                        'actions' => [
                            [
                                'action' => 'update_data',
                                'parameters' => [
                                    'data' => [
                                        'review_started_at' => '@now',
                                        'status' => 'in_review'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'resolve_violation',
                        'label' => 'Verstoß lösen',
                        'from' => ['detected', 'in_review'],
                        'to' => 'resolved',
                        'actions' => [
                            [
                                'action' => 'update_data',
                                'parameters' => [
                                    'data' => [
                                        'resolved_at' => '@now',
                                        'status' => 'resolved'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'dismiss_violation',
                        'label' => 'Verstoß verwerfen',
                        'from' => ['detected', 'in_review'],
                        'to' => 'dismissed'
                    ]
                ]
            ]
        ]);
    }

    private function createTestData(): void
    {
        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);

        $this->contract = UserContract::factory()->create([
            'contract_name' => 'Test Contract'
        ]);

        UserContractAssign::create([
            'user_id' => $this->user->id,
            'user_contract_id' => $this->contract->id
        ]);

        $this->shiftRule = ShiftRule::create([
            'name' => 'Max 8h/Tag',
            'description' => 'Maximum 8 hours per day',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true
        ]);

        $this->shiftRule->contracts()->attach($this->contract->id);
    }

    #[Test]
    public function complete_workflow_integration_test()
    {
        // Step 1: Create a shift that violates the rule
        $project = Project::factory()->create();
        $room = Room::factory()->create();
        $craft = Craft::factory()->create();

        $event = Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay()->setTime(8, 0),
            'end_time' => now()->addDay()->setTime(20, 0),
        ]);

        $shift = Shift::create([
            'event_id' => $event->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'start' => '08:00',
            'end' => '20:00', // 12 hours - violates 8h rule
            'break_minutes' => 60,
            'craft_id' => $craft->id,
            'description' => 'Test shift for workflow integration',
            'is_committed' => false,
        ]);

        $shiftQualification = $shift->shiftQualifications()->create([
            'value' => 1,
            'available' => true,
            'qualification_id' => 1
        ]);

        $shift->users()->attach($this->user->id, [
            'shift_qualification_id' => $shiftQualification->id
        ]);

        // Step 2: Run shift rule validation (should create violation and start workflow)
        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            now(),
            now()->addDays(7)
        );

        $this->assertCount(1, $violations);
        
        /** @var ShiftRuleViolation $violation */
        $violation = $violations->first();
        $this->assertInstanceOf(ShiftRuleViolation::class, $violation);
        $this->assertEquals($this->shiftRule->id, $violation->shift_rule_id);
        $this->assertEquals($shift->id, $violation->shift_id);
        $this->assertEquals($this->user->id, $violation->user_id);

        // Step 3: Check that workflow was automatically started
        $this->assertTrue($violation->hasActiveWorkflow('shift_violation_management'));
        
        $workflowInstance = $violation->getActiveWorkflows()->first();
        $this->assertInstanceOf(WorkflowInstance::class, $workflowInstance);
        $this->assertEquals('detected', $workflowInstance->current_place);

        // Step 4: Test available transitions
        $transitions = $this->workflowService->getAvailableTransitions($workflowInstance);
        $transitionNames = collect($transitions)->pluck('name')->toArray();
        
        $this->assertContains('start_review', $transitionNames);
        $this->assertContains('resolve_violation', $transitionNames);
        $this->assertContains('dismiss_violation', $transitionNames);

        // Step 5: Execute transition to start review
        $success = $this->workflowService->executeTransition($workflowInstance, 'start_review');
        
        $this->assertTrue($success);
        $workflowInstance->refresh();
        $this->assertEquals('in_review', $workflowInstance->current_place);

        // Step 6: Check workflow data was updated
        $workflowData = $workflowInstance->getData();
        $this->assertArrayHasKey('review_started_at', $workflowData);
        $this->assertEquals('in_review', $workflowData['status']);

        // Step 7: Test transitions from in_review state
        $newTransitions = $this->workflowService->getAvailableTransitions($workflowInstance);
        $newTransitionNames = collect($newTransitions)->pluck('name')->toArray();
        
        $this->assertNotContains('start_review', $newTransitionNames);
        $this->assertContains('resolve_violation', $newTransitionNames);
        $this->assertContains('dismiss_violation', $newTransitionNames);

        // Step 8: Resolve the violation (end state)
        $success = $this->workflowService->executeTransition($workflowInstance, 'resolve_violation');
        
        $this->assertTrue($success);
        $workflowInstance->refresh();
        $this->assertEquals('resolved', $workflowInstance->current_place);
        
        // Check workflow is completed
        $this->assertTrue($workflowInstance->isCompleted());
        $this->assertNotNull($workflowInstance->completed_at);

        // Step 9: No more transitions available
        $finalTransitions = $this->workflowService->getAvailableTransitions($workflowInstance);
        $this->assertEmpty($finalTransitions);

        // Step 10: Check workflow data was updated with resolution info
        $finalData = $workflowInstance->getData();
        $this->assertArrayHasKey('resolved_at', $finalData);
        $this->assertEquals('resolved', $finalData['status']);
    }

    #[Test]
    public function can_dismiss_violation_directly()
    {
        // Create violation and workflow
        $violation = $this->createViolationWithWorkflow();
        $workflowInstance = $violation->getActiveWorkflows()->first();

        // Dismiss directly from detected state
        $success = $this->workflowService->executeTransition($workflowInstance, 'dismiss_violation');
        
        $this->assertTrue($success);
        $workflowInstance->refresh();
        $this->assertEquals('dismissed', $workflowInstance->current_place);
        $this->assertTrue($workflowInstance->isCompleted());
    }

    #[Test]
    public function workflow_logs_transitions()
    {
        $violation = $this->createViolationWithWorkflow();
        $workflowInstance = $violation->getActiveWorkflows()->first();

        // Execute multiple transitions
        $this->workflowService->executeTransition($workflowInstance, 'start_review');
        $this->workflowService->executeTransition($workflowInstance, 'resolve_violation');

        $workflowInstance->refresh();
        $logs = $workflowInstance->workflowLogs;

        $this->assertCount(2, $logs);
        
        $firstLog = $logs->first();
        $this->assertEquals('start_review', $firstLog->transition);
        $this->assertEquals('detected', $firstLog->from_place);
        $this->assertEquals('in_review', $firstLog->to_place);

        $secondLog = $logs->last();
        $this->assertEquals('resolve_violation', $secondLog->transition);
        $this->assertEquals('in_review', $secondLog->from_place);
        $this->assertEquals('resolved', $secondLog->to_place);
    }

    #[Test]
    public function cannot_execute_invalid_transitions()
    {
        $violation = $this->createViolationWithWorkflow();
        $workflowInstance = $violation->getActiveWorkflows()->first();

        // Try to execute non-existent transition
        $success = $this->workflowService->executeTransition($workflowInstance, 'invalid_transition');
        $this->assertFalse($success);

        // Current place should remain unchanged
        $workflowInstance->refresh();
        $this->assertEquals('detected', $workflowInstance->current_place);
    }

    #[Test]
    public function multiple_violations_get_separate_workflows()
    {
        // Create two violations
        $violation1 = $this->createViolationWithWorkflow();
        $violation2 = $this->createViolationWithWorkflow();

        // Each should have its own workflow
        $this->assertTrue($violation1->hasActiveWorkflow('shift_violation_management'));
        $this->assertTrue($violation2->hasActiveWorkflow('shift_violation_management'));

        $workflow1 = $violation1->getActiveWorkflows()->first();
        $workflow2 = $violation2->getActiveWorkflows()->first();

        $this->assertNotEquals($workflow1->id, $workflow2->id);

        // Transitions on one workflow don't affect the other
        $this->workflowService->executeTransition($workflow1, 'start_review');
        
        $workflow1->refresh();
        $workflow2->refresh();
        
        $this->assertEquals('in_review', $workflow1->current_place);
        $this->assertEquals('detected', $workflow2->current_place);
    }

    private function createViolationWithWorkflow(): ShiftRuleViolation
    {
        $project = Project::factory()->create();
        $room = Room::factory()->create();
        $craft = Craft::factory()->create();

        $event = Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $room->id,
        ]);

        $shift = Shift::create([
            'event_id' => $event->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'start' => '08:00',
            'end' => '20:00',
            'break_minutes' => 60,
            'craft_id' => $craft->id,
            'is_committed' => false,
        ]);

        $shiftQualification = $shift->shiftQualifications()->create([
            'value' => 1,
            'available' => true,
            'qualification_id' => 1
        ]);

        $shift->users()->attach($this->user->id, [
            'shift_qualification_id' => $shiftQualification->id
        ]);

        $violations = $this->shiftRuleService->validateRulesForUser(
            $this->user,
            now(),
            now()->addDays(7)
        );

        return $violations->first();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}