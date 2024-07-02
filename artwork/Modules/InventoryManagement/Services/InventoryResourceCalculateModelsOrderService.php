<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;

class InventoryResourceCalculateModelsOrderService
{
    /**
     * @return array<int, Model>
     */
    public function getReorderedModels(
        Collection $modelsToOrder,
        int $order,
        Model $movingModel
    ): array {
        $orderedModels = [];

        $modelsToOrder->each(
            function (
                Model $model,
                int $index
            ) use (
                $order,
                &$orderedModels,
                $movingModel
            ): void {
                if ($model->id === $movingModel->id) {
                    return;
                }

                if ($order === 0 && $index === 0) {
                    $orderedModels[] = $movingModel;
                    $orderedModels[] = $model;

                    return;
                }

                $orderedModels[] = $model;

                if (($index + 1) === $order) {
                    $orderedModels[] = $movingModel;
                }
            }
        );

        return $orderedModels;
    }
}
