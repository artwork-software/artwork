<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Http\Requests\StoreSingleShiftPresetRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateSingleShiftPresetRequest;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\Shift\Services\SingleShiftPresetService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class SingleShiftPresetController extends Controller
{
    public function __construct(
        private readonly SingleShiftPresetService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        $presets = SingleShiftPreset::with('shiftsQualifications')->get();
        $crafts = \Artwork\Modules\Craft\Models\Craft::all();
        $shiftQualifications = \Artwork\Modules\Shift\Models\ShiftQualification::all();
        return Inertia::render('Shift/SingleShiftPresetOverview', [
            'presets' => $presets,
            'crafts' => $crafts,
            'shiftQualifications' => $shiftQualifications,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSingleShiftPresetRequest $request)
    {
        $preset = $this->service->createPreset($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(SingleShiftPreset $singleShiftPreset)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SingleShiftPreset $singleShiftPreset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSingleShiftPresetRequest $request, SingleShiftPreset $singleShiftPreset)
    {
        $preset = $this->service->updatePreset($singleShiftPreset, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SingleShiftPreset $singleShiftPreset)
    {
        $success = $this->service->deletePreset($singleShiftPreset);
    }
}
