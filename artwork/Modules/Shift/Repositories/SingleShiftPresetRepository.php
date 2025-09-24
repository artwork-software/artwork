<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Modules\Shift\Models\SingleShiftPreset;

class SingleShiftPresetRepository
{
    public function create(array $data): SingleShiftPreset
    {
        return SingleShiftPreset::create($data);
    }

    public function update(SingleShiftPreset $preset, array $data): SingleShiftPreset
    {
        $preset->update($data);
        return $preset;
    }

    public function delete(SingleShiftPreset $preset): bool
    {
        return $preset->delete();
    }
}
