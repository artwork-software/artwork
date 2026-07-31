<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectTabService;
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
        $contactTypes->load('properties');
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

        // Frontend sendet per_page; perPage bleibt als Fallback für alte Aufrufer erhalten
        $perPage = $request->integer('per_page') ?: $request->integer('perPage', 15);

        $contacts = $activeType
            ? $this->contactService->getByType(
                $activeType->id,
                $request->get('search'),
                $perPage,
                $filters,
                $allowedPropertyIds,
                $request->get('sort'),
                $request->get('direction', 'asc')
            )
            : null;

        return Inertia::render('CRM/Index', [
            'contactTypes' => $contactTypes,
            'activeType' => $activeType,
            'contacts' => $contacts,
            'canImport' => $isCrmManager,
            'importResult' => session('importResult'),
        ]);
    }

    public function show(CrmContact $crmContact, ProjectTabService $projectTabService): Response
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

        // Protokoll der Projekte, mit denen dieser Kontakt verknüpft ist — über die
        // Künstler*innen-Verknüpfung, das Projektteam oder Künstler*innenaufenthalte
        $linkedProjects = $this->buildLinkedProjects($crmContact);

        return Inertia::render('CRM/Show', [
            'contact' => $crmContact,
            'propertyGroups' => $propertyGroups,
            'externalAccessStatus' => $this->resolveExternalAccessStatus($crmContact),
            'linkedProjects' => $linkedProjects,
            'firstProjectTabId' => $linkedProjects->isNotEmpty()
                ? $projectTabService->getDefaultOrFirstProjectTab()->getAttribute('id')
                : null,
            'sourceProfileUrl' => $this->resolveSourceProfileUrl($crmContact),
            // Für den Typ-Wechsel: alle aktiven Typen inkl. zugewiesener Property-IDs
            'contactTypes' => $isCrmManager && $crmContact->entity_type === null
                ? $this->contactTypeService->getAllWithProperties()
                : [],
            'canChangeType' => $isCrmManager && $crmContact->entity_type === null,
        ]);
    }

    /**
     * Alle Projektverknüpfungen des Kontakts, pro Projekt zusammengeführt:
     * Künstler*innen-Verknüpfung ('artist'), Projektteam ('team', inkl. Rollennamen)
     * und Künstler*innenaufenthalte ('residency', inkl. zusammengefasstem Zeitraum).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildLinkedProjects(CrmContact $crmContact): \Illuminate\Support\Collection
    {
        $entries = [];

        $ensureEntry = static function ($project) use (&$entries): int {
            if (!isset($entries[$project->id])) {
                $dates = $project->first_and_last_event_date;

                $entries[$project->id] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'first_event_date' => $dates['first_event_date'] ?? null,
                    'last_event_date' => $dates['last_event_date'] ?? null,
                    'linked_at' => null,
                    'sources' => [],
                    'team_roles' => [],
                    'residency_summary' => null,
                ];
            }

            return $project->id;
        };

        foreach ($crmContact->projects()->orderByDesc('crm_contact_project.created_at')->get() as $project) {
            $id = $ensureEntry($project);
            $entries[$id]['sources'][] = 'artist';
            $entries[$id]['linked_at'] = $project->pivot?->created_at?->format('d.m.Y');
        }

        $roleNames = ProjectRole::query()->pluck('name', 'id');
        foreach ($crmContact->teamProjects()->get() as $project) {
            $id = $ensureEntry($project);
            $entries[$id]['sources'][] = 'team';
            $entries[$id]['team_roles'] = collect((array) ($project->pivot?->roles))
                ->map(static fn ($roleId) => $roleNames[$roleId] ?? null)
                ->filter()
                ->values()
                ->all();
            $entries[$id]['linked_at'] ??= $project->pivot?->created_at?->format('d.m.Y');
        }

        $residenciesByProject = ArtistResidency::query()
            ->where('artist_crm_contact_id', $crmContact->id)
            ->whereNotNull('project_id')
            ->get()
            ->groupBy('project_id');

        if ($residenciesByProject->isNotEmpty()) {
            $residencyProjects = Project::query()
                ->whereIn('id', $residenciesByProject->keys())
                ->get()
                ->keyBy('id');

            foreach ($residenciesByProject as $projectId => $residencies) {
                $project = $residencyProjects->get($projectId);
                if ($project === null) {
                    continue;
                }

                $id = $ensureEntry($project);
                $entries[$id]['sources'][] = 'residency';
                $entries[$id]['residency_summary'] = [
                    'from' => $residencies->min('arrival_date')?->format('d.m.Y'),
                    'to' => $residencies->max('departure_date')?->format('d.m.Y'),
                    'count' => $residencies->count(),
                ];
            }
        }

        return collect($entries)->values();
    }

    /**
     * Link zum Quellprofil eines gespiegelten Kontakts (User/Freelancer/Dienstleister).
     */
    private function resolveSourceProfileUrl(CrmContact $crmContact): ?string
    {
        if ($crmContact->entity_type === null || $crmContact->entity_id === null) {
            return null;
        }

        return match ($crmContact->contactType?->slug) {
            'user' => route('user.edit.info', $crmContact->entity_id),
            'freelancer' => route('freelancer.show', $crmContact->entity_id),
            'service_provider' => route('service_provider.show', $crmContact->entity_id),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveExternalAccessStatus(CrmContact $crmContact): ?array
    {
        $external = $crmContact->externalAccess()->first();

        if ($external === null) {
            return null;
        }

        $pending = $external->pendingSubmissions()
            ->where('status', \Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus::PENDING)
            ->latest('submitted_at')
            ->first();

        return [
            'id' => $external->id,
            'crm_access_expires_at' => $external->crm_access_expires_at?->toIso8601String(),
            'revoked_at' => $external->revoked_at?->toIso8601String(),
            'last_login_at' => $external->last_login_at?->toIso8601String(),
            'has_pending_submission' => $pending !== null,
            'pending_submission_id' => $pending?->id,
        ];
    }
}
