<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anzeigename der Datei bei Upload-Eigenschaften. Gespeichert wird unter einem Hash-Namen,
 * der Originalname (bzw. der umbenannte Name) steht hier und wird beim Download ausgeliefert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_property_values', function (Blueprint $table): void {
            $table->string('file_name')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('crm_property_values', function (Blueprint $table): void {
            $table->dropColumn('file_name');
        });
    }
};
