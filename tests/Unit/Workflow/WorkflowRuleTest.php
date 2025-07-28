<?php

namespace Tests\Unit\Workflow;

use Tests\TestCase;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\Workflow\Models\WorkflowRuleAssignment;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Contract\Models\Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class WorkflowRuleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $rule = new WorkflowRule();
        
        $expectedFillable = [
            'name',
            'description',
            'trigger_type',
            'individual_number_value',
            'warning_color',
            'is_active',
            'configuration',
            'notify_on_violation'
        ];

        $this->assertEquals($expectedFillable, $rule->getFillable());
    }

    #[Test]
    public function it_casts_attributes_correctly()
    {
        $rule = new WorkflowRule([
            'individual_number_value' => '8.5',
            'is_active' => '1',
            'notify_on_violation' => '1',
            'configuration' => '{"test": "value"}'
        ]);

        $this->assertIsFloat($rule->individual_number_value);
        $this->assertEquals(8.5, $rule->individual_number_value);
        
        $this->assertIsBool($rule->is_active);
        $this->assertTrue($rule->is_active);
        
        $this->assertIsBool($rule->notify_on_violation);
        $this->assertTrue($rule->notify_on_violation);
        
        $this->assertIsArray($rule->configuration);
        $this->assertEquals(['test' => 'value'], $rule->configuration);
    }

    #[Test]
    public function it_has_workflow_rule_violations_relationship()
    {
        $rule = new WorkflowRule();
        $relation = $rule->workflowRuleViolations();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(WorkflowRuleViolation::class, $relation->getRelated()::class);
    }

    #[Test]
    public function it_has_workflow_rule_assignments_relationship()
    {
        $rule = new WorkflowRule();
        $relation = $rule->workflowRuleAssignments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(WorkflowRuleAssignment::class, $relation->getRelated()::class);
    }

    #[Test]
    public function it_has_users_to_notify_relationship()
    {
        $rule = new WorkflowRule();
        $relation = $rule->usersToNotify();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    #[Test]
    public function it_has_contracts_relationship()
    {
        $rule = new WorkflowRule();
        $relation = $rule->contracts();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(Contract::class, $relation->getRelated()::class);
    }

    #[Test]
    public function it_checks_if_active_for_subject_when_rule_is_active()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('getKey')->andReturn(1);
        
        $mockAssignmentsRelation = Mockery::mock();
        
        $rule = Mockery::mock(WorkflowRule::class)->makePartial();
        $rule->is_active = true;
        
        $rule->shouldReceive('workflowRuleAssignments')
            ->once()
            ->andReturn($mockAssignmentsRelation);

        $mockAssignmentsRelation->shouldReceive('where')
            ->with('subject_type', get_class($mockSubject))
            ->andReturn($mockAssignmentsRelation);
            
        $mockAssignmentsRelation->shouldReceive('where')
            ->with('subject_id', 1)
            ->andReturn($mockAssignmentsRelation);
            
        $mockAssignmentsRelation->shouldReceive('exists')
            ->andReturn(true);

        $result = $rule->isActiveForSubject($mockSubject);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_rule_is_not_active()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        $rule = new WorkflowRule();
        $rule->is_active = false;

        $result = $rule->isActiveForSubject($mockSubject);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_no_assignment_exists()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('getKey')->andReturn(1);
        
        $mockAssignmentsRelation = Mockery::mock();
        
        $rule = Mockery::mock(WorkflowRule::class)->makePartial();
        $rule->is_active = true;
        
        $rule->shouldReceive('workflowRuleAssignments')
            ->once()
            ->andReturn($mockAssignmentsRelation);

        $mockAssignmentsRelation->shouldReceive('where')
            ->with('subject_type', get_class($mockSubject))
            ->andReturn($mockAssignmentsRelation);
            
        $mockAssignmentsRelation->shouldReceive('where')
            ->with('subject_id', 1)
            ->andReturn($mockAssignmentsRelation);
            
        $mockAssignmentsRelation->shouldReceive('exists')
            ->andReturn(false);

        $result = $rule->isActiveForSubject($mockSubject);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_gets_validation_config_with_basic_attributes()
    {
        $rule = new WorkflowRule([
            'trigger_type' => 'max_working_hours_on_day',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff6b6b'
        ]);

        $config = $rule->getValidationConfig();

        $expected = [
            'trigger_type' => 'max_working_hours_on_day',
            'value' => 8.0,
            'warning_color' => '#ff6b6b'
        ];

        $this->assertEquals($expected, $config);
    }

    #[Test]
    public function it_merges_configuration_in_validation_config()
    {
        $rule = new WorkflowRule([
            'trigger_type' => 'max_working_hours_on_day',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff6b6b',
            'configuration' => [
                'description' => 'Test description',
                'custom_field' => 'custom_value'
            ]
        ]);

        $config = $rule->getValidationConfig();

        $expected = [
            'trigger_type' => 'max_working_hours_on_day',
            'value' => 8.0,
            'warning_color' => '#ff6b6b',
            'description' => 'Test description',
            'custom_field' => 'custom_value'
        ];

        $this->assertEquals($expected, $config);
    }

    #[Test]
    public function it_handles_null_configuration_in_validation_config()
    {
        $rule = new WorkflowRule([
            'trigger_type' => 'max_working_hours_on_day',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff6b6b',
            'configuration' => null
        ]);

        $config = $rule->getValidationConfig();

        $expected = [
            'trigger_type' => 'max_working_hours_on_day',
            'value' => 8.0,
            'warning_color' => '#ff6b6b'
        ];

        $this->assertEquals($expected, $config);
    }

    #[Test]
    public function it_should_notify_on_violation_when_flag_is_true()
    {
        $rule = new WorkflowRule([
            'notify_on_violation' => true
        ]);

        $this->assertTrue($rule->shouldNotifyOnViolation());
    }

    #[Test]
    public function it_should_not_notify_on_violation_when_flag_is_false()
    {
        $rule = new WorkflowRule([
            'notify_on_violation' => false
        ]);

        $this->assertFalse($rule->shouldNotifyOnViolation());
    }

    #[Test]
    public function it_should_not_notify_on_violation_when_flag_is_null()
    {
        $rule = new WorkflowRule([
            'notify_on_violation' => null
        ]);

        $this->assertFalse($rule->shouldNotifyOnViolation());
    }

    #[Test]
    public function it_uses_soft_deletes()
    {
        $rule = new WorkflowRule();
        
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($rule));
    }

    #[Test]
    public function it_can_be_mass_assigned_with_all_fillable_attributes()
    {
        $data = [
            'name' => 'Test Rule',
            'description' => 'Test Description',
            'trigger_type' => 'max_working_hours_on_day',
            'individual_number_value' => 8.5,
            'warning_color' => '#ff6b6b',
            'is_active' => true,
            'configuration' => ['test' => 'value'],
            'notify_on_violation' => true
        ];

        $rule = new WorkflowRule($data);

        $this->assertEquals('Test Rule', $rule->name);
        $this->assertEquals('Test Description', $rule->description);
        $this->assertEquals('max_working_hours_on_day', $rule->trigger_type);
        $this->assertEquals(8.5, $rule->individual_number_value);
        $this->assertEquals('#ff6b6b', $rule->warning_color);
        $this->assertTrue($rule->is_active);
        $this->assertEquals(['test' => 'value'], $rule->configuration);
        $this->assertTrue($rule->notify_on_violation);
    }
}