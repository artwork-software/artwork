<?php

namespace Artwork\Modules\ShiftTimePreset\Services;

use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;
use Artwork\Modules\ShiftTimePreset\Repositories\ShiftTimePresetRepository;

readonly class ShiftTimePresetService
{
    public function __construct(
        private ShiftTimePresetRepository $shiftTimePresetRepository
    ) {
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->shiftTimePresetRepository->all();
    }

    public function createByRequest(array $data): void
    {
        $shiftTimePreset = new ShiftTimePreset();
        $shiftTimePreset->name = $data['name'];
        $shiftTimePreset->start_time = $data['start_time'];
        $shiftTimePreset->end_time = $data['end_time'];
        $shiftTimePreset->break_time = $data['break_time'];
        $this->shiftTimePresetRepository->save($shiftTimePreset);
    }

    public function updateByRequest(ShiftTimePreset $shiftTimePreset, array $data): void
    {
        $shiftTimePreset->fill($data);
        $this->shiftTimePresetRepository->save($shiftTimePreset);
    }

    public function delete(ShiftTimePreset $shiftTimePreset): void
    {
        $this->shiftTimePresetRepository->delete($shiftTimePreset);
    }
}
