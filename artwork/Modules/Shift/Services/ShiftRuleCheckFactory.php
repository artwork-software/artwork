<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Contracts\ShiftRuleCheckInterface;
use Artwork\Modules\Shift\RuleChecks\MaxConsecutiveWorkingDaysCheck;
use Artwork\Modules\Shift\RuleChecks\MaxWorkingHoursOnDayCheck;
use Artwork\Modules\Shift\RuleChecks\MinDaysBeforeCommitCheck;
use Artwork\Modules\Shift\RuleChecks\RestTimeBeforeHolidayCheck;
use Artwork\Modules\Shift\RuleChecks\RestTimeBeforeWorkdayCheck;
use Artwork\Modules\Shift\RuleChecks\WeeklyMaxHoursCheck;
use InvalidArgumentException;

class ShiftRuleCheckFactory
{
    private array $checks = [];

    public function __construct()
    {
        $this->registerDefaultChecks();
    }

    private function registerDefaultChecks(): void
    {
        $this->register(new MaxWorkingHoursOnDayCheck());
        $this->register(new MaxConsecutiveWorkingDaysCheck());
        $this->register(new WeeklyMaxHoursCheck());
        // Backward compatibility alias for previously used trigger type key in DB/UI
        $this->checks['maxWorkingHoursOnWeek'] = $this->checks['weeklyMaxHours'];
        $this->register(new RestTimeBeforeWorkdayCheck());
        $this->register(new RestTimeBeforeHolidayCheck());
        $this->register(new MinDaysBeforeCommitCheck());
    }

    public function register(ShiftRuleCheckInterface $check): void
    {
        $this->checks[$check->getTriggerType()] = $check;
    }

    public function create(string $triggerType): ShiftRuleCheckInterface
    {
        if (!isset($this->checks[$triggerType])) {
            throw new InvalidArgumentException("No rule check found for trigger type: {$triggerType}");
        }

        return $this->checks[$triggerType];
    }

    public function getAllTriggerTypes(): array
    {
        return array_keys($this->checks);
    }
}
