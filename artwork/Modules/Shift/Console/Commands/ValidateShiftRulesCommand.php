<?php

namespace Artwork\Modules\Shift\Console\Commands;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
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
                            $violation->shift?->start_date ?? '-',
                            $violation->violation_date->format('Y-m-d'),
                            $violation->severity
                        ];
                    })->toArray()
                );
            }

            // Check for overdue compensation deadlines
            $this->checkOverdueCompensations();

            $this->info('Shift rule validation completed successfully');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error during shift rule validation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function checkOverdueCompensations(): void
    {
        $overdueDayOffs = CompensationDayOff::with(['violation.shiftRule', 'user'])
            ->overdue()
            ->get();

        if ($overdueDayOffs->isEmpty()) {
            return;
        }

        // Group by violation_id to create one new violation per group
        $grouped = $overdueDayOffs->groupBy('violation_id');

        $this->info("Found {$overdueDayOffs->count()} overdue compensation day offs across {$grouped->count()} violations");

        foreach ($grouped as $violationId => $dayOffs) {
            $firstDayOff = $dayOffs->first();
            $violation = $firstDayOff->violation;

            if (!$violation) {
                continue;
            }

            // Check if a child violation already exists for this
            $existingChild = ShiftRuleViolation::where('parent_violation_id', $violationId)
                ->where('status', 'active')
                ->whereJsonContains('violation_data->type', 'compensation_deadline_expired')
                ->exists();

            if ($existingChild) {
                continue;
            }

            $totalOverdueDays = $dayOffs->sum('value');

            ShiftRuleViolation::create([
                'shift_rule_id' => $violation->shift_rule_id,
                'user_id' => $violation->user_id,
                'violation_date' => $firstDayOff->deadline,
                'violation_data' => [
                    'type' => 'compensation_deadline_expired',
                    'original_violation_date' => $violation->violation_date->format('Y-m-d'),
                    'compensation_days' => $totalOverdueDays,
                ],
                'severity' => 'error',
                'status' => 'active',
                'is_manual' => false,
                'reason' => 'Compensation deadline expired',
                'parent_violation_id' => $violation->id,
            ]);

            $userName = $firstDayOff->user
                ? "{$firstDayOff->user->first_name} {$firstDayOff->user->last_name}"
                : "User #{$firstDayOff->user_id}";

            $this->line(
                "  Created deadline violation for {$userName} "
                . "(original: {$violation->violation_date->format('Y-m-d')}, deadline: {$firstDayOff->deadline->format('Y-m-d')})"
            );
        }
    }
}
