<?php

namespace App\Http\Controllers;

use Artwork\Modules\Permission\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\Permission\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Artwork\Modules\Permission\Services\PermissionPresetService;
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
            return Redirect::back()
                ->with(
                    'error',
                    __('flash-messages.permission-preset.error.created')
                );
        }

        return Redirect::back()->with('success', __('flash-messages.permission-preset.success.created'));
    }

    public function update(UpdatePermissionPresetRequest $request, PermissionPreset $permissionPreset): RedirectResponse
    {
        try {
            $this->permissionPresetService->updateFromRequest($request, $permissionPreset);
        } catch (Throwable $t) {
            return Redirect::back()->with(
                'error',
                __('flash-messages.permission-preset.error.updated')
            );
        }

        return Redirect::back()->with('success', __('flash-messages.permission-preset.success.updated'));
    }

    public function destroy(PermissionPreset $permissionPreset): RedirectResponse
    {
        try {
            $this->permissionPresetService->destroy($permissionPreset);
        } catch (Throwable $t) {
            return Redirect::back()->with(
                'error',
                __('flash-messages.permission-preset.error.deleted')
            );
        }

        return Redirect::back()->with('success', __('flash-messages.permission-preset.success.deleted'));
    }
}
