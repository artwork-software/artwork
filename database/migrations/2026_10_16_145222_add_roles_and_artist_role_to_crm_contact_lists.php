<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CRM-Kontaktliste mit Rollen und Sonderrolle „Künstler*in“:
     * - project_component_crm_contacts.roles: Freitext-Rollen des Kontakts in dieser Liste
     * - project_component_crm_contacts.is_artist: Sonderrolle „Künstler*in“ = Projektkünstler*in
     * - projects.linked_artists: verknüpfte Künstler*innen (CRM) als [{id, name}] für alle Anzeigen
     *   (Kalender, Dienstplan, Exporte …), gepflegt von ProjectArtistService
     *
     * Bestand: Kontakte vom Typ Künstler*in in Kontaktlisten bekommen die Sonderrolle. Ihre bisher automatisch
     * angelegte Verknüpfung als Projektkünstler*in (crm_contact_project) entfällt, weil die Sonderrolle sie
     * ersetzt – sonst bliebe die Person nach dem Entfernen aus der Liste Künstler*in.
     */
    public function up(): void
    {
        Schema::table('project_component_crm_contacts', function (Blueprint $table): void {
            $table->json('roles')->nullable()->after('crm_contact_id');
            $table->boolean('is_artist')->default(false)->after('roles');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->json('linked_artists')->nullable()->after('artists');
        });

        $artistTypeIds = DB::table('crm_contact_types')->where('slug', 'artist')->pluck('id');
        if ($artistTypeIds->isNotEmpty()) {
            DB::table('project_component_crm_contacts')
                ->whereIn(
                    'crm_contact_id',
                    DB::table('crm_contacts')->whereIn('crm_contact_type_id', $artistTypeIds)->select('id')
                )
                ->update(['is_artist' => true]);
        }

        DB::table('crm_contact_project')
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('project_component_crm_contacts')
                ->whereColumn('project_component_crm_contacts.project_id', 'crm_contact_project.project_id')
                ->whereColumn('project_component_crm_contacts.crm_contact_id', 'crm_contact_project.crm_contact_id')
                ->where('project_component_crm_contacts.is_artist', true))
            ->delete();

        $this->backfillLinkedArtists();
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('linked_artists');
        });

        Schema::table('project_component_crm_contacts', function (Blueprint $table): void {
            $table->dropColumn(['roles', 'is_artist']);
        });
    }

    /**
     * Gleiche Regel wie ProjectArtistService::refresh(): Verknüpfungen aus der Komponente
     * „Projektkünstler*in“, danach Kontakte mit Sonderrolle aus Kontaktlisten; ohne Papierkorb, ohne Dubletten.
     */
    private function backfillLinkedArtists(): void
    {
        $pivot = DB::table('crm_contact_project')
            ->join('crm_contacts', 'crm_contacts.id', '=', 'crm_contact_project.crm_contact_id')
            ->whereNull('crm_contacts.deleted_at')
            ->orderBy('crm_contact_project.created_at')
            ->get(['crm_contact_project.project_id', 'crm_contacts.id', 'crm_contacts.display_name']);

        $lists = DB::table('project_component_crm_contacts')
            ->join('crm_contacts', 'crm_contacts.id', '=', 'project_component_crm_contacts.crm_contact_id')
            ->whereNull('crm_contacts.deleted_at')
            ->where('project_component_crm_contacts.is_artist', true)
            ->orderBy('project_component_crm_contacts.id')
            ->get(['project_component_crm_contacts.project_id', 'crm_contacts.id', 'crm_contacts.display_name']);

        $byProject = [];
        foreach ($pivot->concat($lists) as $row) {
            $byProject[$row->project_id][$row->id] ??= ['id' => (int) $row->id, 'name' => (string) $row->display_name];
        }

        foreach ($byProject as $projectId => $artists) {
            DB::table('projects')
                ->where('id', $projectId)
                ->update(['linked_artists' => json_encode(array_values($artists), JSON_UNESCAPED_UNICODE)]);
        }
    }
};
