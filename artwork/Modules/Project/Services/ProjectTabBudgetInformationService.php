<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\DTOs\BudgetInformationDto;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectTabBudgetInformationService
{
    public function __construct(
        private readonly ContractTypeService $contractTypeService,
        private readonly CompanyTypeService $companyTypeService,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function buildBudgetInformationPayload(Project $project, User $user): array
    {
        $dto = BudgetInformationDto::newInstance()
            ->setAccessBudget($project->access_budget)
            ->setContracts($this->visibleContracts($project, $user))
            ->setProjectFiles($this->visibleProjectFiles($project, $user))
            ->setProjectMoneySources($this->visibleMoneySources($project, $user))
            ->setProjectManagerIds($project->managerUsers->pluck('id'))
            ->setContractTypes($this->contractTypeService->getAll())
            ->setCompanyTypes($this->companyTypeService->getAll())
            ->setCurrencies($this->currencyService->getAll())
            ->setCostCenter($project->costCenter);

        return [
            'BudgetInformation' => $dto->toArray(),
        ];
    }

    /**
     * Spiegel von BudgetInformations.vue: Dokumente-Bereich nur mit globalen Budgetrechten,
     * Budgetzugriff im Projekt oder als Projektleitung; darin nur ausdrücklich freigegebene Dateien.
     */
    private function visibleProjectFiles(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value) &&
            !$this->hasBudgetAccess($project, $user) &&
            !$project->managerUsers->contains('id', $user->id)
        ) {
            return new Collection();
        }

        if ($this->isAdmin($user)) {
            return $project->project_files;
        }

        return $project->project_files()
            ->whereHas('accessingUsers', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->filter(fn (ProjectFile $projectFile) => $user->can('view', $projectFile))
            ->values();
    }

    /**
     * Verträge-Bereich nur mit Vertragsrecht oder Budgetzugriff; darin nur Verträge, die die Person
     * laut ContractPolicy öffnen darf.
     */
    private function visibleContracts(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::CONTRACT_EDIT_UPLOAD->value) &&
            !$this->hasBudgetAccess($project, $user)
        ) {
            return new Collection();
        }

        return $project->contracts
            ->filter(fn (Contract $contract) => $user->can('view', $contract))
            ->values();
    }

    private function visibleMoneySources(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value) &&
            !$this->hasBudgetAccess($project, $user)
        ) {
            return new Collection();
        }

        return $project->moneySources;
    }

    private function hasBudgetAccess(Project $project, User $user): bool
    {
        return $project->access_budget->contains('id', $user->id);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }
}
