<?php

namespace App\Http\Controllers;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\Project\Services\ProjectComponentCrmContactService;
use Artwork\Modules\Project\Services\ProjectCrmContactRoleService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Komponente „CRM-Kontaktliste“ im Projekt (interne Sicht).
 *  - Sehen: Zutritt zum Projekt; Feldwerte nach den CRM-Gruppenrechten der Person.
 *  - Anlegen/Bearbeiten/Entfernen: Schreibrecht an der Komponente (writeComponent).
 *  - Bestehende CRM-Kontakte suchen/verknüpfen: zusätzlich CRM-Zugang.
 */
class ProjectComponentCrmContactController extends Controller
{
    public function __construct(
        private readonly ProjectComponentCrmContactService $service,
        private readonly ProjectCrmContactRoleService $roleService,
    ) {
    }

    /**
     * Frei anlegbare Kontakttypen für die Einstellung „Erlaubte Kontakttypen“ der Komponente.
     */
    public function contactTypeOptions(): JsonResponse
    {
        return response()->json(
            $this->service->selectableContactTypes()
                ->map(fn (CrmContactType $type) => $this->service->serializeContactType($type))
                ->values()
        );
    }

    public function index(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        // Wie jede Tab-Komponente: Projekt sehen UND die Komponente sehen dürfen
        abort_unless($user->can('viewComponent', [$project, $component]), 403);

        $canWrite = $user->can('writeComponent', [$project, $component]);
        $visiblePropertyIds = $this->service->visiblePropertyIdsFor($user);

        return response()->json([
            'contacts' => $this->service->entries($project, $component)
                ->map(fn (ProjectComponentCrmContact $entry) => $this->service->serializeEntry(
                    $entry,
                    $visiblePropertyIds,
                    $user,
                    $canWrite,
                ))
                ->values(),
            'contact_types' => $this->service->allowedContactTypes($component)
                ->map(fn (CrmContactType $type) => $this->service->serializeContactType($type))
                ->values(),
            'max_contacts' => $this->service->maxContacts($component),
            // Rollen (Einstellung der Komponente) und die Sonderrolle „Künstler*in“
            'roles_enabled' => $this->roleService->rolesEnabled($component),
            'roles' => $this->roleService->roleList($project, $component),
            'role_suggestions' => $canWrite ? $this->roleService->suggestions() : [],
            'can_write' => $canWrite,
            'can_link_existing' => $canWrite && $user->can(PermissionEnum::CRM_VIEW->value),
            'can_merge' => $user->can(PermissionEnum::CRM_MANAGER->value),
        ]);
    }

    public function search(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->assertCrmContactList($component);
        $this->authorizeLinking($request, $project, $component);

        return response()->json(
            $this->service->searchLinkable(
                $project,
                $component,
                (string) $request->input('search', ''),
                $request->filled('contact_type_id') ? $request->integer('contact_type_id') : null,
            )
                ->map(fn (CrmContact $contact) => [
                    'id' => $contact->id,
                    'display_name' => $contact->display_name,
                    'profile_photo_url' => $contact->profile_photo_url,
                    'contact_type' => $contact->contactType
                        ? $this->service->serializeContactType($contact->contactType)
                        : null,
                ])
                ->values()
        );
    }

    public function mask(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        $contactType = $this->allowedType($request->integer('contact_type_id'), $component);

        return response()->json($this->service->mask($contactType, $this->service->editablePropertyIdsFor($user)));
    }

    public function store(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        $validated = $request->validate([
            'crm_contact_type_id' => ['required', 'integer'],
            'display_name' => ['required', 'string', 'max:255'],
            'property_values' => ['array'],
            'property_values.*' => ['nullable'],
            ...$this->roleRules(),
        ]);

        $entry = $this->service->create(
            $project,
            $component,
            $this->allowedType((int) $validated['crm_contact_type_id'], $component),
            (string) $validated['display_name'],
            $validated['property_values'] ?? [],
            $user,
            $validated['roles'] ?? null,
            isset($validated['is_artist']) ? (bool) $validated['is_artist'] : null,
        );

        return response()->json(['contact' => $this->serialize($entry, $user)], 201);
    }

    public function link(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->assertCrmContactList($component);
        $this->authorizeLinking($request, $project, $component);

        $validated = $request->validate([
            'crm_contact_id' => ['required', 'integer', 'exists:crm_contacts,id'],
            ...$this->roleRules(),
        ]);

        $entry = $this->service->link(
            $project,
            $component,
            CrmContact::query()->findOrFail($validated['crm_contact_id']),
            $this->user($request),
            $validated['roles'] ?? null,
            isset($validated['is_artist']) ? (bool) $validated['is_artist'] : null,
        );

        return response()->json(['contact' => $this->serialize($entry, $this->user($request))], 201);
    }

