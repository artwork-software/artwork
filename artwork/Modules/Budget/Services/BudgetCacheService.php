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

    /**
     * Memoisiert pro Projekt die Menge der mitzuinvalidierenden Projekt-IDs
     * (Projekt selbst + Gruppen + Gruppen-Unterprojekte). Spart bei
     * Mutations-Bursts (Spalte anlegen, Sage-Import) die wiederholten
     * Gruppen-Queries pro gespeichertem Modell. Bewusst statisch: Gruppen-
     * Zugehörigkeit ändert sich innerhalb eines Requests/Imports nicht.
     *
     * @var array<int, array<int, int>>
     */
    private static array $relatedProjectIdsMemo = [];

    /**
     * Dedupliziert WarmBudgetCache-Dispatches pro Request/Prozess.
     *
     * @var array<int, true>
     */
    private static array $warmDispatched = [];

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
        foreach ($this->relatedProjectIds($project->id) as $relatedProjectId) {
            $this->forgetBudgetPayload($relatedProjectId);
        }
    }

    public function forgetForProjectAndRelated(int $projectId): void
    {
        foreach ($this->relatedProjectIds($projectId) as $relatedProjectId) {
            $this->forgetBudgetPayload($relatedProjectId);
        }
    }

    /**
     * True genau beim ersten Aufruf pro Projekt und Prozess - der Aufrufer
     * dispatcht dann den WarmBudgetCache-Job. Wiederholte Saves im selben
     * Request pushen so nicht erneut in die Queue (der Job selbst ist
     * zusaetzlich ShouldBeUnique).
     */
    public function shouldDispatchWarmJob(int $projectId): bool
    {
        if (isset(self::$warmDispatched[$projectId])) {
            return false;
        }

        if (count(self::$warmDispatched) > 500) {
            self::$warmDispatched = [];
        }

        self::$warmDispatched[$projectId] = true;

        return true;
    }

    /**
     * @return array<int, int>
     */
    private function relatedProjectIds(int $projectId): array
    {
        if (!array_key_exists($projectId, self::$relatedProjectIdsMemo)) {
            if (count(self::$relatedProjectIdsMemo) > 500) {
                self::$relatedProjectIdsMemo = [];
            }

            $ids = [$projectId];
            $project = Project::find($projectId);

            if ($project) {
                foreach ($project->groups as $group) {
                    $ids[] = $group->id;
                }

                if ($project->is_group) {
                    foreach ($project->projectsOfGroup as $subProject) {
                        $ids[] = $subProject->id;
                    }
                }
            }

            self::$relatedProjectIdsMemo[$projectId] = $ids;
        }

        return self::$relatedProjectIdsMemo[$projectId];
    }

    /**
     * Budget-Templates werden bei jedem Budget-Tab-Aufruf gebraucht -
     * gecacht statt bei jedem Request alle Template-Tabellen zu laden.
     */
    public function getTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('budget:static:templates', self::TTL_BUDGET, function () {
            return \Artwork\Modules\Budget\Models\Table::where('is_template', true)->get();
        });
    }

    public function forgetTemplates(): void
    {
        Cache::forget('budget:static:templates');
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
