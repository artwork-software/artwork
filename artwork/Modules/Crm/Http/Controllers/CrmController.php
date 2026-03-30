<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    public function __construct(
        private readonly CrmContactTypeService $contactTypeService,
        private readonly CrmContactService $contactService,
        private readonly CrmPropertyGroupService $propertyGroupService,
    ) {}

    public function index(Request $request): Response
    {
        $contactTypes = $this->contactTypeService->getActive();
        $activeTypeSlug = $request->get('type') ?? $contactTypes->first()?->slug;
        $activeType = $activeTypeSlug
            ? $this->contactTypeService->getBySlug($activeTypeSlug) ?? $contactTypes->first()
            : $contactTypes->first();

        $filters = json_decode($request->get('filters', '[]'), true) ?: [];

        // Determine which property groups are visible to the current user
        $user = auth()->user();
        $departmentIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);
        $visibleGroupIds = $this->propertyGroupService
            ->getVisibleForUser($user->id, $departmentIds, $isCrmManager)
            ->pluck('id')
            ->toArray();

        // Filter activeType properties to only include those from visible groups
        if ($activeType) {
            $activeType->load('properties');
            $activeType->setRelation(
                'properties',
                $activeType->properties
                    ->filter(fn ($p) => in_array($p->crm_property_group_id, $visibleGroupIds))
                    ->values()
            );
        }

        $allowedPropertyIds = $activeType
            ? $activeType->properties->pluck('id')->toArray()
            : [];

        $contacts = $activeType
            ? $this->contactService->getByType(
                $activeType->id,
                $request->get('search'),
                $request->integer('perPage', 15),
                $filters,
                $allowedPropertyIds
            )
            : null;

        return Inertia::render('CRM/Index', [
            'contactTypes' => $contactTypes,
            'activeType' => $activeType,
            'contacts' => $contacts,
        ]);
    }

    public function show(CrmContact $crmContact): Response
    {
        $crmContact->load(['contactType.properties', 'propertyValues.property.group', 'roomTypes']);

        $user = auth()->user();
        $departmentIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);
        $propertyGroups = $this->propertyGroupService->getVisibleForUser($user->id, $departmentIds, $isCrmManager);
        $propertyGroups->loadMissing('properties');
        $this->propertyGroupService->annotateEditPermissions($propertyGroups, $user->id, $departmentIds, $isCrmManager);

        // Filter property values to only include those from visible groups
        $visiblePropertyIds = $propertyGroups->flatMap(fn ($g) => $g->properties)->pluck('id')->toArray();
        $crmContact->setRelation(
            'propertyValues',
            $crmContact->propertyValues->whereIn('crm_property_id', $visiblePropertyIds)->values()
        );

        return Inertia::render('CRM/Show', [
            'contact' => $crmContact,
            'propertyGroups' => $propertyGroups,
        ]);
    }
}
