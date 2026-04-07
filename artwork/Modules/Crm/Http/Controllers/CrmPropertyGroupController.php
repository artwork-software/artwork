<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Events\CrmSettingsChanged;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmPropertyGroupController extends Controller
{
    public function __construct(
        private readonly CrmPropertyGroupService $service,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_confidential' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*.permissionable_type' => 'required|string',
            'permissions.*.permissionable_id' => 'required|integer',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
        ]);

        $group = $this->service->store($validated);

        if (!empty($validated['permissions'])) {
            $this->service->updatePermissions($group, $validated['permissions']);
        }

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function update(Request $request, CrmPropertyGroup $crmPropertyGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_confidential' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'permissions' => 'nullable|array',
            'permissions.*.permissionable_type' => 'required|string',
            'permissions.*.permissionable_id' => 'required|integer',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
        ]);

        $this->service->update($crmPropertyGroup, $validated);

        if (array_key_exists('is_confidential', $validated)) {
            if (!$validated['is_confidential']) {
                $crmPropertyGroup->permissions()->delete();
            } else {
                $this->service->updatePermissions($crmPropertyGroup, $validated['permissions'] ?? []);
            }
        }

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function destroy(CrmPropertyGroup $crmPropertyGroup): RedirectResponse
    {
        $this->service->destroy($crmPropertyGroup);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function updatePermissions(Request $request, CrmPropertyGroup $crmPropertyGroup): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*.permissionable_type' => 'required|string',
            'permissions.*.permissionable_id' => 'required|integer',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
        ]);

        $this->service->updatePermissions($crmPropertyGroup, $validated['permissions'] ?? []);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }
}
