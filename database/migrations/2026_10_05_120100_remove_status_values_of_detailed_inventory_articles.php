<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bei Einzelinventar tragen die Einzelartikel den Status. Wurde ein Artikel auf Einzelinventar
 * umgestellt, blieben die Statusmengen des Hauptartikels stehen und wurden in Statuszählung und
 * Statusfilter zusätzlich gezählt. Der Speicherweg legt sie nicht mehr an; Altbestand entfernen.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_article_status_values')
            ->whereIn(
                'inventory_article_id',
                DB::table('inventory_articles')->where('is_detailed_quantity', true)->select('id')
            )
            ->delete();
    }

    public function down(): void
    {
        // Bewusst keine Rückabwicklung: die Werte waren fachlich ungültig.
    }
};
