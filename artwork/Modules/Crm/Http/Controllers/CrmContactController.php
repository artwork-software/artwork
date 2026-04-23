<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmContactController extends Controller
{
    public function __construct(
        private readonly CrmContactService $contactService,
        private readonly CrmPropertyGroupService $propertyGroupService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $contacts = $this->contactService->searchForLinking(
            $request->get('search'),
            $request->integer('type_id') ?: null,
            20
        );

        return response()->json($contacts->map(fn ($c) => [
            'id' => $c->id,
            'display_name' => $c->display_name,
            'profile_photo_url' => $c->profile_photo_url,
            'contact_type' => $c->contactType ? [
                'id' => $c->contactType->id,
                'name' => $c->contactType->name,
                'slug' => $c->contactType->slug,
                'color' => $c->contactType->color,
            ] : null,
        ]));
    }

    public function getData(CrmContact $crmContact): JsonResponse
    {
        $crmContact->load(['contactType.properties', 'propertyValues.property.group']);

        $user = auth()->user();
        $deptIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);

        $groups = $this->propertyGroupService->getVisibleForUser($user->id, $deptIds, $isCrmManager);
        $groups->loadMissing('properties');
        $this->propertyGroupService->annotateEditPermissions($groups, $user->id, $deptIds, $isCrmManager);

        // Filter property values to only include those from visible groups
        $visiblePropertyIds = $groups->flatMap(fn ($g) => $g->properties)->pluck('id')->toArray();
        $crmContact->setRelation(
            'propertyValues',
            $crmContact->propertyValues->whereIn('crm_property_id', $visiblePropertyIds)->values()
        );

        return response()->json([
            'contact' => $crmContact,
            'propertyGroups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crm_contact_type_id' => 'required|exists:crm_contact_types,id',
            'display_name' => 'required|string|max:255',
            'profile_image' => 'nullable|string',
            'is_active' => 'boolean',
            'property_values' => 'array',
            'property_values.*' => 'nullable',
        ]);

        $propertyValues = $validated['property_values'] ?? [];
        $this->authorizePropertyEdits(array_keys($propertyValues));

        $contact = $this->contactService->store(
            $validated,
            $propertyValues
        );

        return redirect()->route('crm.contacts.show', $contact);
    }

    public function update(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'profile_image' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'property_values' => 'array',
            'property_values.*' => 'nullable',
        ]);

        $propertyValues = $validated['property_values'] ?? [];
        $this->authorizePropertyEdits(array_keys($propertyValues));

        $this->contactService->update(
            $crmContact,
            $validated,
            $propertyValues
        );

        return redirect()->back();
    }

    public function destroy(CrmContact $crmContact): RedirectResponse
    {
        $this->contactService->destroy($crmContact);

        return redirect()->route('crm.index');
    }

    public function updateProfileImage(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $request->validate([
            'profile_image' => 'required|image|max:2048',
        ]);

        $path = $request->file('profile_image')->store('crm-profile-images', 'public');
        $this->contactService->updateProfileImage($crmContact, $path);

        return redirect()->back();
    }

    public function uploadPropertyFile(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|integer|exists:crm_properties,id',
            'file' => 'required|file|max:10240',
        ]);

        $propertyId = (int) $request->input('property_id');
        $this->authorizePropertyEdits([$propertyId]);

        $path = $request->file('file')->store('crm-property-files', 'public');
        $this->contactService->savePropertyValue($crmContact, $propertyId, $path);

        return redirect()->back();
    }

    public function deletePropertyFile(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|integer|exists:crm_properties,id',
        ]);

        $propertyId = (int) $request->input('property_id');
        $this->authorizePropertyEdits([$propertyId]);

        $this->contactService->savePropertyValue($crmContact, $propertyId, null);

        return redirect()->back();
    }

    /**
     * Resolve the accommodation_id from a CRM contact's polymorphic entity.
     */
    private function resolveAccommodationId(CrmContact $crmContact): int
    {
        $entity = $crmContact->entity;

        if (!$entity || !($entity instanceof \Artwork\Modules\Accommodation\Models\Accommodation)) {
            abort(422, 'CRM contact is not linked to an accommodation.');
        }

        return $entity->id;
    }

    public function updateRoomTypes(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'room_types' => 'array',
            'room_types.*' => 'integer|exists:accommodation_room_types,id',
            'room_type_costs' => 'array',
            'room_type_costs.*' => 'nullable|numeric|min:0',
        ]);

        $accommodationId = $this->resolveAccommodationId($crmContact);
        $roomTypeIds = $validated['room_types'] ?? [];
        $costs = $validated['room_type_costs'] ?? [];

        $syncData = [];
        foreach ($roomTypeIds as $roomTypeId) {
            $syncData[$roomTypeId] = [
                'accommodation_id' => $accommodationId,
                'cost_per_night' => $costs[$roomTypeId] ?? 0,
            ];
        }

        $crmContact->roomTypes()->sync($syncData);

        return redirect()->back();
    }

    public function storeRoomType(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_per_night' => 'nullable|numeric|min:0',
        ]);

        $accommodationId = $this->resolveAccommodationId($crmContact);

        $roomType = AccommodationRoomType::create(['name' => $validated['name']]);

        $crmContact->roomTypes()->attach($roomType->id, [
            'accommodation_id' => $accommodationId,
            'cost_per_night' => $validated['cost_per_night'] ?? 0,
        ]);

        return redirect()->back();
    }

    public function updateRoomTypeName(Request $request, AccommodationRoomType $roomType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $roomType->update(['name' => $validated['name']]);

        return redirect()->back();
    }

    public function destroyRoomType(CrmContact $crmContact, AccommodationRoomType $roomType): RedirectResponse
    {
        $crmContact->roomTypes()->detach($roomType->id);

        return redirect()->back();
    }

    private function authorizePropertyEdits(array $propertyIds): void
    {
        if (empty($propertyIds)) {
            return;
        }

        $user = auth()->user();
        $deptIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);

        $editablePropertyIds = $this->propertyGroupService->getEditablePropertyIds(
            $user->id,
            $deptIds,
            $isCrmManager
        );

        $forbidden = array_diff(
            array_map('intval', $propertyIds),
            $editablePropertyIds
        );

        if (!empty($forbidden)) {
            abort(403, 'Keine Berechtigung zum Bearbeiten dieser Eigenschaften.');
        }
    }
}
