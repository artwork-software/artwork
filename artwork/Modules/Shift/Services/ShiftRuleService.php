<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\ShiftRuleRepository;
use Artwork\Modules\Shift\Repositories\ShiftRuleViolationRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ShiftRuleService
{
    public function __construct(
        private readonly ShiftRuleRepository $shiftRuleRepository,
        private readonly ShiftRuleViolationRepository $shiftRuleViolationRepository,
        private readonly ShiftRuleCheckFactory $ruleCheckFactory,
    ) {
    }

    public function getAllWithRelations(): EloquentCollection
    {
        return $this->shiftRuleRepository->getAllWithRelations();
    }

    public function getActiveRules(array $columns = ['*']): EloquentCollection
    {
        return $this->shiftRuleRepository->getActive($columns);
    }

    public function createRule(array $attributes, ?array $contractIds = null, ?array $userIds = null): ShiftRule
    {
        $rule = $this->shiftRuleRepository->createRule($attributes);

        if (!empty($contractIds)) {
            $rule->contracts()->sync($contractIds);
        }

        if (!empty($userIds)) {
            $rule->usersToNotify()->sync($userIds);
        }

        return $rule;
    }

    public function updateRule(
        ShiftRule $rule,
        array $attributes,
        ?array $contractIds = null,
        ?array $userIds = null
    ): ShiftRule {
        $this->shiftRuleRepository->update($rule, $attributes);

        $rule->contracts()->sync($contractIds ?? []);
        $rule->usersToNotify()->sync($userIds ?? []);

        return $rule;
    }

    public function deleteRule(ShiftRule $rule): bool
    {
        return $this->shiftRuleRepository->delete($rule);
    }

    public function syncContractsForRule(ShiftRule $rule, array $contractIds): void
    {
        $rule->contracts()->sync($contractIds);
    }

    public function syncUsersForRule(ShiftRule $rule, array $userIds): void
    {
        $rule->usersToNotify()->sync($userIds);
    }

    public function updateContractAssignments(UserContract $contract, array $ruleIds): void
    {
        $contract->shiftRules()->sync($ruleIds);
    }

    public function getActiveViolations(): EloquentCollection
    {
        return $this->shiftRuleViolationRepository->getActiveWithRelations();
    }

    public function getViolationsForDateRange(
        string $startDate,
        string $endDate,
        ?array $userIds = null
    ): Collection {
        return $this->shiftRuleViolationRepository
            ->getActiveForDateRange($startDate, $endDate, $userIds)
            ->groupBy('user_id')
            ->map(fn ($userViolations) => $userViolations->groupBy(
                fn ($v) => $v->violation_date->format('Y-m-d')
            ));
    }

    public function createManualViolation(array $attributes): ShiftRuleViolation
    {
        return $this->shiftRuleViolationRepository->createViolation($attributes);
    }

    public function resolveViolation(ShiftRuleViolation $violation, ?int $userId = null): void
    {
        $this->shiftRuleViolationRepository->resolve($violation, $userId);
    }

    public function ignoreViolation(ShiftRuleViolation $violation, ?int $userId = null): void
    {
        $this->shiftRuleViolationRepository->ignore($violation, $userId);
    }

    public function updateViolationStatus(int $violationId, string $status, ?int $userId = null): void
    {
        $violation = $this->shiftRuleViolationRepository->findOrFail($violationId);

        if ($status === 'resolved') {
            $this->shiftRuleViolationRepository->resolve($violation, $userId);
        } else {
            $this->shiftRuleViolationRepository->ignore($violation, $userId);
        }
    }

    public function processViolation(ShiftRuleViolation $violation, array $attributes): void
    {
        $this->shiftRuleViolationRepository->update($violation, $attributes);
    }

    public function grantCompensation(ShiftRuleViolation $violation, int $userId): void
    {
        $this->shiftRuleViolationRepository->grantCompensation($violation, $userId);
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

        $users = User::whereHas('contract')->get();

        foreach ($users as $user) {
            $userViolations = $this->validateRulesForUser($user, $startDate, $endDate);
            $violations = $violations->concat($userViolations);
        }

        return $violations;
    }

    public function getCompensationDataForUser(User $user): array
    {
        return [
            'openCompensations' => $this->shiftRuleViolationRepository
                ->getOpenCompensationsForUser($user->id),
            'grantedCompensations' => $this->shiftRuleViolationRepository
                ->getGrantedCompensationsForUser($user->id),
            'unprocessedViolations' => $this->shiftRuleViolationRepository
                ->getUnprocessedViolationsForUser($user->id),
            'compensationPeriod' => $user->activeWorkContract()?->compensation_period ?? 0,
        ];
    }

    public function getAvailableRuleTypes(): array
    {
        return [
            'maxWorkingHoursOnDay',
            'maxConsecWorkingDays',
            'weeklyMaxHours',
            'restTimeBeforeWorkday',
            'restTimeBeforeHoliday',
            'minDaysBeforeCommit',
        ];
    }

    public function mapViolationsToArray(Collection $violations): Collection
    {
        return $violations->map(function ($violation) {
            return [
                'id' => $violation->id,
                'rule_name' => $violation->shiftRule?->name,
                'user_name' => $violation->user->first_name . ' ' . $violation->user->last_name,
                'violation_date' => $violation->violation_date,
                'message' => $violation->getViolationMessage(),
                'severity' => $violation->severity,
                'warning_color' => $violation->getWarningColor(),
                'violation_data' => $violation->violation_data ?? null,
                'status' => $violation->status ?? null,
                'is_manual' => $violation->is_manual ?? false,
                'reason' => $violation->reason,
                'compensation_days' => $violation->compensation_days,
                'compensation_deadline' => $violation->compensation_deadline,
                'compensation_reason' => $violation->compensation_reason,
                'compensation_granted_at' => $violation->compensation_granted_at,
                'shift_rule' => $violation->shiftRule ? [
                    'name' => $violation->shiftRule->name,
                    'description' => $violation->shiftRule->description,
                    'warning_color' => $violation->shiftRule->warning_color,
                ] : null,
                'created_by_user' => $violation->createdByUser ? [
                    'first_name' => $violation->createdByUser->first_name,
                    'last_name' => $violation->createdByUser->last_name,
                ] : null,
            ];
        })->values();
    }
}
