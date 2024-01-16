<?php

namespace App\Http\Controllers;

use Artwork\Modules\PermissionPresets\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Services\PermissionPresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PermissionPresetController extends Controller
{
    public function __construct(private readonly PermissionPresetService $permissionPresetService)
    {
    }

    public function index(): Response
    {
        return Inertia::render(
            'PermissionPresets/Index',
            [
                'permission_presets' => $this->permissionPresetService->getPermissionPresets(),
                'available_permissions' => $this->permissionPresetService->getAvailablePermissions(),
            ]
        );
    }

    public function store(StorePermissionPresetRequest $request): RedirectResponse
    {
        try {
            $this->permissionPresetService->createFromRequest($request);
        } catch (Throwable $t) {
            Log::error($t->getMessage());
            return Redirect::back()
                ->with(
                    'error',
                    'Rechte-Preset konnte nicht gespeichert werden. Bitte versuche es erneut.'
                );
        }

        return Redirect::back()->with('success', 'Rechte-Preset erfolgreich erstellt.');
    }

    public function update(UpdatePermissionPresetRequest $request, PermissionPreset $permissionPreset): RedirectResponse
    {
        try {
            $this->permissionPresetService->updateFromRequest($request, $permissionPreset);
        } catch (Throwable $t) {
            Log::error($t->getMessage());
            return Redirect::back()->with(
                'error',
                'Rechte-Preset konnte nicht aktualisiert werden. Bitte versuche es erneut.'
            );
        }

        return Redirect::back()->with('success', 'Rechte-Preset erfolgreich aktualisiert.');
    }

    public function destroy(PermissionPreset $permissionPreset): RedirectResponse
    {
        try {
            $this->permissionPresetService->destroy($permissionPreset);
        } catch (Throwable $t) {
            Log::error($t->getMessage());
            return Redirect::back()->with(
                'error',
                'Rechte-Preset konnte nicht gelöscht werden. Bitte versuche es erneut.'
            );
        }

        return Redirect::back()->with('success', 'Rechte-Preset erfolgreich gelöscht.');
    }
}
