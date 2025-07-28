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

    #[Test]
    public function it_has_correct_name()
    {
        $this->assertEquals('rest_time_before_workday', $this->rule->getName());
    }

    #[Test]
    public function it_has_correct_description()
    {
        $this->assertEquals('Überprüft die Ruhezeit vor Werktagen (mindest Nachtruhe)', $this->rule->getDescription());
    }

    #[Test]
    public function it_detects_violation_when_rest_time_insufficient()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $startDate = Carbon::parse('2025-01-13'); // Monday
        $endDate = Carbon::parse('2025-01-14');   // Tuesday
        
        // Mock shifts relation
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Mock latest shift end on Monday (23:00)
        $mondayShift = (object) ['end_time' => '2025-01-13 23:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('end_time', Carbon::parse('2025-01-13'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('end_time', 'desc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($mondayShift);

        // Mock earliest shift start on Tuesday (06:00)
        $tuesdayShift = (object) ['start_time' => '2025-01-14 06:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('start_time', Carbon::parse('2025-01-14'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('start_time', 'asc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($tuesdayShift);

        $context = [
            'value' => 8, // Minimum 8 hours rest
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertGreaterThan(0, count($violations));
        
        $violation = $violations[0];
        $this->assertEquals(7, $violation['rest_hours']); // 23:00 to 06:00 = 7 hours
        $this->assertEquals(8, $violation['min_rest_hours']);
        $this->assertEquals('2025-01-14', $violation['date']);
        $this->assertStringContainsString('Zu wenig Ruhezeit vor Werktag', $violation['message']);
    }

    #[Test]
    public function it_returns_no_violation_when_rest_time_sufficient()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $startDate = Carbon::parse('2025-01-13');
        $endDate = Carbon::parse('2025-01-14');
        
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Mock latest shift end on Monday (20:00)
        $mondayShift = (object) ['end_time' => '2025-01-13 20:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('end_time', Carbon::parse('2025-01-13'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('end_time', 'desc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($mondayShift);

        // Mock earliest shift start on Tuesday (08:00)
        $tuesdayShift = (object) ['start_time' => '2025-01-14 08:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('start_time', Carbon::parse('2025-01-14'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('start_time', 'asc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($tuesdayShift);

        $context = [
            'value' => 8,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // 20:00 to 08:00 = 12 hours rest (sufficient)
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_skips_sundays()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $startDate = Carbon::parse('2025-01-18'); // Saturday  
        $endDate = Carbon::parse('2025-01-19');   // Sunday
        
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Mock shift end on Saturday
        $saturdayShift = (object) ['end_time' => '2025-01-18 23:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('end_time', Carbon::parse('2025-01-18'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('end_time', 'desc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($saturdayShift);

        $context = [
            'value' => 8,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should skip Sunday (not a workday)
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_handles_missing_shift_data()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $startDate = Carbon::parse('2025-01-13');
        $endDate = Carbon::parse('2025-01-14');
        
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Mock no shift on previous day
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('end_time', Carbon::parse('2025-01-13'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('end_time', 'desc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn(null);

        // Mock shift on current day
        $tuesdayShift = (object) ['start_time' => '2025-01-14 08:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('start_time', Carbon::parse('2025-01-14'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('start_time', 'asc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($tuesdayShift);

        $context = [
            'value' => 8,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should not create violation when previous shift data is missing
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_can_only_apply_to_subjects_with_shifts_method()
    {
        $mockSubjectWithShifts = Mockery::mock(Model::class);
        $mockSubjectWithShifts->shouldReceive('shifts')
            ->andReturn(true);

        $mockSubjectWithoutShifts = Mockery::mock(Model::class);

        $this->assertTrue($this->rule->canApplyTo($mockSubjectWithShifts));
        $this->assertFalse($this->rule->canApplyTo($mockSubjectWithoutShifts));
    }

    #[Test]
    public function it_has_correct_configuration()
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('min_rest_hours', $config['fields']);
        
        $restHoursField = $config['fields']['min_rest_hours'];
        $this->assertEquals('number', $restHoursField['type']);
        $this->assertEquals('Mindest-Ruhezeit vor Werktag (Stunden)', $restHoursField['label']);
        $this->assertEquals(8, $restHoursField['default']);
        $this->assertEquals(1, $restHoursField['min']);
        $this->assertEquals(24, $restHoursField['max']);
    }

    #[Test]
    public function it_uses_default_values_when_context_missing()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Mock empty results for missing context
        $mockShiftsRelation->shouldReceive('whereDate')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn(null);

        $violations = $this->rule->validate($mockSubject, []);

        $this->assertIsArray($violations);
    }

    #[Test]
    public function it_extends_date_range_by_one_day_backward()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        
        $startDate = Carbon::parse('2025-01-14'); // Tuesday (should check Monday too)
        $endDate = Carbon::parse('2025-01-14');   // Tuesday
        
        $mockSubject->shouldReceive('shifts')
            ->andReturn($mockShiftsRelation);

        // Should query for Monday's end shift
        $mondayShift = (object) ['end_time' => '2025-01-13 22:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('end_time', Carbon::parse('2025-01-13')) // Monday
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('end_time', 'desc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($mondayShift);

        // Tuesday's start shift
        $tuesdayShift = (object) ['start_time' => '2025-01-14 05:00:00'];
        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('start_time', Carbon::parse('2025-01-14'))
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('orderBy')
            ->with('start_time', 'asc')
            ->andReturn($mockShiftsRelation);
        $mockShiftsRelation->shouldReceive('first')
            ->andReturn($tuesdayShift);

        $context = [
            'value' => 8,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        // Should detect violation (22:00 to 05:00 = 7 hours)
        $this->assertGreaterThan(0, count($violations));
    }
}