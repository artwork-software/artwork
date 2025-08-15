<?php

namespace Artwork\Modules\Shift\Console\Commands;

use Artwork\Modules\Shift\Services\ShiftRuleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ValidateShiftRulesCommand extends Command
{
    protected $signature = 'shift-rules:validate {--days=14 : Number of days to validate ahead}';

    protected $description = 'Validate shift rules for upcoming days and create violations';

    public function __construct(
        private readonly ShiftRuleService $shiftRuleService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $startDate = now();
        $endDate = now()->addDays($days);

        $this->info("Validating shift rules from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        try {
            $violations = $this->shiftRuleService->validateShiftRulesForDateRange($startDate, $endDate);

            $this->info("Found {$violations->count()} rule violations");

            if ($violations->count() > 0) {
                $this->table(
                    ['Rule', 'User', 'Shift Date', 'Violation Date', 'Severity'],
                    $violations->map(function ($violation) {
                        return [
                            $violation->shiftRule->name,
                            $violation->user->first_name . ' ' . $violation->user->last_name,
                            $violation->shift->start_date,
                            $violation->violation_date->format('Y-m-d'),
                            $violation->severity
                        ];
                    })->toArray()
                );
            }

            $this->info('Shift rule validation completed successfully');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error during shift rule validation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
