<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Events\CrmSettingsChanged;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CrmContactTypeController extends Controller
{
    public function __construct(
        private readonly CrmContactTypeService $service,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $this->service->store($validated);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function update(Request $request, CrmContactType $crmContactType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $this->service->update($crmContactType, $validated);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function syncProperties(Request $request, CrmContactType $crmContactType): RedirectResponse
    {
        $validated = $request->validate([
            'properties' => 'present|array',
            'properties.*.id' => 'required|integer|exists:crm_properties,id',
            'properties.*.is_required' => 'boolean',
            'properties.*.show_in_list' => 'boolean',
            'properties.*.is_filterable' => 'boolean',
        ]);

        $this->service->syncProperties($crmContactType, $validated['properties']);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function destroy(CrmContactType $crmContactType): RedirectResponse
    {
        $this->service->destroy($crmContactType);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }
}
