<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Darstellung einer Mehrfachauswahl-Eigenschaft: "checkboxes" (Standard) oder "dropdown".
     */
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->string('multiselect_display', 20)->nullable()->after('select_values');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->dropColumn('multiselect_display');
        });
    }
};
