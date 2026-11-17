<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plätze eines Termins über alle Preisklassen; null heißt wie bisher: die Summe der Klassen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->unsignedInteger('capacity')->nullable()->after('classes');
        });
    }

    public function down(): void
    {
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->dropColumn('capacity');
        });
    }
};
