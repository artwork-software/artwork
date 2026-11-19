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
use Artwork\Modules\Shift\Support\SafeBroadcast;

class CrmPropertyController extends Controller
{
    public function __construct(
        private readonly CrmPropertyService $service,
    ) {
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'properties' => 'required|array',
            'properties.*.id' => 'required|exists:crm_properties,id',
            'properties.*.sort_order' => 'required|integer',
        ]);

        $this->service->reorder($validated['properties']);

        SafeBroadcast::send(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crm_property_group_id' => 'required|exists:crm_property_groups,id',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::enum(CrmPropertyTypeEnum::class)],
            'select_values' => 'nullable|array',
            'multiselect_display' => ['nullable', 'string', Rule::in(['checkboxes', 'dropdown'])],
            'tooltip_text' => 'nullable|string|max:255',
            'contact_type_ids' => 'sometimes|array',
            'contact_type_ids.*' => 'integer|exists:crm_contact_types,id',
        ]);

        $contactTypeIds = $validated['contact_type_ids'] ?? null;
        unset($validated['contact_type_ids']);

        $this->service->store($validated, $contactTypeIds);

        SafeBroadcast::send(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function update(Request $request, CrmProperty $crmProperty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', 'string', Rule::enum(CrmPropertyTypeEnum::class)],
            'select_values' => 'nullable|array',
            'multiselect_display' => ['nullable', 'string', Rule::in(['checkboxes', 'dropdown'])],
            'tooltip_text' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer',
            'contact_type_ids' => 'sometimes|array',
            'contact_type_ids.*' => 'integer|exists:crm_contact_types,id',
        ]);

        $contactTypeIds = $validated['contact_type_ids'] ?? null;
        unset($validated['contact_type_ids']);

        $this->service->update($crmProperty, $validated);

        if ($contactTypeIds !== null) {
            $this->service->syncContactTypes($crmProperty, $contactTypeIds);
        }

        SafeBroadcast::send(new CrmSettingsChanged());

        return redirect()->back();
    }

    public function destroy(CrmProperty $crmProperty): RedirectResponse
    {
        $this->service->destroy($crmProperty);

        SafeBroadcast::send(new CrmSettingsChanged());

        return redirect()->back();
    }
}
