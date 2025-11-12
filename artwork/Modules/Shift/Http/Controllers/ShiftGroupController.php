<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Http\Requests\StoreShiftGroupRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftGroupRequest;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Services\ShiftGroupService;
use Inertia\Inertia;

class ShiftGroupController extends Controller
{
    public function __construct(
        protected ShiftGroupService $shiftGroupService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings/ShiftGroups/Index', [
            'shiftGroups' => $this->shiftGroupService->getAllShiftGroups(),
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
    public function store(StoreShiftGroupRequest $request): void
    {
        $this->shiftGroupService->createShiftGroup($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(ShiftGroup $shiftGroup): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftGroup $shiftGroup): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftGroupRequest $request, ShiftGroup $shiftGroup): void
    {
        $this->shiftGroupService->updateShiftGroup($shiftGroup, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftGroup $shiftGroup): void
    {
        $this->shiftGroupService->deleteShiftGroup($shiftGroup);
    }
}
