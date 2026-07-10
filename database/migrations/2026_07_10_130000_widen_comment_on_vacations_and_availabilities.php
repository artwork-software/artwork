<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notiz-Feld für Verfügbarkeiten/Abwesenheiten war auf 20 Zeichen begrenzt; für sinnvolle
     * Notizen im überarbeiteten Eintrags-Modal auf 100 Zeichen verbreitert.
     */
    public function up(): void
    {
        Schema::table('vacations', function (Blueprint $table): void {
            $table->string('comment', 100)->nullable()->change();
        });

        Schema::table('availabilities', function (Blueprint $table): void {
            $table->string('comment', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table): void {
            $table->string('comment', 20)->nullable()->change();
        });

        Schema::table('availabilities', function (Blueprint $table): void {
            $table->string('comment', 20)->nullable()->change();
        });
    }
};