    public function update(
        Request $request,
        Project $project,
        Component $component,
        CrmContact $crmContact,
    ): JsonResponse {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'property_values' => ['array'],
            'property_values.*' => ['nullable'],
        ]);

        $entry = $this->service->findEntry($project, $component, $crmContact) ?? abort(404);
        $entry = $this->service->update(
            $entry,
            (string) $validated['display_name'],
            $validated['property_values'] ?? [],
            $user,
        );

        return response()->json(['contact' => $this->serialize($entry, $user)]);
    }

    public function destroy(
        Request $request,
        Project $project,
        Component $component,
        CrmContact $crmContact,
    ): JsonResponse {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        $entry = $this->service->findEntry($project, $component, $crmContact) ?? abort(404);
        $this->service->remove($entry, $user);

        return response()->json(['message' => __('Contact removed.')]);
    }

    /**
     * Rollen eines Kontakts in dieser Liste und Sonderrolle „Künstler*in“ setzen.
     */
    public function updateEntryRoles(
        Request $request,
        Project $project,
        Component $component,
        CrmContact $crmContact,
    ): JsonResponse {
        $this->assertCrmContactList($component);
        $user = $this->user($request);
        abort_unless($user->can('writeComponent', [$project, $component]), 403);

        $validated = $request->validate($this->roleRules());
        $entry = $this->service->findEntry($project, $component, $crmContact) ?? abort(404);
        $this->roleService->assignToEntry(
            $entry,
            array_key_exists('roles', $validated) ? (array) ($validated['roles'] ?? []) : null,
            isset($validated['is_artist']) ? (bool) $validated['is_artist'] : null,
        );

        return response()->json([
            'contact' => $this->serialize($entry->refresh(), $user),
            'roles' => $this->roleService->roleList($project, $component),
        ]);
    }

    public function storeRole(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->authorizeRoleChange($request, $project, $component);
        $validated = $request->validate(['name' => ['required', 'string', 'max:' . ProjectCrmContactRoleService::MAX_ROLE_LENGTH]]);
        $this->roleService->addRole($project, $component, (string) $validated['name']);

        return response()->json(['roles' => $this->roleService->roleList($project, $component)]);
    }

    public function renameRole(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->authorizeRoleChange($request, $project, $component);
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'new_name' => ['required', 'string', 'max:' . ProjectCrmContactRoleService::MAX_ROLE_LENGTH],
        ]);
        $this->roleService->renameRole($project, $component, (string) $validated['name'], (string) $validated['new_name']);

        return response()->json(['roles' => $this->roleService->roleList($project, $component)]);
    }

    public function destroyRole(Request $request, Project $project, Component $component): JsonResponse
    {
        $this->authorizeRoleChange($request, $project, $component);
        $validated = $request->validate(['name' => ['required', 'string']]);
        $this->roleService->deleteRole($project, $component, (string) $validated['name']);

        return response()->json(['roles' => $this->roleService->roleList($project, $component)]);
    }

    private function authorizeRoleChange(Request $request, Project $project, Component $component): void
    {
        $this->assertCrmContactList($component);
        abort_unless($this->user($request)->can('writeComponent', [$project, $component]), 403);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function roleRules(): array
    {
        return [
            'roles' => ['sometimes', 'nullable', 'array'],
            'roles.*' => ['string', 'max:' . ProjectCrmContactRoleService::MAX_ROLE_LENGTH],
            'is_artist' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ProjectComponentCrmContact $entry, User $user): array
    {
        return $this->service->serializeEntry(
            $entry->load([
                'crmContact.contactType',
                'crmContact.propertyValues',
                'createdByExternalAccess',
                'reviewedBy:id,first_name,last_name',
            ]),
            $this->service->visiblePropertyIdsFor($user),
            $user,
            true,
        );
    }

    private function authorizeLinking(Request $request, Project $project, Component $component): void
    {
        $user = $this->user($request);
        abort_unless(
            $user->can('writeComponent', [$project, $component]) && $user->can(PermissionEnum::CRM_VIEW->value),
            403,
        );
    }

    private function allowedType(int $typeId, Component $component): CrmContactType
    {
        $type = $this->service->allowedContactTypes($component)->firstWhere('id', $typeId);
        abort_if($type === null, 422, __('This contact type is not allowed here.'));

        return $type;
    }

    private function assertCrmContactList(Component $component): void
    {
        abort_unless($this->service->isCrmContactListComponent($component), 404);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
