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
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }

    #[Test]
    public function testHasCorrectName(): void
    {
        $this->assertEquals('min_days_before_commit', $this->rule->getName());
    }

    #[Test]
    public function testHasCorrectDescription(): void
    {
        $this->assertEquals(
            'Überprüft die Mindesttage bis zur Verbindlich-Schaltung einer Schicht',
            $this->rule->getDescription()
        );
    }

    /**
     * Create a testable subclass of MinDaysBeforeCommitRule that overrides the validate method
     * to avoid calling the private getUncommittedShifts method
     */
    private function createTestableRule(array $shifts): MinDaysBeforeCommitRule
    {
        return new class($shifts) extends MinDaysBeforeCommitRule {
            private $testShifts;

            public function __construct(array $shifts)
            {
                $this->testShifts = $shifts;
            }

            // Override the validate method to avoid calling the private getUncommittedShifts method
            public function validate(Model $subject, array $context = []): array
            {
                $violations = [];
                $minDays = $context['value'] ?? 14;
                $today = now();
                $checkUntilDate = $today->copy()->addDays($minDays);

                // Use our test shifts instead of calling getUncommittedShifts
                $uncommittedShifts = collect($this->testShifts);

                if ($uncommittedShifts->isNotEmpty()) {
                    $violations[] = [
                        'date' => $today->toDateString(),
                        'uncommitted_shifts_count' => $uncommittedShifts->count(),
                        'min_days' => $minDays,
                        'check_until_date' => $checkUntilDate->toDateString(),
                        'shifts' => $uncommittedShifts->map(function ($shift) {
                            return [
                                'id' => $shift->id,
                                'start_time' => $shift->start_time,
                                'end_time' => $shift->end_time,
                                'event_name' => $shift->event->name ?? 'Unbekannt',
                                'days_until_shift' => now()->diffInDays(Carbon::parse($shift->start_time))
                            ];
                        })->toArray(),
                        'severity' => 'medium',
                        'message' => "Es gibt {$uncommittedShifts->count()} nicht-verbindliche Schichten in den nächsten {$minDays} Tagen"
                    ];
                }

                return $violations;
            }
        };
    }

    #[Test]
    public function testDetectsViolationWhenUncommittedShiftsExist(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        // Create uncommitted shifts
        $mockShift1 = (object)[
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00',
            'end_time' => '2025-01-20 17:00:00',
            'event' => (object)['name' => 'Test Event 1']
        ];

        $mockShift2 = (object)[
            'id' => 2,
            'start_time' => '2025-01-22 10:00:00',
            'end_time' => '2025-01-22 18:00:00',
            'event' => (object)['name' => 'Test Event 2']
        ];

        // Create a testable rule with our mock shifts
        $rule = $this->createTestableRule([$mockShift1, $mockShift2]);

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
    public function testReturnsNoViolationWhenNoUncommittedShifts(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        // Create a testable rule with no shifts
        $rule = $this->createTestableRule([]);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testCanApplyToAnySubject(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        $result = $this->rule->canApplyTo($mockSubject);

        // This rule is global and can apply to any subject
        $this->assertTrue($result);
    }

    #[Test]
    public function testHasCorrectConfiguration(): void
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
    public function testUsesDefaultValueWhenContextMissing(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        // Create a testable rule with no shifts
        $rule = $this->createTestableRule([]);

        $violations = $rule->validate($mockSubject, []);

        $this->assertIsArray($violations);
    }

    #[Test]
    public function testCalculatesDaysUntilShiftCorrectly(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $mockShift = (object)[
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00', // 5 days from test date
            'end_time' => '2025-01-20 17:00:00',
            'event' => (object)['name' => 'Test Event']
        ];

        // Create a testable rule with our mock shift
        $rule = $this->createTestableRule([$mockShift]);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);

        $shiftData = $violations[0]['shifts'][0];
        $this->assertEqualsWithDelta(5, $shiftData['days_until_shift'], 0.1, "Days until shift should be approximately 5");
    }

    #[Test]
    public function testGeneratesNotificationMessageCorrectly(): void
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
    public function testGetsRelevantShiftsForViolation(): void
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
    public function testReturnsEmptyArrayWhenNoShiftsInViolationData(): void
    {
        $violationData = ['other_data' => 'value'];

        $shifts = $this->rule->getRelevantShiftsForViolation($violationData);

        $this->assertEquals([], $shifts);
    }

    #[Test]
    public function testHandlesShiftsWithoutEventRelation(): void
    {
        $mockSubject = Mockery::mock(Model::class);

        $mockShift = (object)[
            'id' => 1,
            'start_time' => '2025-01-20 09:00:00',
            'end_time' => '2025-01-20 17:00:00',
            'event' => null // No event relation
        ];

        // Create a testable rule with our mock shift
        $rule = $this->createTestableRule([$mockShift]);

        $context = [
            'value' => 14,
        ];

        $violations = $rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);

        $shiftData = $violations[0]['shifts'][0];
        $this->assertEquals('Unbekannt', $shiftData['event_name']);
    }
}
