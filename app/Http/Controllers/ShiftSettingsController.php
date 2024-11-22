<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftSettingsController extends Controller
{
    public function index(ShiftQualificationService $shiftQualificationService): Response
    {
        return Inertia::render('Settings/ShiftSettings', [
            'crafts' => Craft::query()
                ->with('managingUsers', 'managingFreelancers', 'managingServiceProviders')
                ->orderBy('position')
                ->get(),
            'eventTypes' => EventType::all(),
            'usersWithPermission' => User::permission(PermissionEnum::SHIFT_PLANNER->value)->get(),
            'usersWithInventoryPermission' => User::permission(PermissionEnum::INVENTORY_PLANER->value)->get(),
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'shiftTimePresets' => ShiftTimePreset::all()
        ]);
    }

    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function edit(int $id): void
    {
    }

    public function update(Request $request, int $id): void
    {
    }

    public function destroy(int $id): void
    {
    }
}
