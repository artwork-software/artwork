<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftRuleService
{
    private ShiftRuleCheckFactory $ruleCheckFactory;

    public function __construct(?ShiftRuleCheckFactory $ruleCheckFactory = null)
    {
        $this->ruleCheckFactory = $ruleCheckFactory ?: new ShiftRuleCheckFactory();
    }
    public function validateRulesForUser(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();

        $activeContract = $user->activeWorkContract();
        if (!$activeContract) {
            return $violations;
        }

        $rules = $this->getRulesForContract($activeContract);

        foreach ($rules as $rule) {
            $ruleViolations = $this->checkRuleForUser($rule, $user, $startDate, $endDate);
            $violations = $violations->concat($ruleViolations);
        }

        return $violations;
    }

    public function checkRuleForUser(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $ruleCheck = $this->ruleCheckFactory->create($rule->trigger_type);
        return $ruleCheck->check($rule, $user, $startDate, $endDate);
    }


    private function getRulesForContract(UserContract $contract): Collection
    {
        return ShiftRule::whereHas('contracts', function ($query) use ($contract): void {
            $query->where('contract_id', $contract->id);
        })->where('is_active', true)->get();
    }

    public function validateShiftRulesForDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Get all users with active contracts
        $users = User::whereHas('contract')->get();

        foreach ($users as $user) {
            $userViolations = $this->validateRulesForUser($user, $startDate, $endDate);
            $violations = $violations->concat($userViolations);
        }

        return $violations;
    }
}
