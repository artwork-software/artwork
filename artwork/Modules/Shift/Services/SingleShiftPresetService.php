<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\Shift\Repositories\SingleShiftPresetRepository;
use Inertia\Inertia;

class SingleShiftPresetService
{
    public function __construct(
        private readonly SingleShiftPresetRepository $repository
    ) {}

    public function createPreset(array $data): SingleShiftPreset
    {
        $qualifications = $data['shift_qualifications'] ?? [];
        unset($data['shift_qualifications']);
        $preset = $this->repository->create($data);
        if (!empty($qualifications)) {
            $syncData = [];
            foreach ($qualifications as $q) {
                $syncData[$q['id']] = ['quantity' => $q['quantity']];
            }
            $preset->shiftsQualifications()->sync($syncData);
        }
        return $preset;
    }

    public function updatePreset(SingleShiftPreset $preset, array $data): SingleShiftPreset
    {
        $qualifications = $data['shift_qualifications'] ?? null;
        unset($data['shift_qualifications']);
        $preset = $this->repository->update($preset, $data);
        if ($qualifications !== null) {
            $syncData = [];
            foreach ($qualifications as $q) {
                $syncData[$q['id']] = ['quantity' => $q['quantity']];
            }
            $preset->shiftsQualifications()->sync($syncData);
        }
        return $preset;
    }

    public function deletePreset(SingleShiftPreset $preset): bool
    {
        $preset->shiftsQualifications()->detach();
        return $this->repository->delete($preset);
    }

    public function getAllPresets(): \Illuminate\Database\Eloquent\Collection
    {
        return SingleShiftPreset::query()
        ->select([
            'id', 'name', 'start_time', 'end_time', 'break_duration', 'craft_id', 'description'
        ])
        ->with([
            'craft:id,name,abbreviation,color',
            'shiftsQualifications:id,name,icon,available',
        ])
        ->get();
    }

    public function shareSingleShiftPresets(): void
    {
        Inertia::share([
            'singleShiftPresets' => $this->getAllPresets(),
        ]);
    }
}
