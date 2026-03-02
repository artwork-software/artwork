<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Facades\Cache;

class BudgetCacheService
{
    private const TTL_BUDGET = 3600;
    private const TTL_STATIC = 86400;

    public function getBudgetPayload(int $projectId): ?array
    {
        return Cache::get($this->budgetKey($projectId));
    }

    public function setBudgetPayload(int $projectId, array $payload): void
    {
        Cache::put($this->budgetKey($projectId), $payload, self::TTL_BUDGET);
    }

    public function forgetBudgetPayload(int $projectId): void
    {
        Cache::forget($this->budgetKey($projectId));
    }

    public function forgetForProjectGroup(Project $project): void
    {
        $this->forgetBudgetPayload($project->id);

        foreach ($project->groups as $group) {
            $this->forgetBudgetPayload($group->id);
        }

        if ($project->is_group) {
            foreach ($project->projectsOfGroup as $subProject) {
                $this->forgetBudgetPayload($subProject->id);
            }
        }
    }

    public function getStaticLookups(): array
    {
        return [
            'moneySources' => Cache::remember('budget:static:money_sources', self::TTL_STATIC, function () {
                return MoneySource::all();
            }),
            'contractTypes' => Cache::remember('budget:static:contract_types', self::TTL_STATIC, function () {
                return ContractType::all()->toArray();
            }),
            'companyTypes' => Cache::remember('budget:static:company_types', self::TTL_STATIC, function () {
                return CompanyType::all()->toArray();
            }),
            'currencies' => Cache::remember('budget:static:currencies', self::TTL_STATIC, function () {
                return Currency::all()->toArray();
            }),
        ];
    }

    public function forgetStaticLookups(): void
    {
        Cache::forget('budget:static:money_sources');
        Cache::forget('budget:static:contract_types');
        Cache::forget('budget:static:company_types');
        Cache::forget('budget:static:currencies');
    }

    private function budgetKey(int $projectId): string
    {
        return 'budget:project:' . $projectId;
    }
}
