<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmContactController extends Controller
{
    // Kontakte dieser Typen werden aus User-/Freelancer-/Dienstleister-Profilen
    // gespiegelt und sind im CRM read-only — Schreibzugriffe würden sonst in die
    // Quell-Entität zurückgeschrieben.
    private const MIRRORED_SLUGS = [
        CrmSystemContactTypeEnum::USER->value,
        CrmSystemContactTypeEnum::FREELANCER->value,
        CrmSystemContactTypeEnum::SERVICE_PROVIDER->value,
    ];

    public function __construct(
        private readonly CrmContactService $contactService,
        private readonly CrmPropertyGroupService $propertyGroupService,
    ) {}

    private function abortIfMirrored(CrmContact $crmContact): void
    {
        if (in_array($crmContact->contactType?->slug, self::MIRRORED_SLUGS, true)) {
            abort(403, 'Gespiegelte Kontakte können nur über das jeweilige Profil geändert werden.');
        }
    }

    public function search(Request $request): JsonResponse
    {
        $typeId = $request->integer('type_id') ?: null;

        // Alternativ zur type_id kann der stabile Slug (z.B. "artist") übergeben werden.
        if ($typeId === null && ($typeSlug = $request->string('type_slug')->toString()) !== '') {
            $typeId = CrmContactType::query()->where('slug', $typeSlug)->value('id');

            if ($typeId === null) {
                return response()->json([]);
            }
        }

        $contacts = $this->contactService->searchForLinking(
            $request->get('search'),
            $typeId,
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

    /**
     * Liefert die Anlage-Maske für einen Kontakttyp: alle für den aktuellen User
     * editierbaren Properties des Typs, gruppiert nach Property-Gruppen.
     * Upload-Felder sind ausgenommen (brauchen einen bestehenden Kontakt).
     */
    public function createMask(Request $request): JsonResponse
    {
        $typeSlug = $request->string('type_slug')->toString();
        $contactType = CrmContactType::query()
            ->where('slug', $typeSlug)
            ->with('properties')
            ->firstOrFail();

        $user = auth()->user();
        $deptIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);

        $editablePropertyIds = array_flip(
            $this->propertyGroupService->getEditablePropertyIds($user->id, $deptIds, $isCrmManager)
        );
        $typeProperties = $contactType->properties->keyBy('id');

        $groups = $this->propertyGroupService->getVisibleForUser($user->id, $deptIds, $isCrmManager);
        $groups->loadMissing('properties');

        $maskGroups = $groups
            ->map(function ($group) use ($editablePropertyIds, $typeProperties) {
                $properties = $group->properties
                    ->filter(fn ($property) => isset($editablePropertyIds[$property->id])
                        && $typeProperties->has($property->id)
                        && $property->type !== CrmPropertyTypeEnum::UPLOAD)
                    ->sortBy(fn ($property) => $typeProperties[$property->id]->pivot->sort_order ?? 0)
                    ->values()
                    ->map(fn ($property) => [
                        'id' => $property->id,
                        'name' => $property->name,
                        'type' => $property->type,
                        'select_values' => $property->select_values,
                        'tooltip_text' => $property->tooltip_text,
                        'is_required' => (bool) ($typeProperties[$property->id]->pivot->is_required ?? false),
                    ]);

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'icon' => $group->icon,
                    'properties' => $properties,
                    // Gruppen-Reihenfolge des Typs (kleinste Pivot-sort_order der Gruppe)
                    'sort_order' => $properties
                        ->map(fn ($property) => $typeProperties[$property['id']]->pivot->sort_order ?? 0)
                        ->min() ?? 0,
                ];
            })
            ->filter(fn ($group) => count($group['properties']) > 0)
            ->sortBy('sort_order')
            ->values();

        return response()->json([
            'contact_type' => [
                'id' => $contactType->id,
                'name' => $contactType->name,
                'slug' => $contactType->slug,
            ],
            'groups' => $maskGroups,
        ]);
    }

    /**
     * Schlanke Tooltip-Daten (Gegenstück zu UserController::tooltipInfo) —
     * liefert nur Basisdaten + Kontaktfelder aus für den User sichtbaren Gruppen.
     */
    public function tooltipInfo(CrmContact $crmContact): JsonResponse
    {
        $crmContact->load(['contactType', 'propertyValues.property.group']);

        $user = auth()->user();
        $deptIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);

        $groups = $this->propertyGroupService->getVisibleForUser($user->id, $deptIds, $isCrmManager);
        $groups->loadMissing('properties');
        $visiblePropertyIds = $groups->flatMap(fn ($g) => $g->properties)->pluck('id')->all();

        $visibleValue = static function (string $propertyName) use ($crmContact, $visiblePropertyIds): ?string {
            return $crmContact->propertyValues
                ->first(
                    static fn ($propertyValue) => $propertyValue->property?->name === $propertyName
                        && in_array($propertyValue->crm_property_id, $visiblePropertyIds, true)
                )?->value;
        };

        return response()->json([
            'id' => $crmContact->id,
            'display_name' => $crmContact->display_name,
            'profile_photo_url' => $crmContact->profile_photo_url,
            'contact_type' => $crmContact->contactType ? [
                'id' => $crmContact->contactType->id,
                'name' => $crmContact->contactType->name,
                'slug' => $crmContact->contactType->slug,
                'color' => $crmContact->contactType->color,
                'icon' => $crmContact->contactType->icon,
            ] : null,
            'email' => $visibleValue('Email'),
            'phone_number' => $visibleValue('Telefon'),
            'city' => $visibleValue('Stadt'),
        ]);
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

    public function store(Request $request): JsonResponse|RedirectResponse
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

        // JSON-Clients (z.B. "Neue Künstler*in anlegen & verknüpfen" im Projekt)
        // brauchen den Kontakt direkt zurück statt eines Redirects
        if ($request->wantsJson()) {
            $contact->load('contactType');

            return response()->json([
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'profile_photo_url' => $contact->profile_photo_url,
                'contact_type' => $contact->contactType ? [
                    'id' => $contact->contactType->id,
                    'name' => $contact->contactType->name,
                    'slug' => $contact->contactType->slug,
                    'color' => $contact->contactType->color,
                ] : null,
            ], 201);
        }

        return redirect()->route('crm.contacts.show', $contact);
    }

    public function update(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $this->abortIfMirrored($crmContact);

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

    public function changeType(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $this->abortIfMirrored($crmContact);

        // Entity-gebundene Kontakte (Künstler*innen, Unterkünfte, Hersteller …) hängen
        // strukturell an ihrem Typ — der Wechsel ist nur für freie Kontakte erlaubt.
        if ($crmContact->entity_type !== null) {
            abort(403, 'Der Kontakttyp von verknüpften Kontakten kann nicht geändert werden.');
        }

        $validated = $request->validate([
            'crm_contact_type_id' => 'required|integer|exists:crm_contact_types,id',
        ]);

        $newType = CrmContactType::findOrFail($validated['crm_contact_type_id']);

        if (in_array($newType->slug, self::MIRRORED_SLUGS, true)) {
            abort(422, 'Kontakte können nicht in einen system-verwalteten Typ umgewandelt werden.');
        }

        $this->contactService->changeType($crmContact, $newType);

        return redirect()->back();
    }

    public function destroy(CrmContact $crmContact): RedirectResponse
    {
        $this->abortIfMirrored($crmContact);

        $this->contactService->destroy($crmContact);

        return redirect()->route('crm.index');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:crm_contacts,id',
        ]);

        $contacts = CrmContact::with('contactType')->whereIn('id', $validated['ids'])->get();

        foreach ($contacts as $contact) {
            // Gespiegelte Kontakte werden übersprungen statt die ganze Auswahl zu blocken
            if (in_array($contact->contactType?->slug, self::MIRRORED_SLUGS, true)) {
                continue;
            }

            $this->contactService->destroy($contact);
        }

        return redirect()->back();
    }

    public function getTrashed(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedContacts = CrmContact::onlyTrashed()
            ->with('contactType')
            ->when($search !== '', fn ($query) => $query->where('display_name', 'like', '%' . $search . '%'))
            ->orderBy('display_name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CrmContact $contact) => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'profile_photo_url' => $contact->profile_photo_url,
                'deleted_at' => $contact->deleted_at?->format('d.m.Y H:i'),
                'contact_type' => $contact->contactType ? [
                    'name' => $contact->contactType->name,
                    'icon' => $contact->contactType->icon,
                    'color' => $contact->contactType->color,
                ] : null,
            ]);

        return \Inertia\Inertia::render('Trash/CrmContacts', [
            'trashed_contacts' => $trashedContacts,
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        CrmContact::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        CrmContact::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->back();
    }

    public function forceDeleteAll(): RedirectResponse
    {
        CrmContact::onlyTrashed()->get()->each(fn (CrmContact $contact) => $contact->forceDelete());

        return redirect()->back();
    }

    public function updateProfileImage(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $this->abortIfMirrored($crmContact);

        $request->validate([
            'profile_image' => 'required|image|max:2048',
        ]);

        $path = $request->file('profile_image')->store('crm-profile-images', 'public');
        $this->contactService->updateProfileImage($crmContact, $path);

        return redirect()->back();
    }

    public function uploadPropertyFile(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $this->abortIfMirrored($crmContact);

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
        $this->abortIfMirrored($crmContact);

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
