<?php

namespace App\Http\Controllers;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistNameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectCrmContactController extends Controller
{
    public function __construct(
        private readonly ProjectTabArtistNameService $projectTabArtistNameService,
    ) {
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'crm_contact_id' => 'required|integer|exists:crm_contacts,id',
        ]);

        $crmContact = CrmContact::with('contactType')->findOrFail($validated['crm_contact_id']);

        if ($crmContact->contactType?->slug !== CrmSystemContactTypeEnum::ARTIST->value) {
            abort(422, 'Only CRM contacts of the artist contact type can be linked.');
        }

        $project->crmContacts()->syncWithoutDetaching([$crmContact->id]);

        return response()->json([
            'linked_crm_contacts' => $this->projectTabArtistNameService->mapLinkedCrmContacts($project),
        ]);
    }

    public function destroy(Project $project, CrmContact $crmContact): JsonResponse
    {
        $this->authorize('update', $project);

        $project->crmContacts()->detach($crmContact->id);

        return response()->json([
            'linked_crm_contacts' => $this->projectTabArtistNameService->mapLinkedCrmContacts($project),
        ]);
    }
}
