<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetTimeLine;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Illuminate\Http\Request;

class PresetTimeLineController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(ShiftPreset $shiftPreset): void
    {
        $shiftPreset->timeLine()->create();
    }


    public function show(PresetTimeLine $presetTimeLine): void
    {
    }


    public function edit(PresetTimeLine $presetTimeLine): void
    {
    }


    public function update(Request $request): void
    {
        foreach ($request->timelines as $timeline) {
            $findTimeLine = PresetTimeLine::find($timeline['id']);
            $findTimeLine->update([
                'start' => $timeline['start'],
                'end' => $timeline['end'],
                'description' => $timeline['description']
            ]);
        }
    }

    public function destroy(PresetTimeLine $presetTimeLine): void
    {
        $presetTimeLine->delete();
    }
}
