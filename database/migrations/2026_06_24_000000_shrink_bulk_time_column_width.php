<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spalte 6 der Bulk-Ansicht (Projekt-Terminliste) enthält ausschließlich Zeit-Inputs
 * im Format HH:MM (Startzeit + Endzeit). Die alten Defaults (308px DB / 250px Frontend-
 * Reset) waren dafür deutlich zu breit. Neuer Default ist 150px.
 *
 * Bestandswerte > 150px werden hier auf 150px reduziert; bewusst schmaler gesetzte Werte
 * bleiben unangetastet.
 */
return new class extends Migration
{
    private const TARGET = 150;

    public function up(): void
    {
        DB::table('users')
            ->select('id', 'bulk_column_size')
            ->orderBy('id')
            ->chunk(500, function ($users) {
                foreach ($users as $user) {
                    $sizes = json_decode($user->bulk_column_size ?? '', true);

                    if (!is_array($sizes) || !isset($sizes[6])) {
                        continue;
                    }

                    if ((int) $sizes[6] <= self::TARGET) {
                        continue;
                    }

                    $sizes[6] = self::TARGET;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['bulk_column_size' => json_encode($sizes)]);
                }
            });
    }

    public function down(): void
    {
        // Bewusst kein Rollback: der vorherige Wert war unnötig breit.
    }
};
