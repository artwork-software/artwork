<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_daily_settings', function (Blueprint $table): void {
            // Zugewiesene-Personen-Overview + Tagesbalken-Avatare im Projekt-Schichten-Tab
            // (default AN bei allen; Frontend behandelt fehlenden Wert ebenfalls als an)
            $table->boolean('show_project_assignments')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_daily_settings', function (Blueprint $table): void {
            $table->dropColumn('show_project_assignments');
        });
    }
};
