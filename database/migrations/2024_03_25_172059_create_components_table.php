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
        // \App\Enums\TabComponentEnums::toArray()
        // get all the values from the enum and pass it to the enum method
        // this will return an array of all the values in the enum
        // this array will be used as the values for the type column



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
                \App\Enums\TabComponentEnums::PROJECT_SHIFTS->value,
                \App\Enums\TabComponentEnums::PROJECT_STATUS->value,
                \App\Enums\TabComponentEnums::CHECKLIST->value,
                \App\Enums\TabComponentEnums::DOCUMENT->value,
                \App\Enums\TabComponentEnums::PROJECT_BUDGET->value,
                \App\Enums\TabComponentEnums::KEY_VISUAL->value,
                \App\Enums\TabComponentEnums::PROJECT_TEAM->value,
                \App\Enums\TabComponentEnums::PROJECT_INFOS->value,
            ])->default(\App\Enums\TabComponentEnums::TEXT_FIELD->value);
            $table->string('preview')->nullable();
            $table->json('defaults')->nullable();
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
