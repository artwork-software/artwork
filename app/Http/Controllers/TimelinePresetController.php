<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Http\Requests\TimelinePresetSearchRequest;
use Artwork\Modules\Shift\Http\Requests\TimelinePresetStoreRequest;
use Artwork\Modules\Shift\Http\Requests\TimelinePresetUpdateRequest;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\Shift\Services\ShiftPresetTimelineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimelinePresetController extends Controller
{
    public function __construct(
        private readonly ShiftPresetTimelineService $shiftPresetTimelineService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/TimelinePresetSettings', [
            'timelinePresets' => ShiftPresetTimeline::with('times')->withCount('times')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimelinePresetStoreRequest $request): void
    {
        $this->shiftPresetTimelineService->storeTimelinePreset($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TimelinePresetUpdateRequest $request, ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $this->shiftPresetTimelineService->updateTimelinePreset($shiftPresetTimeline, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $shiftPresetTimeline->times()->each(function ($time): void {
            $time->delete();
        });

        $shiftPresetTimeline->delete();
    }

    public function duplicate(ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $this->shiftPresetTimelineService->duplicate($shiftPresetTimeline);
    }

    public function search(TimelinePresetSearchRequest $request)
    {
        $search = $request->search;
        return ShiftPresetTimeline::search($search)->get();
    }
}
