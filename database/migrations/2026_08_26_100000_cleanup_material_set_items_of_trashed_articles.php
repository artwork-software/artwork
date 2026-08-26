<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Einmalige Datenbereinigung: Set-Items, deren Artikel bereits im Papierkorb liegt, entfernen.
     * Ab jetzt räumt InventoryArticleService::delete() beim Soft-Delete direkt mit auf.
     */
    public function up(): void
    {
        if (!Schema::hasTable('material_set_items')) {
            return;
        }

        DB::table('material_set_items')
            ->whereIn(
                'inventory_article_id',
                DB::table('inventory_articles')->whereNotNull('deleted_at')->select('id')
            )
            ->delete();
    }

    public function down(): void
    {
        // Datenbereinigung ist nicht umkehrbar.
    }
};
