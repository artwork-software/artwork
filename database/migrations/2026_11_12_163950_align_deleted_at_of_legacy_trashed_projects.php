<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wiederherstellen holt seit 1.10.1 nur zurück, was ab dem Löschzeitpunkt des Projekts in den Papierkorb ging
 * (ProjectService::restore). Bis dahin lief die Löschkaskade zuerst und das Projekt bekam seinen Löschzeitpunkt
 * erst an ihrem Ende. Bei so gelöschten Projekten liegen Dateien, Kommentare, Checklisten, Aufgaben und Termine
 * also vor dem Löschzeitpunkt und kämen ohne diese Migration nicht mehr zurück.
 *
 * Für solche Projekte rückt der Löschzeitpunkt auf den Beginn der Kaskade: den frühesten Inhalt, der höchstens
 * eine Stunde (Laufzeitgrenze des SoftDeleteProjectJob) vor dem Projekt gelöscht wurde. Das entspricht dem
 * Fenster, mit dem das Wiederherstellen bis 1.10.1 gerechnet hat. Projekte mit Inhalten, die NACH dem Projekt
 * gelöscht wurden, sind schon mit dem neuen Ablauf gelöscht und bleiben unverändert. Auf Instanzen, die vor
 * diesem Release laufen, gibt es nur den alten Ablauf; die Erkennung zählt nur auf Testsystemen, auf denen der
 * neue Ablauf schon lief.
 */
return new class extends Migration
{
    private const CASCADE_WINDOW_SECONDS = 3600;

    public function up(): void
    {
        DB::table('projects')
            ->whereNotNull('deleted_at')
            ->select(['id', 'deleted_at'])
            ->chunkById(200, function ($projects): void {
                foreach ($projects as $project) {
                    $this->alignProject((int) $project->id, (string) $project->deleted_at);
                }
            });
    }

    public function down(): void
    {
        // Der frühere Zeitpunkt (Ende der Kaskade) hat keine Funktion mehr und wird nicht wiederhergestellt.
    }

    private function alignProject(int $projectId, string $deletedAt): void
    {
        $deletedTimes = $this->contentDeletedTimes($projectId);

        if ($deletedTimes->contains(static fn (string $time): bool => $time > $deletedAt)) {
            return;
        }

        $windowStart = date('Y-m-d H:i:s', strtotime($deletedAt) - self::CASCADE_WINDOW_SECONDS);
        $cascadeStart = $deletedTimes
            ->filter(static fn (string $time): bool => $time >= $windowStart && $time < $deletedAt)
            ->min();

        if ($cascadeStart === null) {
            return;
        }

        DB::table('projects')->where('id', $projectId)->update(['deleted_at' => $cascadeStart]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function contentDeletedTimes(int $projectId): \Illuminate\Support\Collection
    {
        $checklistIds = DB::table('checklists')->where('project_id', $projectId)->pluck('id');

        return collect()
            ->merge(DB::table('checklists')->where('project_id', $projectId)->whereNotNull('deleted_at')->pluck('deleted_at'))
            ->merge(DB::table('tasks')->whereIn('checklist_id', $checklistIds)->whereNotNull('deleted_at')->pluck('deleted_at'))
            ->merge(DB::table('project_files')->where('project_id', $projectId)->whereNotNull('deleted_at')->pluck('deleted_at'))
            ->merge(DB::table('comments')->where('project_id', $projectId)->whereNotNull('deleted_at')->pluck('deleted_at'))
            ->merge(DB::table('events')->where('project_id', $projectId)->whereNotNull('deleted_at')->pluck('deleted_at'))
            ->map(static fn ($time): string => (string) $time)
            ->values();
    }
};
