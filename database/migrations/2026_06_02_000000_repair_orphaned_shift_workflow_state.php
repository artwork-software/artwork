<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Datenreparatur für den Schichtplan-Anfrage-Workflow.
 *
 * Hintergrund: Genehmigte Anfragen haben den Live-Zeiger `current_request_id` auf der Schicht
 * nicht zurückgesetzt, und abgebrochene/gelöschte Anfragen haben verwaiste Zeiger hinterlassen.
 * Dadurch wurden Schichten dauerhaft aus der "Alle Schichten festsetzen"-Auswahl ausgeschlossen
 * und zeigten einen falschen "In approval workflow"-Status. Diese Migration bringt den Bestand
 * in einen konsistenten Zustand (passend zum korrigierten accept()/destroy()-Verhalten).
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Schichten, die auf eine GENEHMIGTE Anfrage zeigen: Live-Zeiger lösen
        //    (Historie bleibt über die Pivot-Tabelle shift_plan_request_shifts erhalten).
        DB::table('shifts')
            ->whereNotNull('current_request_id')
            ->whereIn('current_request_id', function ($q): void {
                $q->select('id')->from('shift_plan_requests')->where('status', 'approved');
            })
            ->update([
                'current_request_id' => null,
                'in_workflow' => false,
                'workflow_rejection_reason' => null,
            ]);

        // 2. Verwaiste Zeiger: referenzierte Anfrage existiert nicht mehr -> komplett freigeben.
        DB::table('shifts')
            ->whereNotNull('current_request_id')
            ->whereNotIn('current_request_id', function ($q): void {
                $q->select('id')->from('shift_plan_requests');
            })
            ->update([
                'current_request_id' => null,
                'in_workflow' => false,
                'workflow_rejection_reason' => null,
            ]);

        // 3. Schichten aus ABGELEHNTEN Anfragen: Zuordnung bleibt (für "erneut anfragen"),
        //    aber das aktive Workflow-Flag darf nicht mehr gesetzt sein.
        DB::table('shifts')
            ->where('in_workflow', true)
            ->whereIn('current_request_id', function ($q): void {
                $q->select('id')->from('shift_plan_requests')->where('status', 'rejected');
            })
            ->update(['in_workflow' => false]);
    }

    public function down(): void
    {
        // Reine Datenreparatur – nicht umkehrbar.
    }
};
