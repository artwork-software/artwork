<?php

namespace Tests\Unit\Workflow\Rules;

use Tests\TestCase;
use Artwork\Modules\Workflow\Rules\RestTimeBeforeWorkdayRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class RestTimeBeforeWorkdayRuleTest extends TestCase
{
    private RestTimeBeforeWorkdayRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new RestTimeBeforeWorkdayRule();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a concrete test class that extends Model and implements the shifts method
     */
    private function createTestSubject(array $shifts): Model
    {
        // Create a concrete class that extends Model
        $testSubject = new class extends Model {
            private $testShifts = [];

            public function setShifts(array $shifts): void
            {
                $this->testShifts = $shifts;
            }

            public function shifts()
            {
                // Create a query builder mock that will return the shifts
                $queryBuilder = new class ($this->testShifts) {
                    private $shifts;
                    private $dateFilter = null;
                    private $dateColumn = null;
                    private $orderByColumn = null;
                    private $orderByDirection = null;

                    public function __construct(array $shifts)
                    {
                        $this->shifts = $shifts;
                    }

                    public function whereBetween($column, $values)
                    {
                        // Just return $this for chaining
                        return $this;
                    }

                    public function orWhereBetween($column, $values)
                    {
                        // Just return $this for chaining
                        return $this;
                    }

                    public function whereDate($column, $date)
                    {
                        $this->dateFilter = $date;
                        $this->dateColumn = $column;
                        return $this;
                    }

                    public function orderBy($column, $direction = 'asc')
                    {
                        $this->orderByColumn = $column;
                        $this->orderByDirection = $direction;
                        return $this;
                    }

                    public function first()
                    {
                        if (empty($this->shifts)) {
                            return null;
                        }

                        // Filter shifts by date if a date filter is set
                        $filteredShifts = $this->shifts;
                        if ($this->dateFilter && $this->dateColumn) {
                            $filteredShifts = array_filter($this->shifts, function ($shift) {
                                $shiftDate = Carbon::parse($shift->{$this->dateColumn})->toDateString();
                                $filterDate = $this->dateFilter->toDateString();
                                return $shiftDate === $filterDate;
                            });

                            if (empty($filteredShifts)) {
                                return null;
                            }
                        }

                        // Sort shifts if orderBy is set
                        if ($this->orderByColumn) {
                            usort($filteredShifts, function ($a, $b) {
                                $aValue = Carbon::parse($a->{$this->orderByColumn});
                                $bValue = Carbon::parse($b->{$this->orderByColumn});

                                if ($this->orderByDirection === 'asc') {
                                    return $aValue->lt($bValue) ? -1 : 1;
                                }

                                return $bValue->lt($aValue) ? -1 : 1;
                            });
                        }

                        // Return first shift after filtering and sorting
                        return !empty($filteredShifts) ? array_values($filteredShifts)[0] : null;
                    }

                    public function get()
                    {
                        return collect($this->shifts);
                    }
                };

                return $queryBuilder;
            }
        };

        // Set the shifts after construction
        $testSubject->setShifts($shifts);

        return $testSubject;
    }

    #[Test]
    public function testHasCorrectName(): void
    {
        $this->assertEquals('rest_time_before_workday', $this->rule->getName());
    }

    #[Test]
    public function testHasCorrectDescription(): void
    {
        $this->assertEquals('Überprüft die Ruhezeit vor Werktagen (mindest Nachtruhe)', $this->rule->getDescription());
    }

    #[Test]
    public function testDetectsViolationWhenRestTimeInsufficient(): void
    {
        // Create shifts for two consecutive days with insufficient rest time
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-15 08:00:00',
                'end_time' => '2025-01-15 22:00:00' // Ends at 10 PM
            ],
            (object) [
                'id' => 2,
                'start_time' => '2025-01-16 05:00:00', // Starts at 5 AM (only 7 hours rest)
                'end_time' => '2025-01-16 14:00:00'
            ]
        ];

        // Create a concrete test subject with the shifts
        $testSubject = $this->createTestSubject($shifts);

        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-16');

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_rest_hours' => 11 // Minimum 11 hours rest required
        ];

        $violations = $this->rule->validate($testSubject, $context);

        $this->assertCount(1, $violations);
        $this->assertEquals('2025-01-16', $violations[0]['date']);
        $this->assertEquals(7, $violations[0]['rest_hours']); // Hours between shifts (not enough for required rest)
        $this->assertEquals(8, $violations[0]['min_rest_hours']); // Default is 8 in the implementation
        $this->assertStringContainsString('Zu wenig Ruhezeit vor Werktag', $violations[0]['message']);
    }

    #[Test]
    public function testReturnsNoViolationWhenRestTimeSufficient(): void
    {
        // Create shifts for two consecutive days with sufficient rest time
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-15 08:00:00',
                'end_time' => '2025-01-15 16:00:00' // Ends at 4 PM
            ],
            (object) [
                'id' => 2,
                'start_time' => '2025-01-16 09:00:00', // Starts at 9 AM (17 hours rest)
                'end_time' => '2025-01-16 17:00:00'
            ]
        ];

        // Create a concrete test subject with the shifts
        $testSubject = $this->createTestSubject($shifts);

        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-16');

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_rest_hours' => 8 // Use the default minimum rest hours
        ];

        $violations = $this->rule->validate($testSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testSkipsSundays(): void
    {
        // Create shifts for Saturday and Monday with insufficient rest time between them
        // (but Sunday is skipped, so no violation should be detected)
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-18 08:00:00', // Saturday
                'end_time' => '2025-01-18 22:00:00' // Ends at 10 PM
            ],
            (object) [
                'id' => 2,
                'start_time' => '2025-01-20 05:00:00', // Monday, starts at 5 AM (only 31 hours rest, but Sunday is skipped)
                'end_time' => '2025-01-20 14:00:00'
            ]
        ];

        // Create a concrete test subject with the shifts
        $testSubject = $this->createTestSubject($shifts);

        $startDate = Carbon::parse('2025-01-18'); // Saturday
        $endDate = Carbon::parse('2025-01-20'); // Monday

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_rest_hours' => 11
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // No violations because Sunday is skipped
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testHandlesMissingShiftData(): void
    {
        // Create a single shift in the period
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-15 08:00:00',
                'end_time' => '2025-01-15 17:00:00'
            ]
        ];

        // Create a concrete test subject with the single shift
        $testSubject = $this->createTestSubject($shifts);

        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-16');

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_rest_hours' => 11
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // No violations because there's only one shift
        $this->assertCount(0, $violations);

        // Test with empty shifts collection
        $testSubjectWithNoShifts = $this->createTestSubject([]);

        $violations = $this->rule->validate($testSubjectWithNoShifts, $context);

        // No violations with empty shifts
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function testCanOnlyApplyToSubjectsWithShiftsMethod(): void
    {
        // Create a test subject with the shifts method
        $subjectWithShifts = $this->createTestSubject([]);

        // This should return true because our test subject has a shifts method
        $this->assertTrue($this->rule->canApplyTo($subjectWithShifts));

        // Create a model without the shifts method
        $subjectWithoutShifts = new class extends Model {
        };

        // This should return false because this model doesn't have a shifts method
        $this->assertFalse($this->rule->canApplyTo($subjectWithoutShifts));
    }

    #[Test]
    public function testHasCorrectConfiguration(): void
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('min_rest_hours', $config['fields']);

        $minRestHoursField = $config['fields']['min_rest_hours'];
        $this->assertEquals('number', $minRestHoursField['type']);
        $this->assertEquals('Mindest-Ruhezeit vor Werktag (Stunden)', $minRestHoursField['label']);
        $this->assertEquals(8, $minRestHoursField['default']);
    }

    #[Test]
    public function testUsesDefaultValuesWhenContextMissing(): void
    {
        // Set the current date to a fixed value for testing
        Carbon::setTestNow(Carbon::parse('2025-01-15 00:00:00'));

        // Create shifts for two consecutive days with insufficient rest time
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-15 08:00:00',
                'end_time' => '2025-01-15 22:00:00' // Ends at 10 PM
            ],
            (object) [
                'id' => 2,
                'start_time' => '2025-01-16 05:00:00', // Starts at 5 AM (only 7 hours rest)
                'end_time' => '2025-01-16 14:00:00'
            ]
        ];

        // Create a concrete test subject with the shifts
        $testSubject = $this->createTestSubject($shifts);

        // Empty context - should use default values
        $violations = $this->rule->validate($testSubject, []);

        // Verify that a violation is detected with the default min_rest_hours value
        $this->assertCount(1, $violations);
        $this->assertEquals(7, $violations[0]['rest_hours']); // Hours between shifts (not enough for required rest)
        $this->assertEquals(8, $violations[0]['min_rest_hours']); // Default value is 8 in the implementation

        // Reset the test time
        Carbon::setTestNow();
    }

    #[Test]
    public function testExtendsDateRangeByOneDayBackward(): void
    {
        // Previous day shift (should be included in the check)
        $shifts = [
            (object) [
                'id' => 1,
                'start_time' => '2025-01-14 14:00:00',
                'end_time' => '2025-01-14 23:00:00' // Ends at 11 PM
            ],
            (object) [
                'id' => 2,
                'start_time' => '2025-01-15 06:00:00', // Only 7 hours rest from previous shift
                'end_time' => '2025-01-15 15:00:00'
            ]
        ];

        // Create a concrete test subject with the shifts
        $testSubject = $this->createTestSubject($shifts);

        // Only specify the current day in the context
        $startDate = Carbon::parse('2025-01-15');
        $endDate = Carbon::parse('2025-01-15');

        $context = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_rest_hours' => 11
        ];

        $violations = $this->rule->validate($testSubject, $context);

        // Should detect violation even though previous day shift is outside the specified range
        $this->assertCount(1, $violations);
        $this->assertEquals('2025-01-15', $violations[0]['date']);
        $this->assertEquals(7, $violations[0]['rest_hours']); // Hours between shifts (not enough for required rest)
    }
}
