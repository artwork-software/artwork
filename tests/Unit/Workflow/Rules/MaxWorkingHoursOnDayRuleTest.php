<?php

namespace Tests\Unit\Workflow\Rules;

use Tests\TestCase;
use Artwork\Modules\Workflow\Rules\MaxWorkingHoursOnDayRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class MaxWorkingHoursOnDayRuleTest extends TestCase
{
    private MaxWorkingHoursOnDayRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new MaxWorkingHoursOnDayRule();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_name()
    {
        $this->assertEquals('max_working_hours_on_day', $this->rule->getName());
    }

    #[Test]
    public function it_has_correct_description()
    {
        $this->assertEquals('Überprüft das Tagesmaximum an Arbeitsstunden', $this->rule->getDescription());
    }

    #[Test]
    public function it_can_apply_to_subjects_with_getPlannedWorkingHours_method()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(true);

        $result = $this->rule->canApplyTo($mockSubject);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_apply_to_subjects_with_shifts_method()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockSubject->shouldReceive('shifts')
            ->andReturn(true);

        $result = $this->rule->canApplyTo($mockSubject);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_cannot_apply_to_subjects_without_required_methods()
    {
        $mockSubject = Mockery::mock(Model::class);

        $result = $this->rule->canApplyTo($mockSubject);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_violation_when_hours_exceed_limit()
    {
        $mockSubject = Mockery::mock(Model::class);
        $date = Carbon::parse('2025-01-15');
        
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with($date)
            ->andReturn(10.0);

        $context = [
            'date' => $date,
            'max_hours' => 8
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(1, $violations);
        $this->assertEquals($date, $violations[0]['date']);
        $this->assertEquals(10.0, $violations[0]['planned_hours']);
        $this->assertEquals(8, $violations[0]['max_hours']);
        $this->assertStringContainsString('Tagesmaximum von 8h überschritten', $violations[0]['message']);
    }

    #[Test]
    public function it_returns_no_violation_when_hours_within_limit()
    {
        $mockSubject = Mockery::mock(Model::class);
        $date = Carbon::parse('2025-01-15');
        
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->with($date)
            ->andReturn(6.0);

        $context = [
            'date' => $date,
            'max_hours' => 8
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_uses_default_values_when_context_missing()
    {
        $mockSubject = Mockery::mock(Model::class);
        
        $mockSubject->shouldReceive('getPlannedWorkingHours')
            ->andReturn(10.0);

        $violations = $this->rule->validate($mockSubject, []);

        $this->assertCount(1, $violations);
        $this->assertEquals(8, $violations[0]['max_hours']); // Default value
    }

    #[Test]
    public function it_gets_planned_hours_via_shifts_relation()
    {
        $mockSubject = Mockery::mock(Model::class);
        $mockShiftsRelation = Mockery::mock();
        $mockShiftsCollection = Mockery::mock();
        $date = Carbon::parse('2025-01-15');

        $mockSubject->shouldReceive('shifts')
            ->once()
            ->andReturn($mockShiftsRelation);

        $mockShiftsRelation->shouldReceive('whereDate')
            ->with('start_time', $date)
            ->andReturn($mockShiftsRelation);

        $mockShiftsRelation->shouldReceive('get')
            ->andReturn($mockShiftsCollection);

        $mockShiftsCollection->shouldReceive('sum')
            ->with(Mockery::type('Closure'))
            ->andReturn(6.0);

        $context = [
            'date' => $date,
            'max_hours' => 8
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_returns_zero_hours_when_no_methods_available()
    {
        $mockSubject = Mockery::mock(Model::class);
        $date = Carbon::parse('2025-01-15');

        $context = [
            'date' => $date,
            'max_hours' => 8
        ];

        $violations = $this->rule->validate($mockSubject, $context);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_has_correct_configuration()
    {
        $config = $this->rule->getConfiguration();

        $this->assertArrayHasKey('fields', $config);
        $this->assertArrayHasKey('max_hours', $config['fields']);
        
        $maxHoursField = $config['fields']['max_hours'];
        $this->assertEquals('number', $maxHoursField['type']);
        $this->assertEquals('Maximale Stunden pro Tag', $maxHoursField['label']);
        $this->assertEquals(8, $maxHoursField['default']);
        $this->assertEquals(1, $maxHoursField['min']);
        $this->assertEquals(24, $maxHoursField['max']);
    }
}