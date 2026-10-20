<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;

class ProjectTabArtistNameService
{
    public function buildArtistNamePayload(Project $project): array
    {
        return [
            'artist_name' => $project->artists,
            'linked_crm_contacts' => $this->mapLinkedCrmContacts($project),
            'list_artists' => $this->mapListArtists($project),
        ];
    }

    /**
     * Personen mit der Sonderrolle „Künstler*in“ in CRM-Kontaktlisten des Projekts – hier nur zur Anzeige,
     * gepflegt werden sie in der jeweiligen Liste.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mapListArtists(Project $project): array
    {
        $linkedIds = $project->crmContacts()->pluck('crm_contacts.id')->all();

        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('is_artist', true)
            ->whereNotIn('crm_contact_id', $linkedIds)
            ->whereHas('crmContact')
            ->with(['crmContact.contactType', 'component:id,name,data'])
            ->get()
            ->groupBy('crm_contact_id')
            ->map(function ($entries) {
                $contact = $entries->first()->crmContact;

                return [
                    ...$this->serializeContact($contact),
                    'via' => $entries
                        ->map(fn (ProjectComponentCrmContact $entry) => (string) ($entry->component?->data['title'] ?? '')
                            ?: (string) $entry->component?->name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('display_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
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
            ->map(fn(CrmContact $contact) => $this->serializeContact($contact))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeContact(CrmContact $contact): array
    {
        return [
            'id' => $contact->id,
            'display_name' => $contact->display_name,
            'profile_photo_url' => $contact->profile_photo_url,
            'contact_type' => $contact->contactType ? [
                'id' => $contact->contactType->id,
                'name' => $contact->contactType->name,
                'slug' => $contact->contactType->slug,
                'color' => $contact->contactType->color,
            ] : null,
        ];
    }
}
