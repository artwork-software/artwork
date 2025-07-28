<?php

namespace Tests\Unit\Workflow\Rules;

use Tests\TestCase;
use Artwork\Modules\Workflow\Rules\MinDaysBeforeCommitRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class MinDaysBeforeCommitRuleTest extends TestCase
{
    private MinDaysBeforeCommitRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new MinDaysBeforeCommitRule();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_name()
    {
        $this->assertEquals('min_days_before_commit', $this->rule->getName());
    }

    #[Test]
    public function it_has_correct_description()
    {
        $this->assertEquals('Überprüft die Mindesttage bis zur Verbindlich-Schaltung einer Schicht', $this->rule->getDescription());
    }

    #[Test]
    public function it_detects_violation_when_uncommitted_shifts_exist()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        // Mock uncommitted shifts
        $mockShift1 = (object) [
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00',
            'end_time' => '2025-01-20 17:00:00',
            'event' => (object) ['name' => 'Test Event 1']
        ];
        
        $mockShift2 = (object) [
            'id' => 2,
            'start_time' => '2025-01-22 10:00:00',
            'end_time' => '2025-01-22 18:00:00',
            'event' => (object) ['name' => 'Test Event 2']
        ];

        $mockCollection = collect([$mockShift1, $mockShift2]);

        // Mock the getUncommittedShifts method by creating a partial mock
        $rule = Mockery::mock(MinDaysBeforeCommitRule::class)->makePartial();
        $rule->shouldReceive('getUncommittedShifts')
            ->andReturn($mockCollection);

        $context = [
            'value' => 14, // 14 days before commit required
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);
        
        $violation = $violations[0];
        $this->assertEquals(2, $violation['uncommitted_shifts_count']);
        $this->assertEquals(14, $violation['min_days']);
        $this->assertArrayHasKey('shifts', $violation);
        $this->assertCount(2, $violation['shifts']);
        $this->assertStringContainsString('Es gibt 2 nicht-verbindliche Schichten', $violation['message']);
    }

    #[Test]
    public function it_returns_no_violation_when_no_uncommitted_shifts()
    {
        $mockSubject = Mockery::mock(Model::class);

        // Mock empty collection
        $mockCollection = collect([]);

        $rule = Mockery::mock(MinDaysBeforeCommitRule::class)->makePartial();
        $rule->shouldReceive('getUncommittedShifts')
            ->andReturn($mockCollection);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_can_apply_to_any_subject()
    {
        $mockSubject = Mockery::mock(Model::class);

        $result = $this->rule->canApplyTo($mockSubject);

        // This rule is global and can apply to any subject
        $this->assertTrue($result);
    }

    #[Test]
    public function it_has_correct_configuration()
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('min_days', $config['fields']);
        $this->assertTrue($config['global_rule']);
        $this->assertTrue($config['notification_required']);
        
        $minDaysField = $config['fields']['min_days'];
        $this->assertEquals('number', $minDaysField['type']);
        $this->assertEquals('Mindesttage bis zur Verbindlich-Schaltung', $minDaysField['label']);
        $this->assertEquals(14, $minDaysField['default']);
        $this->assertEquals(1, $minDaysField['min']);
        $this->assertEquals(30, $minDaysField['max']);
    }

    #[Test]
    public function it_uses_default_value_when_context_missing()
    {
        $mockSubject = Mockery::mock(Model::class);

        $rule = Mockery::mock(MinDaysBeforeCommitRule::class)->makePartial();
        $rule->shouldReceive('getUncommittedShifts')
            ->andReturn(collect([]));

        $violations = $this->rule->validate($mockSubject, []);

        $this->assertIsArray($violations);
    }

    #[Test]
    public function it_calculates_days_until_shift_correctly()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));
        
        $mockShift = (object) [
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00', // 5 days from test date
            'end_time' => '2025-01-20 17:00:00',
            'event' => (object) ['name' => 'Test Event']
        ];

        $mockCollection = collect([$mockShift]);

        $rule = Mockery::mock(MinDaysBeforeCommitRule::class)->makePartial();
        $rule->shouldReceive('getUncommittedShifts')
            ->andReturn($mockCollection);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);
        
        $shiftData = $violations[0]['shifts'][0];
        $this->assertEquals(5, $shiftData['days_until_shift']);
    }

    #[Test]
    public function it_generates_notification_message_correctly()
    {
        $violationData = [
            'uncommitted_shifts_count' => 3,
            'min_days' => 10
        ];

        $message = $this->rule->generateNotificationMessage($violationData);

        $this->assertStringContainsString('Es gibt 3 nicht-verbindliche Schichten', $message);
        $this->assertStringContainsString('nächsten 10 Tagen', $message);
    }

    #[Test]
    public function it_gets_relevant_shifts_for_violation()
    {
        $violationData = [
            'shifts' => [
                ['id' => 1, 'name' => 'Shift 1'],
                ['id' => 2, 'name' => 'Shift 2']
            ]
        ];

        $shifts = $this->rule->getRelevantShiftsForViolation($violationData);

        $this->assertCount(2, $shifts);
        $this->assertEquals(['id' => 1, 'name' => 'Shift 1'], $shifts[0]);
    }

    #[Test]
    public function it_returns_empty_array_when_no_shifts_in_violation_data()
    {
        $violationData = ['other_data' => 'value'];

        $shifts = $this->rule->getRelevantShiftsForViolation($violationData);

        $this->assertEquals([], $shifts);
    }

    #[Test]
    public function it_handles_shifts_without_event_relation()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        $mockShift = (object) [
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00',
            'end_time' => '2025-01-20 17:00:00',
            'event' => null // No event relation
        ];

        $mockCollection = collect([$mockShift]);

        $rule = Mockery::mock(MinDaysBeforeCommitRule::class)->makePartial();
        $rule->shouldReceive('getUncommittedShifts')
            ->andReturn($mockCollection);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);
        
        $shiftData = $violations[0]['shifts'][0];
        $this->assertEquals('Unbekannt', $shiftData['event_name']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}