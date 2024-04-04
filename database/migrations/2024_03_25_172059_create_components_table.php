<?php

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
            $table->enum('type',[
                \App\Enums\TabComponentEnums::TEXT_AREA->value,
                \App\Enums\TabComponentEnums::TEXT_FIELD->value,
                \App\Enums\TabComponentEnums::CHECKBOX->value,
                \App\Enums\TabComponentEnums::DROPDOWN->value,
                \App\Enums\TabComponentEnums::TITLE->value,
                \App\Enums\TabComponentEnums::CALENDAR->value,
                \App\Enums\TabComponentEnums::PROJECT_STATUS->value,
                \App\Enums\TabComponentEnums::CHECKLIST->value,
                \App\Enums\TabComponentEnums::PROJECT_TEAM->value,
                \App\Enums\TabComponentEnums::PROJECT_GROUP->value,
                \App\Enums\TabComponentEnums::PROJECT_ATTRIBUTES->value,
                \App\Enums\TabComponentEnums::SHIFT_TAB->value,
                \App\Enums\TabComponentEnums::RELEVANT_DATES_FOR_SHIFT_PLANNING->value,
                \App\Enums\TabComponentEnums::SHIFT_CONTACT_PERSONS->value,
                \App\Enums\TabComponentEnums::GENERAL_SHIFT_INFORMATION->value,
                \App\Enums\TabComponentEnums::BUDGET->value,
                \App\Enums\TabComponentEnums::PROJECT_BUDGET_DEADLINE->value,
                \App\Enums\TabComponentEnums::COMMENT_TAB->value,
                \App\Enums\TabComponentEnums::PROJECT_DOCUMENTS->value,
                \App\Enums\TabComponentEnums::PROJECT_TITLE->value,
            ])->default(\App\Enums\TabComponentEnums::TEXT_FIELD->value);
            $table->string('preview')->nullable();
            $table->json('data')->nullable();
            $table->boolean('special')->default(false);
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
