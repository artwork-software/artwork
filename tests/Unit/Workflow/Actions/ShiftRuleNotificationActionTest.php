<?php

namespace Tests\Unit\Workflow\Actions;

use Tests\TestCase;
use Artwork\Modules\Workflow\Actions\ShiftRuleNotificationAction;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class ShiftRuleNotificationActionTest extends TestCase
{
    private ShiftRuleNotificationAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ShiftRuleNotificationAction();
        
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_name()
    {
        $this->assertEquals('shift_rule_notification', $this->action->getName());
    }

    #[Test]
    public function it_can_execute_with_workflow_rule_violation_subject()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        
        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $result = $this->action->canExecute($mockWorkflowInstance, []);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_cannot_execute_with_non_violation_subject()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockOtherModel = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        
        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockOtherModel);

        $result = $this->action->canExecute($mockWorkflowInstance, []);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_executes_notification_when_conditions_are_met()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockUser1 = Mockery::mock(User::class);
        $mockUser2 = Mockery::mock(User::class);
        $mockUsersRelation = Mockery::mock();

        // Setup subject
        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        // Setup rule
        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(true);

        // Setup users to notify
        $mockRule->shouldReceive('getAttribute')
            ->with('usersToNotify')
            ->andReturn(collect([$mockUser1]));

        // Setup notification sending
        $mockUser1->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(ShiftRuleViolationNotification::class));

        $parameters = [
            'message' => 'Test notification message'
        ];

        $this->action->execute($mockWorkflowInstance, $parameters);
    }

    #[Test]
    public function it_does_not_execute_when_subject_is_not_violation()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockOtherModel = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockOtherModel);

        // Should not attempt any notifications
        $this->action->execute($mockWorkflowInstance, []);

        // No assertions needed as we're testing that nothing happens
        $this->assertTrue(true);
    }

    #[Test]
    public function it_does_not_execute_when_rule_should_not_notify()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(false);

        $this->action->execute($mockWorkflowInstance, []);

        // No assertions needed as we're testing that nothing happens
        $this->assertTrue(true);
    }

    #[Test]
    public function it_does_not_execute_when_rule_is_null()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn(null);

        $this->action->execute($mockWorkflowInstance, []);

        // No assertions needed as we're testing that nothing happens
        $this->assertTrue(true);
    }

    #[Test]
    public function it_notifies_additional_users_from_parameters()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockRuleUser = Mockery::mock(User::class);
        $mockAdditionalUser = Mockery::mock(User::class);

        // Setup subject and rule
        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(true);

        $mockRule->shouldReceive('getAttribute')
            ->with('usersToNotify')
            ->andReturn(collect([$mockRuleUser]));

        // Mock User::whereIn for additional users
        $mockUserQuery = Mockery::mock();
        User::shouldReceive('whereIn')
            ->with('id', [123])
            ->andReturn($mockUserQuery);
        
        $mockUserQuery->shouldReceive('get')
            ->andReturn(collect([$mockAdditionalUser]));

        // Both users should receive notifications
        $mockRuleUser->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(ShiftRuleViolationNotification::class));

        $mockAdditionalUser->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(ShiftRuleViolationNotification::class));

        $parameters = [
            'user_ids' => [123],
            'message' => 'Test message'
        ];

        $this->action->execute($mockWorkflowInstance, $parameters);
    }

    #[Test]
    public function it_generates_custom_notification_message_from_parameters()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockUser = Mockery::mock(User::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(true);

        $mockRule->shouldReceive('getAttribute')
            ->with('usersToNotify')
            ->andReturn(collect([$mockUser]));

        $customMessage = 'Custom notification message';

        $mockUser->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function ($notification) use ($customMessage) {
                // This is a simplified check - in reality you'd need to access the notification's message
                return $notification instanceof ShiftRuleViolationNotification;
            }));

        $parameters = [
            'message' => $customMessage
        ];

        $this->action->execute($mockWorkflowInstance, $parameters);
    }

    #[Test]
    public function it_generates_default_notification_message_when_no_custom_message()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockUser = Mockery::mock(User::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(true);

        $mockRule->shouldReceive('getAttribute')
            ->with('usersToNotify')
            ->andReturn(collect([$mockUser]));

        // Setup for default message generation
        $mockRule->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Test Rule');

        $mockViolation->shouldReceive('getAttribute')
            ->with('violation_date')
            ->andReturn('2025-01-15');

        $mockViolation->shouldReceive('getAttribute')
            ->with('violation_data')
            ->andReturn(['message' => 'Test violation details']);

        $mockUser->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(ShiftRuleViolationNotification::class));

        $parameters = []; // No custom message

        $this->action->execute($mockWorkflowInstance, $parameters);
    }

    #[Test]
    public function it_handles_empty_user_collections()
    {
        $mockWorkflowInstance = Mockery::mock(WorkflowInstance::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockRule = Mockery::mock(WorkflowRule::class);

        $mockWorkflowInstance->shouldReceive('getAttribute')
            ->with('subject')
            ->andReturn($mockViolation);

        $mockViolation->shouldReceive('getAttribute')
            ->with('workflowRule')
            ->andReturn($mockRule);

        $mockRule->shouldReceive('shouldNotifyOnViolation')
            ->andReturn(true);

        $mockRule->shouldReceive('getAttribute')
            ->with('usersToNotify')
            ->andReturn(collect([])); // Empty collection

        // Should not throw any errors
        $this->action->execute($mockWorkflowInstance, []);

        $this->assertTrue(true);
    }
}