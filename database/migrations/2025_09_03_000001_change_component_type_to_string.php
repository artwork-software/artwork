<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schritt 1: Neue temporäre Spalte als STRING
        Schema::table('components', function (Blueprint $table) {
            $table->string('type_temp', 255)->nullable()->after('type');
        });

        // Schritt 2: Werte kopieren
        DB::statement('UPDATE components SET type_temp = type');

        // Schritt 3: ENUM-Spalte entfernen
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Schritt 4: type_temp in type umbenennen
        Schema::table('components', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schritt 1: ENUM-Spalte wiederherstellen (mit allen bisherigen Werten)
        Schema::table('components', function (Blueprint $table) {
            $table->enum('type_old', [
                'TextArea','TextField','Checkbox','DropDown','Title','CalendarTab',
                'ProjectStateComponent','ChecklistComponent','ProjectTeamComponent','ProjectGroupComponent',
                'ProjectAttributesComponent','ShiftTab','RelevantDatesForShiftPlanningComponent',
                'ShiftContactPersonsComponent','GeneralShiftInformationComponent','BudgetTab',
                'ProjectBudgetDeadlineComponent','CommentTab','ProjectDocumentsComponent',
                'ProjectTitleComponent','SeparatorComponent','CommentAllTab','ProjectAllDocumentsComponent',
                'ChecklistAllComponent','BudgetInformations','BulkBody','ArtistResidenciesComponent',
                'ProjectGroupDisplayComponent','GroupProjectDisplayComponent','DisclosureComponent',
                'ArtistNameDisplayComponent'
            ])->nullable()->after('type');
        });

        // Schritt 2: Werte zurückkopieren
        DB::statement('UPDATE components SET type_old = type');

        // Schritt 3: STRING-Spalte entfernen
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Schritt 4: type_old in type umbenennen
        Schema::table('components', function (Blueprint $table) {
            $table->renameColumn('type_old', 'type');
        });
    }
};

