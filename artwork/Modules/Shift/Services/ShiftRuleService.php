<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ShiftRuleService
{
    private ShiftRuleCheckFactory $ruleCheckFactory;

    public function __construct(ShiftRuleCheckFactory $ruleCheckFactory = null)
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



    private function createViolation(
        ShiftRule $rule,
        Shift $shift,
        User $user,
        Carbon $date,
        array $violationData
    ): ShiftRuleViolation {
        // Check if violation already exists for this combination
        $existingViolation = ShiftRuleViolation::where([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d')
        ])->first();

        if ($existingViolation) {
            // Update existing violation data if needed
            $existingViolation->update([
                'violation_data' => $violationData,
                'severity' => 'warning',
                'status' => 'active'
            ]);
            return $existingViolation;
        }

        // Create new violation - this will trigger the workflow automatically
        return ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date,
            'violation_data' => $violationData,
            'severity' => 'warning',
            'status' => 'active'
        ]);
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
