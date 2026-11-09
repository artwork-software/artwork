<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vorlagen des Projektinhalte-Exports (Excel/Word aus der Projektübersicht): Spalten samt Optionen und
 * die Filter ohne Zeitraum. Vorlagen sind für alle mit Exportrecht sichtbar und gelten für beide Formate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_content_export_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('columns');
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_content_export_templates');
    }
};
