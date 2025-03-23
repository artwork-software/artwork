<?php

use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
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
        $enumValues = [
            ProjectTabComponentEnum::TEXT_AREA->value,
            ProjectTabComponentEnum::TEXT_FIELD->value,
            ProjectTabComponentEnum::CHECKBOX->value,
            ProjectTabComponentEnum::DROPDOWN->value,
            ProjectTabComponentEnum::TITLE->value,
            ProjectTabComponentEnum::CALENDAR->value,
            ProjectTabComponentEnum::PROJECT_STATUS->value,
            ProjectTabComponentEnum::CHECKLIST->value,
            ProjectTabComponentEnum::PROJECT_TEAM->value,
            ProjectTabComponentEnum::PROJECT_GROUP->value,
            ProjectTabComponentEnum::PROJECT_ATTRIBUTES->value,
            ProjectTabComponentEnum::SHIFT_TAB->value,
            ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING->value,
            ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS->value,
            ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION->value,
            ProjectTabComponentEnum::BUDGET->value,
            ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE->value,
            ProjectTabComponentEnum::COMMENT_TAB->value,
            ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
            ProjectTabComponentEnum::PROJECT_TITLE->value,
            ProjectTabComponentEnum::SEPARATOR->value,
            ProjectTabComponentEnum::COMMENT_ALL_TAB->value,
            ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS->value,
            ProjectTabComponentEnum::CHECKLIST_ALL->value,
            ProjectTabComponentEnum::BUDGET_INFORMATIONS->value,
            // Weitere bestehende Werte
            ProjectTabComponentEnum::BULK_EDIT->value, // Neuer Wert
            ProjectTabComponentEnum::ARTIST_RESIDENCIES->value, // Neuer Wert
            ProjectTabComponentEnum::PROJECT_GROUP_DISPLAY->value, // Neuer Wert
            ProjectTabComponentEnum::GROUP_PROJECT_DISPLAY->value, // Neuer Wert
            ProjectTabComponentEnum::DISCLOSURE_COMPONENT->value, // Neuer Wert
        ];

        // Erstelle eine SQL-Anweisung, um die ENUM-Spalte zu ändern
        $enumValuesString = "'" . implode("','", $enumValues) . "'";

        // Führe die SQL-Anweisung aus, um die Spalte zu ändern
        DB::statement("ALTER TABLE components MODIFY COLUMN type ENUM($enumValuesString)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $enumValues = [
            ProjectTabComponentEnum::TEXT_AREA->value,
            ProjectTabComponentEnum::TEXT_FIELD->value,
            ProjectTabComponentEnum::CHECKBOX->value,
            ProjectTabComponentEnum::DROPDOWN->value,
            ProjectTabComponentEnum::TITLE->value,
            ProjectTabComponentEnum::CALENDAR->value,
            ProjectTabComponentEnum::PROJECT_STATUS->value,
            ProjectTabComponentEnum::CHECKLIST->value,
            ProjectTabComponentEnum::PROJECT_TEAM->value,
            ProjectTabComponentEnum::PROJECT_GROUP->value,
            ProjectTabComponentEnum::PROJECT_ATTRIBUTES->value,
            ProjectTabComponentEnum::SHIFT_TAB->value,
            ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING->value,
            ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS->value,
            ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION->value,
            ProjectTabComponentEnum::BUDGET->value,
            ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE->value,
            ProjectTabComponentEnum::COMMENT_TAB->value,
            ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
            ProjectTabComponentEnum::PROJECT_TITLE->value,
            ProjectTabComponentEnum::SEPARATOR->value,
            ProjectTabComponentEnum::COMMENT_ALL_TAB->value,
            ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS->value,
            ProjectTabComponentEnum::CHECKLIST_ALL->value,
            ProjectTabComponentEnum::BUDGET_INFORMATIONS->value,
            // Weitere bestehende Werte
            ProjectTabComponentEnum::BULK_EDIT->value,
            ProjectTabComponentEnum::ARTIST_RESIDENCIES->value, // Neuer Wert
            ProjectTabComponentEnum::PROJECT_GROUP_DISPLAY->value, // Neuer Wert
            ProjectTabComponentEnum::GROUP_PROJECT_DISPLAY->value, // Neuer Wert
            ProjectTabComponentEnum::DISCLOSURE_COMPONENT->value, // Neuer Wert
        ];

        // Erstelle eine SQL-Anweisung, um die ENUM-Spalte zu ändern
        $enumValuesString = "'" . implode("','", $enumValues) . "'";

        // Führe die SQL-Anweisung aus, um die Spalte zu ändern
        DB::statement("ALTER TABLE components MODIFY COLUMN type ENUM($enumValuesString)");
    }
};
