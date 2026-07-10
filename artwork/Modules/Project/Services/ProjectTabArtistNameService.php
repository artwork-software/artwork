<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Project\Models\Project;

class ProjectTabArtistNameService
{
    public function buildArtistNamePayload(Project $project): array
    {
        return [
            'artist_name' => $project->artists,
            'linked_crm_contacts' => $this->mapLinkedCrmContacts($project),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapLinkedCrmContacts(Project $project): array
    {
        return $project->crmContacts()
            ->with('contactType')
            ->orderBy('display_name')
            ->get()
            ->map(fn(CrmContact $contact) => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'profile_photo_url' => $contact->profile_photo_url,
                'contact_type' => $contact->contactType ? [
                    'id' => $contact->contactType->id,
                    'name' => $contact->contactType->name,
                    'slug' => $contact->contactType->slug,
                    'color' => $contact->contactType->color,
                ] : null,
            ])
            ->values()
            ->all();
    }
}
