<?php

namespace App\Http\Controllers;

use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Illuminate\Http\Request;

class PresetTimeLineController extends Controller
{
    public function store(ShiftPreset $shiftPreset): void
    {
        $shiftPreset->timeline()->create();
    }

    public function update(Request $request): void
    {
        foreach ($request->timelines as $timeline) {
            $findTimeLine = ShiftPresetTimeline::find($timeline['id']);
            $findTimeLine->update([
                'start' => $timeline['start'],
                'end' => $timeline['end'],
                'description' => nl2br($timeline['description_without_html'])
            ]);
        }
    }

    public function destroy(ShiftPresetTimeline $presetTimeLine): void
    {
        $presetTimeLine->delete();
    }
}
