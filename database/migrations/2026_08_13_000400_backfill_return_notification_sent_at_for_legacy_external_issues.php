<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Altbestand entschärfen: Ohne diesen Backfill würde der erste Lauf von
     * artwork:send-external-issue-return-due-notifications nach dem Deploy für
     * jede historisch überfällige, nie formal zurückgegebene externe Ausgabe
     * eine rote Prio-2-Benachrichtigung (+ Mail) an die Aussteller schicken.
     * Ausgaben, die erst ab heute fällig werden, bleiben unberührt.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('external_issues', 'return_notification_sent_at')) {
            return;
        }

        DB::table('external_issues')
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', now()->toDateString())
            ->whereNull('return_notification_sent_at')
            ->whereNull('received_by_id')
            ->whereNull('return_status')
            ->update(['return_notification_sent_at' => now()]);
    }

    public function down(): void
    {
        // Backfill ist nicht sinnvoll rückgängig zu machen (echte Versand-Zeitstempel
        // wären nicht von den gestempelten unterscheidbar).
    }
};
