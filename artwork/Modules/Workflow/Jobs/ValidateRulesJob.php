<?php

namespace Artwork\Modules\Workflow\Jobs;

use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Carbon $startDate,
        private readonly Carbon $endDate
    ) {
        $this->onQueue('rule-validation');
    }

    public function handle(WorkflowRuleService $ruleService): void
    {
        try {
            $violations = $ruleService->checkRuleViolationsForDateRange(
                $this->startDate,
                $this->endDate
            );

            logger()->info('Rule validation completed', [
                'start_date' => $this->startDate->toDateString(),
                'end_date' => $this->endDate->toDateString(),
                'violations_found' => $violations->count()
            ]);
        } catch (\Exception $e) {
            logger()->error('Rule validation failed', [
                'start_date' => $this->startDate->toDateString(),
                'end_date' => $this->endDate->toDateString(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Rule validation job permanently failed', [
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'error' => $exception->getMessage()
        ]);
    }
}
