<?php

use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentPermissionEnum;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum(
                'type',
                [
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
                    ProjectTabComponentEnum::BUDGET_INFORMATIONS->value
                ]
            )->default(ProjectTabComponentEnum::TEXT_FIELD->value);
            $table->json('data')->nullable();
            $table->boolean('special')->default(false);
            $table->boolean('sidebar_enabled')->default(true);
            $table->enum(
                'permission_type',
                [
                    ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value,
                    ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_SOME_EDIT->value,
                    ProjectTabComponentPermissionEnum::PERMISSION_TYPE_SOME_SEE_SOME_EDIT->value
                ]
            )->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
