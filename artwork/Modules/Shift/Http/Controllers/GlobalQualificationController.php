<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGlobalQualificationRequest;
use App\Http\Requests\UpdateGlobalQualificationRequest;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\User\Models\User;

class GlobalQualificationController extends Controller
{

    public function __construct(
        protected GlobalQualificationService $globalQualificationService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function store(StoreGlobalQualificationRequest $request): void
    {
        $this->globalQualificationService->create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(GlobalQualification $globalQualification): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GlobalQualification $globalQualification): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGlobalQualificationRequest $request, GlobalQualification $globalQualification): void
    {
        $this->globalQualificationService->update($globalQualification, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GlobalQualification $globalQualification): void
    {
        $this->globalQualificationService->delete($globalQualification);
    }

    public function toggleForUser(GlobalQualification $globalQualification, User $user): void
    {
        $this->globalQualificationService->activateOrDeactivateInUser($globalQualification, $user);
    }
}
