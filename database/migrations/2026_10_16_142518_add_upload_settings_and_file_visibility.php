<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dateiupload-Komponente:
     * - upload_settings je Platzierung (Tab bzw. Ordner): erlaubte Endungen, max. Größe, max. Anzahl
     * - project_files.visible_in: Upload-Bereiche (Platzierungs-Schlüssel), in denen die Datei erscheint;
     *   null = wie bisher über die Tab-Auswahl der Bereiche
     * - project_files.uploaded_via: Upload-Bereich, über den die Datei hochgeladen wurde
     */
    public function up(): void
    {
        Schema::table('component_in_tabs', function (Blueprint $table): void {
            $table->json('upload_settings')->nullable()->after('scope');
        });
        Schema::table('disclosure_components', function (Blueprint $table): void {
            $table->json('upload_settings')->nullable()->after('scope');
        });
        Schema::table('project_files', function (Blueprint $table): void {
            $table->json('visible_in')->nullable()->after('tab_id');
            $table->string('uploaded_via', 40)->nullable()->after('visible_in');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->dropColumn(['visible_in', 'uploaded_via']);
        });
        Schema::table('disclosure_components', function (Blueprint $table): void {
            $table->dropColumn('upload_settings');
        });
        Schema::table('component_in_tabs', function (Blueprint $table): void {
            $table->dropColumn('upload_settings');
        });
    }
};
