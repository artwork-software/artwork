<?php

use App\Enums\ComponentPermissionNameEnum;
use App\Enums\TabComponentEnums;
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
                    TabComponentEnums::TEXT_AREA->value,
                    TabComponentEnums::TEXT_FIELD->value,
                    TabComponentEnums::CHECKBOX->value,
                    TabComponentEnums::DROPDOWN->value,
                    TabComponentEnums::TITLE->value,
                    TabComponentEnums::CALENDAR->value,
                    TabComponentEnums::PROJECT_STATUS->value,
                    TabComponentEnums::CHECKLIST->value,
                    TabComponentEnums::PROJECT_TEAM->value,
                    TabComponentEnums::PROJECT_GROUP->value,
                    TabComponentEnums::PROJECT_ATTRIBUTES->value,
                    TabComponentEnums::SHIFT_TAB->value,
                    TabComponentEnums::RELEVANT_DATES_FOR_SHIFT_PLANNING->value,
                    TabComponentEnums::SHIFT_CONTACT_PERSONS->value,
                    TabComponentEnums::GENERAL_SHIFT_INFORMATION->value,
                    TabComponentEnums::BUDGET->value,
                    TabComponentEnums::PROJECT_BUDGET_DEADLINE->value,
                    TabComponentEnums::COMMENT_TAB->value,
                    TabComponentEnums::PROJECT_DOCUMENTS->value,
                    TabComponentEnums::PROJECT_TITLE->value,
                    TabComponentEnums::SEPARATOR->value,
                    TabComponentEnums::COMMENT_ALL_TAB->value,
                    TabComponentEnums::PROJECT_ALL_DOCUMENTS->value,
                    TabComponentEnums::CHECKLIST_ALL->value,
                    TabComponentEnums::BUDGET_INFORMATIONS->value
                ]
            )->default(TabComponentEnums::TEXT_FIELD->value);
            $table->json('data')->nullable();
            $table->boolean('special')->default(false);
            $table->boolean('sidebar_enabled')->default(true);
            $table->enum(
                'permission_type',
                [
                    ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value,
                    ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_SOME_EDIT->value,
                    ComponentPermissionNameEnum::PERMISSION_TYPE_SOME_SEE_SOME_EDIT->value
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
