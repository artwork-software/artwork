<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nur Termine solcher Terminarten tauchen in der Ticketing-Komponente eines Projekts auf
        // und können zum Verkauf in artwork tickets freigegeben werden.
        Schema::table('event_types', function (Blueprint $table): void {
            $table->boolean('relevant_for_ticketing')->default(false)->after('relevant_for_project_period');
        });
    }

    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table): void {
            $table->dropColumn('relevant_for_ticketing');
        });
    }
};
