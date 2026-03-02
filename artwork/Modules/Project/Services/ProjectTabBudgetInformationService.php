<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\DTOs\BudgetInformationDto;

class ProjectTabBudgetInformationService
{
    public function __construct(
        private readonly ContractTypeService $contractTypeService,
        private readonly CompanyTypeService $companyTypeService,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function buildBudgetInformationPayload(Project $project): array
    {
        $dto = BudgetInformationDto::newInstance()
            ->setAccessBudget($project->access_budget)
            ->setContracts($project->contracts)
            ->setProjectFiles($project->project_files)
            ->setProjectMoneySources($project->moneySources)
            ->setProjectManagerIds($project->managerUsers->pluck('id'))
            ->setContractTypes($this->contractTypeService->getAll())
            ->setCompanyTypes($this->companyTypeService->getAll())
            ->setCurrencies($this->currencyService->getAll())
            ->setCostCenter($project->costCenter);

        return [
            'BudgetInformation' => $dto->toArray(),
        ];
    }
}

