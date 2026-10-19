<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Projektkünstler*innen aus zwei Quellen:
 * - Freitext `projects.artists` (wie bisher)
 * - verknüpfte CRM-Kontakte: Komponente „Projektkünstler*in“ (crm_contact_project) und Kontakte mit der
 *   Sonderrolle „Künstler*in“ in CRM-Kontaktlisten (project_component_crm_contacts.is_artist)
 *
 * Die verknüpften Kontakte stehen zusätzlich als `projects.linked_artists` ([{id, name}]) am Projekt, damit
 * alle Anzeigen (Kalender, Dienstplan, Exporte, Druck, Übersicht, BI, App) sie ohne weitere Abfragen lesen
 * können. refresh() muss nach jeder Änderung an den Quellen laufen (Verknüpfen/Entfernen, Sonderrolle,
 * Umbenennen/Löschen/Zusammenführen von Kontakten, Prüfung externer Einträge).
 *
 * Von Externen angelegte Listeneinträge zählen erst nach der internen Prüfung (reviewed_at), sonst stünde
 * ein ungeprüfter Name sofort in Kalender, Dienstplan und Exporten.
 */
class ProjectArtistService
{
    /**
     * @param int|array<int, int> $projectIds
     */
    public function refresh(int|array $projectIds): void
    {
        $projectIds = array_values(array_unique(array_filter(array_map('intval', (array) $projectIds))));
        if ($projectIds === []) {
            return;
        }

        $pivot = DB::table('crm_contact_project')
            ->join('crm_contacts', 'crm_contacts.id', '=', 'crm_contact_project.crm_contact_id')
            ->whereIn('crm_contact_project.project_id', $projectIds)
            ->whereNull('crm_contacts.deleted_at')
            ->orderBy('crm_contact_project.created_at')
            ->orderBy('crm_contact_project.crm_contact_id')
            ->get(['crm_contact_project.project_id', 'crm_contacts.id', 'crm_contacts.display_name']);

        $lists = DB::table('project_component_crm_contacts')
            ->join('crm_contacts', 'crm_contacts.id', '=', 'project_component_crm_contacts.crm_contact_id')
            ->whereIn('project_component_crm_contacts.project_id', $projectIds)
            ->where('project_component_crm_contacts.is_artist', true)
            // Von Externen angelegte Einträge erst nach interner Prüfung anzeigen
            ->where(function ($query): void {
                $query->whereNull('project_component_crm_contacts.created_by_external_access_id')
                    ->orWhereNotNull('project_component_crm_contacts.reviewed_at');
            })
            ->whereNull('crm_contacts.deleted_at')
            ->orderBy('project_component_crm_contacts.id')
            ->get(['project_component_crm_contacts.project_id', 'crm_contacts.id', 'crm_contacts.display_name']);

        $byProject = array_fill_keys($projectIds, []);
        foreach ($pivot->concat($lists) as $row) {
            $byProject[(int) $row->project_id][(int) $row->id] ??= [
                'id' => (int) $row->id,
                'name' => (string) $row->display_name,
            ];
        }

        foreach ($byProject as $projectId => $artists) {
            // ohne Model-Events/updated_at: reine Anzeige-Daten
            DB::table('projects')->where('id', $projectId)->update([
                'linked_artists' => $artists === []
                    ? null
                    : json_encode(array_values($artists), JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Alle Projekte, in denen der Kontakt als Künstler*in verknüpft ist (z. B. nach Umbenennen/Löschen).
     */
    public function refreshForContact(int $contactId): void
    {
        $this->refresh($this->projectIdsOfContact($contactId));
    }

    /**
     * @return list<int>
     */
    public function projectIdsOfContact(int $contactId): array
    {
        return array_values(array_unique(array_map('intval', array_merge(
            DB::table('crm_contact_project')->where('crm_contact_id', $contactId)->pluck('project_id')->all(),
            DB::table('project_component_crm_contacts')
                ->where('crm_contact_id', $contactId)
                ->where('is_artist', true)
                ->pluck('project_id')
                ->all(),
        ))));
    }

    /**
     * Projekte mit Künstler*innen aus Listen dieser Komponente (vor dem Löschen der Komponente sammeln:
     * die FK-Cascade auf component_id umgeht die Model-Events der Listeneinträge).
     *
     * @return list<int>
     */
    public function projectIdsOfComponent(int $componentId): array
    {
        return array_values(array_unique(array_map('intval', DB::table('project_component_crm_contacts')
            ->where('component_id', $componentId)
            ->where('is_artist', true)
            ->pluck('project_id')
            ->all())));
    }

    /**
     * Anzeige-Text: Freitext zuerst, danach verknüpfte Namen, die im Freitext noch nicht vorkommen.
     *
     * @param array<int, array{id?: int, name?: string}>|null $linkedArtists
     */
    public static function displayText(?string $freeText, ?array $linkedArtists): string
    {
        $freeText = trim((string) $freeText);
        $parts = $freeText !== '' ? [$freeText] : [];

        foreach ((array) $linkedArtists as $artist) {
            $name = trim((string) ($artist['name'] ?? ''));
            if ($name === '' || mb_stripos($freeText, $name) !== false || in_array($name, $parts, true)) {
                continue;
            }
            $parts[] = $name;
        }

        return implode(', ', $parts);
    }

    public static function displayTextFor(Project $project): string
    {
        return self::displayText($project->artists, $project->linked_artists);
    }
}
