<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\TableColumnOrder;
use Artwork\Modules\Budget\Repositories\TableColumnOrderRepository;
use Illuminate\Support\Collection;
use Throwable;

class TableColumnOrderService
{
    public function __construct(private readonly TableColumnOrderRepository $tableColumnOrderRepository)
    {
    }

    /**
     * @throws Throwable
     */
    public function create(array $attributes): TableColumnOrder
    {
        $this->tableColumnOrderRepository->saveOrFail(
            ($instance = $this->tableColumnOrderRepository->getNewModelInstance())->fill($attributes)
        );

        return $instance;
    }

    public function getAllOrderedByPosition(): Collection
    {
        return $this->tableColumnOrderRepository->getAllOrderedByPosition();
    }

    public function updateTableColumnOrders(array $newTableColumnOrders): void
    {
        foreach ($newTableColumnOrders as $index => $tableColumnOrderId) {
            $this->tableColumnOrderRepository->update(
                $this->tableColumnOrderRepository->findOrFail($tableColumnOrderId),
                ['position' => $index]
            );
        }
    }
}
