<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2026_10_05_110200 legt notifications.groupType inzwischen mit JSON_VALID-Guard an. Instanzen, auf
 * denen die Migration schon mit dem ersten Ausdruck lief (Staging, Entwicklungs-DBs), bekommen die
 * Spalte hier neu – überall sonst ist das ein No-op (keine zweite Tabellenkopie auf Prod).
 */
return new class extends Migration
{
    public function up(): void
    {
        $expression = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'notifications')
            ->where('COLUMN_NAME', 'groupType')
            ->value('GENERATION_EXPRESSION');

        if ($expression !== null && stripos((string) $expression, 'json_valid') === false) {
            (require database_path('migrations/2026_10_05_110200_make_notifications_group_type_a_generated_column.php'))
                ->up();
        }
    }

    public function down(): void
    {
        // Der Guard bleibt: 2026_10_05_110200 legt die Spalte ohnehin so an
    }
};
