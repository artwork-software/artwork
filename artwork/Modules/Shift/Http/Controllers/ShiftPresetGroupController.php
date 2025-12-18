<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShiftPresetGroupController extends Controller
{
    /**
     * Display a listing of shift preset groups.
     */
    public function index(): Response
    {
        $groups = ShiftPresetGroup::query()
            ->select(['id', 'name'])
            ->withCount('presets')
            ->with([
                'presets' => function ($q): void {
                    $q->select([
                        'single_shift_presets.id',
                        'single_shift_presets.name',
                        'single_shift_presets.start_time',
                        'single_shift_presets.end_time',
                        'single_shift_presets.break_duration',
                        'single_shift_presets.craft_id',
                        'single_shift_presets.description',
                    ])->with([
                        'craft:id,name,abbreviation,color',
                        'shiftsQualifications:id,name,icon,available',
                    ]);
                }
            ])
            ->orderBy('name')
            ->get();

        $presets = SingleShiftPreset::query()
            ->select([
                'id',
                'name',
                'start_time',
                'end_time',
                'break_duration',
                'craft_id',
                'description',
            ])
            ->with([
                'craft:id,name,abbreviation,color',
                'shiftsQualifications:id,name,icon,available',
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/ShiftPresetGroups', [
            'groups'  => $groups,
            'presets' => $presets,
        ]);
    }


    /**
     * Store a newly created ShiftPresetGroup.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'preset_ids' => ['nullable', 'array'],
            'preset_ids.*' => ['integer', 'exists:single_shift_presets,id'],
        ]);

        DB::transaction(function () use ($data): void {
            $group = ShiftPresetGroup::create([
                'name' => $data['name'],
            ]);

            $group->presets()->sync($data['preset_ids'] ?? []);
        });

        return redirect()->back()->with('success', [
            'shift_preset_group' => __('Group created'),
        ]);
    }

    /**
     * Update the specified ShiftPresetGroup.
     */
    public function update(Request $request, ShiftPresetGroup $shiftPresetGroup): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'preset_ids' => ['nullable', 'array'],
            'preset_ids.*' => ['integer', 'exists:single_shift_presets,id'],
        ]);

        DB::transaction(function () use ($data, $shiftPresetGroup): void {
            $shiftPresetGroup->update([
                'name' => $data['name'],
            ]);

            $shiftPresetGroup->presets()->sync($data['preset_ids'] ?? []);
        });

        return redirect()->back()->with('success', [
            'shift_preset_group' => __('Group updated'),
        ]);
    }

    /**
     * Remove the specified ShiftPresetGroup.
     */
    public function destroy(ShiftPresetGroup $shiftPresetGroup): RedirectResponse
    {
        DB::transaction(function () use ($shiftPresetGroup): void {
            $shiftPresetGroup->presets()->detach();
            $shiftPresetGroup->delete();
        });

        return redirect()->back()->with('success', [
            'shift_preset_group' => __('Group deleted'),
        ]);
    }
}
