<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Die Änderungsübersicht filtert nach craft_id und sortiert absteigend nach changed_at
     * (paginiert). Mit diesem zusammengesetzten Index kann MySQL den Craft-Filter und die
     * Sortierung über einen Index bedienen (kein Filesort) und beim LIMIT früh abbrechen –
     * auch für die open/ack-Filter (acknowledged_at wird als Residual-Bedingung geprüft).
     */
    public function up(): void
    {
        Schema::table('committed_shift_changes', function (Blueprint $table): void {
            $table->index(
                ['craft_id', 'changed_at'],
                'committed_shift_changes_craft_id_changed_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('committed_shift_changes', function (Blueprint $table): void {
            $table->dropIndex('committed_shift_changes_craft_id_changed_at_index');
        });
    }
};
