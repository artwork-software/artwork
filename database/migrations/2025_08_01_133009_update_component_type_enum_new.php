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
        // Schritt 1: Neue temporäre Spalte mit erweitertem ENUM
        Schema::table('components', function (Blueprint $table) {
            $table->enum('type_temp', [
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

        // Schritt 2: Daten von alter Spalte in neue kopieren
        DB::statement('UPDATE components SET type_temp = type');

        // Schritt 3: Alte Spalte löschen
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Schritt 4: Neue Spalte in "type" umbenennen
        Schema::table('components', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schritt 1: Ursprüngliche ENUM-Werte wiederherstellen
        Schema::table('components', function (Blueprint $table) {
            $table->enum('type_old', [
                'TextArea','TextField','Checkbox','DropDown','Title','CalendarTab',
                'ProjectStateComponent','ChecklistComponent','ProjectTeamComponent','ProjectGroupComponent',
                'ProjectAttributesComponent','ShiftTab','RelevantDatesForShiftPlanningComponent',
                'ShiftContactPersonsComponent','GeneralShiftInformationComponent','BudgetTab',
                'ProjectBudgetDeadlineComponent','CommentTab','ProjectDocumentsComponent',
                'ProjectTitleComponent','SeparatorComponent','CommentAllTab','ProjectAllDocumentsComponent',
                'ChecklistAllComponent','BudgetInformations','BulkBody','ArtistResidenciesComponent',
                'ProjectGroupDisplayComponent','GroupProjectDisplayComponent','DisclosureComponent'
            ])->nullable()->after('type');
        });

        // Schritt 2: Daten rückübertragen
        DB::statement('UPDATE components SET type_old = type');

        // Schritt 3: Neue Spalte entfernen
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Schritt 4: Alte Spalte wiederherstellen
        Schema::table('components', function (Blueprint $table) {
            $table->renameColumn('type_old', 'type');
        });
    }
};
