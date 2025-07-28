<?php

namespace Artwork\Modules\Workflow\Console\Commands;

use Artwork\Modules\Workflow\Jobs\NightlyShiftRuleValidationJob;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ValidateShiftRulesCommand extends Command
{
    protected $signature = 'shift-rules:validate 
                           {--days=14 : Number of days ahead to validate}
                           {--auto-workflows : Automatically create workflows for violations}';

    protected $description = 'Validate shift rules for the specified date range';

    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');
        $autoCreateWorkflows = $this->option('auto-workflows');

        $this->info("Starting shift rule validation for the next {$daysAhead} days...");

        $startTime = now();
        
        try {
            NightlyShiftRuleValidationJob::dispatch($daysAhead, $autoCreateWorkflows);
            
            $this->info('Validation job dispatched successfully.');
            $this->line("Start time: {$startTime->format('Y-m-d H:i:s')}");
            $this->line("Validating from: " . now()->format('Y-m-d') . " to " . now()->addDays($daysAhead)->format('Y-m-d'));
            
            if ($autoCreateWorkflows) {
                $this->line('Auto-creating workflows for violations: YES');
            }

            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to dispatch validation job: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}