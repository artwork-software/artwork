<?php

namespace Tests\Unit\Workflow;

use Tests\TestCase;
use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\Workflow\Repositories\WorkflowRuleRepository;
use Artwork\Modules\Workflow\Repositories\WorkflowRuleViolationRepository;
use Artwork\Modules\Workflow\Contracts\WorkflowRule as WorkflowRuleContract;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class WorkflowRuleServiceTest extends TestCase
{
    private WorkflowRuleService $service;
    private WorkflowRuleRepository $mockRuleRepository;
    private WorkflowRuleViolationRepository $mockViolationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRuleRepository = Mockery::mock(WorkflowRuleRepository::class);
        $this->mockViolationRepository = Mockery::mock(WorkflowRuleViolationRepository::class);

        $this->service = new WorkflowRuleService(
            $this->mockRuleRepository,
            $this->mockViolationRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testCanRegisterARule(): void
    {
        $mockRule = Mockery::mock(WorkflowRuleContract::class);
        $mockRule->shouldReceive('getName')
            ->once()
            ->andReturn('test_rule');

        $this->service->registerRule($mockRule);

        $availableTypes = $this->service->getAvailableRuleTypes();

        $this->assertContains('test_rule', $availableTypes);
    }

    #[Test]
    public function testCanCreateARule(): void
    {
        $expectedData = [
            'name' => 'Test Rule',
            'trigger_type' => 'max_working_hours_on_day',
            'individual_number_value' => 8.0,
            'configuration' => ['test' => 'config'],
            'is_active' => true
        ];

        $mockRule = Mockery::mock(WorkflowRule::class);

        $this->mockRuleRepository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($mockRule);

        $result = $this->service->createRule(
            name: 'Test Rule',
            triggerType: 'max_working_hours_on_day',
            value: 8.0,
            configuration: ['test' => 'config']
        );

        $this->assertSame($mockRule, $result);
    }

    #[Test]
    public function testCanAssignRuleToSubject(): void
    {
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockSubject = Mockery::mock(Model::class);
        $mockRelation = Mockery::mock();

        $mockSubject->shouldReceive('getKey')->andReturn(1);

        $mockRule->shouldReceive('workflowRuleAssignments')
            ->once()
            ->andReturn($mockRelation);

        $mockRelation->shouldReceive('create')
            ->once()
            ->with([
                'subject_type' => get_class($mockSubject),
                'subject_id' => 1,
                'assigned_at' => Mockery::type(\Illuminate\Support\Carbon::class),
                'assigned_by' => 123
            ]);

        $this->service->assignRuleToSubject($mockRule, $mockSubject, 123);
    }

    #[Test]
    public function testValidatesRulesForSubject(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockRule = Mockery::mock(WorkflowRule::class);
        $mockRuleImplementation = Mockery::mock(WorkflowRuleContract::class);
        $mockViolation = Mockery::mock(WorkflowRuleViolation::class);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(7);

        // Mock rule repository
        $this->mockRuleRepository
            ->shouldReceive('getActiveRulesForSubject')
            ->once()
            ->with($mockSubject)
            ->andReturn(collect([$mockRule]));

        // Mock rule configuration
        $mockRule->shouldReceive('getValidationConfig')
            ->andReturn([
                'trigger_type' => 'test_rule',
                'value' => 8,
                'warning_color' => '#ff0000'
            ]);

        $mockRule->shouldReceive('getAttribute')
            ->with('trigger_type')
            ->andReturn('test_rule');

        // Register test rule
        $mockRuleImplementation->shouldReceive('getName')
            ->andReturn('test_rule');

        $mockRuleImplementation->shouldReceive('validate')
            ->andReturn([
                [
                    'date' => $startDate->toDateString(),
                    'message' => 'Test violation'
                ]
            ]);

        $this->service->registerRule($mockRuleImplementation);

        // Mock violation creation
        $this->mockViolationRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($mockViolation);

        $result = $this->service->validateRulesForSubject($mockSubject, $startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function testReturnsEmptyCollectionWhenNoRulesActive(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(7);

        $this->mockRuleRepository
            ->shouldReceive('getActiveRulesForSubject')
            ->once()
            ->with($mockSubject)
            ->andReturn(collect([]));

        $result = $this->service->validateRulesForSubject($mockSubject, $startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function testSkipsValidationWhenRuleImplementationNotFound(): void
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockRule = Mockery::mock(WorkflowRule::class);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(7);

        $this->mockRuleRepository
            ->shouldReceive('getActiveRulesForSubject')
            ->once()
            ->with($mockSubject)
            ->andReturn(collect([$mockRule]));

        $mockRule->shouldReceive('getAttribute')
            ->with('trigger_type')
            ->andReturn('non_existent_rule');

        $result = $this->service->validateRulesForSubject($mockSubject, $startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function testGetsRuleConfiguration(): void
    {
        $mockRule = Mockery::mock(WorkflowRuleContract::class);
        $expectedConfig = [
            'fields' => [
                'max_hours' => [
                    'type' => 'number',
                    'label' => 'Maximum Hours',
                    'default' => 8
                ]
            ]
        ];

        $mockRule->shouldReceive('getName')
            ->andReturn('test_rule');

        $mockRule->shouldReceive('getConfiguration')
            ->once()
            ->andReturn($expectedConfig);

        $this->service->registerRule($mockRule);

        $result = $this->service->getRuleConfiguration('test_rule');

        $this->assertEquals($expectedConfig, $result);
    }

    #[Test]
    public function testReturnsEmptyArrayForUnknownRuleConfiguration(): void
    {
        $result = $this->service->getRuleConfiguration('unknown_rule');

        $this->assertEquals([], $result);
    }

    #[Test]
    public function it_gets_available_rule_types()
    {
        $mockRule1 = Mockery::mock(WorkflowRuleContract::class);
        $mockRule1->shouldReceive('getName')->andReturn('rule_one');

        $mockRule2 = Mockery::mock(WorkflowRuleContract::class);
        $mockRule2->shouldReceive('getName')->andReturn('rule_two');

        $this->service->registerRule($mockRule1);
        $this->service->registerRule($mockRule2);

        $types = $this->service->getAvailableRuleTypes();

        $this->assertIsArray($types);
        $this->assertContains('rule_one', $types);
        $this->assertContains('rule_two', $types);
    }
}
