<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
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

        $contact = $this->contactService->store(
            $validated,
            $validated['property_values'] ?? []
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

        $this->contactService->update(
            $crmContact,
            $validated,
            $validated['property_values'] ?? []
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

        $path = $request->file('file')->store('crm-property-files', 'public');
        $this->contactService->savePropertyValue(
            $crmContact,
            (int) $request->input('property_id'),
            $path
        );

        return redirect()->back();
    }

    public function deletePropertyFile(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|integer|exists:crm_properties,id',
        ]);

        $this->contactService->savePropertyValue(
            $crmContact,
            (int) $request->input('property_id'),
            null
        );

        return redirect()->back();
    }
}
