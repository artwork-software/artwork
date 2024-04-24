<?php

namespace Artwork\Modules\BudgetColumnSetting\Services;

use Artwork\Modules\BudgetColumnSetting\Http\Requests\UpdateBudgetColumnSettingRequest;
use Artwork\Modules\BudgetColumnSetting\Models\BudgetColumnSetting;
use Artwork\Modules\BudgetColumnSetting\Repositories\BudgetColumnSettingRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class BudgetColumnSettingService
{
    public function __construct(private BudgetColumnSettingRepository $budgetColumnSettingRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->budgetColumnSettingRepository->getAll();
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        BudgetColumnSetting $budgetColumnSetting,
        UpdateBudgetColumnSettingRequest $updateBudgetColumnSettingRequest
    ): void {
        $this->budgetColumnSettingRepository->updateOrFail(
            $budgetColumnSetting,
            $updateBudgetColumnSettingRequest->validated()
        );
    }

    public function getColumnNameByColumnPosition(int $position): string
    {
        $budgetColumnSetting = $this->budgetColumnSettingRepository->findByColumnPosition($position);

        if (is_null($budgetColumnSetting)) {
            return '';
        }

        return $budgetColumnSetting->column_name;
    }
}
