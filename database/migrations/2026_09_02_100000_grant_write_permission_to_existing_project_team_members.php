<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schreiben in Tab-Komponenten setzt ab jetzt Schreibrecht im Projekt voraus (Team-Häkchen
     * "Schreibrecht", Projektleitung, Ersteller:in, Abteilung oder globales Schreibrecht).
     * Bestandsschutz: alle bestehenden Teammitglieder behalten ihr bisheriges Schreibverhalten,
     * neue Teammitglieder starten standardmäßig mit Schreibrecht.
     */
    public function up(): void
    {
        if (!Schema::hasTable('project_user')) {
            return;
        }

        DB::table('project_user')->where('can_write', false)->update(['can_write' => true]);

        Schema::table('project_user', function (Blueprint $table): void {
            $table->boolean('can_write')->default(true)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_user')) {
            return;
        }

        Schema::table('project_user', function (Blueprint $table): void {
            $table->boolean('can_write')->default(false)->change();
        });
    }
};
