<?php

namespace Artwork\Modules\ProjectTab\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class BudgetInformationDto extends BaseDto
{
    public ?CostCenter $costCenter = null;

    public ?Collection $projectManagerIds = null;

    public ?EloquentCollection $project_files = null;

    public ?EloquentCollection $contracts = null;

    public ?EloquentCollection $accessBudget = null;

    public ?EloquentCollection $projectMoneySources = null;

    public ?EloquentCollection $contractTypes = null;

    public ?EloquentCollection $companyTypes = null;

    public ?EloquentCollection $currencies = null;

    public ?EloquentCollection $collectingSocieties = null;

    public function setProjectManagerIds(?Collection $projectManagerIds): self
    {
        $this->projectManagerIds = $projectManagerIds;

        return $this;
    }

    public function setProjectFiles(?EloquentCollection $project_files): self
    {
        $this->project_files = $project_files;

        return $this;
    }

    public function setContracts(?EloquentCollection $contracts): self
    {
        $this->contracts = $contracts;

        return $this;
    }

    public function setAccessBudget(?EloquentCollection $accessBudget): self
    {
        $this->accessBudget = $accessBudget;

        return $this;
    }

    public function setProjectMoneySources(?EloquentCollection $projectMoneySources): self
    {
        $this->projectMoneySources = $projectMoneySources;

        return $this;
    }

    public function setContractTypes(?EloquentCollection $contractTypes): self
    {
        $this->contractTypes = $contractTypes;

        return $this;
    }

    public function setCompanyTypes(?EloquentCollection $companyTypes): self
    {
        $this->companyTypes = $companyTypes;

        return $this;
    }

    public function setCurrencies(?EloquentCollection $currencies): self
    {
        $this->currencies = $currencies;

        return $this;
    }

    public function setCollectingSocieties(?EloquentCollection $collectingSocieties): self
    {
        $this->collectingSocieties = $collectingSocieties;

        return $this;
    }

    public function getCostCenter(): ?CostCenter
    {
        return $this->costCenter;
    }
    public function setCostCenter(?CostCenter $costCenter): self
    {
        $this->costCenter = $costCenter;

        return $this;
    }

    public function getProjectManagerIds(): ?Collection
    {
        return $this->projectManagerIds;
    }

    public function getProjectFiles(): ?EloquentCollection
    {
        return $this->project_files;
    }

    public function getContracts(): ?EloquentCollection
    {
        return $this->contracts;
    }

    public function getAccessBudget(): ?EloquentCollection
    {
        return $this->accessBudget;
    }

    public function getProjectMoneySources(): ?EloquentCollection
    {
        return $this->projectMoneySources;
    }

    public function getContractTypes(): ?EloquentCollection
    {
        return $this->contractTypes;
    }

    public function getCompanyTypes(): ?EloquentCollection
    {
        return $this->companyTypes;
    }

    public function getCurrencies(): ?EloquentCollection
    {
        return $this->currencies;
    }

    public function getCollectingSocieties(): ?EloquentCollection
    {
        return $this->collectingSocieties;
    }
}
