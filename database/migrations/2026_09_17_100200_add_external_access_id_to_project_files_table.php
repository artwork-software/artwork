<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herkunftsvermerk für Dateien, die externe Personen über einen freigegebenen Tab hochladen.
 * Externe dürfen nur ihre eigenen Uploads wieder löschen; intern wird der Upload gekennzeichnet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->foreignId('external_access_id')
                ->nullable()
                ->after('project_id')
                ->constrained('external_accesses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('external_access_id');
        });
    }
};
