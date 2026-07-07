<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Events\CrmSettingsChanged;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Services\CrmPropertyService;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmPropertyController extends Controller
{
    public function __construct(
        private readonly CrmPropertyService $service,
    ) {}

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'properties' => 'required|array',
            'properties.*.id' => 'required|exists:crm_properties,id',
            'properties.*.sort_order' => 'required|integer',
        ]);

        $this->service->reorder($validated['properties']);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crm_property_group_id' => 'required|exists:crm_property_groups,id',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::enum(CrmPropertyTypeEnum::class)],
            'select_values' => 'nullable|array',
            'tooltip_text' => 'nullable|string',
        ]);

        $this->service->store($validated);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function update(Request $request, CrmProperty $crmProperty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:text,date,number,checkbox,select,link,textarea,upload',
            'select_values' => 'nullable|array',
            'tooltip_text' => 'nullable|string',
            'sort_order' => 'sometimes|integer',
        ]);

        $this->service->update($crmProperty, $validated);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function destroy(CrmProperty $crmProperty): RedirectResponse
    {
        $this->service->destroy($crmProperty);

        broadcast(new CrmSettingsChanged());

        return redirect()->back();
    }
}
