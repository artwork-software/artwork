<?php

namespace Tests\Unit\Workflow\Jobs;

use Tests\TestCase;
use Artwork\Modules\Workflow\Jobs\NightlyShiftRuleValidationJob;
use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class NightlyShiftRuleValidationJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated_with_default_parameters()
    {
        $job = new NightlyShiftRuleValidationJob();

        $this->assertInstanceOf(NightlyShiftRuleValidationJob::class, $job);
    }

    #[Test]
    public function it_can_be_instantiated_with_custom_parameters()
    {
        $job = new NightlyShiftRuleValidationJob(21, false);

        $this->assertInstanceOf(NightlyShiftRuleValidationJob::class, $job);
    }

    #[Test]
    public function it_processes_violations_and_logs_success()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $mockRuleService = Mockery::mock(WorkflowRuleService::class);
        $mockViolation1 = Mockery::mock(WorkflowRuleViolation::class);
        $mockViolation2 = Mockery::mock(WorkflowRuleViolation::class);

        // Setup violation properties
        $mockViolation1->workflow_rule_id = 1;
        $mockViolation1->subject_type = 'User';
        $mockViolation1->subject_id = 1;
        $mockViolation1->violation_date = '2025-01-16';
        $mockViolation1->violation_data = ['test' => 'data'];
        $mockViolation1->severity = 'high';

        $mockViolation2->workflow_rule_id = 2;
        $mockViolation2->subject_type = 'User';
        $mockViolation2->subject_id = 2;
        $mockViolation2->violation_date = '2025-01-17';
        $mockViolation2->violation_data = ['test' => 'data2'];
        $mockViolation2->severity = 'medium';

        $violations = collect([$mockViolation1, $mockViolation2]);

        // Mock WorkflowRuleViolation::where for cleanup
        WorkflowRuleViolation::shouldReceive('whereBetween')
            ->once()
            ->with('violation_date', Mockery::type('array'))
            ->andReturn(Mockery::mock()->shouldReceive('where')->andReturn(
                Mockery::mock()->shouldReceive('whereDate')->andReturn(
                    Mockery::mock()->shouldReceive('delete')->andReturn(0)->getMock()
                )->getMock()
            )->getMock());

        // Mock rule service
        $mockRuleService->shouldReceive('checkRuleViolationsForDateRange')
            ->once()
            ->with(
                Mockery::type(Carbon::class),
                Mockery::type(Carbon::class)
            )
            ->andReturn($violations);

        // Mock existing violation checks (return null = no existing)
        WorkflowRuleViolation::shouldReceive('where')
            ->andReturn(Mockery::mock()->shouldReceive('first')->andReturn(null)->getMock());

        // Mock save for new violations
        $mockViolation1->shouldReceive('save')->once();
        $mockViolation2->shouldReceive('save')->once();

        $job = new NightlyShiftRuleValidationJob(14, false);
        $job->handle($mockRuleService);

        // Verify logging
        Log::shouldHaveReceived('info')
            ->with('Starting nightly shift rule validation', Mockery::type('array'));
        
        Log::shouldHaveReceived('info')
            ->with('Nightly shift rule validation completed', Mockery::type('array'));
    }

    #[Test]
    public function it_updates_existing_violations()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $mockRuleService = Mockery::mock(WorkflowRuleService::class);
        $mockNewViolation = Mockery::mock(WorkflowRuleViolation::class);
        $mockExistingViolation = Mockery::mock(WorkflowRuleViolation::class);

        // Setup new violation
        $mockNewViolation->workflow_rule_id = 1;
        $mockNewViolation->subject_type = 'User';
        $mockNewViolation->subject_id = 1;
        $mockNewViolation->violation_date = '2025-01-16';
        $mockNewViolation->violation_data = ['updated' => 'data'];
        $mockNewViolation->severity = 'high';

        $violations = collect([$mockNewViolation]);

        // Mock cleanup
        WorkflowRuleViolation::shouldReceive('whereBetween')
            ->once()
            ->andReturn(Mockery::mock()->shouldReceive('where')->andReturn(
                Mockery::mock()->shouldReceive('whereDate')->andReturn(
                    Mockery::mock()->shouldReceive('delete')->andReturn(0)->getMock()
                )->getMock()
            )->getMock());

        // Mock rule service
        $mockRuleService->shouldReceive('checkRuleViolationsForDateRange')
            ->once()
            ->andReturn($violations);

        // Mock existing violation found
        WorkflowRuleViolation::shouldReceive('where')
            ->with([
                'workflow_rule_id' => 1,
                'subject_type' => 'User',
                'subject_id' => 1,
                'violation_date' => '2025-01-16'
            ])
            ->andReturn(Mockery::mock()->shouldReceive('first')->andReturn($mockExistingViolation)->getMock());

        // Mock update of existing violation
        $mockExistingViolation->shouldReceive('update')
            ->once()
            ->with([
                'violation_data' => ['updated' => 'data'],
                'severity' => 'high',
                'updated_at' => Mockery::type(Carbon::class)
            ]);

        $job = new NightlyShiftRuleValidationJob(14, false);
        $job->handle($mockRuleService);

        Log::shouldHaveReceived('info')
            ->with('Nightly shift rule validation completed', Mockery::on(function ($data) {
                return $data['updated_violations'] === 1 && $data['new_violations'] === 0;
            }));
    }

    #[Test]
    public function it_handles_exceptions_and_logs_errors()
    {
        $mockRuleService = Mockery::mock(WorkflowRuleService::class);

        // Mock cleanup to throw exception
        WorkflowRuleViolation::shouldReceive('whereBetween')
            ->andThrow(new \Exception('Database error'));

        $job = new NightlyShiftRuleValidationJob();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $job->handle($mockRuleService);

        Log::shouldHaveReceived('error')
            ->with('Error during nightly shift rule validation', Mockery::type('array'));
    }

    #[Test]
    public function it_logs_failure_when_job_fails()
    {
        $job = new NightlyShiftRuleValidationJob();
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        Log::shouldHaveReceived('error')
            ->with('Nightly shift rule validation job failed', [
                'error' => 'Test failure',
                'trace' => $exception->getTraceAsString()
            ]);
    }

    #[Test]
    public function it_cleans_up_old_violations_before_processing()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $mockRuleService = Mockery::mock(WorkflowRuleService::class);
        $mockQueryBuilder = Mockery::mock();

        // Mock the cleanup query chain
        WorkflowRuleViolation::shouldReceive('whereBetween')
            ->once()
            ->with('violation_date', Mockery::type('array'))
            ->andReturn($mockQueryBuilder);

        $mockQueryBuilder->shouldReceive('where')
            ->once()
            ->with('status', 'pending')
            ->andReturn($mockQueryBuilder);

        $mockQueryBuilder->shouldReceive('whereDate')
            ->once()
            ->with('created_at', '<', Mockery::type(Carbon::class))
            ->andReturn($mockQueryBuilder);

        $mockQueryBuilder->shouldReceive('delete')
            ->once()
            ->andReturn(5); // 5 old violations deleted

        // Mock rule service to return empty violations
        $mockRuleService->shouldReceive('checkRuleViolationsForDateRange')
            ->once()
            ->andReturn(collect([]));

        $job = new NightlyShiftRuleValidationJob();
        $job->handle($mockRuleService);

        // Verify the cleanup was called
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    #[Test]
    public function it_uses_custom_days_ahead_parameter()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $mockRuleService = Mockery::mock(WorkflowRuleService::class);

        // Mock cleanup
        WorkflowRuleViolation::shouldReceive('whereBetween')
            ->andReturn(Mockery::mock()->shouldReceive('where')->andReturn(
                Mockery::mock()->shouldReceive('whereDate')->andReturn(
                    Mockery::mock()->shouldReceive('delete')->andReturn(0)->getMock()
                )->getMock()
            )->getMock());

        // Verify that the service is called with custom date range (21 days)
        $mockRuleService->shouldReceive('checkRuleViolationsForDateRange')
            ->once()
            ->with(
                Mockery::on(function ($startDate) {
                    return $startDate->isSameDay(Carbon::parse('2025-01-15'));
                }),
                Mockery::on(function ($endDate) {
                    return $endDate->isSameDay(Carbon::parse('2025-02-05')); // 21 days later
                })
            )
            ->andReturn(collect([]));

        $job = new NightlyShiftRuleValidationJob(21, false); // Custom 21 days
        $job->handle($mockRuleService);

        Log::shouldHaveReceived('info')
            ->with('Starting nightly shift rule validation', Mockery::on(function ($data) {
                return $data['days_ahead'] === 21;
            }));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}