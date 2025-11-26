<?php

namespace Artwork\Modules\Shift\QueryBuilders;

use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Illuminate\Database\Eloquent\Builder;

class ShiftsQualificationsBuilder extends Builder
{
    /**
     * Überschreibt das Standard-Update, um Änderungen zu loggen.
     */
    public function update(array $values): int
    {
        // Nur tracken, wenn relevante Felder dabei sind
        $trackKeys = array_intersect(['value', 'shift_qualification_id'], array_keys($values));

        if (empty($trackKeys)) {
            // nichts Relevantes -> normales Verhalten
            return parent::update($values);
        }

        // 1. Betroffene Datensätze VOR dem Update laden
        /** @var \Illuminate\Database\Eloquent\Collection<int, ShiftsQualifications> $models */
        $models = (clone $this)->get();

        if ($models->isEmpty()) {
            return parent::update($values);
        }

        $originals = [];
        foreach ($models as $model) {
            $originals[$model->getKey()] = $model->getAttributes();
        }

        // 2. Das eigentliche DB-Update ausführen
        $updatedRows = parent::update($values);

        // 3. Logging über ShiftChangeRecorder
        /** @var ShiftChangeRecorder $recorder */
        $recorder = app(ShiftChangeRecorder::class);

        foreach ($models as $model) {
            $fresh = $model->fresh();
            if (! $fresh) {
                continue;
            }

            $original = $originals[$model->getKey()] ?? null;
            if (! $original) {
                continue;
            }

            $recorder->recordWithOriginal($fresh, $original, 'updated');
        }

        return $updatedRows;
    }
}
