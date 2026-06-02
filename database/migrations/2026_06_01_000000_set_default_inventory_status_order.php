<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Ref 1.29: bring the standard inventory statuses into the order required by
     * the spec (einsatzbereit, defekt, nicht auffindbar, ausgesondert). Only rows
     * whose name matches are touched; custom statuses keep their existing order
     * and can be re-sorted via drag & drop in the settings.
     */
    public function up(): void
    {
        $order = [
            'einsatzbereit' => 1,
            'defekt' => 2,
            'nicht auffindbar' => 3,
            'ausgesondert' => 4,
        ];

        foreach ($order as $name => $position) {
            DB::table('inventory_article_statuses')
                ->whereRaw('LOWER(name) = ?', [$name])
                ->update(['order' => $position]);
        }
    }

    public function down(): void
    {
        // Non-destructive ordering change; nothing to revert.
    }
};
