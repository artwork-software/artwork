<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalScopeResolver;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectComponentCrmContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM-Kontaktliste im freigegebenen Tab (externe Sicht). Externe sehen die Kontakte der Liste ohne
 * vertrauliche Felder, legen neue Kontakte der erlaubten Typen an und bearbeiten/entfernen nur eigene.
 *
 * Services per Methoden-Injection (siehe ExternalTabSubmissionController): Konstruktor-DI würde
 * Abhängigkeiten vor der externen Session-Middleware auflösen.
 */
class ExternalCrmContactListController extends Controller
{
    public function index(
        Request $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): JsonResponse {
        $this->assertComponentInTab($component, $tab, $service, $scopeResolver);
        $external = $this->external($request);
        $scope = $this->scope($request);
        $canWrite = $scope->access_type->value === 'write' && !$scope->isLockedForExternal();
        $visiblePropertyIds = $service->externalPropertyIds();

        return response()->json([
            'contacts' => $service->entries($project, $component)
                ->map(fn (ProjectComponentCrmContact $entry) => $service->serializeEntry(
                    $entry,
                    $visiblePropertyIds,
                    $external,
                    $canWrite,
                ))
                ->values(),
            'contact_types' => $service->allowedContactTypes($component)
                ->map(fn (CrmContactType $type) => $service->serializeContactType($type))
                ->values(),
            'max_contacts' => $service->maxContacts($component),
        ]);
    }

    public function mask(
        Request $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): JsonResponse {
        $this->assertComponentInTab($component, $tab, $service, $scopeResolver);
        $contactType = $this->allowedType($request->integer('contact_type_id'), $component, $service);

        return response()->json($service->mask($contactType, $service->externalPropertyIds()));
    }

    public function store(
        Request $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): JsonResponse {
        $this->assertComponentInTab($component, $tab, $service, $scopeResolver);
        $validated = $this->validatePayload($request, requireType: true);
        $external = $this->external($request);
        $contactType = $this->allowedType((int) $validated['crm_contact_type_id'], $component, $service);

        $entry = $service->create(
            $project,
            $component,
            $contactType,
            (string) $validated['display_name'],
            $validated['property_values'] ?? [],
            $external,
        );

        return response()->json([
            'contact' => $service->serializeEntry(
                $entry->load(['crmContact.contactType', 'crmContact.propertyValues']),
                $service->externalPropertyIds(),
                $external,
                true,
            ),
        ], 201);
    }

    public function update(
        Request $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
        CrmContact $crmContact,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): JsonResponse {
        $this->assertComponentInTab($component, $tab, $service, $scopeResolver);
        $validated = $this->validatePayload($request, requireType: false);
        $external = $this->external($request);
        $entry = $service->findEntry($project, $component, $crmContact) ?? abort(404);

        $entry = $service->update(
            $entry,
            (string) $validated['display_name'],
            $validated['property_values'] ?? [],
            $external,
        );

        return response()->json([
            'contact' => $service->serializeEntry(
                $entry->load(['crmContact.contactType', 'crmContact.propertyValues']),
                $service->externalPropertyIds(),
                $external,
                true,
            ),
        ]);
    }

    public function destroy(
        Request $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
        CrmContact $crmContact,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): JsonResponse {
        $this->assertComponentInTab($component, $tab, $service, $scopeResolver);
        $entry = $service->findEntry($project, $component, $crmContact) ?? abort(404);

        $service->remove($entry, $this->external($request));

        return response()->json(['message' => __('Contact removed.')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $requireType): array
    {
        return $request->validate([
            'crm_contact_type_id' => [$requireType ? 'required' : 'nullable', 'integer'],
            'display_name' => ['required', 'string', 'max:255'],
            'property_values' => ['array'],
            'property_values.*' => ['nullable'],
        ]);
    }

    private function allowedType(
        int $typeId,
        Component $component,
        ProjectComponentCrmContactService $service,
    ): CrmContactType {
        $type = $service->allowedContactTypes($component)->firstWhere('id', $typeId);
        abort_if($type === null, 422, __('This contact type is not allowed here.'));

        return $type;
    }

    /**
     * Defense in depth: Die Komponente muss eine CRM-Kontaktliste sein und im freigegebenen Tab liegen.
     */
    private function assertComponentInTab(
        Component $component,
        ProjectTab $tab,
        ProjectComponentCrmContactService $service,
        ExternalScopeResolver $scopeResolver,
    ): void {
        abort_unless(
            $service->isCrmContactListComponent($component)
            && $scopeResolver->componentBelongsToTab($component->id, $tab->id),
            404,
        );
    }

    private function external(Request $request): ExternalAccess
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        return $external;
    }

    private function scope(Request $request): ExternalAccessScope
    {
        /** @var ExternalAccessScope $scope */
        $scope = $request->attributes->get('external_scope');

        return $scope;
    }
}
